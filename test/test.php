<?php

require_once "../config.dist.php";

$something = 'This variable has global scope';

header("Content-type: text/html; charset=iso-8859-1");

function show_my_template($use_this_title) {
	
	$_outline = new Outline('test');
	$_outline->cache(); // enable caching
	
	if ($_outline->cached(10)) {
		
		echo "<p><b>This is the cached version of the page. This is cached only as an example - the cached version of this page will expire after ten seconds, for demonstration purposes. If you reload the page after ten seconds, you should see a different message here...</b></p>";
		
		require $_outline->get();
		
	} else {
		
		echo "<p><b>This is the uncached version of the page. The page will have to actually do some work now, to populate the template before rendering it. The output will then be cached. The messages in red are debugging messages from Outline - these will tell you if the compiler was loaded, what it compiled, etc.</b></p>";
		
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