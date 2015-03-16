# Outline 2.0 #

Outline 2.0 is currently in development. You can check out a development snapshot here:

http://php-outline.googlecode.com/svn/branches/2

With this version, I am attempting to more gracefully solve various design issues with the code architecture and API - issues that cannot be resolved while maintaining backwards compatibility.

The overall goal is to simplify integration with modern frameworks (such as my personal favorite, [Yii](http://www.yiiframework.com/)) by simplifying and opening up the API.

In many ways, the new branch will be leaner and simpler than the old branch - the goal is to make it deal **strictly** with templating, nothing else.

Hence, the new branch will be better suited for integration with third-party frameworks, but perhaps less suitable (out of the box) for stand-alone PHP development. At a later time, a view-class for stand-alone PHP development may be created around it, possibly adding plugins to support features from version 1, giving template compatibility (not API compatibility) with version 1 templates.

[Please feel free to contribute your thoughts and comments on the discussion board.](http://groups.google.com/group/php-outline/browse_thread/thread/4e70f750985c9eb5)


## Details ##

**restructured codebase** : the codebase has been reorganized and filenames have been standardized, e.g. `"OutlineUtil.php"`, not `"util.php"`. Plugins now reside in a subfolder, and a clear distinction between compiler-plugins and their runtime counterparts has been made.

**single-instance** : no more `new OutlineTpl()` for every template - a single instance of the engine can perform any number of renderings or compiles, without creating lots of instances. This will reduce overhead and make Outline even faster. A view-class for stand-alone PHP development may support instance-based views at a later time.

**improved configuration** : no more global/static configuration with define() - each instance of the engine can be configured as needed.

**helpers** : helpers do not fit into the current architecture - they were never really used for anything in version 1 in the first place, but they may or may not be supported in version 2.

**no caching engine** : the built-in caching engine has been dropped - caching decisions are not decisions that your template engine should make for you; modern frameworks provide much more flexible caching engines, offering many more choices of caching media and strategies. Support for uncached inserts has been dropped as well - segment caching, as support by Yii among other frameworks, can be used instead. A view-class for stand-alone PHP development may support these features at a later time. A plugin with Yii-specific commands should include support for "renderDynamic", the framework-generic equivalent of uncached inserts, probably using the same syntax offered in Outline version 1, possibly achieving backwards compatibility with version 1 templates.

**no filename strategies** : the engine no longer implements strategies for mapping of "template names" to filenames, resolving "roots", or adding file extensions, etc. The new API requires complete paths to source and destination files as arguments. A view-class for stand-alone PHP development may support a simplified API and with built-in filename strategy at a later time.

**no form-helper** : the form-helper, which was in early stage of development (in SVN), has  been dropped - I did not have a clear vision when I started building this, and have since realized that this is far beyond the scope of templating. Modern frameworks provide excellent form-builders, which can be integrated with the template engine by means of plugins and helpers.

_THE FOLLOWING IDEAS ARE NOT FINAL:_

**support for content-blocks** : to support features like `{content_for}`, commonly used by many frameworks, the engine will need to support some means of outputting not only the rendered template content, but also any named, captured blocks of content generated during rendering. A Yii-specific plugin should support "clips", the framework-generic equivalent of this feature.

**include behavior** : `{include}` will run templates in an isolated scope - that is, included templates will not be able to see their parent template's variables, unless these are passed-in explicitly. This is a design-decision which may make some aspects of templating slightly more terse, but will reduce the risk of template-variable overlap, giving more predictable results.

**compiler behavior** : for various reasons (`{include}` behavior in particular), compiled templates will consist of a function-declaration, rather than a flat PHP script. This guarantees that a template is loaded only once, even if rendered/included many times, and also opens up other possibilities for the future, such as static template variables (could be useful for things like alternation, e.g. with `{cycle}`), early template loading, and other performance-oriented strategies.


### Request For Comments ###

[Please feel free to contribute your thoughts and comments on the discussion board.](http://groups.google.com/group/php-outline/browse_thread/thread/4e70f750985c9eb5)