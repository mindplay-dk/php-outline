## Template Syntax ##

Simple conditional blocks can be implemented using if-blocks:

> `{if $var == 42} ... {elseif $var == 21} ... {else} ... {/if}`

You have your basic for-loops, which work in both directions:

> `{for $var from 1 to 20} ... {/for}`

> `{for $var from 10 to 0} ... {/for}`

You can also specify an increment, to achieve sequences like 0, 10, 20, 30 ...

> `{for $var from 0 to 100 by 10} ... {/for}`

Looping through each element of an array is similar to php:

> `{foreach $array as $var} ... {/foreach}`

Or when looping through an associative array:

> `{foreach $array as $index => $var} ... {foreach}`

Additionally, you can use {else} with the {foreach} command to execute a default action, if the foreach'ed $array is empty:

> `{foreach $array as $var} ... {else} ... {/foreach}`

You have your while-loops:

> `{while $var < 20} ... {/while}`

And you can escape from template-syntax, when you need the `{` and `}` chars for something else, e.g. inline javascript or css:

> `{ignore} ... javascript or css ... {/ignore}`

You can insert comments into your templates:

> `{* this won't be displayed or have any effect whatsoever *}`

You can of course output simple variables and define()'d php constants:

> `{$var} ... simply outputs the contents of $var`

> `{#NAME} ... outputs the contents of a defined constant, NAME`

And you can apply modifiers to them - using a syntax similar to Smarty:

> `{$var|strtoupper} ... prints $var in uppercase`

> `{#NAME|strtoupper} ... prints value of constant NAME in uppercase`

> `{$var|md5} ... prints the md5-sum of $var`

> `{$var|ucfirst} ... first letter in uppercase`

> `{$var|wordwrap:30:"\n"|strtolower}`

Note that the {#} tag can actually be used to output the return-value of any php statement - you can use it to call any php-function you wish. For example, to call the time() function:

> `{#time()}`

Note that any available (internal or user) php-function can be used as a modifier - it just needs to accept its input value as its first argument. It is also possible to override php-functions with template-specific modifier functions - a few standard PHP functions are overridden by default, because the input value was not the first argument.

Modifiers can also be applied to blocks of content, using the {modify} block tags:

> `{modify html|strtoupper}<html>example</html>{/modify}`

The above example encodes the string `"<html>example</html>"` as HTML (e.g. replacing the angled brackets with named entities), and puts it in all caps.

The "." operator is commonly used in template engines for "lazy" array-resolution - for example `{$array.item.subitem}` instead of `{$array['item']['subitem']} - in Outline, this operator retains it's original meaning, which is simply to concatenate strings. However, Outline also offers a way to do "lazy" array resolution:

> `{@array.item.subitem}`

Note that you can also apply modifiers to the @ command, using the | syntax.

You can set variables, evaluate complex expressions, etc.:

> `{set $text = 'some text'}`

> `{set $number++}`

> `{set $total = $total + $price}`

> etc.

You can capture a section of the template's output to a variable:

> `{capture $myvar} capture this! {/capture}`

The string "capture this!" is now stored in $myvar.

Inside loops, you can alternate between values:

> `{cycle} value 1 {next} value 2 {next} value 3 ... {/cycle}`

You can include subtemplates:

> `{include some_template}`

> `{include $var}`

You can include and display textfiles:

> `{display myfile.ext}`

And you can include php-scripts:

> `{require myscript.php}`

## Modifiers ##

You can declare your own modifiers. Simply write standard PHP functions, or (better) prefix your function name with "`outline_`" - these functions will take presedent over non-prefixed functions, which means you can override standard pfp-functions with custom modifiers.

A small library of standard modifiers are included - here are a few examples:

> `{$var|replace:"find":"replace"}`

> replaces "find" with "replace" - avoid php's standard str\_replace() function, as it expects the string to operate on to come last.

> `{$var|default:"empty"}`

> outputs "empty" instead of null, "" (empty string) or 0 (zero).

> `{$var|wed}`

> `{$var|wed:50}`

> prevents orphans by replacing the last space with "&nbsp;". in rendered HTML, this prevents single hanging words, which is considered bad typography. the optional number, is the maximum length of the last word - if longer than that, no change is made.

For a complete list of modifiers, see the example template included with Outline.

Modifiers can also be applied to blocks of content - see the description of the `{modify}` block above.

## Custom Functions ##

When no command is found, Outline looks for a custom function - for example, in your php script, if you declare the following function:

> `function outline_function_fancy($args) { var_dump($args); }`

You can now call the function from a template, like so:

> `{fancy name="Bob" age=90}`

Outline parses any number of attributes and passes them as arguments to your function, so your function receives a value like:

> `array("name" => "Bob", age => 90)`

Note that this is not parsed as such - anything you place after "name=" is taken as php expressions. That means a value can come from a function-call, method-call, etc. - and it also means that you _must_ quote strings.

## Uncached Inserts ##

Inserts are similar to custom functions, but are called an executed _even after_ caching. That is, everything your template outputs can be cached, but the output from an insert is _never_ cached.

An insert is simply a function, similar to custom functions. For example:

> `function outline_insert_now($args) { return date("r"); }`

The template syntax for calling an insert is also similar:

> `{insert:now}`

This will display today's date - even if the template's output was cached three days ago.

Since inserts cannot be cached, they should be used only for content that changes constantly - for example, a box that displays whether a user is currently logged in, real time status displays, etc.

## User Blocks ##

User blocks can be used when you have something that repeats on a page, possibly with variations.

For example:

```
{block:headline title='' color='#00f'}
	<h1 style="color:{$color}">
		{$title}
	</h1>
{/block}
```

The `name=value` pairs specify default values for the arguments. Once declared, you can call the block, almost like any other tag, but using an "!" before the blockname:

> `{!headline title="A blue headline"}`

In the above call, the {$color} value defaults to '#00f' as specified in the block declaration. You can override the default color as follows:

> `{!headline title="A red headline" color="#f00"}`

Technically, blocks are simply php-functions, and therefore have all the features of a normal function declaration header in PHP - but rather than separating arguments with commas, they are separated with spaces, making them look and feel more like markup.

Note that the blockname is case-insensitive.

You can place your block declaration in an external template, and {include} it from other templates - just be aware that the include statement must precede the call; a block can not be called before it has been declared.

Also note that a block has it's own closed scope - it cannot see any template variables outside of itself, so if you have a variable in the template itself, that you need access to inside the block, you have to pass it as an argument.

Constants, on the other hand, are available globally.