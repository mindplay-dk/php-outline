<?php

/*

OutlineSystem Plugin
--------------------

Copyright (C) 2007-2008, Rasmus Schultz <http://www.mindplay.dk>

Please see "README.txt" for license and other information.

*/

class OutlineSystem extends OutlinePlugin {
	
	// * System tags and helper methods:
	
	public function var_tag($args) {
		$this->compiler->code('echo ' . $this->apply_modifiers('$'.$args) . ';');
	}
	
	public function echo_tag($args) {
		$this->compiler->code('echo ' . $this->apply_modifiers($args) . ';');
	}
	
	public function set_tag($args) {
		$this->compiler->code($args.';');
	}
	
	public function apply_modifiers($args) {
		
		$mods = $this->compiler->escape_split($args, OUTLINE_MODIFIER_PIPE);
		$code = trim(array_shift($mods));
		
		foreach ($mods as $mod) {
			$args = $this->compiler->escape_split($mod, OUTLINE_MODIFIER_SEP);
			$mod = trim(array_shift($args));
			if (function_exists(OUTLINE_MODIFIER_PREFIX.$mod)) {
				$code = OUTLINE_MODIFIER_PREFIX.$mod . '(' . $code . (count($args) ? ', '.implode(', ', $args) : '') . ')';
			} else if (function_exists($mod)) {
				$code = $mod . '(' . $code . (count($args) ? ', '.implode(', ', $args) : '') . ')';
			} else {
				throw new OutlineException("modifier '$mod' not found", $this->compiler);
			}
		}
		
		return $code;
		
	}
	
	// * if / elseif / else / endif tags:
	
	public function if_block($args) {
		$this->compiler->code('if ('.$args.') {');
	}

	public function end_if_block($args) {
		$this->compiler->code('}');
	}
	
	public function else_tag($args) {
		$this->compiler->checkBlock('if', 'else');
		$this->compiler->code('} else {');
	}

	public function elseif_tag($args) {
		$this->compiler->checkBlock('if', 'elseif');
		$this->compiler->code('} else if ('.$args.') {');
	}
	
	// * require, include, display tags:
	
	public function require_tag($args) {
		$this->compiler->code("require_once '$args';");
	}
	
	protected $include_num = 0;
	
	public function include_tag($args) {
		$tplname = trim($args);
		if (substr($tplname,0,1) != '$') $tplname = "'$tplname'";
		$var = '$outline_include_' . ($this->include_num++);
		$this->compiler->code("$var = new Outline($tplname); require $var" . '->get();');
	}
	
	public function display_tag($args) {
		$this->compiler->code("echo htmlspecialchars(file_get_contents('".$args."'));");
	}
	
	// * user-block:
	
	static protected $block_keyword = null;
	
	public function user_block($args) {
		if (self::$block_keyword) throw new OutlineException("nested user-block declarations are not allowed", $this->compiler);
		$pos = strpos($args, " "); if ($pos === false) $pos = strlen($args);
		$keyword = self::$block_keyword = strtolower(substr($args, 0, $pos));
		$function = OUTLINE_USERBLOCK_PREFIX.$keyword;
		$args = substr($args, $pos+1);
		$this->compiler->code("function {$function}({$args}) {");
	}
	
	public function end_user_block($args) {
		self::$block_keyword = null;
		$this->compiler->code('}');
	}
	
	public function user_tag($args) {
		$args = trim($args);
		$pos = strpos($args, " "); if ($pos === false) $pos = strlen($args);
		$keyword = strtolower(substr($args, 0, $pos));
		$args = substr($args, $pos+1);
		$this->compiler->code(OUTLINE_USERBLOCK_PREFIX.$keyword.'('.$args.');');
	}
	
	// * capture block:
	
	protected $cap_stack = array();
	
	public function capture_block($args) {
		if (substr($args,0,1) != '$') throw new OutlineException('no variable specified for capture');
		$this->cap_stack[] = $args;
		$this->compiler->code('ob_start();');
	}
	
	public function end_capture_block($args) {
		$var = array_pop($this->cap_stack);
		$this->compiler->code($var . ' = ob_get_clean();');
	}	
	
