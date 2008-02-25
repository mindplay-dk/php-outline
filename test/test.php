<?php

require_once "../config.dist.php";

$something = 'This variable has global scope';

function show_my_template($use_this_title) {
	
	$_outline = new Outline('test');
	$_outline->cache(); // enable caching
	
	if ($_outline->cached(10)) {
		
		echo "<b>CACHE HIT</b>";
		
		require $_outline->get();
		
	} else {
		
		echo "<b>CACHE MISS</b>";
		
		$testvar = 'This variable has local scope';
		$testdate = date("r");
		$testarray = array(
			"RED" => "ff0000",
			"GREEN" => "00a000",
			"BLUE" => "4080FF"
		);
		
		// start caching, render the template, end caching:
		
		$_outline->capture();
		require $_outline->get();
		$_outline->stop();
		
	}
	
}

show_my_template('Welcome to Outline - the fast and light template engine for php!');

?>