First, copy the "config.dist.php" file and customize it according to your own needs. The file itself contains some explanations of each configuration setting.

There are two options for templating with Outline, namely `Outline` and `OutlineTpl`.

The `OutlineTpl` class is an extension to `Outline`, providing a classic interface for templating, where template variables are assigned by calling a function. This approach is demonstrated in the "test\_tpl.php" test script.

The `Outline` class allows you to execute compiled template code in any scope - be it inside a function, a method, or even the main php script if you wish. The "test\_outline.php" script demonstrates this approach.

## Using the `OutlineTpl` class ##

First you need to include the configuration file and the class itself:

```
require_once "config.php"; // based on the "config.dist.php" sample configuration file
require_once OUTLINE_CLASS_PATH."/tpl.php"; // this contains the OutlineTpl class
```

One instance of the template engine is created per template. Some template engines let you render several templates in succession, using the same engine - this is not really a useful feature, and causes more complications than benefits.

You specify which template to use at construction time, for example:

```
$test = new OutlineTpl('products');
```

With the default settings, this loads the "products.html" template from the path defined by the `OUTLINE_TEMPLATE_PATH` constant. (see the "config.dist.php" sample configuration file, and refer to the comments in "class/engine.php" for more information.)

To assign variables for use in the template, simply assign them:

```
$test->assign('title', 'Fancy Dandy Product');
```

You may also wish to assign objects or arrays by reference, by using `assign_by_ref()`.

Finally, either render the template directly to output, or to a variable:

```
$test->display(); // renders the template to output
$html = $test->fetch(); // renders the template and returns the rendered content
```

That's all there is to basic usage of the `OutlineTpl` class. A common usage scenario is to extend the class for your own purposes - the scripts in the "test" folder demonstrates how to extend the class, how to use caching, and other more advanced features.

## Using the `Outline` class ##

To render a template, include the engine, create a Outline instance for your template, and call the get() function, which returns the path to the compiled template:

```
require "config.php";
$outline = new Outline('test');
require $outline->get();
```

This is a very different approach from that used by most other template engines, where you first have to go through the work of manually assigning every variable you want to make available to the template. Any global variables will be available to your template.

For simple pages that peform a single operation, this may work just fine, but a lot of the time, you are going to want to run the compiled template in an isolated scope - you can do this by wrapping your template in a function, for example:

```
function show_my_template($color, $icon) {
	$data = array("1", "2", "3");
	$_outline = new Outline('test');
	require $_outline->get();
}
```

The template now runs within the function's local scope, which means the variables your template will see, are the variables created and assigned in that function's local scope - including the function's arguments. You template will also have access to the $_outline variable, which is why it's prefixed with an underscore; to prevent the template author from accidentally using or overwriting it inside the template._

You could also wrap the template rendering code in  a method of an object - for example, a render() method on a simple class designed to render a specific template:

```
class MyTemplate extends Outline {
	
	public function __construct() {
		parent::__construct('test');
	}
	
	private $color = '#f00', $icon = 'icon.gif';
	
	public function setColor($color) {
		$this->color = $color;
	}
	
	public function render() {
		$icon = $this->icon;
		$color = $this->color;
		require $this->get();
	}
	
}
```