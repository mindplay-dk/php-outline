<?php

require_once "../config.dist.php";

$something = 'This variable has global scope';

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
	
	$a = substr("abc", rand(0,2), 1);
	$b = substr("123", rand(0,2), 1);
	
	$_outline->cache($a,$b); // enable caching
	
	$_outline->clear_cache('a'); // clear everything cached for the 'test' template as a/*
	
	if ($_outline->cached(120)) {
		
		echo "<p><b>This is cache instance $a/$b of the page.</b></p>";
		
		require $_outline->get();
		
	} else {
		
		echo "<p><b>This is the uncached version of the page. The page will be cached as instance $a/$b.</b></p>";
		
		$testvar = 'This variable has local scope';
		$testdate = date("r");
		$testarray = array(
			"RED" => "ff0000",
			"GREEN" => "00a000",
			"BLUE" => "4080FF"
		);
		$empty_array = array();
		
		$test_cache = true;
		$cache_msg = "You're looking at cached instance $a/$b of this page";
		
		// start caching, render the template, end caching:
		
		$_outline->capture();
		require $_outline->get();
		$_outline->stop();
		
		require $_outline->get();
		
	}
	
}

show_my_template('Welcome to Outline - the fast and light template engine for php!');

?>