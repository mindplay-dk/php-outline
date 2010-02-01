<?php

/*

Outline (Engine)
----------------

Copyright (C) 2007-2009, Rasmus Schultz <http://www.mindplay.dk>

Please see "README.txt" for license and usage information.
	
*/

class OutlineException extends Exception {
  
  /*
  General exception thrown by [OutlineEngine], [OutlineCache] and
  various methods in an [OutlinePlugin] (e.g. [OutlineSystem])
  */
  
	public function __construct($message) {
		parent::__construct($message, -1);
	}
  
}

interface IOutlineEngine {
  
  /*
  [OutlineEngine] and [OutlineTpl] implement this interface, which
  enables various commands to determine the engine context.
  */
  
	public function & getOutlineEngine();
  
}

class OutlineEngine implements IOutlineEngine {
	
  /*
  This class acts as a lightweight proxy to [OutlineCompiler], which
  will be loaded by this class as necessary. Custom template engines
  can be built on this base class, which is the base class for [Outline].
  */
  
	protected $template;
	protected $compiled;
	
	public $config = array(
		"template_path" =>       OUTLINE_TEMPLATE_PATH,   /* Path to folder containing templates */
		"compiled_path" =>       OUTLINE_COMPILED_PATH,   /* older containing compiled templates (must be writable) */
		"template_suffix" =>     '.tpl.html',             /* Suffix (extension) of template files */
		"compiled_suffix" =>     '.tpl.php',              /* Suffix (extension) of compiled template files (usually ".php") */
		"cache_path" =>          OUTLINE_CACHE_PATH,      /* The folder in which the Cache class stores it's content */
		"cache_suffix" =>        '.html',                 /* File extension or suffix for cache files */
		"cache_time" =>          OUTLINE_CACHE_TIME,      /* Default cache time (in seconds) */
		"quiet" =>               true,                    /* Suppresses E_NOTICE and E_WARNING error messages */
    "default_root" =>        null,                    /* Default rootname for template-names with no rootname */
		"bracket_open" =>        '{',                     /* Opening bracket for Outline commands */
		"bracket_close" =>       '}',                     /* Closing bracket for Outline commands */
		"bracket_comment" =>     null,                    /* Additional brackets are automatically configured, but can be customized as needed */
		"bracket_end_comment" => null,                    /* - */
		"bracket_ignore" =>      null,                    /* - */
		"bracket_end_ignore" =>  null,                    /* - */
		"outline_context" =>     null,
    "roots" =>               array(),
		"plugins" =>             array()
	);
	
	public function __construct() {
    if ($this->config['outline_context'] === null) {
      $this->config['outline_context'] = & $this;
    }
	}
	
	public function __destruct() {
		foreach ($this as $index => $value) unset($this->$index);
	}
	
	public function build($template, $compiled, $force = false) {
		
		/*
    Builds $template and writes the resulting compiled script to $compiled.
    
		Returns true if the template was built, false if the compiled
    template was already up-to-date.
    */
		
		$this->template = $template;
		$this->compiled = $compiled;
		
		if (!file_exists($this->template)) throw new OutlineException("OutlineEngine::build(): template not found: " . $this->template);
		
		if ($force || !file_exists($this->compiled) || (filemtime($this->template) > @filemtime($this->compiled))) {
			
			if (!@constant("OUTLINE_COMPILER")) {
				if (@constant("OUTLINE_DEBUG")) OutlineDebug("loading OutlineCompiler");
				require OUTLINE_CLASS_PATH . "/compiler.php";
			}
			
			if (@constant("OUTLINE_DEBUG")) OutlineDebug("compiling template '$template' to '$compiled'");
			
			try {
				$compiler = new OutlineCompiler($this);
				@mkdir(dirname($compiled), OUTLINE_DIR_MODE, true);
				OutlineUtil::write_file($compiled, $compiler->compile(file_get_contents($template)));
				$compiler->__destruct(); unset($compiler);
			} catch (OutlineCompilerException $e) {
				throw new OutlineException("error compiling template '$template', line " . $e->getLineNum() . " - " . $e->getMessage());
			}
			
			return true;
			
		}
		
		return false;
		
	}
	
  // --- IOutlineEngine implementation:
  
	public function & getOutlineEngine() {
		return $this;
	}
	
}

class Outline extends OutlineEngine {
	
  /*
  This is the core template engine, which integrates with [OutlineCache],
  adds support for multiple roots, and provides support-functions for the
  standard commands in [OutlineSystem].
  */
  
	protected $tplname;
  
  protected $caching = true;
	protected $cache;
	
	protected static $engine_stack = array();
	
	protected static $error_level;
  
