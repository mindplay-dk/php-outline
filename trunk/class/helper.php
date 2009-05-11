<?php

abstract class OutlineHelper {
  
  /*
  This class implements a base class for late-load helper objects.
  */
  
  private $engine;
  private $type;
  
  public function __construct(OutlineEngine &$engine, $type) {
    $this->engine = & $engine;
    $this->type = $type;
  }
  
  public function __get($name) {
    return $this->_get(
      $name,
      include($this->engine->get_metadata_path($name, $this->type))
    );
  }
  
  abstract protected function _get($name, $data);
  
}

?>