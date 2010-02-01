<?php

/*

OutlineCompiler
---------------

Copyright (C) 2007-2009, Rasmus Schultz <http://www.mindplay.dk>

Please see "README.txt" for license and other information.
  
*/

define("OUTLINE_COMPILER", 1);

define("OUTLINE_PHPTAG_OPEN",         '<'.'?php ');
define("OUTLINE_PHPTAG_CLOSE",        ' ?'.'>');
define("OUTLINE_MODIFIER_PIPE",       '|');
define("OUTLINE_MODIFIER_SEP",        ':');
define("OUTLINE_COMMAND_CANCEL",      '/');
define("OUTLINE_MODIFIER_PREFIX",     'outline__');
define("OUTLINE_USERBLOCK_PREFIX",    'outline__user_');
define("OUTLINE_USERBLOCK_CONST",     'OUTLINE_USER_');
define("OUTLINE_USERFUNC_PREFIX",     'outline_function_');
define("OUTLINE_INSERTFUNC_PREFIX",   'outline_insert_');

class OutlineCompilerException extends Exception {
  
  protected $linenum = 0;
  
  public function __construct($message, OutlineCompiler & $compiler) {
    parent::__construct($message, -1);
    $this->linenum = $compiler->getLineNum();
  }
  
  public function getLineNum() { return $this->linenum; }
  
}

class OutlineCompiler {
  
  const BRACKET_OPEN        = 1;
  const BRACKET_CLOSE       = 2;
  const BRACKET_COMMENT     = 3;
  const BRACKET_END_COMMENT = 4;
  const BRACKET_IGNORE      = 5;
  const BRACKET_END_IGNORE  = 6;
  
  const COMMAND_TAG =   1;
  const COMMAND_BLOCK = 2;
  
  // * Brackets:
  
  protected $brackets_begin, $brackets_end, $brackets_comment, $brackets_ignore;
  
  // * Other members:
  
  protected $blocks = array();
  protected $tags = array();
  
  protected $commands;
  protected $runtimes;
  
  protected $plugins = array();
  protected $plugin_registry = array();
  
  static protected $loaded_plugins = array();
  
  public $current_plugin = null;
  
  public $utf8 = false;
  public $uid;
  
  public $engine;
  public $config;
  
  protected $init;
  protected $compiled;
  
  public function __construct(Outline &$engine) {
    $this->engine = & $engine;
    $this->config = $engine->getConfig();
    $this->commands = array(
      array("type" => self::COMMAND_BLOCK, "commands" => & $this->blocks),
      array("type" => self::COMMAND_TAG,   "commands" => & $this->tags)
    );
    $this->brackets_begin = array(
      $this->config['bracket_ignore'] => self::BRACKET_IGNORE,
      $this->config['bracket_comment'] => self::BRACKET_COMMENT,
      $this->config['bracket_open'] => self::BRACKET_OPEN
    );
    $this->brackets_end = array(
      $this->config['bracket_close'] => self::BRACKET_CLOSE
    );
    $this->brackets_comment = array(
      $this->config['bracket_end_comment'] => self::BRACKET_END_COMMENT
    );
    $this->brackets_ignore = array(
      $this->config['bracket_end_ignore'] => self::BRACKET_END_IGNORE
    );
  }
  
  public function __destruct() {
    foreach ($this as $index => $value) unset($this->$index);
  }
  
  // --- Compiler and Parser methods:
  
