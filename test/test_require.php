<?php

if (basename(__FILE__) == basename($_SERVER['PHP_SELF']))
  die('This script is included from one of the other test scripts');

define("TEST_VALUE", "This is the constant TEST_VALUE, defined in 'test_require.php'");

class TestClass {
	var $variable = 'This is the contents of TestClass->$variable';
	function method() {
		return 'This is the return value of TestClass->method()';
	}
}

?>