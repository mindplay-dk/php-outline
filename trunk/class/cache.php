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
	
	protected $config;
	
	public function __construct(&$config, $path) {
		
		$this->config = & $config;
		
		if (count($path) == 0) throw new OutlineException("OutlineCache::__construct() : path is empty");
		
		$this->path = array($this->config['cache_path']);
		
		$last = count($path);
		for ($n=0; $n<$last; $n++) {
			$this->path[] = '/' . OutlineUtil::clean($path[$n]) . ($n == $last-1 ? $this->config['cache_suffix'] : '');
		}
		
		$this->time = time();
		
	}
	
	public function valid($time_sec = null) {
		
		if ($this->checked) return $this->valid;
		$this->checked = true;
		
		if ($time_sec === null) $time_sec = $this->config['cache_time'];
		
		$time = @filemtime(implode($this->path));
		return ( $this->valid = $time ? ($this->time - $time < $time_sec) : false );
		
	}
	
	public function get() {
		if ($this->valid) return implode($this->path);
		throw new OutlineException("OutlineCache::get() : valid() must be called before get(), and only if valid() returns true");
	}
	
	public function capture() {
		
		if (@constant("OUTLINE_DEBUG")) OutlineDebug("Starting cache capture");
		
		if ($this->buffering) throw new OutlineException("OutlineCache::capture() : capture() may only be called once, or after stop() has been called");
		
		$path = ''; $last = count($this->path);
		
		for ($n=0; $n<$last-1; $n++) {
			$path .= $this->path[$n];
			if (!is_dir($path)) {
				if (!mkdir($path)) throw new OutlineException("OutlineCache::capture() : unable to create folder '$path'");
			}
		}
		
		$this->buffering = true;
		
		ob_start();
		
	}
	
	public function stop() {
		
		if (@constant("OUTLINE_DEBUG")) OutlineDebug("Cache capture finished");
		
		if (!$this->buffering) throw new OutlineException("OutlineCache::stop() : capture() must be called before stop() can be called");
		
		if (!@file_put_contents(implode($this->path), ob_get_contents()))
			throw new OutlineException("OutlineCache::stop() : unable to write new cache entry ".implode($this->path));
		
		ob_end_flush();
		
	}
	/*
	public function clean() {
		
		$path = dirname($this->path);
		
		foreach (func_get_args() as $dir)
			$path .= '/' . OutlineUtil::clean($dir);
		
		if (is_dir($path)) OutlineUtil::delete($path, $this->config['cache_suffix']);
		
		if (is_file($path.$this->config['cache_suffix'])) unlink($path.$this->config['cache_suffix']);
		
	}
	*/
	
}

?>