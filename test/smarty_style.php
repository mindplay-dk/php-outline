<?php

error_reporting(E_ALL | E_STRICT);

require_once "../config.dist.php";

$something = 'This variable has global scope';

header("Content-type: text/html; charset=iso-8859-1");

/*

This example demonstrates a simple class for Smarty-style
templating with Outline - this example is for evaluation
and discussion, alhtough it may eventually migrate into
the codebase...

*/

class OutlineTpl {
	
	protected $vars = array();
	protected $outline;
	
	public function __destruct() {
		if ($this->outline) $this->outline->__destruct();
		foreach ($this as $index => $value) unset($this->$index);
	}
	
	public function assign($var, $value) {
		$this->vars[$var] = $value;
	}
	
	public function assign_by_ref($var, &$value) {
		$this->vars[$var] = &$value;
	}
	
	public function display($tplname) {
		$this->outline = new Outline($tplname);
		extract($this->vars);
		require $this->outline->get();
	}
	
	public function fetch($tplname) {
		ob_start();
		$this->display($tplname);
		$content = ob_get_clean();
		return $content;
	}
	
}

$tpl = new OutlineTpl();

$tpl->assign("testvar", 'This variable has local scope');
$tpl->assign("testdate", date("r"));

$colors = array(
	"RED" => "ff0000",
	"GREEN" => "00a000",
	"BLUE" => "4080FF"
);	

$tpl->assign_by_ref("testarray", $colors);

$tpl->display('test');

?>