	public function __construct($tplname, $config = null) {
		
    /*
    $tplname: name of template to load - will be compiled as needed.
    $config: optional configuration settings - see [OutlineEngine] for valid configuration options.
    */
    
		parent::__construct();
		
		$this->config['plugins']['OutlineSystem'] = OUTLINE_CLASS_PATH . "/system.php";
		
		if (is_array($config)) {
			foreach ($config as $name => $value) {
				if (!array_key_exists($name, $this->config)) throw new OutlineException("Outline::__construct() : invalid configuration option '$name'");
				if (is_array($this->config[$name])) {
					$this->config[$name] += $value;
				} else {
					$this->config[$name] = $value;
				}
			}
		} else if ($config === null && count(self::$engine_stack)) {
			if (@constant("OUTLINE_DEBUG")) OutlineDebug("inheriting configuration of parent engine");
			$this->config = & self::$engine_stack[0]->config;
		}
		
    if ($this->config['bracket_comment']==null)
      $this->config['bracket_comment'] = $this->config['bracket_open'].'*';
    if ($this->config['bracket_end_comment']==null)
      $this->config['bracket_end_comment'] = '*'.$this->config['bracket_close'];
    if ($this->config['bracket_ignore']==null)
      $this->config['bracket_ignore'] = $this->config['bracket_open'].'ignore'.$this->config['bracket_close'];
    if ($this->config['bracket_end_ignore']==null)
      $this->config['bracket_end_ignore'] = $this->config['bracket_open'].'/ignore'.$this->config['bracket_close'];
    
    $this->tplname = $tplname;
    
		$this->caching = !$this->build(
			$this->getAbsTplPath(),
			$this->config["compiled_path"] . '/' . $this->getRelTplPath($tplname) . $this->config["compiled_suffix"],
			@constant("OUTLINE_ALWAYS_COMPILE")
		);
		
		if (!isset($this->config['functions'])) $this->config['functions'] = array();
    
    if (!is_array($config) || !array_key_exists('default_root', $config)) {
      $bits = explode(':', $tplname, 2);
      if (count($bits) == 2) $this->config['default_root'] = $bits[0];
    }
		
	}
	
