<?php

/*

OutlineForm (Helper)
--------------------

Copyright (C) 2007-2009, Rasmus Schultz <http://www.mindplay.dk>

Please see "README.txt" for license and usage information.
	
*/

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
  
  The $attr array contains all of the attributes of this form element -
  an attribute can be made "internal" by prefixing it with a hash (#),
  meaning it will not be exposed as an HTML attribute.
  */
  
  protected $attr;
  
  public function __construct(&$attr) {
    $this->attr = & $attr;
    $this->attr['#name'] = $attr['name'];
  }
  
  public function getValue() {
    return $this->attr['value'];
  }
  
  public function setValue($value) {
    $this->attr['value'] = $value;
  }
  
  // --- Magic accessor:
  
  public function __get($name) {
    if ($name == 'value') return $this->getValue();
    return array_key_exists($name, $this->attr) ? $this->attr[$name] : @$this->attr['#'.$name];
  }
  
  // --- Common helper functions:
  
  public function build_attr($attr) {
    $str = '';
    foreach ($this->attr as $name => $value) {
      if (strlen($value) && substr($name,0,1) != '#') {
        $str .= ' ' . $name . '="' . htmlspecialchars($value) . '"';
      }
    }
    return substr($str,1);
  }
  
  // --- Abstract interface to be implemented by each element:
  
  abstract public function render($subelement = null);
  
}
