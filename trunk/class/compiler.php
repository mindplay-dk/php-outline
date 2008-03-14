<?php

/*

OutlineCompiler
---------------

Copyright (C) 2007-2008, Rasmus Schultz <http://www.mindplay.dk>

Please see "README.txt" for license and other information.
	
*/

define("OUTLINE_COMPILER", 1);

define("OUTLINE_BRACKET_OPEN",        '{');
define("OUTLINE_BRACKET_CLOSE",       '}');
define("OUTLINE_BRACKET_COMMENT",     '{*');
define("OUTLINE_BRACKET_END_COMMENT", '*}');
define("OUTLINE_BRACKET_IGNORE",      '{ignore}');
define("OUTLINE_BRACKET_END_IGNORE",  '{/ignore}');
define("OUTLINE_COMMAND_CANCEL",      '/');
define("OUTLINE_PHPTAG_OPEN",         '<'.'?php ');
define("OUTLINE_PHPTAG_CLOSE",        ' ?'.'>');
define("OUTLINE_MODIFIER_PIPE",       '|');
define("OUTLINE_MODIFIER_SEP",        ':');
define("OUTLINE_MODIFIER_PREFIX",     'outline__');
define("OUTLINE_USERBLOCK_PREFIX",    'outline__user_');
define("OUTLINE_USERBLOCK_CONST",     'OUTLINE_USER_');

class OutlineCompilerException extends Exception {
	
	protected $linenum = 0;
	
	public function __construct($message, OutlineCompiler & $compiler) {
		parent::__construct($message, -1);
		$this->linenum = $compiler->getLineNum();
	}
	
	public function getLineNum() { return $this->linenum; }
	
}

class OutlineCompiler {
	
	const BRACKET_OPEN        = 1;
	const BRACKET_CLOSE       = 2;
	const BRACKET_COMMENT     = 3;
	const BRACKET_END_COMMENT = 4;
	const BRACKET_IGNORE      = 5;
	const BRACKET_END_IGNORE  = 6;
	
	const COMMAND_TAG =   1;
	const COMMAND_BLOCK = 2;
	
	// * Brackets:
	
	static protected $brackets_begin = array(
		OUTLINE_BRACKET_IGNORE => self::BRACKET_IGNORE,
		OUTLINE_BRACKET_COMMENT => self::BRACKET_COMMENT,
		OUTLINE_BRACKET_OPEN => self::BRACKET_OPEN
	);
	
	static protected $brackets_end = array(
		OUTLINE_BRACKET_CLOSE => self::BRACKET_CLOSE
	);
	
	static protected $brackets_comment = array(
		OUTLINE_BRACKET_END_COMMENT => self::BRACKET_END_COMMENT
	);
	
	static protected $brackets_ignore = array(
		OUTLINE_BRACKET_END_IGNORE => self::BRACKET_END_IGNORE
	);
	
	// * Other members:
	
	static protected $blocks = array();
	static protected $tags = array();
	
	protected $commands;
	
	protected $plugins = array();
	static protected $plugin_registry = array();
	static protected $current_plugin = null;
	
	protected $utf8 = false;
	
	protected $engine;
	
	public function __construct(OutlineEngine & $engine) {
		$this->engine = & $engine;
		$this->commands = array(
			array("type" => self::COMMAND_BLOCK, "commands" => & self::$blocks),
			array("type" => self::COMMAND_TAG,   "commands" => & self::$tags)
		);
	}
	
	public function __destruct() {
		foreach ($this as $index => $value) unset($this->$index);
	}
	
	public function & getEngine() {
		return $this->engine;
	}
	
	// --- Compiler and Parser methods:
	
	public function compile($tpl) {
		
		if ($this->utf8 = self::is_utf8($tpl)) trigger_error("OutlineCompiler running in UTF-8 mode", E_USER_NOTICE);
		
		$brackets = & self::$brackets_begin;
		$command = '';
		$in_command = false;
		$in_comment = false;
		
		$i = 0;
		
		$this->compiled = '';
		$this->coding = false;
		$this->linenum = 1;
		
		while ($i < strlen($tpl)) {
			
			if ($newline = (substr($tpl, $i, 1) === "\n")) $this->linenum++;
			
			foreach ($brackets as $bracket => $type) {
				
				if (substr($tpl, $i, strlen($bracket)) === $bracket) {
					
					switch ($type) {
						
						// * Normal opening/closing brackets:
						
						case self::BRACKET_OPEN:	
							$in_command = true;
							$brackets = & self::$brackets_end;
						break;
						
						case self::BRACKET_CLOSE:
							$in_command = false;
							$this->parse($command);
							$command = '';
							$brackets = & self::$brackets_begin;
						break;
						
						// * Comments:
						
						case self::BRACKET_COMMENT:
							$in_comment = true;
							$brackets = & self::$brackets_comment;
						break;
						
						case self::BRACKET_END_COMMENT:
							$in_comment = false;
							$brackets = & self::$brackets_begin;
						break;
						
						// * Ignore command:
						
						case self::BRACKET_IGNORE:
							$in_command = true;
							$brackets = & self::$brackets_ignore;
						break;
						
						case self::BRACKET_END_IGNORE:
							$in_command = false;
							$this->output($command);
							$command = '';
							$brackets = & self::$brackets_begin;
						break;
						
					}
					
					$i += strlen($bracket);
					
					continue 2;
					
				}
				
			}
			
			if ($in_command) {
				$command .= substr($tpl, $i, 1);
			} elseif (!$in_comment || $newline) {
				$this->output(substr($tpl, $i, 1));
			}
			
			$i++;
			
		}
		
		if (count($this->block_stack))
			throw new OutlineCompilerException("OutlineCompiler::compile() : unterminated block: " . end($this->block_stack) . " at end of template", $this);
		
		if ($this->coding) $this->compiled .= OUTLINE_PHPTAG_CLOSE;
		
		foreach ($this->plugins as $class => $plugin) {
			$plugin->__destruct();
			unset($this->plugins[$class]);
		}
		
		return $this->compiled;
		
	}
	
