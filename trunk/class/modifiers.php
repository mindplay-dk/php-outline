<?php

/*

Outline Modifiers
-----------------

Copyright (C) 2007-2008, Rasmus Schultz <http://www.mindplay.dk>

Please see "README.txt" for license and other information.

*/

function outline__replace($str, $search = '', $replace = '') { return str_replace($search, $replace, $str); }
function outline__default($var, $default = '') { return empty($var) ? $default : $var; }
function outline__strip($str, $replace = ' ') { return preg_replace('!\s+!', $replace, $str); }
function outline__date($var, $format) { return date($format, $var); }
function outline__time($var, $format) { return strftime($format, $var); }
function outline__html($var, $quote = ENT_QUOTES) { return htmlspecialchars($var, $quote); }
function outline__url($var) { return rawurlencode($var); }
function outline__escape($var) { return strtr($var, array('\\'=>'\\\\',"'"=>"\\'",'"'=>'\\"',"\r"=>'\\r',"\n"=>'\\n','</'=>'<\/')); }

function outline__wed($str, $max=18) {
	$str = rtrim($str);
	$space = strrpos($str, ' ');
	if ($space !== false && strlen($str)-$space <= $max) {
		$str = substr($str, 0, $space).'&nbsp;'.substr($str, $space + 1);
	}
	return $str;
}


?>