<?php

/*

Outline (Engine)
----------------

Copyright (C) 2007, Rasmus Schultz <http://www.mindplay.dk>

Please see "README.txt" for license and usage information.
	
*/

require_once OUTLINE_CLASS_PATH . "/modifiers.php";

class Outline {
	
	var $tplname;
	var $template;
	var $compiled;
	
	var $caching = true;
	var $cache;
	
	function Outline($tplname) {
		
		$this->tplname = $tplname;
		
		$this->template = OUTLINE_TEMPLATE_PATH . '/' . $tplname . OUTLINE_TEMPLATE_SUFFIX;
		$this->compiled = OUTLINE_COMPILED_PATH . '/' . $tplname . OUTLINE_COMPILED_SUFFIX;
		
		if (!file_exists($this->template)) trigger_error("Outline::Outline(): Template not found: " . $this->template, E_USER_ERROR);
		
		if ((filemtime($this->template) > @filemtime($this->compiled)) || !file_exists($this->compiled) || @constant("OUTLINE_ALWAYS_COMPILE")) {
			if (defined("OUTLINE_DEBUG")) OutlineDebug("Loading OutlineCompiler");
			require_once OUTLINE_CLASS_PATH . "/compiler.php";
			$comp = new OutlineCompiler();
			$comp->build($tplname);
			unset($comp);
			$this->caching = false;
		}
		
	}
	
	function cache() {
		
		if (!defined("OUTLINE_CACHE_ENGINE"))
			require_once OUTLINE_CLASS_PATH . "/cache.php";
		
		$path = func_get_args();
		if (count($path) == 0) $path[] = $this->tplname;
		
		$this->cache = new OutlineCache($path);
		
	}
	
	function cached($time) {
		if (!$this->caching || empty($this->cache)) return false;
		return $this->cache->valid($time);
	}
	
	function capture() {
		if (empty($this->cache)) return false;
		if (defined("OUTLINE_DEBUG")) OutlineDebug("Starting cache capture");
		$this->cache->capture();
	}
	
	function stop() {
		if (empty($this->cache)) return false;
		$this->cache->stop();
		if (defined("OUTLINE_DEBUG")) OutlineDebug("Cache capture finished");
	}
	
	function get() {
		if ($this->caching && isset($this->cache) && $this->cache->valid()) {
			return $this->cache->get();
		} else {
			return $this->compiled;
		}
	}
	
}

?>