<?php

/*

OutlineCache
------------

Copyright (C) 2007-2008, Rasmus Schultz <http://www.mindplay.dk>

Please see "README.txt" for license and usage information.

*/

define("OUTLINE_CACHE_ENGINE", true);

class OutlineCache {
	
	protected $path, $time;
	
	protected $valid = false;
	protected $checked = false;
	protected $buffering = false;
	
	public function __construct($path) {
		
		if (count($path) == 0) throw new OutlineException("Cache: constructor requires one or more arguments");
		
		$this->path = array(OUTLINE_CACHE_PATH);
		
		$last = count($path);
		for ($n=0; $n<$last; $n++) {
			$this->path[] = '/' . self::_clean($path[$n]) . ($n == $last-1 ? OUTLINE_CACHE_SUFFIX : '');
		}
		
		$this->time = time();
		
	}
	
	public function valid($time_sec = OUTLINE_CACHE_TIME) {
		
		if ($this->checked) return $this->valid;
		
		$time = @filemtime(implode($this->path));
		$this->valid = $time ? ($this->time - $time < $time_sec) : false;
		$this->checked = true;
		
		return $this->valid;
		
	}
	
	public function get() {
		if ($this->valid) return implode($this->path);
		throw new OutlineException("Cache: valid() must be called before get(), and only if valid() returns true");
	}
	
	public function capture() {
		
		if ($this->buffering) throw new OutlineException("Cache: capture() may only be called once, or after stop() has been called");
		
		$path = ''; $last = count($this->path);
		
		for ($n=0; $n<$last-1; $n++) {
			$path .= $this->path[$n];
			if (!is_dir($path)) {
				if (!mkdir($path)) throw new OutlineException("Cache: unable to create folder ".$path);
			}
		}
		
		$this->buffering = true;
		
		ob_start();
		
	}
	
	public function stop() {
		
		if (!$this->buffering) throw new OutlineException("Cache: capture() must be called before stop() can be called");
		
		if (!@file_put_contents(implode($this->path), ob_get_contents()))
			throw new OutlineException("Cache: unable to write new cache entry ".implode($this->path));
		
		ob_end_flush();
		
	}
	
	// --- Static function for flushing cache:
	
	public static function expire() {
		
		$path = OUTLINE_CACHE_PATH;
		
		foreach (func_get_args() as $dir)
			$path .= '/' . self::_clean($dir);
		
		if (is_dir($path)) self::_delete($path);
		
		if (is_file($path.OUTLINE_CACHE_SUFFIX)) unlink($path.OUTLINE_CACHE_SUFFIX);
		
	}
	
	// --- Private helper functions:
	
	protected static function _clean($dir) {
		return $dir; /* TO-DO: replace invalid characters */
	}
	
	protected static function _delete($path) {
		
		$wiped = true;
		
		foreach (glob($path . '/*' . OUTLINE_CACHE_SUFFIX) as $file)
			if (!unlink($file)) trigger_error("Cache: unable to remove file '$file'", E_USER_WARNING);
		
		foreach (glob($path . '/*', GLOB_ONLYDIR) as $dir) {
			$wiped = $wiped && self::_delete($dir);
			if (!rmdir($dir)) trigger_error("Cache: unable to remove dir '$dir'", E_USER_WARNING);
		}
		
		return $wiped;
		
	}
	
}

?>