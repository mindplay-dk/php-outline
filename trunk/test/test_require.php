<?php

define("TEST_VALUE", "This is the constant TEST_VALUE, defined in 'test_require.php'");

class TestClass {
	var $variable = 'This is the contents of TestClass->$variable';
	function method() {
		return 'This is the return value of TestClass->method()';
	}
}

$testobject = new TestClass();

?>