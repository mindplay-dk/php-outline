<?php

class OutlineTpl implements IOutlineEngine {
	
	/*
	
	This is a Smarty-style wrapper-class for the Outline engine.
	
	If you prefer the style of templating where you first assign()
	every template-variable to an instance, and then display() or
	fetch() a template, you can use this class.
	
	The constructor's $config argument is used as configuration
	for the engine - see the [OutlineEngine] class for options.
	
	*/
	
	protected $vars = array();
	protected $engines = array();
	protected $config;
	protected $current_tplname;
	
	public function __construct($config = null) {
		$this->config = $config;
		$this->config['outline_context'] = & $this;
	}
	
	public function __destruct() {
		foreach ($this->engines as $engine) $engine->__destruct();
		foreach ($this as $index => $value) unset($this->$index);
	}
	
	protected function & getEngine($tplname) {
		if (!isset($this->engines[$tplname])) 
			$this->engines[$tplname] = new Outline($tplname, $this->config);
		$this->current_tplname = $tplname;
		return $this->engines[$tplname];
	}
	
	public function assign($var, $value) {
		$this->vars[$var] = $value;
	}
	
	public function assign_by_ref($var, &$value) {
		$this->vars[$var] = &$value;
	}
	
	public function display($tplname) {
		$this->getEngine($tplname);
		extract($this->vars);
		require $this->engines[$tplname]->get();
	}
	
	public function fetch($tplname) {
		ob_start();
		$this->display($tplname);
		$content = ob_get_clean();
		return $content;
	}
	
	public function addPlugin($class, $path) {
		$this->config['plugins'][$class] = $path;
	}
	
	public function & getOutlineEngine() {
		return $this->engines[$this->current_tplname];
	}
	
}

?>