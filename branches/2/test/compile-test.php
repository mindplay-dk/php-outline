<?php

require "_header.php";

function outline_function_testfunc($value) {
  return 'The time is: '.date('H:i:s',$value);
}

$engine = new Outline();

$engine->compile(
  dirname(__FILE__).'/templates/test.tpl.html',
  dirname(__FILE__).'/compiled/test.tpl.php'
);
