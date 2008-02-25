<?php

/*

OutlineCompiler
---------------

Copyright (C) 2008, Rasmus Schultz <http://www.mindplay.dk>

Please see "README.txt" for license and other information.
	
*/

define("OUTLINE_TOKEN_OPEN",   "{");
define("OUTLINE_TOKEN_CLOSE",  "}");
define("OUTLINE_TOKEN_PIPE",   "|");
define("OUTLINE_TOKEN_VAR",    '$');
define("OUTLINE_TOKEN_IGNORE", '*');
define("OUTLINE_TOKEN_CANCEL", '/');
define("OUTLINE_TOKEN_CONST",  '#');

define("OUTLINE_STATE_PASSTHRU", 1);
define("OUTLINE_STATE_PARSING",  2);

define("OUTLINE_CLOSE_IGNORE_TAG", '{/ignore}');

function & OutlinePlugins(&$outline) {
	
	static $plugin_names;
	
	$plugins = array();
	
	if (!isset($plugin_names)) {
		$plugin_names = array();
		foreach (glob(OUTLINE_PLUGIN_PATH."/*.php") as $path) {
			$plugin_names[] = ucfirst(basename($path, '.php'));
			require_once $path;
		}
		if (defined("OUTLINE_DEBUG")) OutlineDebug("Loading Plugins (" . implode(', ', $plugin_names) . ")");
	}
	
	foreach ($plugin_names as $name) {
		$class = "Outline" . $name;
		$plugins[$name] = new $class($outline);
	}
	
	return $plugins;
	
}

class OutlineCompiler {
	
	var $o;
	var $coding;
	var $linenum;
	var $infile;
	
	var $plugins;
	
	var $ignore;
	
	function build($tplname) {
		
		if (defined("OUTLINE_DEBUG")) OutlineDebug("Building template '$tplname'");
		
		$this->plugins = & OutlinePlugins($this);
		
		$infile = OUTLINE_TEMPLATE_PATH . '/' . $tplname . OUTLINE_TEMPLATE_SUFFIX;
		$outfile = OUTLINE_COMPILED_PATH . '/' . $tplname . OUTLINE_COMPILED_SUFFIX;
		
		if (!file_exists($infile)) trigger_error("OutlineCompiler::build(): input file '$infile' not found", E_USER_ERROR);
		$i = fopen($infile, 'r');
		
		$this->infile = $infile;
		
		@mkdir(dirname($outfile), 0777, true);
		$this->o = fopen($outfile, 'w');
		if (!$this->o) trigger_error("OutlineCompiler::build(): output file '$outfile' is not writable", E_USER_ERROR);
		
		$state = OUTLINE_STATE_PASSTHRU;
		
		$this->coding = false;
		
		$tag = '';
		
		$this->linenum = 1;
		
		$this->ignore = false;
		
		$close_ignore = str_repeat(' ', strlen(OUTLINE_CLOSE_IGNORE_TAG));
		
		while (!feof($i)) {
			
			$buffer = fread($i, OUTLINE_BUFFER_SIZE);
			$len = strlen($buffer);
			
			for ($n=0; $n<$len; $n++) {
				
				$char = $buffer{$n};
				
				if ($this->ignore) {
					
					$close_ignore = substr($close_ignore.$char, 1, strlen(OUTLINE_CLOSE_IGNORE_TAG));
					if ($close_ignore == OUTLINE_CLOSE_IGNORE_TAG) {
						$dummy = '';
						outline_ignore_close($this, $dummy);
						fseek($this->o, -strlen(OUTLINE_CLOSE_IGNORE_TAG)+1, SEEK_CUR);
						ftruncate($this->o, ftell($this->o));
					} else {
						fwrite($this->o, $this->emit($char));
					}
					
				} else {
					
					switch ($state) {
						
						case OUTLINE_STATE_PASSTHRU:
							
							if ($char == OUTLINE_TOKEN_OPEN) {
								$state = OUTLINE_STATE_PARSING;
							} else {
								fwrite($this->o, $this->emit($char));
							}
							
						break;
						
						case OUTLINE_STATE_PARSING:
							
							if ($char == OUTLINE_TOKEN_CLOSE) {
								fwrite($this->o, $this->parse($tag));
								$tag = '';
								$state = OUTLINE_STATE_PASSTHRU;
							} else {
								$tag .= $char;
							}
							
						break;
						
					}
					
				}
				
				if ($char == "\n") $this->linenum++;
				
			}
			
		}
		
		fwrite($this->o, $this->emit(''));
		
		fclose($this->o);
		
		if (count($this->plugins['Basics']->struct_stack))
			$this->error('Unexpected end of file - no closing tag found for {' . end($this->plugins['Basics']->struct_stack) . '}');
		
	}
	
