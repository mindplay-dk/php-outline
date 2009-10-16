<?php require_once OUTLINE_PLUGIN_PATH.'/system.runtime.php'; $outline = Outline::get_context(); ?><html>

<head>
	<title><?php echo $use_this_title; ?></title>
</head>

<body>


	<h1>Testing the {require} and {ignore} commands</h1>


<?php require_once 'test_require.php'; ?>

<p>Testing »funny« characters</p>

<p>Testing context object - the local $outline reference is of type: <?php echo get_class($outline); ?></p>

<p>Contextual object's getTest() method returned: <?php echo $outline->getTest(); ?></p>

<p>Printing a constant defined in 'test_require.php': <?php echo TEST_VALUE; ?></p>

<p>Applying a modifier to the same constant: <?php echo outline__replace(TEST_VALUE, 'i', 'iiiii'); ?></p>

<h1>Testing the {block} command</h1>

<h1>Testing variables and standard modifiers</h1>

<p>This value was assigned in the template function in 'test.php': "<?php echo $testvar; ?>"</p>

<p>And the same variable with a native PHP function applied as modifier: "<?php echo md5($testvar); ?>"</p>

<p>This modifier uses a variable as one of it's arguments: "<?php echo outline__replace($testvar, 'variable', 'VARIABLE'); ?>"</p>

<p>This demonstrates <span style="color:#<?php echo $testarray['RED']; ?>">lazy</span> <span style="color:#<?php echo $testarray['BLUE']; ?>">array</span> <span style="color:#<?php echo outline__upper($testarray['GREEN']); ?>">resolution</span> using ampersand.</p>

<textarea cols="80" rows="15">
default: <?php echo outline__default($empty_array, "the array is empty"); ?>

strip: <?php $test="spaces   will   be   stripped"; echo outline__strip($test); ?>

date: <?php echo outline__date(time(), "Y-m-d H:i:s"); ?>

time: <?php echo outline__time(time(), "%c"); ?>

html: <?php $test = "<strong>bold text</strong>"; echo outline__html($test); ?>

url: <?php $test = "url-encode this!"; ?>http://www.google.com/search?<?php echo outline__url($test); ?>

escape: <?php $test = "'this will be escaped\nfor javascript'"; ?>javascript:window.alert('<?php echo outline__escape($test); ?>');
wed: <?php $test = "the wed modifier prevents orphans in html"; echo outline__html(outline__wed($test)); ?>

lower: <?php $test = 'LOWERCASE'; echo outline__lower($test); ?>

upper: <?php $test = 'uppercase'; echo outline__upper($test); ?>

format: <?php $test = 3.1415; echo outline__format($test); ?> | <?php $test = 42000000; echo outline__format($test, 0); ?>

br: <?php $test = "this is\na test"; echo outline__html(outline__br($test)); ?>

chop: <?php $test = "this is an example - the chop function shortens elaborate text at a given length"; echo outline__chop($test, 65); ?>

</textarea>

<h1>Testing the {modify} command</h1>
<pre>
<?php ob_start(); ?>
<div>You should see a div tag in all caps</div>
<?php echo outline__upper(outline__html(ob_get_clean())); ?>
</pre>

<h1>Testing the {capture} command</h1>

<?php ob_start(); ?>
This content was captured to the $test variable.
<?php $test = ob_get_clean(); ?>

<p>Captured text: "<?php echo trim($test); ?>"</p>

<h1>Testing the {set} command</h1>

<?php $var='This string was assigned using the set command'; ?>

<p><?php echo $var; ?></p>

<h1>Testing the {while} command</h1>

<?php $i = 5; ?>
<ul>
	<?php while ($i > 0) { ?>
		<li>countdown <?php echo $i; ?> ...</li>
		<?php $i = $i - 1; ?>
	<?php } ?>
</ul>

<h1>Testing variations of the {for} command</h1>

<p>Counting to ten: <?php for ($var=1; $var<=10; $var+=1) { echo $var; ?>, <?php } ?>...</p>

<p>Counting to a hundred by ten at a time: <?php for ($var=0; $var<=100; $var+=10) { echo $var; ?>, <?php } ?>...</p>

<p>Counting backwards from ten: <?php for ($var=10; $var>=1; $var+=-1) { echo $var; ?>, <?php } ?>...</p>

<p>Using dynamic arguments: <?php $outline_for_var = new OutlineIterator(date('Y'), date('Y')-9, 1); while ($outline_for_var->next()) { $var = $outline_for_var->index; echo $var; ?>, <?php } ?>...</p>

<h1>Testing the {include} command</h1>

<?php $outline_include_0 = new Outline('include/test_include'); require $outline_include_0->get(); ?>

<?php if ($outline) { ?>
	<p>After include, contextual OutlineTest object's getTest() method returned: <?php echo $outline->getTest(); ?></p>
<?php } ?>

<h1>Testing the {foreach} command</h1>

<?php $odd = false; ?>

<?php foreach ($testarray as $color => $rgb) { ?>
	<p style="color:#<?php echo $rgb; ?>"><?php echo $color; ?></p>
<?php } ?>

<h1>Testing {else} with {foreach}</h1>

<?php foreach ($empty_array as $name => $value) { ?>
	<p>This will never execute</p>
<?php } if (empty($empty_array)) { ?>
	<p>This displays because the foreach'ed array was empty.</p>
<?php } ?>

<h1>Testing access to a class declared in 'test_require.php'</h1>

<?php $testobject = new TestClass(); ?>

<p>Variable access: <?php echo $testobject->variable; ?></p>
<p>Method access: <?php echo $testobject->method(); ?></p>

<h1>Testing the {if} command</h1>

<p>
	<?php for ($var=1; $var<=10; $var+=1) { ?>
		<?php echo $var; ?>
		<?php if ($var < 3) { ?>
			is less than three
		<?php } else if ($var > 8) { ?>
			is greater than eight
		<?php } else { ?>
			is between three and eight
		<?php } ?>
		<br />
	<?php } ?>
</p>

<h1>Testing the {cycle} and {next} commands</h1>

<p>
	<?php for ($var=1; $var<=9; $var+=1) { ?>
		<?php $outline_cycle_1 = isset($outline_cycle_1) ? $outline_cycle_1+1 : 1; if ($outline_cycle_1 == 1) { ?>
			one,
		<?php } else if ($outline_cycle_1 == 2) { ?>
			two,
		<?php } else if ($outline_cycle_1 == 3) { ?>
			three,
		<?php } if ($outline_cycle_1 == 3) { $outline_cycle_1 = 0; } ?>
	<?php } ?>
	...
</p>







<p>Contextual object's getTest() method returned: <?php echo $outline->getTest(); ?></p>

<h2>Page generated at <?php echo $testdate; ?></h2>
<h3>Displaying the compiled template using the {display} command:</h3>
<pre>
<?php echo htmlspecialchars(file_get_contents('compiled/__default__/test.tpl.php')); ?>
</pre>

</body>

</html>
<?php Outline::finish(); ?>