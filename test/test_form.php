<?php

/*

This example demonstrates basic use of the OutlineForm plugin.

*/

define("OUTLINE_ALWAYS_COMPILE", true);

error_reporting(E_ALL);

require_once "../config.dist.php";
require_once OUTLINE_CLASS_PATH."/tpl.php";

header("Content-type: text/html; charset=iso-8859-1");

$tpl = new OutlineTpl('form_test');
$tpl->addPlugin('OutlineFormPlugin', OUTLINE_CLASS_PATH.'/form.php');

#$tpl->form->test->email = 'rasmus@mindplay.dk';

#$tpl->assign('email', 'rasmus@mindplay.dk'); # this will change - form models will be accessed via helpers

$tpl->display();

echo "<pre>";
var_dump($tpl->form->test);
echo "</pre>";

?>