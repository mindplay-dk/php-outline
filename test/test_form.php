<?php

/*

This example demonstrates basic use of the OutlineForm plugin.

NOTES:

In the compiled template, use the context object (in this case OutlineTpl) to
obtain the form helper object - initialize the form helper object from the
compiled template at startup.

Replace the form element tags in the compiled template with calls to the render
methods of the form elements in the helper's elements collection.

Render the form elements using Outline user blocks - this will be a lot more
efficient than using individual templates for each element. Build all the
system standard renderers into a single template file.

Allow switching to a different the template and render function by setting an
attribute on a form element.

*/

define("OUTLINE_ALWAYS_COMPILE", true);

error_reporting(E_ALL);

require_once "../config.dist.php";
require_once OUTLINE_CLASS_PATH."/tpl.php";

header("Content-type: text/html; charset=iso-8859-1");

$tpl = new OutlineTpl('form_test');
$tpl->addPlugin('OutlineFormPlugin', OUTLINE_CLASS_PATH.'/form.php');

$tpl->form->test->email = 'rasmus@mindplay.dk';

$tpl->display();

echo "<pre>";
var_dump($tpl->form->test);
echo "</pre>";

?>