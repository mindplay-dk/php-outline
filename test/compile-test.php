<?php

require "_header.php";

function outline_function_testfunc($value) {
  return 'The time is: '.date('H:i:s',$value);
}

function OutlineDebug($msg) {
	echo "<div style=\"color:#f00\"><strong>Outline</strong>: $msg</div>";
}

$engine = new Outline(array('trace_callback' => 'OutlineDebug'));

$compiled_path = dirname(__FILE__).'/compiled/test.tpl.php';

$engine->compile(
  dirname(__FILE__).'/templates/test.tpl.html',
  $compiled_path,
  true // force recompile
);

require $engine->load($compiled_path);
