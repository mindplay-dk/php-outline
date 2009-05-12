<?php

require_once OUTLINE_CLASS_PATH . '/helper.php';

class OutlineHelper_form extends OutlineHelper {
  
  /*
  This OutlineHelper implements the form collection.
  */
  
  private $forms = array();
  
  protected function _get($name, $data) {
    
    if (!isset($this->forms[$name])) {
      $this->forms[$name] = new OutlineFormModel($data);
    }
    
    return $this->forms[$name];
    
  }
  
}

class OutlineFormModel {
  
  /*
  This class implements the runtime model for a single form.
  */
  
  protected $elements;
  
  static protected $loaded = array();
  
  public function __construct(&$data) {
    
    foreach ($data as $name => $attr) {
      
      if (!isset(self::$loaded[$attr['#file']])) {
        require_once $attr['#file'];
        self::$loaded[$attr['#file']] = true;
      }
      
      $class_name = $attr['#class'];
      $this->elements[$name] = new $class_name($data[$name]);
      
    }
    
  }
  
  public function & __get($name) {
    return $this->elements[$name];
  }
  
  public function __set($name, $value) {
    $this->elements[$name]->setValue($value);
  }
  
}

abstract class OutlineFormElement {
  
  /*
  This is the abstract base class for all form elements.
  */
  
  protected $attr;
  
  public function __construct(&$attr) {
    $this->attr = & $attr;
  }
  
  abstract public function setValue($value);
  
}

?>