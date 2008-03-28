<?php

/*

Outline (Engine)
----------------

Copyright (C) 2007-2008, Rasmus Schultz <http://www.mindplay.dk>

Please see "README.txt" for license and usage information.
	
*/

class OutlineException extends Exception {
	public function __construct($message) {
		parent::__construct($message, -1);
	}
}

class OutlineEngine {
	
	protected $template;
	protected $compiled;
	
	public $config = array(
		"template_path" =>       OUTLINE_TEMPLATE_PATH,   /* Path to folder containing templates */
		"compiled_path" =>       OUTLINE_COMPILED_PATH,   /* older containing compiled templates (must be writable) */
		"template_suffix" =>     '.html',                 /* Suffix (extension) of template files */
		"compiled_suffix" =>     '.php',                  /* Suffix (extension) of compiled template files (usually ".php") */
		"cache_path" =>          OUTLINE_CACHE_PATH,      /* The folder in which the Cache class stores it's content */
		"cache_suffix" =>        '.html',                 /* File extension or suffix for cache files */
		"cache_time" =>          OUTLINE_CACHE_TIME,      /* Default cache time (in seconds) */
		"quiet" =>               true,                    /* Suppresses E_NOTICE and E_WARNING error messages */
		"bracket_open" =>        '{',
		"bracket_close" =>       '}',
		"bracket_comment" =>     '{*',
		"bracket_end_comment" => '*}',
		"bracket_ignore" =>      '{ignore}',
		"bracket_end_ignore" =>  '{/ignore}',
		"plugins" =>             array()
	);
	
	public function __construct() {
		$this->config['plugins']['OutlineSystem'] = OUTLINE_CLASS_PATH . "/system.php";
	}
	
	public function __destruct() {
		foreach ($this as $index => $value) unset($this->$index);
	}
	
	public function addPlugin($class, $path) {
		$this->config['plugins'][$class] = $path;
	}
	
	public function build($template, $compiled, $force = false) {
		
		// Builds $template and writes the resulting compiled script to $compiled.
		// Returns true if the template was built, false if the compiled template was already up-to-date.
		
		$this->template = $template;
		$this->compiled = $compiled;
		
		if (!file_exists($this->template)) throw new OutlineException("OutlineEngine::build(): template not found: " . $this->template);
		
		if ((filemtime($this->template) > @filemtime($this->compiled)) || !file_exists($this->compiled) || $force) {
			
			if (!@constant("OUTLINE_COMPILER")) {
				if (@constant("OUTLINE_DEBUG")) OutlineDebug("loading OutlineCompiler");
				require OUTLINE_CLASS_PATH . "/compiler.php";
			}
			
			if (@constant("OUTLINE_DEBUG")) OutlineDebug("compiling template '$template' to '$compiled'");
			
			try {
				$compiler = new OutlineCompiler($this);
				@mkdir(dirname($compiled), 0777, true);
				file_put_contents($compiled, $compiler->compile(file_get_contents($template)));
				$compiler->__destruct(); unset($compiler);
			} catch (OutlineCompilerException $e) {
				throw new OutlineException("error compiling template '$template', line " . $e->getLineNum() . " - " . $e->getMessage());
			}
			
			return true;
			
		}
		
		return false;
		
	}
	
}

class Outline extends OutlineEngine {
	
	protected $tplname;
	
	protected $caching = true;
	protected $cache;
	
	protected static $engine_stack = array();
	
	protected static $error_level;
	
	public function __construct($tplname, $config = null) {
		
		parent::__construct();
		
		$this->tplname = $tplname;
		
		$this->caching = !$this->build(
			$this->config['template_path'] . '/' . $tplname . $this->config['template_suffix'],
			$this->config['compiled_path'] . '/' . $tplname . $this->config['compiled_suffix'],
			@constant("OUTLINE_ALWAYS_COMPILE")
		);
		
		if (is_array($config)) {
			foreach ($config as $name => $value) {
				if (!array_key_exists($name, $this->config)) throw new OutlineException("Outline::__construct() : invalid configuration option '$name'");
				$this->config[$name] = $value;
			}
		} else if ($config === null && count(self::$engine_stack)) {
			if (@constant("OUTLINE_DEBUG")) OutlineDebug("inheriting configuration of parent engine");
			$this->config = & self::$engine_stack[0];
		}
		
	}
	
	public function cache() {
		
		if (!@constant("OUTLINE_CACHE_ENGINE"))
			require OUTLINE_CLASS_PATH . "/cache.php";
		
		$path = explode('/',$this->tplname);
		if ($add_path = func_get_args()) $path = array_merge($path, $add_path);
		
		$this->cache = new OutlineCache($this->config, $path);
		
	}
	
	public function cached($time) {
		if (!$this->caching || empty($this->cache)) return false;
		return $this->cache->valid($time);
	}
	
	public function capture() {
		if (empty($this->cache)) return false;
		$this->cache->capture();
	}
	
	public function stop() {
		if (empty($this->cache)) return false;
		$this->cache->stop();
	}
	
	public function get() {
		self::$engine_stack[] = & $this;
		if ((count(self::$engine_stack) == 1) && $this->config['quiet']) self::$error_level = error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING | E_STRICT ^ E_STRICT);
		if ($this->caching && !empty($this->cache) && $this->cache->valid()) {
			return $this->cache->get();
		} else {
			return $this->compiled;
		}
	}
	
	public static function finish() {
		if ((count(self::$engine_stack) == 1) && self::$engine_stack[0]->config['quiet']) error_reporting(self::$error_level);
		array_pop(self::$engine_stack);
	}
	
}

class OutlineUtil {
	
	public static function clean($fname) {
		$pattern = "/([[:alnum:]_\.-]*)/";
		$replace = "_";
		return str_replace(str_split(preg_replace($pattern,$replace,$fname)),$replace,$fname);
	}
	
	public static function delete($path, $suffix) {
		
		$wiped = true;
		
		foreach (glob($path . '/*' . $suffix) as $file)
			if (!unlink($file)) trigger_error("OutlineUtil::delete() : unable to remove file '$file'", E_USER_WARNING);
		
		foreach (glob($path . '/*', GLOB_ONLYDIR) as $dir) {
			$wiped = $wiped && self::_delete($dir, $suffix);
			if (!rmdir($dir)) trigger_error("OutlineUtil::delete() : unable to remove dir '$dir' - not empty?", E_USER_WARNING);
		}
		
		return $wiped;
		
	}
	
}

?>