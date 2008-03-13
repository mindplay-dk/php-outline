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
				require OUTLINE_CLASS_PATH . "/system.php";
			}
			
			if (@constant("OUTLINE_DEBUG")) OutlineDebug("compiling template '$template' to '$compiled'");
			
			try {
				$compiler = new OutlineCompiler();
				file_put_contents($compiled, $compiler->compile(file_get_contents($template)));
				$compiler->__destruct(); unset($compiler);
			} catch (OutlineCompilerException $e) {
				throw new OutlineException("error compiling template '$template', line " . $e->getLineNum() . " - " . $e->getMessage());
			}
			
			unset($comp);
			
			return true;
			
		}
		
		return false;
		
	}
	
}

class Outline extends OutlineEngine {
	
	protected $tplname;
	
	protected $caching = true;
	protected $cache;
	
	public function __construct($tplname) {
		
		$this->tplname = $tplname;
		
		$this->caching = !$this->build(
			OUTLINE_TEMPLATE_PATH . '/' . $tplname . OUTLINE_TEMPLATE_SUFFIX,
			OUTLINE_COMPILED_PATH . '/' . $tplname . OUTLINE_COMPILED_SUFFIX,
			@constant("OUTLINE_ALWAYS_COMPILE")
		);
		
	}
	
	public function cache() {
		
		if (!@constant("OUTLINE_CACHE_ENGINE"))
			require OUTLINE_CLASS_PATH . "/cache.php";
		
		$path = func_get_args();
		if (count($path) == 0) $path[] = $this->tplname;
		
		$this->cache = new OutlineCache($path);
		
	}
	
	public function cached($time) {
		if (!$this->caching || empty($this->cache)) return false;
		return $this->cache->valid($time);
	}
	
	public function capture() {
		if (empty($this->cache)) return false;
		if (@constant("OUTLINE_DEBUG")) OutlineDebug("Starting cache capture");
		$this->cache->capture();
	}
	
	public function stop() {
		if (empty($this->cache)) return false;
		$this->cache->stop();
		if (@constant("OUTLINE_DEBUG")) OutlineDebug("Cache capture finished");
	}
	
	public function get() {
		if ($this->caching && isset($this->cache) && $this->cache->valid()) {
			return $this->cache->get();
		} else {
			return $this->compiled;
		}
	}
		
}

?>