<?php

/*
This example demonstrates Outline at it's simplest - no caching, and no encapsulation.
*/

require_once "_header.php";

class MyOutline {
  
  /*
  A tiny "template engine" for testing purposes.
  */
  
  protected static $engine;
  
  protected $template_path;
  protected $compiled_path;
  
  public function __construct($tpl) {
    if (!isset(self::$engine)) {
      self::$engine = new Outline(array(
        'trace_callback' => array($this, 'trace')
      ));
    }
    $this->template_path = dirname(__FILE__).'/templates/'.$tpl.'.tpl.html';
    $this->compiled_path = dirname(__FILE__).'/compiled/'.$tpl.'.tpl.php';
  }
  
  public function getTest() {
    return "this message comes from the MyOutline test class";
  }
  
  public function render($_vars = array()) {
    self::$engine->compile(
      $this->template_path,
      $this->compiled_path,
      true // force recompile
    );
    extract($_vars);
    require self::$engine->load($this->compiled_path);
  }
  
  public function trace($msg) {
    echo "<div style=\"color:#f00\"><strong>Outline</strong>: $msg</div>";
  }
  
}

header("Content-type: text/html; charset=iso-8859-1");

function outline_function_testfunc($args) {
  // TODO: demonsrate obtaining runtimes, engine and context
  return "today's date is " . date("r") . ' - passed string was: ' . $args['value'];
}

$test = new MyOutline('test');

$test->render(array(
  'page_title' => 'Outline Test Page',
  'testvar' => 'This variable has local scope',
  'testdate' => date("r"),
  'testarray' => array(
    "RED" => "ff0000",
    "GREEN" => "00a000",
    "BLUE" => "4080ff"
  ),
  'empty_array' => array(),
));

?>