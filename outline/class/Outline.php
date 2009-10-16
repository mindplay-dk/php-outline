<?php

/*

Outline (Engine)
----------------

Copyright (C) 2007-2009, Rasmus Schultz <http://www.mindplay.dk>

Please see "README.txt" for license and usage information.
	
*/

class OutlineException extends Exception {
  
  /*
  General exception thrown by Outline classes.
  */
  
	public function __construct($message) {
		parent::__construct($message, -1);
	}
  
}

class Outline {
  
  /*
  This is the core engine class, which provides functions to compile
  and render Outline templates.
  */
  
	protected $config = array(
    "trace_callback" =>      null,             /* Callback function for engine trace messages (optional) */
		"quiet" =>               true,             /* Suppresses E_NOTICE and E_WARNING error messages */
    "file_mode" =>           '0777',
    "dir_mode" =>            '0777',
		"plugins" =>             array('system'),
		"bracket_open" =>        '{',
		"bracket_close" =>       '}',
		"bracket_comment" =>     '{*',
		"bracket_end_comment" => '*}',
		"bracket_ignore" =>      '{ignore}',
		"bracket_end_ignore" =>  '{/ignore}'
	);
  
  protected static $error_level;
  
  public function __construct($config = null) {
    
    /*
    $config: optional array of engine configuration settings (see $config above)
    */
    
    if (is_array($config)) foreach ($config as $name => $value) {
      if (!array_key_exists($name, $this->config)) {
        throw new OutlineException("Outline::__construct() : invalid configuration option '$name'");
      }
      if (is_array($this->config[$name])) {
        $this->config[$name] += $value;
      } else {
        $this->config[$name] = $value;
      }
    }
    
  }
  
	public function __destruct() {
		foreach ($this as $index => $value) unset($this->$index);
	}
  
  public function getConfig() {
    return $this->config;
  }
  
  protected function trace($msg) {
    if (!$this->config['trace_callback']) return;
    call_user_func($this->config['trace_callback'], $msg);
  }
  
  protected $functions = array();
  
	public function compile($template_path, $compiled_path, $force = false) {
		
		/*
    Compiles a template, if the compiled template at the given destination path
    if older then the template file at the given source path.
    
		Returns true if the template was built, false if it was already up-to-date.
    */
		
		if (!file_exists($template_path)) {
      throw new OutlineException("OutlineEngine::compile(): template file not found: {$template_path}");
    }
		
		if ($force || !file_exists($compiled_path) || (filemtime($template_path) > @filemtime($compiled_path))) {
			
			if (!@constant("OUTLINE_COMPILER")) {
				$this->trace("loading compiler");
				require OUTLINE_CLASS_PATH . "/OutlineCompiler.php";
			}
			
			$this->trace("compiling template '$template_path' to '$compiled_path'");
			
			try {
        $compiler = new OutlineCompiler($this);
				@mkdir(dirname($compiled_path), $this->config['dir_mode'], true);
				OutlineUtil::write_file($compiled_path, $compiler->compile(file_get_contents($template_path)), $this->config['file_mode']);
			} catch (OutlineCompilerException $e) {
				throw new OutlineException("Outline::compile() : error compiling template '$template_path', line " . $e->getLineNum() . " - " . $e->getMessage());
			}
			
			return true;
			
		}
		
		return false;
		
	}
  
}
