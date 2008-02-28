<?php

/*

OutlineCache
----------

Copyright (C) 2008, Rasmus Schultz <http://www.mindplay.dk>

Please see "README.txt" for license and usage information.

*/

define("OUTLINE_CACHE_ENGINE", true);

class OutlineCache {
	
	var $path;
	var $time;
	var $valid = false;
	var $checked = false;
	var $buffering = false;
	
	function OutlineCache($path) {
		
		if (count($path) == 0) trigger_error("Cache: constructor requires one or more arguments", E_USER_ERROR);
		
		$this->path = array(OUTLINE_CACHE_PATH);
		
		$last = count($path);
		for ($n=0; $n<$last; $n++) {
			$this->path[] = '/' . OutlineCache::_clean($path[$n]) . ($n == $last-1 ? OUTLINE_CACHE_SUFFIX : '');
		}
		
		$this->time = time();
		
	}
	
	function valid($time_sec = OUTLINE_CACHE_TIME) {
		
		if ($this->checked) return $this->valid;
		
		$time = @filemtime(implode($this->path));
		
		$this->valid = $time ? ($this->time - $time < $time_sec) : false;
		
		$this->checked = true;
		
		return $this->valid;
		
	}
	
	function get() {
		
		if ($this->valid) return implode($this->path);
		
		trigger_error("Cache: valid() must be called before get(), and only if valid() returns true");
		
	}
	
	function capture() {
		
		if ($this->buffering) trigger_error("Cache: capture() may only be called once, or after stop() has been called");
		
		$path = '';
		
		$last = count($this->path);
		
		for ($n=0; $n<$last-1; $n++) {
			$path .= $this->path[$n];
			if (!is_dir($path)) {
				if (!mkdir($path)) trigger_error("Cache: unable to create folder ".$path, E_USER_ERROR);
			}
		}
		
		$this->buffering = true;
		
		ob_start();
		
	}
	
	function stop() {
		
		if (!$this->buffering) trigger_error("Cache: capture() must be called before stop() can be called");
		
		if (!@file_put_contents(implode($this->path), ob_get_contents()))
			trigger_error("Cache: unable to write new cache entry ".implode($this->path), E_USER_ERROR);
		
		ob_end_flush();
		
	}
	
	// --- Static function for flushing cache:
	
	function expire() {
		
		$path = OUTLINE_CACHE_PATH;
		
		foreach (func_get_args() as $dir)
			$path .= '/' . OutlineCache::_clean($dir);
		
		if (is_dir($path)) {
			OutlineCache::_delete($path);
		}
		
		if (is_file($path.OUTLINE_CACHE_SUFFIX)) {
			unlink($path.OUTLINE_CACHE_SUFFIX);
		}
		
	}
	
	// --- Private helper functions:
	
	function _clean($dir) {
		return $dir; /* TO-DO: replace invalid characters */
	}
	
	function _delete($path) {
		
		$wiped = true;
		
		foreach (glob($path . '/*' . OUTLINE_CACHE_SUFFIX) as $file) {
			if (!unlink($file)) trigger_error("Cache: unable to remove file '$file'", E_USER_NOTICE);
		}
		
		foreach (glob($path . '/*', GLOB_ONLYDIR) as $dir) {
			$wiped = $wiped && OutlineCache::_delete($dir);
			if (!rmdir($dir)) trigger_error("Cache: unable to remove dir '$dir'", E_USER_NOTICE);
		}
		
		return $wiped;
		
	}
	
}

?>