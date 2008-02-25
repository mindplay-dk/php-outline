<?php

/*

OutlineBasics
-------------

Copyright (C) 2008, Rasmus Schultz <http://www.mindplay.dk>

Please see "README.txt" for license and other information.

===========================================================

This plugin implements these basic commands:

- for
- foreach
- if/else/elseif
- cycle/next
- while
- require
- capture
- display
- include
- ignore
- block

*/

class OutlineBasics {
	
	var $outline;
	
	var $struct_stack;
	var $cap_stack;

	var $cycle_num = 0;
	var $cycles = array();
	var $cycle_stack = array();
	
	var $for_stack = array();
	
	var $blockname = null;
	
	function OutlineBasics(&$outline) {
		$this->outline = &$outline;
	}
	
	// --- Structure stack:
	
	function struct_stack_push($keyword) {
		$this->struct_stack[] = $keyword;
	}
	
	function struct_stack_pop($keyword, $alt_keyword = null) {
		$this->struct_stack_check($keyword, $alt_keyword);
		array_pop($this->struct_stack);
	}
	
	function struct_stack_check($keyword, $alt_keyword = null) {
		if (end($this->struct_stack) != $keyword)
			$this->outline->error('Unmatched control tag {' . ($alt_keyword ? $alt_keyword : $keyword) . '}' . (count($this->struct_stack) ? ', expected closing tag for {' . end($this->struct_stack) . '}' : ''));
	}
	
	// --- Capture stack:
	
	function cap_stack_push($varname) {
		$this->cap_stack[] = $varname;
	}
	
	function cap_stack_pop() {
		return array_pop($this->cap_stack);
	}
	
	// --- Cycle stack:
	
	function cycle_stack_push($num) {
		$this->cycle_stack[] = $num;
	}
	
	function cycle_stack_pop() {
		return array_pop($this->cycle_stack);
	}
	
	function cycle_stack_get() {
		return end($this->cycle_stack);
	}

	// --- For stack:
	
	function for_stack_push($varname) {
		$this->for_stack[] = $varname;
	}
	
	function for_stack_pop() {
		return array_pop($this->for_stack);
	}
	
	function for_stack_check($varname) {
		return in_array($varname, $this->for_stack);
	}
	
	// --- Include counter:
	
	function include_number() {
		static $num = 0;
		return $num++;
	}
	
}

// --- {set $var=...}

function outline_set(&$gen, &$args) {
	return $args.';';
}

// --- {for $var from x to y by z} .. {/for}

function outline_for(&$gen, &$args) {
	
	$bits = array();
	
	foreach (explode(" ", $args) as $item)
		if (strlen(trim($item))) $bits[] = trim($item);

	$ok = true;
	
	$step = 1;
	$op = '<=';
	
	if ($bits[1] == 'from' && $bits[3] == 'to' && ctype_digit($bits[2]) && ctype_digit($bits[4])) {
		$from = intval($bits[2]);
		$to = intval($bits[4]);
		if (count($bits) == 5) {
			$step = 1;
		} else if (count($bits) == 7 && $bits[5] == 'by' && ctype_digit($bits[6])) {
			$step = intval($bits[6]);
		} else {
			$ok = false;
		}
	} else {
		$ok = false;
	}
	
	$var = $bits[0];
	
	if (!$ok || $var{0} != OUTLINE_TOKEN_VAR) {
		$gen->error("Syntax error in {for} statement");
	}
	
	if ($to < $from) {
		$step = -$step;
		$op = '>=';
	}
	
	if ($gen->plugins['Basics']->for_stack_check($var)) $gen->error("Attempt to use same iterator '$var' in two nested {for} statements");
	
	$gen->plugins['Basics']->struct_stack_push('for');
	$gen->plugins['Basics']->for_stack_push($var);
	
	return " for ($var=$from; $var$op$to; $var+=$step) { ";
	
}

function outline_for_close(&$gen, &$args) {
	$gen->plugins['Basics']->struct_stack_pop('for', '/for');
	$gen->plugins['Basics']->for_stack_pop();
	return ' } ';
}

// --- {foreach $array as $item => $value} .. {/foreach}

function outline_foreach(&$gen, &$args) {
	$gen->plugins['Basics']->struct_stack_push('foreach');
	return 'foreach ('.$args.') {';
}

function outline_foreach_close(&$gen, &$args) {
	$gen->plugins['Basics']->struct_stack_pop('foreach', '/foreach');
	return '}';
}

// --- {if} .. {elseif} .. {else} .. {/if}

function outline_if(&$gen, &$args) {
	$gen->plugins['Basics']->struct_stack_push('if');
	return 'if ('.$args.') {';
}

function outline_else(&$gen, &$args) {
	$gen->plugins['Basics']->struct_stack_check('if', 'else');
	return '} else {';
}

function outline_elseif(&$gen, &$args) {
	$gen->plugins['Basics']->struct_stack_check('if', 'elseif');
	return '} else if ('.$args.') {';
}

function outline_if_close(&$gen, &$args) {
	$gen->plugins['Basics']->struct_stack_pop('if', '/if');
	return '}';
}

