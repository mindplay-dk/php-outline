<?php

/*
This example demonstrates Outline at it's simplest - no caching, and no encapsulation.
*/

require_once "../config.dist.php";

header("Content-type: text/html; charset=iso-8859-1");

function outline_insert_timestamp($args) {
	$outline = Outline::get_context();
	return "<span style=\"color:#{$args['color']};\">" . time() . "</span> (context:".get_class($outline).")";
}

function outline_function_testfunc($args) {
	$outline = Outline::get_context();
	return "today's date is " . date("r") . ' - passed string was: ' . $args['value'] . " (context:".get_class($outline).")";
}

class MyOutline extends Outline {
	public function getTest() {
		return "this message comes from the MyOutline test class";
	}
}

function show_my_template($use_this_title) {
	
	$_outline = new MyOutline('test');
	
  $testvar = 'This variable has local scope';
  $testdate = date("r");
  $testarray = array(
    "RED" => "ff0000",
    "GREEN" => "00a000",
    "BLUE" => "4080ff"
  );
  $empty_array = array();
  
  require $_outline->get();
	
}

show_my_template('Welcome to Outline - the fast and light template engine for php!');

?>