	public function get() {
    
    /*
    Returns the path to the compiled (and/or cached) template.
    
    With no caching, usage is simple:
    
      require $outline->get();
    
    With caching enabled, two passes are required when the template
    output is first cached - this is because insert-commands can not
    be executed in the first pass, since they can not be cached.
    
    So usage is slightly more complicated:
    
      if ($outline->cached()) {
        // already cached - only one pass required
        require $outline->get();
      } else {
        // first pass captures to cache and generates code for insert commands:
    		$outline->capture();
    		require $outline->get();
    		$outline->stop();
        // second pass generates the actual template output:
    		require $outline->get();
      }
    
    */
    
		self::$engine_stack[] = & $this;
		if ((count(self::$engine_stack) == 1) && $this->config['quiet']) self::$error_level = error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING | E_STRICT ^ E_STRICT);
		if ($this->caching && !empty($this->cache) && $this->cache->valid()) {
			return $this->cache->get();
		} else {
			return $this->compiled;
		}
    
	}
	
  public function get_metadata_path($name, $type = 'system') {
    
    /*
    Returns the path to a metadata array, for a given helper type,
    associated with this template.
    */
    
    return $this->config['compiled_path'] . '/' . $this->getRelTplPath() . ".{$name}.{$type}.php";
    
  }
  
  public function get_helper_path($type = 'system') {
    
    /*
    Returns the path to a helper class for the given helper type.
    */
    
    return OUTLINE_CLASS_PATH . "/{$type}.helper.php";
    
  }
  
  // --- Caching support functions:
  
	public function cache() {
		
    /*
    Turns on caching - loads [OutlineCache] as needed.
    
    This method takes any number of arguments - any value that has
    a meaningful string representation, is valid. The arguments
    determine where in the cache hierachy the output is cached.
    
    For example:
    
      $outline->cache('product', 'list', $pagenum);
    
    This gives you three levels of caching hierachy - this places
    all your product-related content under the first-order cache
    named 'product', and all your product lists under the
    second-order cache named 'list'.
    */
    
		if (!@constant("OUTLINE_CACHE_ENGINE"))
			require OUTLINE_CLASS_PATH . "/cache.php";
		
		$path = explode('/', $this->getRelTplPath());
		if ($add_path = func_get_args()) $path = array_merge($path, $add_path);
		
		$this->cache = new OutlineCache($this->config, $path);
		if (!$this->caching) $this->clear_cache();
		
	}
	
  	public function cached($time = null) {
    
    /*
    Returns true if the cache for this template has expired.
    
    $time: optional - cache lifetime in seconds, defaults
           to 'cache_time' in the configuration.
    */
    
		if (!$this->caching || empty($this->cache)) return false;
		return $this->cache->valid($time === null ? $this->config['cache_time'] : $time);
    
	}
	
	public function clear_cache() {
    
    /*
    Manually clear the cache for this template.
    
    Generally, the cache is automatically cleared as needed - with long
    cache lifetimes, sometimes it is preferable to manually clear when
    the rendered data has been updated.
    */
    
		if (!$this->cache) throw new OutlineException("can't clear cache, caching is not enabled - you must call cache() first");
		
    $path = array($this->getRelTplPath());
		if ($add_path = func_get_args()) $path = array_merge($path, $add_path);
    
		$this->cache->clear($path);
    
	}
	
	protected $capturing = false;
	
	public function capture() {
    
    /*
    Begins capture to the cache. You must first enable caching, by
    calling the [cache()] method.
    */
    
		if (empty($this->cache)) return false;
		$this->cache->capture();
		$this->capturing = true;
    
	}
	
	public function stop() {
    
    /*
    Completes capture to the cache. You must first begin capture, by
    calling the [capture()] method.
    */
    
		if (empty($this->cache)) return false;
		$this->cache->stop();
		$this->capturing = false;
    
	}
	
  // --- Template name/path handling:
  
	public function getTplName() {
    
    /*
    Returns the template name supplied at construction.
    */
    
		return $this->tplname;
    
	}
  
  public function getRelTplPath() {
    
    /*
    Maps the template name to a relative path, not including
    the file extension, which should be appended as needed.
    */
    
    $bits = explode(':', $this->tplname, 2);
    
    if (count($bits) == 2) return $bits[0] . '/' . $bits[1];
    
    return ( $this->config['default_root'] == null ? '__default__' : $this->config['default_root'] ) . '/' . $bits[0];
    
  }
  
  public function getAbsTplPath() {
    
    /*
    Maps the template name to an absolute template path,
    including the file extension.
    */
    
    $bits = explode(':', $this->tplname, 2);
    
    if (count($bits) == 2) {
      list($name, $path) = $bits;
    } else {
      $name = @$this->config["default_root"];
      $path = $bits[0];
    }
    
    $root = ( $name == null ? $this->config["template_path"] : @$this->config['roots'][$name] );
    if (!$root) throw new OutlineException("Outline::getAbsTplPath() : unknown root name '$name");
    
    return $root . '/' . $path . $this->config["template_suffix"];
    
  }
  
  // --- Support functions for compiled templates:
  
	public static function finish() {
    
    /*
    Compiled templates call this method when they exit.
    */
    
		if ((count(self::$engine_stack) == 1) && self::$engine_stack[0]->config['quiet']) error_reporting(self::$error_level);
		array_pop(self::$engine_stack);
    
	}
	
	public static function & get_context() {
    
    /*
    Returns the context object of the currently executing template engine.
    
    In compiled templates, the reserved variable $outline contains a reference
    to the this object.
    */
    
		return self::$engine_stack[count(self::$engine_stack)-1]->config['outline_context'];
    
	}
	
	public static function defer($function, $args) {
    
    /*
    The insert-command generates calls to this method, which dispatches an
    insert function, or generates required php code, as needed.
    */
    
		if (!function_exists($function)) throw new OutlineException("Outline::defer() : function '{$function}' does not exist");
		$engine = self::$engine_stack[count(self::$engine_stack)-1];
		if ($engine->capturing) {
			return "<?php echo {$function}(" . str_replace("\n", "", var_export($args,true)) . "); ?>";
		} else {
			return call_user_func($function, $args);
		}
    
	}
	
	public static function register_function($function, $name) {
    
    /*
    User blocks generate calls to this method, to register user functions.
    */
    
		$context = self::get_context();
		$engine = $context->getOutlineEngine();
		$engine->config['functions'][$name] = $function;
    
	}
	
	public static function dispatch($name, $args) {
    
    /*
    Compiled templates call this method to dispatch user block functions.
    */
    
		$context = self::get_context();
		$engine = $context->getOutlineEngine();
		if (!isset($engine->config['functions'][$name])) throw new OutlineException("Outline::dispatch() : user function '$name' does not exist");
		call_user_func($engine->config['functions'][$name], $args);
    
	}
	
}

class OutlineIterator {
	
  /*
  Compiled templates, that use the for-command, use this helper class.
  */
  
	public $index, $start, $end, $step;
	
	public function __construct($start, $end, $step) {
		$this->start = $start;
		$this->end = $end;
		$this->step = ($end<$start && $step>0 ? -$step : $step);
		$this->index = $start - $this->step;
	}
	
	public function next() {
		$more = ($this->step>0 ? $this->index<$this->end : $this->index>$this->end);
		$this->index += $this->step;
		return $more;
	}
	
}