	// * while block:
	
	public function while_block($args) {
		$this->compiler->code('while ('.$args.') {');
	}
	
	public function end_while_block($args) {
		$this->compiler->code('}');
	}
	
	// * for block:
	
	protected $for_stack = array();
	
	public function for_block($args) {
		
		$bits = array();
		
		foreach (explode(" ", $args) as $item)
			if (strlen(trim($item))) $bits[] = trim($item);
		
		$ok = true; $step = 1; $op = '<=';
		
		if ($bits[1] == 'from' && $bits[3] == 'to' && ctype_digit($bits[2]) && ctype_digit($bits[4])) {
			$from = intval($bits[2]);
			$to = intval($bits[4]);
			if (count($bits) == 5) {
				$step = 1;
			} else if (count($bits) == 7 && $bits[5] == 'by' && ctype_digit($bits[6])) {
				$step = intval($bits[6]);
				$ok = ($step != 0);
			} else {
				$ok = false;
			}
		} else {
			$ok = false;
		}
		
		$var = $bits[0];
		
		if (in_array($var, $this->for_stack)) throw new OutlineException("use of same iterator '$var' in nested {for} statements", $this->compiler);
		
		if (!$ok || substr($var,0,1) != '$') throw new OutlineException("syntax error in for-statement", $this->compiler);
		
		if ($to < $from) { $step = -$step; $op = '>='; }
		
		$this->for_stack[] = $var;
		
		$this->compiler->code("for ($var=$from; $var$op$to; $var+=$step) {");
		
	}
	
	public function end_for_block($args) {
		array_pop($this->for_stack);
		$this->compiler->code('}');
	}	
	
	// * foreach block:
	
	public function foreach_block($args) {
		$this->compiler->code('foreach ('.$args.') {');
	}
	
	public function end_foreach_block($args) {
		$this->compiler->code('}');
	}
	
	// * cycle/next block:
	
	protected $cycle_num = 0, $cycles = array(), $cycle_stack = array();
	
	public function cycle_block($args) {
		$num = ++$this->cycle_num;
		$this->cycle_stack[] = $num;
		$this->cycles[$num] = 1;
		$var = '$outline_cycle_' . $num;
		$this->compiler->code("!isset($var) ? $var = 0 : $var++; if ($var == 1) {");
	}
	
	public function cycle_next_tag($args) {
		$this->compiler->checkBlock('cycle', 'next');
		$num = end($this->cycle_stack);
		$count = ++$this->cycles[$num];
		$var = '$outline_cycle_' . $num;
		$this->compiler->code("} else if ($var == $count) {");
	}
	
	public function end_cycle_block($args) {
		$num = array_pop($this->cycle_stack);
		$count = $this->cycles[$num]++;
		$var = '$outline_cycle_' . $num;
		$this->compiler->code("} if ($var == $count) { $var = 0; }");
	}
	
	public static function register() {
		OutlineCompiler::registerTag('$', 'var_tag');
		OutlineCompiler::registerTag('#', 'echo_tag');
		OutlineCompiler::registerTag('set', 'set_tag');
		OutlineCompiler::registerBlock('if', 'if_block');
		OutlineCompiler::registerTag('else', 'else_tag');
		OutlineCompiler::registerTag('elseif', 'elseif_tag');
		OutlineCompiler::registerTag('require', 'require_tag');
		OutlineCompiler::registerTag('include', 'include_tag');
		OutlineCompiler::registerTag('display', 'display_tag');
		OutlineCompiler::registerBlock('block', 'user_block');
		OutlineCompiler::registerTag('!', 'user_tag');
		OutlineCompiler::registerBlock('capture', 'capture_block');
		OutlineCompiler::registerBlock('while', 'while_block');
		OutlineCompiler::registerBlock('for', 'for_block');
		OutlineCompiler::registerBlock('foreach', 'foreach_block');
		OutlineCompiler::registerBlock('cycle', 'cycle_block');
		OutlineCompiler::registerTag('next', 'cycle_next_tag');
	}
	
}

OutlineCompiler::registerPlugin('OutlineSystem');

?>