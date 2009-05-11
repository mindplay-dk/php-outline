<?php

abstract class OutlineHelper {
  
  /*
  This class implements a base class for late-load helper objects.
  */
  
  private $engine;
  private $type;
  private $vars;
  
  public function __construct(OutlineEngine &$engine, $type, &$vars) {
    $this->engine = & $engine;
    $this->type = $type;
    $this->vars = & $vars;
  }
  
  public function __get($name) {
    extract($this->vars);
    return $this->_get(
      $name,
      include($this->engine->get_metadata_path($name, $this->type))
    );
  }
  
  abstract protected function _get($name, $data);
  
}

?>