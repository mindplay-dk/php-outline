<?php

/*

Outline (Helper)
----------------

Copyright (C) 2007-2009, Rasmus Schultz <http://www.mindplay.dk>

Please see "README.txt" for license and usage information.
  
*/

abstract class OutlineHelper {
  
  /*
  This class implements a base class for late-load helper objects.
  */
  
  private $engine;
  private $type;
  private $vars;
  private $data;
  
  public function __construct(OutlineEngine &$engine, $type, &$vars) {
    $this->engine = & $engine;
    $this->type = $type;
    $this->vars = & $vars;
  }
  
  public function __get($name) {
    
    if (!isset($this->data)) {
      extract($this->vars);
      $this->data = include($this->engine->get_metadata_path($name, $this->type));
    }
    
    return $this->_get($name, $this->data);
    
  }
  
  abstract protected function _get($name, $data);
  
}
