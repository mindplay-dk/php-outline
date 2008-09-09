<?php

class OutlineUtil {
	
	public static function clean($fname) {
		$pattern = "/([[:alnum:]_\.]*)/";
		$replace = "_";
		return str_replace(str_split(preg_replace($pattern,$replace,$fname)),$replace,$fname);
	}
	
	public static function delete($path, $suffix) {
		
		$wiped = true;
		
		foreach (glob($path . '/*' . $suffix) as $file) {
			if (!unlink($file)) trigger_error("OutlineUtil::delete() : unable to remove file '$file'", E_USER_WARNING);
		}
		
		foreach (glob($path . '/*', GLOB_ONLYDIR) as $dir) {
			$wiped = $wiped && self::delete($dir, $suffix);
			if (!rmdir($dir)) trigger_error("OutlineUtil::delete() : unable to remove dir '$dir' - not empty?", E_USER_WARNING);
		}
		
		return $wiped;
		
	}
	
	private static $files = array();
	
	public static function write_file($filename, $content) {
		
		$temp = tempnam(OUTLINE_CACHE_PATH, 'temp');
		if (!($f = @fopen($temp, 'wb'))) {
			$temp = OUTLINE_CACHE_PATH . DIRECTORY_SEPARATOR . uniqid('temp');
			if (!($f = @fopen($temp, 'wb'))) {
				trigger_error("OutlineUtil::write_file() : error writing temporary file '$temp'", E_USER_WARNING);
				return false;
			}
		}
		
		fwrite($f, $content);
		fclose($f);
		
		if (!@rename($temp, $filename)) {
			@unlink($filename);
			@rename($temp, $filename);
		}
		
		@chmod($filename, OUTLINE_FILE_MODE);
		
		return true;
		
	}
	
}

?>