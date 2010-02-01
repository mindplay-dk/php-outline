<?php

/*

This example demonstrates traditional templating with Outline -
using caching, and using the OutlineTpl encapsulation class to
contain all template variables.

Note that the OutlineTpl class is not part of the Outline core,
and has to be included manually (or from your config file).
*/

error_reporting(E_ALL);

require_once "../config.dist.php";
require_once OUTLINE_CLASS_PATH."/tpl.php";

$something = 'This variable has global scope';

header("Content-type: text/html; charset=iso-8859-1");

function outline_insert_timestamp($args) {
	$outline = Outline::get_context();
	return "<span style=\"color:#{$args['color']};\">" . time() . "</span> (context:".get_class($outline).")";
}

function outline_function_format_date($args) {
	$outline = Outline::get_context();
	return "today's date is " . date("r") . ' - passed string was: ' . $args['value'] . " (context:".get_class($outline).")";
}

class OutlineTest extends OutlineTpl {
	public function getTest() {
		return 'this is a test message returned from the OutlineTest class which extends OutlineTpl';
	}
}

$tpl = new OutlineTest('stuff:test', array(
  "cache_time" => 10,
  "roots" => array(
    "stuff" => OUTLINE_SCRIPT_PATH.'/templates'
  )
));

$tpl->assign("testvar", 'This variable has local scope');
$tpl->assign("testdate", date("r"));

$tpl->apply(array("testvar" => 'This will be ignored'), false);

$colors = array(
	"RED" => "ff0000",
	"GREEN" => "00a000",
	"BLUE" => "4080FF"
);	

$tpl->assign_by_ref("testarray", $colors);

$a = substr("abc", rand(0,2), 1);
$b = substr("123", rand(0,2), 1);
$tpl->cache($a, $b); // comment out to disable caching

echo '<p style="color:#f00">Cache version '.$a.', '.$b.' - '.($tpl->cached() ? 'fresh' : 'expired').'</p>';

$tpl->display();

?>