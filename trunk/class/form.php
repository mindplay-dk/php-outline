<?php

/*

OutlineForm Plugin
------------------

Copyright (C) 2007-2009, Rasmus Schultz <http://www.mindplay.dk>

Please see "README.txt" for license and other information.

*/

class OutlineFormPlugin extends OutlinePlugin {
  
  // * form block
  
  static protected $form = null;
  
  public function form_block($_args) {
    if (self::$form) throw new OutlineException("nested form declarations are not allowed", $this->compiler);
    $args = $this->compiler->parse_attributes($_args);
    if (!isset($args['name'])) throw new OutlineException("missing name attribute in form tag", $this->compiler);
    self::$form = array();
    $this->compiler->output('<form name="');
    $this->compiler->code('echo '.$args['name']);
    $this->compiler->output('">');
  }
  
  public function end_form_block($args) {
    self::$form = null;
    $this->compiler->output('</form>');
  }
  
	// --- Plugin registration:
	
	public static function register(&$compiler) {
    $compiler->registerBlock('form', 'form_block');
  }
  
}

?>