<?php

/*

OutlineForm Plugin
------------------

Copyright (C) 2007-2009, Rasmus Schultz <http://www.mindplay.dk>

Please see "README.txt" for license and other information.

*/

interface IOutlineFormPlugin {
  public static function render(OutlineCompiler &$compiler, $args);
}

class OutlineFormPlugin extends OutlinePlugin {
  
  // * form block
  
  static protected $form = null;
  
  public function form_block($_args) {
    if (self::$form) throw new OutlineException("nested form declarations are not allowed", $this->compiler);
    $args = $this->compiler->parse_attributes($_args);
    if (!isset($args['name'])) throw new OutlineException("missing name attribute in form tag", $this->compiler);
    self::$form = $args['name'];
    $this->compiler->build_tag('form', $args);
  }
  
  public function end_form_block($args) {
    self::$form = null;
    $this->compiler->output('</form>');
  }
  
  public function form_element($_args) {
    $this->compiler->checkBlock('form', 'form:');
    @list($element, $args) = explode(" ", $_args, 2);
    $class_name = 'OutlineForm_'.$element;
    if (!class_exists($class_name)) require_once OUTLINE_CLASS_PATH.'/form.'.$element.'.php';
    call_user_func(array($class_name, 'render'), $this->compiler, $this->compiler->parse_attributes($args));
  }
  
	// --- Plugin registration:
	
	public static function register(&$compiler) {
    $compiler->registerBlock('form', 'form_block');
    $compiler->registerTag('form:', 'form_element');
  }
  
}

// --- Core Form Elements:

class OutlineForm_text implements IOutlineFormPlugin {
  public static function render(OutlineCompiler &$compiler, $args) {
    $compiler->build_tag('input type="text"', $args);
  }
}

?>