// --- {while $var..} .. {/while}

function outline_while(&$gen, &$args) {
	$gen->plugins['Basics']->struct_stack_push('while');
	return 'while ('.$args.') {';
}

function outline_while_close(&$gen, &$args) {
	$gen->plugins['Basics']->struct_stack_pop('while', '/while');
	return '}';
}

// --- {require 'script.php'}

function outline_require(&$gen, &$args) {
	return "require_once '$args';";
}

// --- {capture $var} .. {/capture}

function outline_capture(&$gen, &$args) {
	if ($args{0} != '$') $gen->error('{capture} tag needs a variable');
	$gen->plugins['Basics']->struct_stack_push('capture');
	$gen->plugins['Basics']->cap_stack_push($args);
	return 'ob_start();';
}

function outline_capture_close(&$gen, &$args) {
	$gen->plugins['Basics']->struct_stack_pop('capture', '/capture');
	$var = $gen->plugins['Basics']->cap_stack_pop();
	return $var.' = ob_get_clean();';
}

// --- {display $filename}

function outline_display(&$gen, &$args) {
	return "echo htmlspecialchars(file_get_contents('".$args."'));";
}

// --- {include 'other_template'}

function outline_include(&$gen, &$tplname) {
	
	/*
	$template = OUTLINE_TEMPLATE_FOLDER . '/' . $tplname . OUTLINE_TEMPLATE_SUFFIX;
	$compiled = OUTLINE_COMPILED_FOLDER . '/' . $tplname . OUTLINE_COMPILED_SUFFIX;
	
	if (!file_exists($template)) $gen->error("Unable to include the template '" . $tplname . OUTLINE_TEMPLATE_SUFFIX . "'");
	
	if (@constant("OUTLINE_ALWAYS_COMPILE") || !file_exists($compiled) || (filemtime($template) > @filemtime($compiled))) {
		$gen = new OutlineCompiler();
		$gen->build($tplname);
		unset($gen);
	}
	
	return "require '" . $tplname . OUTLINE_COMPILED_SUFFIX . "';";
	*/
	
	$tplname = trim($tplname);
	
	if ($tplname{0} != '$') $tplname = "'$tplname'";
	
	$var = '$outline_include_' . $gen->plugins['Basics']->include_number();
	
	return "$var = new Outline($tplname); require $var" . '->get();';
	
}

// --- {ignore} javascript/css {/ignore}

function outline_ignore(&$gen, &$args) {
	$gen->plugins['Basics']->struct_stack_push('ignore');
	$gen->ignore = true;
}

function outline_ignore_close(&$gen, &$args) {
	$gen->plugins['Basics']->struct_stack_pop('ignore', '/ignore');
	$gen->ignore = false;
}

// --- {cycle} .. {next} .. {next} .. {/cycle}

function outline_cycle(&$gen, &$args) {
	$gen->plugins['Basics']->struct_stack_push('cycle');
	$num = ++ $gen->plugins['Basics']->cycle_num;
	$gen->plugins['Basics']->cycle_stack_push($num);
	$gen->plugins['Basics']->cycles[$num] = 1;
	$var = '$outline_cycle_' . $num;
	return "if (!isset($var)) { $var = 0; } $var++; if ($var == 1) {";
}

function outline_next(&$gen, &$args) {
	$gen->plugins['Basics']->struct_stack_check('cycle', 'next');
	$num = $gen->plugins['Basics']->cycle_stack_get();
	$count = ++ $gen->plugins['Basics']->cycles[$num];
	$var = '$outline_cycle_' . $num;
	return "} else if ($var == $count) {";
}

function outline_cycle_close(&$gen, &$args) {
	$gen->plugins['Basics']->struct_stack_pop('cycle', '/cycle');
	$num = $gen->plugins['Basics']->cycle_stack_pop();
	$count = $gen->plugins['Basics']->cycles[$num]++;
	$var = '$outline_cycle_' . $num;
	return "} if ($var == $count) { $var = 0; }";
}

// --- {block name $var, $var, $var, .. } ... {/block}

function outline_block(&$gen, &$args) {
	$gen->plugins['Basics']->struct_stack_push('block');
	$pos = strpos($args, " "); if ($pos === false) $pos = strlen($args);
	$blockname = strtoupper(substr($args, 0, $pos));
	$args = substr($args, $pos+1);
	if (defined("OUTLINE_BLOCK_DECL_".$blockname)) $gen->error("block '$blockname' redeclared");
	if (function_exists("outline_".strtolower($blockname))) $gen->error("blockname '$blockname' is reserved");
	$gen->plugins['Basics']->blockname = $blockname;
	@define("OUTLINE_BLOCK_DECL_".$blockname, 1);
	return ' @define("OUTLINE_BLOCK_DECL_' . $blockname . '",1); function outline_block_' . $blockname . '(' . $args . ') { ';
}

function outline_block_close(&$gen, &$args) {
	$gen->plugins['Basics']->struct_stack_pop('block', '/block');
	$gen->plugins['Basics']->blockname = null;
	return ' } ';
}

?>