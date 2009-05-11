<?php

/*

OutlineForm Plugin
------------------

Copyright (C) 2007-2009, Rasmus Schultz <http://www.mindplay.dk>

Please see "README.txt" for license and other information.

*/

interface IOutlineFormPlugin {
  public static function render(OutlineFormPlugin &$plugin, $args);
}

class OutlineFormPlugin extends OutlinePlugin {
  
  // * Form tags:
  
  protected $form = null;
  protected $elements = array();
  
  public function form_block($_args) {
    
    if ($this->form)
      throw new OutlineException("OutlineFormPlugin::form_block() : nested form declarations are not allowed", $this->compiler);
    
    $args = $this->compiler->parse_attributes($_args);
    
    if (!isset($args['name']))
      throw new OutlineException("OutlineFormPlugin::form_block() : missing name attribute in form tag", $this->compiler);
    
    if (!$this->is_simple($args['name']))
      throw new OutlineException("OutlineFormPlugin::form_block() : invalid name attribute in form tag", $this->compiler);
    
    $this->form = $this->unquote($args['name']);
    
    $this->build_tag('form', $args);
    
  }
  
  public function end_form_block($args) {
    
    $this->compiler->output('</form>');
    
    OutlineUtil::write_file(
      OUTLINE_COMPILED_PATH.'/'.$this->compiler->engine->getRelTplPath().'.'.strtolower($this->form).'.forms.php',
      OUTLINE_PHPTAG_OPEN."\n\n".
      "class OutlineFormModel_{$this->form} extends OutlineFormModel {\n".
      "  public function __construct(\$vars) {\n".
      "    extract(\$vars);\n".
      "    \$this->initFormModel(array(\n".
      "      ".implode(",\n      ", $this->elements)."\n".
      "    ));\n".
      "    parent::__construct();\n".
      "  }\n".
      "}\n\n".
      "function & Outline_helper_{$this->form}_forms(OutlineEngine &\$engine) {\n".
      "  static \$instance = new OutlineFormModel_{$this->form}(\$engine);\n".
      "  return \$instance;\n".
      "}\n\n".
      OUTLINE_PHPTAG_CLOSE
    );
    
    $this->form = null;
    
  }
  
  public function form_element($_args) {
    
    $this->compiler->checkBlock('form', 'form:');
    @list($element, $args) = explode(" ", $_args, 2);
    
    $class_name = 'OutlineForm_'.$element;
    if (!class_exists($class_name)) require_once OUTLINE_CLASS_PATH.'/form.'.$element.'.php';
    
    call_user_func_array(
      array($class_name, 'render'),
      array(&$this, $this->compiler->parse_attributes($args))
    );
    
  }
  
  // --- Helper functions:
  
  public function is_simple($expr) {
    # * RegEx: ^\"([^"]|\\\")+\"$|^\'([^']|\\\')+\'$|^\d+$
    return preg_match('/^\\"([^"]|\\\\\\")+\\"$|^\\\'([^\']|\\\\\\\')+\\\'$|^\\d+$/', $expr);
  }
  
  public function unquote($expr) {
    # this should only be used after checking the code fragment with is_simple()
    return is_numeric($expr) ? $expr : substr($expr,1,strlen($expr)-2);
  }
  
  public function build_tag($name, $attr) {
    $this->compiler->output("<{$name}");
    $code = array();
    foreach ($attr as $name => $expr) {
      if ($this->is_simple($expr)) {
        $code[] = '\' '.$name.'="'.$this->unquote($expr).'"\''; // * simple constant literal/number
      } else {
        $code[] = '\' '.$name.'="\'.('.$expr.').\'"\''; // * variable dynamic expression
      }
    }
    $this->compiler->code('echo '.implode('.', $code).";");
    $this->compiler->output('>');
  }
  
  // --- Element registration:
  
  public function add_element($file, $class_name, $args) {
    
    if (!isset($args['name']))
      throw new OutlineException("OutlineFormPlugin::register_element() : cannot register element without a name", $this->compiler);
    
    $code = array(
      "'#file' => '{$file}'",
      "'#class' => '{$class_name}'"
    );
    
    foreach ($args as $name => $expr) {
      $code[] = "'$name' => $expr";
    }
    
    $this->elements[] = $args['name'] . ' => array(' . implode(', ', $code) . ')';
    
  }
  
	// --- Plugin registration:
	
	public static function register(&$compiler) {
    $compiler->registerBlock('form', 'form_block');
    $compiler->registerTag('form:', 'form_element');
  }
  
}

// --- Core Form Elements:

class OutlineForm_text implements IOutlineFormPlugin {
  
  public static function render(OutlineFormPlugin &$plugin, $args) {
    $plugin->build_tag('input type="text"', $args);
    $plugin->add_element(__FILE__, __CLASS__, $args);
  }
  
}

?>