	protected function parse($command) {
		
		$cancel = (substr($command, 0, strlen(OUTLINE_COMMAND_CANCEL)) === OUTLINE_COMMAND_CANCEL);
		
		$match = 0;
		
		$lcommand = strtolower($command);
		
		foreach ($this->commands as $c) {
			
			foreach ($c['commands'] as $keyword => $item) {
				if ((substr($lcommand, $cancel ? strlen(OUTLINE_COMMAND_CANCEL) : 0, strlen($keyword)) === $keyword) && (strlen($keyword) > $match)) {
					$match = strlen($keyword);
					$type = $c['type'];
					$classname = $item['class'];
					$function = ($cancel ? 'end_' : '') . $item['function'];
					$args = trim(substr($command, strlen($keyword)));
					$command_name = substr($command, $cancel ? strlen(OUTLINE_COMMAND_CANCEL) : 0, strlen($keyword));
				}
			}
			
		}
		
		if (!$match) throw new OutlineCompilerException("OutlineCompiler::parse() : unrecognized tag: ".htmlspecialchars($command), $this);
		
		if ($classname && !isset($this->plugins[$classname]))
			$this->plugins[$classname] = new $classname($this);
		
		switch ($type) {
			
			case self::COMMAND_BLOCK:
				$cancel ? $this->popBlock($command_name, $command) : $this->pushBlock($command_name, $command);
				$this->plugins[$classname]->$function($args);
			return;
			
			case self::COMMAND_TAG:
				$this->plugins[$classname]->$function($args);
			return;
			
		}
	
	}
	
	// --- Coding and output methods:
	
	protected $coding;
	
	public function code($php) {
		$this->compiled .= ( $this->coding ? ' ' : OUTLINE_PHPTAG_OPEN ) . $php;
		$this->coding = true;
	}
	
	public function output($text) {
		$this->compiled .= ( $this->coding ? OUTLINE_PHPTAG_CLOSE : '' ) . $text;
		$this->coding = false;
	}
	
	// --- Utility methods:
	
	public static function is_utf8(&$str) {
		return ( mb_detect_encoding($str,'ASCII,UTF-8',true) == 'UTF-8' );
	}
	
	public function split(&$str) {
		if (!$this->utf8) return str_split($str,1);
		$chars = null;
		preg_match_all('/.{1}|[^\x00]{1,1}$/us', $str, $chars);
		return $chars[0];
	}
	
	public function escape_split(&$str, $token) {
		
		$a = array();
		$bit = ''; $last = ''; $quote = '';
		$chars = $this->split($str);
		$len = count($chars);
		
		for ($i=0; $i<$len; $i++) {
			$char = $chars[$i];
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
	
	// --- Block/nesting management methods:
	
	protected $block_stack = array();
	
	public function pushBlock($name, $command) {
		$this->block_stack[] = $name;
	}
	
	public function popBlock($name, $command) {
		$this->checkBlock($name, $command);
		array_pop($this->block_stack);
	}
	
	public function checkBlock($name, $command) {
		if (end($this->block_stack) !== $name)
			throw new OutlineCompilerException("unmatched tag: ".htmlspecialchars($command) . (count($this->block_stack) ? " - expected closing tag for " . end($this->block_stack) : ""), $this);
	}
	
	// --- Command registration methods:
	
	protected static function registerCommand($type, $keyword, $function) {
		
		if ( isset(self::$tags[$keyword]) || isset(self::$blocks[$keyword]) )
			trigger_error("OutlineCompiler::register() : keyword '$keyword' already registered", E_USER_ERROR);
		
		$plugin = array(
			"class" => self::$current_plugin,
			"function" => $function
		);
		
		switch ($type) {
			case self::COMMAND_BLOCK: self::$blocks[$keyword] = $plugin; break;
			case self::COMMAND_TAG: self::$tags[$keyword] = $plugin; break;
		}
		
	}
	
	public static function registerTag($keyword, $function) {
		self::registerCommand(self::COMMAND_TAG, $keyword, $function);
	}
	
	public static function registerBlock($keyword, $function) {
		self::registerCommand(self::COMMAND_BLOCK, $keyword, $function);
	}
	
	// --- Plugin management methods:
	
	public static function registerPlugin($classname) {
		
		if (in_array($classname, self::$plugin_registry))
			trigger_error("OutlineCompiler::registerPlugin() : plugin '$classname' already registered", E_USER_ERROR);
		
		self::$plugin_registry[] = $classname;
		
		self::$current_plugin = $classname;
		call_user_func(array($classname, "register"));
		self::$current_plugin = null;
		
	}
	
	// --- Error management methods:
	
	protected $linenum;
	
	public function getLineNum() { return $this->linenum; }
	
}

abstract class OutlinePlugin {
	
	protected $compiler;
	
	public function __construct(OutlineCompiler & $compiler) {
		$this->compiler = & $compiler;
	}
	
	public function __destruct() {
		foreach ($this as $index => $value) unset($this->$index);
	}
	
	public static function register() {
		trigger_error("OutlinePlugin::register() : plugins must override this method", E_USER_ERROR);
	}
	
}

?>