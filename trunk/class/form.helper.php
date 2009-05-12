<?php

require_once OUTLINE_CLASS_PATH . '/helper.php';

class OutlineHelper_form extends OutlineHelper {
  
  private $forms = array();
  
  protected function _get($name, $data) {
    
    if (!isset($this->forms[$name])) {
      $this->forms[$name] = new OutlineFormModel($data);
    }
    
    return $this->forms[$name];
    
  }
  
}

class OutlineFormModel {
  
  protected $data;
  
  public function __construct($data) {
    $this->data = $data;
  }
  
}

?>