<?php

/*

Outline Utility Functions
-------------------------

Copyright (C) 2007-2009, Rasmus Schultz <http://www.mindplay.dk>

Please see "README.txt" for license and other information.

*/

class OutlineUtil {
  
  /*
  This class implements a small library of common, static
  utility functions, used by various classes.
  */
  
  public static function clean($fname) {
    
    /*
    Cleans the given filename, removing any invalid characters.
    */
    
    $pattern = "/([[:alnum:]_\.]*)/";
    $replace = "_";
    return str_replace(str_split(preg_replace($pattern,$replace,$fname)),$replace,$fname);
    
  }
  
  public static function write_file($path, $content, $mode) {
    
    /*
    Atomically writes, or overwrites, the given content to a file.
    
    Atomic file writes are required for cache updates, and when
    writing compiled templates, to avoid race conditions.
    */
    
    $temp = tempnam(dirname($path), 'temp');
    if (!($f = @fopen($temp, 'wb'))) {
      $temp = dirname($path) . DIRECTORY_SEPARATOR . uniqid('temp');
      if (!($f = @fopen($temp, 'wb'))) {
        trigger_error("OutlineUtil::write_file() : error writing temporary file '$temp'", E_USER_WARNING);
        return false;
      }
    }
    
    fwrite($f, $content);
    fclose($f);
    
    if (!@rename($temp, $path)) {
      unlink($path);
      rename($temp, $path);
    }
    
    @chmod($path, $mode);
    
    return true;
    
  }
  
  public static function normalize_path($path) {
    
    /*
    Normalizes a path to match the native operating system.
    */
    
    return str_replace(
      DIRECTORY_SEPARATOR == '/' ? '\\' : '/',
      DIRECTORY_SEPARATOR == '/' ? '/' : '\\',
      $path
    );
    
  }
  
}