  public function compile($template_path) {
    
    $tpl = file_get_contents($template_path);
    $this->uid = sprintf('%u',crc32($template_path));
    
    if ($this->utf8 = self::is_utf8($tpl))
      $this->engine->trace("OutlineCompiler running in UTF-8 mode");
    
    $brackets = & $this->brackets_begin;
    $command = '';
    $in_command = false;
    $in_comment = false;
    
    $i = 0;
    
    $this->init = array();
    $this->compiled = '';
    $this->coding = false;
    $this->linenum = 1;
    
    $this->init("\$_ = OutlineRuntime::start(__FILE__, isset(\$this) ? \$this : null);");
    
    foreach ($this->config['plugins'] as $plugin) {
      
      $class = 'OutlinePlugin_'.$plugin;
      $class_path = OUTLINE_PLUGIN_PATH.'/'.$plugin.'.plugin.php';
      
      $runtime = $plugin.'.runtime.php';
      $runtime_path = OUTLINE_PLUGIN_PATH.'/'.$runtime;
      
      if (!in_array($class, self::$loaded_plugins)) {
        self::$loaded_plugins[] = $class;
        $this->engine->trace("Loading plugin '{$class}' from '{$class_path}'");
        require_once $class_path;
        
        if (file_exists($runtime_path))
          require_once $runtime_path;
      }
      
      $this->registerPlugin($class);
      
    }
    
    while ($i < strlen($tpl)) {
      
      if ($newline = (substr($tpl, $i, 1) === "\n")) $this->linenum++;
      
      foreach ($brackets as $bracket => $type) {
        
        if (substr($tpl, $i, strlen($bracket)) === $bracket) {
          
          switch ($type) {
            
            // * Normal opening/closing brackets:
            
            case self::BRACKET_OPEN:  
              $in_command = true;
              $brackets = & $this->brackets_end;
            break;
            
            case self::BRACKET_CLOSE:
              $in_command = false;
              $this->parse($command);
              $command = '';
              $brackets = & $this->brackets_begin;
            break;
            
            // * Comments:
            
            case self::BRACKET_COMMENT:
              $in_comment = true;
              $brackets = & $this->brackets_comment;
            break;
            
            case self::BRACKET_END_COMMENT:
              $in_comment = false;
              $brackets = & $this->brackets_begin;
            break;
            
            // * Ignore command:
            
            case self::BRACKET_IGNORE:
              $in_command = true;
              $brackets = & $this->brackets_ignore;
            break;
            
            case self::BRACKET_END_IGNORE:
              $in_command = false;
              $this->output($command);
              $command = '';
              $brackets = & $this->brackets_begin;
            break;
            
          }
          
          $i += strlen($bracket);
          
          continue 2;
          
        }
        
      }
      
      if ($in_command) {
        $command .= substr($tpl, $i, 1);
      } elseif (!$in_comment || $newline) {
        $this->output(substr($tpl, $i, 1));
      }
      
      $i++;
      
    }
    
    if (count($this->block_stack))
      throw new OutlineCompilerException("OutlineCompiler::compile() : unterminated block: " . end($this->block_stack) . " at end of template", $this);
    
    $this->code('$_ = OutlineRuntime::finish(__FILE__);');
    
    if ($this->coding) $this->compiled .= OUTLINE_PHPTAG_CLOSE;
    
    foreach ($this->config['plugins'] as $plugin) {
      $class = 'OutlinePlugin_'.$plugin;
      if (isset($this->plugins[$class])) {
        if (file_exists(OUTLINE_PLUGIN_PATH.'/'.$runtime)) {
          include_once OUTLINE_PLUGIN_PATH.'/'.$runtime;
          $this->init("\$_->init_runtime('{$plugin}');");
        }
        $this->plugins[$class]->__destruct();
        unset($this->plugins[$class]);
      }
    }
    
    $this->compiled = OUTLINE_PHPTAG_OPEN . implode(' ', $this->init) . OUTLINE_PHPTAG_CLOSE . $this->compiled;
    
    return $this->compiled;
    
  }
  
  protected function parse($command) {
    
    $cancel = (substr($command, 0, strlen(OUTLINE_COMMAND_CANCEL)) === OUTLINE_COMMAND_CANCEL);
    
    @list($function, $args) = explode(" ", $command, 2);
    $function = OUTLINE_USERFUNC_PREFIX . $function;
    if (function_exists($function)) {
      $this->code('echo '.$function.'('.$this->build_arguments($args).');');
      return;
    }
    
    $match = 0;
    $lcommand = strtolower($command);
    foreach ($this->commands as $c) {
      foreach ($c['commands'] as $keyword => $item) {
        if ((substr($lcommand, $cancel ? strlen(OUTLINE_COMMAND_CANCEL) : 0, strlen($keyword)) === $keyword) && (strlen($keyword) > $match)) {
          $match = strlen($keyword);
          $type = $c['type'];
          $classname = $item['class'];
          $function = ($cancel ? 'end_' : '') . $item['function'];
          $args = trim(substr($command, strlen($keyword)));
          $command_name = substr($command, $cancel ? strlen(OUTLINE_COMMAND_CANCEL) : 0, strlen($keyword));
        }
      }
    }
    
    if (!$match)
      throw new OutlineCompilerException("OutlineCompiler::parse() : unrecognized tag: ".htmlspecialchars($command), $this);
    
    if ($classname && !isset($this->plugins[$classname]))
      $this->plugins[$classname] = new $classname($this);
    
    switch ($type) {
      
      case self::COMMAND_BLOCK:
        $cancel ? $this->popBlock($command_name, $command) : $this->pushBlock($command_name, $command);
        $this->plugins[$classname]->$function($args);
      return;
      
      case self::COMMAND_TAG:
        $this->plugins[$classname]->$function($args);
      return;
      
    }
  
  }
  
  // --- Coding and output methods:
  
  protected $coding;
  
  public function init($php) {
    $this->init[] = $php;
  }
  
  public function code($php) {
    $this->compiled .= ( $this->coding ? ' ' : OUTLINE_PHPTAG_OPEN ) . $php;
    $this->coding = true;
  }
  
  public function output($text) {
    $this->compiled .= ( $this->coding ? OUTLINE_PHPTAG_CLOSE : '' ) . $text;
    $this->coding = false;
  }
  
  // --- Utility methods:
  
  public static function is_utf8(&$str) {
    return ( mb_detect_encoding($str,'ASCII,UTF-8',true) == 'UTF-8' );
  }
  
