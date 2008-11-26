<?php

class OutlineTpl implements IOutlineEngine {
	
	/*
	This class simplifies Outline, implementing the more
	traditional style of templating, where you create an
	object, assign named variables, and then render and
	print (or render and return) the template output.
	
	The constructor's $config argument is used as configuration
	for the engine - see the [OutlineEngine] class for options.	
	*/
  
	protected $vars = array();
	protected $engine;
	protected $config;
	protected $tplname;
  protected $caching = false;
	
	public function __construct($tplname, $config = null) {
    $this->tplname = $tplname;
		$this->config = is_array($config) ? $config : array();
		$this->config['outline_context'] = & $this;
	}
	
	public function __destruct() {
		if ($this->engine) $this->engine->__destruct();
		foreach ($this as $index => $value) unset($this->$index);
	}
	
  protected function initEngine() {
    if (!$this->engine)
      $this->engine = new Outline($this->tplname, $this->config);
  }
  
	public function assign($var, $value) {
		$this->vars[$var] = $value;
	}
	
	public function assign_by_ref($var, &$value) {
		$this->vars[$var] = &$value;
	}
	
  public function cache() {
    $this->caching = true;
    $this->initEngine();
    $args = func_get_args();
    call_user_func_array(
      array($this->engine, 'cache'),
      $args
    );
  }
  
  public function cached() {
    $this->initEngine();
    return ( $this->caching && $this->engine->cached() );
  }
  
	public function display() {
    $this->initEngine();
    extract($this->vars);
    if (!$this->caching || $this->engine->cached()) {
      require $this->engine->get();
    } else {
  		$this->engine->capture();
  		require $this->engine->get();
  		$this->engine->stop();
  		require $this->engine->get();
    }
	}
	
	public function fetch() {
		ob_start();
		$this->display();
		$content = ob_get_clean();
		return $content;
	}
	
	public function addPlugin($class, $path) {
    if (!isset($this->config['plugins']))
      $this->config['plugins'] = array();
		$this->config['plugins'][$class] = $path;
	}
	
	public function & getOutlineEngine() {
		return $this->engine;
	}
	
}

?>