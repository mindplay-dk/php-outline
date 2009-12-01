<?php

/*

Outline System Plugin Run-time
------------------------------

Copyright (C) 2007-2009, Rasmus Schultz <http://www.mindplay.dk>

Please see "README.txt" for license and other information.

*/

class OutlineRuntime_system implements IOutlineRuntime {
  
  protected $runtime;
  
  public function __construct(OutlineRuntime & $runtime) {
    $this->runtime = & $runtime;
  }
  
}

function outline__replace($str, $search = '', $replace = '') { return str_replace($search, $replace, $str); }
function outline__default($var, $default = '') { return empty($var) ? $default : $var; }
function outline__strip($str, $replace = ' ') { return preg_replace('!\s+!', $replace, $str); }
function outline__date($var, $format) { return date($format, $var); }
function outline__time($var, $format) { return strftime($format, $var); }
function outline__html($var, $quote = ENT_QUOTES) { return htmlspecialchars($var, $quote); }
function outline__url($var) { return rawurlencode($var); }
function outline__escape($var) { return strtr($var, array('\\'=>'\\\\',"'"=>"\\'",'"'=>'\\"',"\r"=>'\\r',"\n"=>'\\n','</'=>'<\/')); }
function outline__upper($var) { return strtoupper($var); }
function outline__lower($var) { return strtolower($var); }
function outline__format($var, $decimals=2, $dec_point='.', $thousand_sep=',') { return $var ? number_format(floatval($var), $decimals, $dec_point, $thousand_sep) : ''; }
function outline__br($var, $replace = "<br />") { return str_replace("\n", $replace, $var); }
function outline__chop($var, $max = 100, $dots = '...', $chop = false) { return strlen($var)>$max ? ( $chop ? substr($var,0,$max).$dots : preg_replace('/\s+?(\S+)?$/','',substr($var, 0, $max)).$dots ) : $var; }

function outline__wed($str, $max=18) {
  $str = rtrim($str);
  $space = strrpos($str, ' ');
  if ($space !== false && strlen($str)-$space <= $max) {
    $str = substr($str, 0, $space).'&nbsp;'.substr($str, $space + 1);
  }
  return $str;
}

class OutlineIterator {
  
  /*
  Compiled templates, that use the for-command, use this helper class.
  */
  
  public $index, $start, $end, $step;
  
  public function __construct($start, $end, $step) {
    $this->start = $start;
    $this->end = $end;
    $this->step = ($end<$start && $step>0 ? -$step : $step);
    $this->index = $start - $this->step;
  }
  
  public function next() {
    $more = ($this->step>0 ? $this->index<$this->end : $this->index>$this->end);
    $this->index += $this->step;
    return $more;
  }
  
}