  public function split(&$str) {
    if (!$this->utf8) return str_split($str,1);
    $chars = null;
    preg_match_all('/.{1}|[^\x00]{1,1}$/us', $str, $chars);
    return $chars[0];
  }
  
  public function escape_split(&$str, $token) {
    
    $a = array();
    $bit = ''; $last = ''; $quote = '';
    $chars = $this->split($str);
    $len = count($chars);
    
    for ($i=0; $i<$len; $i++) {
      $char = $chars[$i];
      if ($char == "'" || $char == '"') {
        if ($last != "\\") {
          if ($quote == '') {
            $quote = $char;
          } else if ($quote == $char) {
            $quote = '';
          }
        }
      }
      if ($char == $token && $quote == '') {
        $a[] = trim($bit);
        $bit = '';
      } else {
        $bit .= $char;
      }
      $last = $char;
    }
    
    if (trim($bit) != '') $a[] = trim($bit);
    
    return $a;
    
  }
  
  public function parse_attributes($str) {
    $attribs = array();
    foreach ($bits = $this->escape_split($str, " ") as $bit) {
      $a = explode("=", $bit, 2);
      if (count($a)==2) $attribs[trim($a[0])] = trim($a[1]);
    }
    return $attribs;
  }
  
  public function apply_modifiers($args) {
    
    $mods = $this->escape_split($args, OUTLINE_MODIFIER_PIPE);
    $code = trim(array_shift($mods));
    
    foreach ($mods as $mod) {
      $args = $this->escape_split($mod, OUTLINE_MODIFIER_SEP);
      $mod = trim(array_shift($args));
      if (function_exists(OUTLINE_MODIFIER_PREFIX.$mod)) {
        $code = OUTLINE_MODIFIER_PREFIX.$mod . '(' . $code . (count($args) ? ', '.implode(', ', $args) : '') . ')';
      } else if (function_exists($mod)) {
        $code = $mod . '(' . $code . (count($args) ? ', '.implode(', ', $args) : '') . ')';
      } else {
        throw new OutlineCompilerException("modifier '$mod' not found", $this);
      }
    }
    
    return $code;
    
  }
  
  public function build_arguments($args) {
    $a = array();
    foreach ($this->parse_attributes($args) as $name => $value)
      $a[] = "\"$name\" => $value";
    return "array(".implode(", ", $a).")";
  }
  
  // --- Block/nesting management methods:
  
  protected $block_stack = array();
  
  public function pushBlock($name, $command) {
    $this->block_stack[] = $name;
  }
  
  public function popBlock($name, $command) {
    $this->checkBlock($name, $command);
    array_pop($this->block_stack);
  }
  
  public function getBlock() {
    return end($this->block_stack);
  }
  
  public function checkBlock($name, $command) {
    if (end($this->block_stack) !== $name)
      throw new OutlineCompilerException("unmatched tag: ".htmlspecialchars($command) . (count($this->block_stack) ? " - expected closing tag for " . end($this->block_stack) : ""), $this);
  }
  
  // --- Command registration methods:
  
  protected function registerCommand($type, $keyword, $function) {
    
    if ( isset($this->tags[$keyword]) || isset($this->blocks[$keyword]) )
      trigger_error("OutlineCompiler::register() : keyword '$keyword' already registered", E_USER_ERROR);
    
    $plugin = array(
      "class" => $this->current_plugin,
      "function" => $function
    );
    
    switch ($type) {
      case self::COMMAND_BLOCK: $this->blocks[$keyword] = $plugin; break;
      case self::COMMAND_TAG: $this->tags[$keyword] = $plugin; break;
    }
    
  }
  
  public function registerTag($keyword, $function) {
    $this->registerCommand(self::COMMAND_TAG, $keyword, $function);
  }
  
  public function registerBlock($keyword, $function) {
    $this->registerCommand(self::COMMAND_BLOCK, $keyword, $function);
  }
  
  // --- Plugin management methods:
  
  public function registerPlugin($classname) {
    
    $this->engine->trace("Registering plugin '$classname'");
    
    if (in_array($classname, $this->plugin_registry))
      trigger_error("OutlineCompiler::registerPlugin() : plugin '$classname' already registered", E_USER_ERROR);
    
    $this->plugin_registry[] = $classname;
    
    $this->current_plugin = $classname;
    call_user_func_array(array($classname, "register"), array(&$this));
    $this->current_plugin = null;
    
  }
  
  // --- Error management methods:
  
  protected $linenum;
  
  public function getLineNum() { return $this->linenum; }
  
}

abstract class OutlinePlugin {
  
  /*
  Base class for Outline plugins.
  */
  
  protected $compiler;
  
  public function __construct(OutlineCompiler & $compiler) {
    $this->compiler = & $compiler;
  }
  
  public function __destruct() {
    foreach ($this as $index => $value) unset($this->$index);
  }
  
  public static function register(&$compiler) {
    trigger_error("OutlinePlugin::register() : plugins must override this method", E_USER_ERROR);
  }
  
}
