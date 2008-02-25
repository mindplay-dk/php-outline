<?php

/*

Outline Modifiers
-----------------

Copyright (C) 2008, Rasmus Schultz <http://www.mindplay.dk>

Please see "README.txt" for license and other information.

===========================================================

This script implements Outline's standard modifiers.

*/

function outline_replace($str, $search = '', $replace = '') {
	return str_replace($search, $replace, $str);
}

function outline_default($var, $default = '') {
	return empty($var) ? $default : $var;
}

function outline_wed($str, $max=18) {
	$str = rtrim($str);
	$space = strrpos($str, ' ');
	if ($space !== false && strlen($str)-$space <= $max) {
		$str = substr($str, 0, $space).'&nbsp;'.substr($str, $space + 1);
	}
	return $str;
}

function strip($str, $replace = ' ') {
	return preg_replace('!\s+!', $replace, $str);
}

function outline_date($var, $format) {
	return date($format, $var);
}

function outline_strftime($var, $format) {
	return strftime($format, $var);
}

?>