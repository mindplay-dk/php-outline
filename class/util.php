<?php

class OutlineUtil {
	
	public static function clean($fname) {
		$pattern = "/([[:alnum:]_\.-]*)/";
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
	
}

?>