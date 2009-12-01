<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
	<title>Outline 2 - Test Page</title>
</head>

<body>

<?php

if (!isset($_GET['bypass'])) define('RECOMPILE', true);

$start = microtime(true);

require_once "../outline/engine.php";

class OutlineTest {
  
  /*
  A tiny "template engine" for testing purposes.
  */
  
  protected static $engine;
  
  protected $template_path;
  protected $compiled_path;
  
  public function __construct($tpl) {
    $this->template_path = dirname(__FILE__).'/templates/'.$tpl.'.tpl.html';
    $this->compiled_path = dirname(__FILE__).'/compiled/'.$tpl.'.tpl.php';
  }
  
  public function getTest() {
    return "this message comes from the OutlineTest test class";
  }
  
  public function render($_vars = array()) {
    if (defined('RECOMPILE')) self::$engine->compile(
      $this->template_path,
      $this->compiled_path,
      true // force recompile
    );
    extract($_vars);
    require self::$engine->load($this->compiled_path);
  }
  
  public static function trace($msg) {
    echo "<div style=\"color:#f00\"><strong>Outline</strong>: $msg</div>";
  }
  
  public static function init() {
    self::$engine = new Outline(array(
      'trace_callback' => array(__CLASS__, 'trace')
    ));
  }
  
}

OutlineTest::init();

function outline_function_testfunc($args) {
  // TODO: demonsrate obtaining runtimes, engine and context
  return "passed value was: {$args['value']}";
}

define('TEST_VALUE', 'This is a sample constant defined in outline-test.php');

$test = new OutlineTest('test');

$test->render(array(
  'page_title' => 'Outline 2 - Test Page',
  'testvar' => 'This variable has local scope',
  'testdate' => date("r"),
  'testarray' => array(
    "RED" => "ff0000",
    "GREEN" => "00a000",
    "BLUE" => "4080ff"
  ),
  'empty_array' => array(),
));

echo '<em>'.(defined('RECOMPILE') ? 'compiled and ' : '').'rendered in '.number_format(1000*(microtime(true)-$start),2).' msec'.(defined('RECOMPILE') ? ' (<a href="?bypass">bypass compiler</a>)' : '').'</em>';

?>

</body>

</html>
