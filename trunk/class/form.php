<?php

/*

OutlineForm Plugin
------------------

Copyright (C) 2007-2009, Rasmus Schultz <http://www.mindplay.dk>

Please see "README.txt" for license and other information.

*/

interface IOutlineFormPlugin {
  public static function build(OutlineFormPlugin &$plugin, $args, $subelement);
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
    
    if (!isset($args['autocomplete']))
      $args['autocomplete'] = '"off"';
    
    $this->form = $this->unquote($args['name']);
    
    $this->build_tag('form', $args);
    
  }
  
  public function end_form_block($args) {
    
    $this->compiler->output('</form>');
    
    $elements = array();
    foreach ($this->elements as $name => $code) {
      $elements[] = "'$name' => array(" . implode(', ', $code) . ')';
    }
    
    OutlineUtil::write_file(
      $this->compiler->engine->get_metadata_path($this->form, 'form'),
      OUTLINE_PHPTAG_OPEN."\n\n".
      "return array(\n".
      "  ".implode(",\n  ", $elements)."\n".
      ");\n\n".
      OUTLINE_PHPTAG_CLOSE
    );
    
    $this->form = null;
    
  }
  
  public function form_element($_args) {
    
    $this->compiler->checkBlock('form', 'form:');
    @list($element, $args) = explode(" ", $_args, 2);
    @list($element, $subelement) = explode(":", $element, 2);
    
    $class_name = 'OutlineForm_'.$element;
    if (!class_exists($class_name)) require_once OUTLINE_CLASS_PATH.'/form.'.$element.'.php';
    
    call_user_func_array(
      array($class_name, 'build'),
      array(&$this, $this->compiler->parse_attributes($args), $subelement)
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
  
  public function getForm() {
    return $this->form;
  }
  
  // --- Element registration:
  
  public function add_element($file, $class_name, $args, $subelement = null) {
    
    if (!isset($args['name']))
      throw new OutlineException("OutlineFormPlugin::add_element() : cannot add element without a name", $this->compiler);
    
    $name = $this->unquote($args['name']);
    
    if ($subelement) {
      
      $this->elements[$name][] = "'#$subelement' => true";
      
    } else {
      
      $this->elements[$name] = array(
        "'#file' => '{$file}'",
        "'#class' => '{$class_name}'"
      );
      
      foreach ($args as $id => $expr) {
        $this->elements[$name][] = "'$id' => $expr";
      }
      
    }
    
    $this->compiler->code('echo $outline->form->'.$this->form.'->'.$this->unquote($args['name']).'->render('.($subelement ? var_export($subelement, true) : '').');');
    
  }
  
	// --- Plugin registration:
	
	public static function register(&$compiler) {
    $compiler->registerBlock('form', 'form_block');
    $compiler->registerTag('form:', 'form_element');
  }
  
}

// --- Core Form Elements:

define('OUTLINE_FORM_RUNTIME', OUTLINE_CLASS_PATH . '/form.system.php');

class OutlineForm_text implements IOutlineFormPlugin {
  public static function build(OutlineFormPlugin &$plugin, $args, $subelement) {
    $plugin->add_element(OUTLINE_FORM_RUNTIME, 'OutlineFormElement_text', $args, $subelement);
  }
}

class OutlineForm_password implements IOutlineFormPlugin {
  public static function build(OutlineFormPlugin &$plugin, $args, $subelement) {
    $plugin->add_element(OUTLINE_FORM_RUNTIME, 'OutlineFormElement_password', $args, $subelement);
  }
}

class OutlineForm_submit implements IOutlineFormPlugin {
  public static function build(OutlineFormPlugin &$plugin, $args, $subelement) {
    if (!isset($args['name'])) $args['name'] = '"'.$plugin->getForm().'_submit"';
    $plugin->add_element(OUTLINE_FORM_RUNTIME, 'OutlineFormElement_submit', $args, $subelement);
  }
}

?>