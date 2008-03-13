<?php

/*

This is the Outline configuration file for the test-script.

You can use this as a template for your own configuration files.

*/

define("OUTLINE_SYSTEM_PATH", dirname(__FILE__));                       // Root path of your Outline installation
define("OUTLINE_SCRIPT_PATH", OUTLINE_SYSTEM_PATH . "/test");           // Root path of your application

define("OUTLINE_DEBUG", true);                                          // If set, displays various debugging messages during load/compile
#define("OUTLINE_ALWAYS_COMPILE", true);                                 // If set, compiles templates unconditionally, on every run

define("OUTLINE_TEMPLATE_PATH", OUTLINE_SCRIPT_PATH . "/templates");    // Path to folder containing templates
define("OUTLINE_COMPILED_PATH", OUTLINE_SCRIPT_PATH . "/compiled");     // Folder containing compiled templates (must be writable)
define("OUTLINE_CACHE_PATH",    OUTLINE_SCRIPT_PATH . "/cache");        // The folder in which the Cache class stores it's content
define("OUTLINE_CLASS_PATH",    OUTLINE_SYSTEM_PATH . "/class");        // Path to Outline's system classes

define("OUTLINE_TEMPLATE_SUFFIX", ".html");                             // Suffix (extension) of template files
define("OUTLINE_COMPILED_SUFFIX", ".php");                              // Suffix (extension) of compiled template files (usually ".php")

define("OUTLINE_CACHE_SUFFIX", ".html");                                // File extension or suffix for cache files
define("OUTLINE_CACHE_TIME", 60*60*24);                                 // Default cache time (in seconds)

function OutlineDebug($msg) {
	echo "<div style=\"color:#f00\"><strong>Outline</strong>: $msg</div>";
}

require_once OUTLINE_CLASS_PATH . "/engine.php";
require_once OUTLINE_CLASS_PATH . "/modifiers.php";

?>