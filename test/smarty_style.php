<?php

/*

This example demonstrates Smarty-style templating with Outline

The OutlineTpl class is not part of the Outline system as such,
it's just a wrapper-class provided for your convenience, and so
it has to be included manually (or from your config file).

*/

error_reporting(E_ALL | E_STRICT);

require_once "../config.dist.php";
require_once OUTLINE_CLASS_PATH."/tpl.php";

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

class OutlineTest extends OutlineTpl {
	public function getTest() {
		return 'this is a test message returned from the OutlineTest class which extends OutlineTpl';
	}
}

$tpl = new OutlineTest();

$tpl->assign("testvar", 'This variable has local scope');
$tpl->assign("testdate", @date("r"));

$colors = array(
	"RED" => "ff0000",
	"GREEN" => "00a000",
	"BLUE" => "4080FF"
);	

$tpl->assign_by_ref("testarray", $colors);

$tpl->display('test');

?>