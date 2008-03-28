<?php

class OutlineTpl {
	
	/*
	
	This is a Smarty-style wrapper-class for the Outline engine.
	
	If you prefer the style of templating where you first assign()
	every template-variable to an instance, and then display() or
	fetch() a template, you can use this class.
	
	The constructor's $config argument is used as configuration
	for the engine - see the [OutlineEngine] class for options.
	
	*/
	
	protected $vars = array();
	protected $outline;
	protected $config;
	
	public function __construct($config = null) {
		$this->config = $config;
	}
	
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
		$this->outline = new Outline($tplname, $this->config);
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

?>