	function parse($tag) {
		
		if ($tag{0} == OUTLINE_TOKEN_IGNORE) return;
		
		$mods = $this->escape_split($tag, OUTLINE_TOKEN_PIPE);
		
		$tag = trim(array_shift($mods));
		
		if ($tag{0} == OUTLINE_TOKEN_VAR || $tag{0} == OUTLINE_TOKEN_CONST) {
			
			$code = $tag{0} == OUTLINE_TOKEN_CONST ? substr($tag,1) : $tag;
			
			foreach ($mods as $mod) {
				$args = $this->escape_split($mod, ':');
				$mod = trim(array_shift($args));
				if (function_exists('outline_'.$mod)) {
					$code = 'outline_' . $mod . '(' . $code . (count($args) ? ', '.implode(', ', $args) : '') . ')';
				} else if (function_exists($mod)) {
					$code = $mod . '(' . $code . (count($args) ? ', '.implode(', ', $args) : '') . ')';
				} else {
					$this->error("Function '$mod' not found");
				}
			}
			
			return $this->code("echo $code;");
			
		} else {
			
			$offset = strpos($tag, ' ');
			$keyword = ( $offset ? substr($tag, 0, $offset) : $tag );
			
			if ($keyword{0} == OUTLINE_TOKEN_CANCEL) {
				$keyword = substr($keyword, 1);
				$code = $this->exec($keyword, trim(substr($tag, $offset)), true);
			} else {
				$code = $this->exec($keyword, trim(substr($tag, $offset)), false);
			}
			
			return $this->code($code);
			
		}
		
	}
	
	function & escape_split($str, $token) {
		
		$a = array();
		$bit = '';
		$len = strlen($str);
		$last = '';
		$quote = '';
		
		for ($i=0; $i<$len; $i++) {
			$char = $str{$i};
			if ($char == "'" || $char == '"') {
				if ($last != "\\") {
					if ($quote == '') {
						$quote = $char;
					} else if ($quote == $char) {
						$quote = '';
					}
				}
			}
			if ($char == $token && $quote == '') {
				$a[] = trim($bit);
				$bit = '';
			} else {
				$bit .= $char;
			}
			$last = $char;
		}
		
		if (trim($bit) != '') $a[] = trim($bit);
		
		return $a;
		
	}
	
	function exec(&$keyword, &$arguments, $closing) {
		
		$funcname = 'outline_' . $keyword . ($closing ? '_close' : '');
		
		if (function_exists($funcname)) {
			return call_user_func($funcname, $this, $arguments);
		} else {
			$blockname = strtoupper($keyword);
			if (defined("OUTLINE_BLOCK_DECL_".$blockname)) {
				return ' outline_block_' . $blockname . '(' . $arguments . '); ';
			} else {
				$this->error("Unrecognized " . ($closing ? 'closing ' : '') . "tag '$keyword'");
			}
		}
		
	}
	
	function code($php) {
		$code = ( $this->coding ? '' : OUTLINE_PHPTAG_OPEN ) . $php;
		$this->coding = true;
		return $code;
	}
	
	function emit($text) {
		$code = ( $this->coding ? OUTLINE_PHPTAG_CLOSE : '' ) . $text;
		$this->coding = false;
		return $code;
	}
	
	function error($msg) {
		trigger_error("OutlineCompiler : $msg - in <b>" . $this->infile . "</b> on line <b>" . $this->linenum . "</b><br />", E_USER_ERROR);
	}
	
}

?>