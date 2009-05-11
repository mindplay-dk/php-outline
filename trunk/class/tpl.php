<?php

/*

OutlineTpl (Engine Wrapper)
---------------------------

Copyright (C) 2007-2009, Rasmus Schultz <http://www.mindplay.dk>

Please see "README.txt" for license and usage information.
	
*/

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
    
    /*
    Instanciates the [Outline] engine, as needed.
    */
    
    if (!$this->engine)
      $this->engine = new Outline($this->tplname, $this->config);
    
  }
  
	public function assign($var, $value) {
    
    /*
    Exposes the given value as a template variable with the name $var.
    */
    
		$this->vars[$var] = $value;
    
	}
	
	public function assign_by_ref($var, &$value) {
    
    /*
    Exposes the given value-reference, typically an object or array
    reference, as a template variable with the name $var.
    */
    
		$this->vars[$var] = &$value;
    
	}
	
  public function cache() {
    
    /*
    Turns on caching.
    
    This method takes any number of arguments - any value that has
    a meaningful string representation, is valid. The arguments
    determine where in the cache hierachy the output is cached.
    
    For example:
    
      $outline->cache('product', 'list', $pagenum);
    
    This gives you three levels of caching hierachy, which gives
    you the ability to clear all product-related pages, all product
    list pages, or a specific product list page, by clearing from
    any point in the hierachy and below.
    */
    
    $this->caching = true;
    $this->initEngine();
    $args = func_get_args();
    call_user_func_array(
      array($this->engine, 'cache'),
      $args
    );
    
  }
  
  public function cached($time = null) {
    
    /*
    Returns true, if the current cache is valid (not expired).
    
    $time: optional, cache expiration time in seconds - overrides the
           cache expiration setting in the configuration.
    */
    
    $this->initEngine();
    return ( $this->caching && $this->engine->cached($time) );
    
  }
  
  public function clear_cache() {
    
    /*
    Clears the cache.
    
    By default, this method clears the entire cache for this template.
    
    If you wish to clear only a part of the cache hierachy, you can
    use any number of arguments, the same way you use the [cache()] method.
    */
    
    $this->initEngine();
    $args = func_get_args();
    call_user_func_array(
      array($this->engine, 'clear_cache'),
      $args
    );
    
  }
  
	public function display() {
    
    /*
    Renders the template and displays the generated content.
    */
    
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
    
    /*
    Renders the template and returns (but does not display)
    the generated content.
    */
    
		ob_start();
		$this->display();
		$content = ob_get_clean();
		return $content;
    
	}
	
	public function addPlugin($class, $path) {
    
    /*
    Adds a plugin required to render your template.
    
    [OutlineSystem] is an example of an [OutlinePlugin] - if you
    wish to plug in your own commands, you should study those
    two classes.
    */
    
    if (!isset($this->config['plugins']))
      $this->config['plugins'] = array();
		$this->config['plugins'][$class] = $path;
    
	}
	
  // --- Support for helper objects:
  
  private $helpers = array();
  
  function & __get($type) {
    
    /*
    Obtain a late-load helper object of the given type.
    */
    
    $this->initEngine();
    
    if (!isset($this->helpers[$type])) {
      require_once $this->engine->get_helper_path($type);
      $outline__class_name = 'OutlineHelper_'.$type;
      $this->helpers[$type] = new $outline__class_name($this->engine, $type, &$this->vars);
    }
    
    return $this->helpers[$type];
    
  }
  
  // --- IOutlineEngine implementation:
  
	public function & getOutlineEngine() {
    return $this->engine;
  }
	
}

?>