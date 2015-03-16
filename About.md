Outline is a lightweight "template engine" designed for PHP developers.

#### Why yet another template engine? ####

To me, in a lot of situations, the whole idea of a "template engine" in the typical sense of the word, actually makes very little sense - first of all because PHP itself arguably **is** a template engine.

_Why would you build another template engine on top of a template engine?_

From this philosophy, Outline was designed as an "extension" over PHP, a kind of pre-processor - whereas many of the large template engines currently around were (more or less) designed as a replacement for PHP itself, which makes even less sense.

My goal with Outline, is to do as little "re-inventing the wheel" as possible - rather than replacing or wrapping (and thus limiting) PHP functionality, Outline tries only to make existing php-syntax more accessible and convenient, in situations where focus is on presentation and layout; typically the HTML output.

The "engine" itself consists of about 300 lines of code, which means that there is barely any overhead when running an already previously compiled template.

The compiler is also extremely lightweight - the goal is to keep the entire engine under 1000 lines of code. (if you count the lines, you'll see there's more than a 1000, but this includes plenty of whitespace and comments)

The reason why Outline is so small, is that it does not implement any real parser or interpreter as such - the compiler basically consists of a simple template-to-PHP translation system, with basic structural syntax checks.

While some of the more convenient syntax is similar to that of large template engines such as [Smarty](http://smarty.php.net), a lot of the syntax is more similar to that of php, and validation of most of the php-syntax is left for php to do.

This makes Outline a good choice for php-developers, while non-programmers (html/css template designers) only need to learn a very small subset of simplified php-syntax - all in all, probably less than you would have to learn to make good use of any of the larger template engines around...

Like most template engines, Outline does not enforce separation of logic and presentation. While separation is something that any good developer should pay serious attention to, as always, this remains the responsibility of the developer - it's not something that you can expect (even large) template engines to do for you, or even, for that matter, to encourage.

## Important ##

Since Outline allows you to call _any_ user-defined or custom php-function directly, this type of template engine is **not** suitable for use in an application where non-trusted users are allowed to edit templates!

For applications with user-editable templates, Outline should **not** be seen as an alternative to large template engines, such as Smarty, which may implement security measures.

For applications developed by a closed team (or solo), however, in the author's opinion, Outline may be much simpler to work with than any of the larger engines.