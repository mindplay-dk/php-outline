# To-do #

Ideas for a coming release:

  * Lazy array resolution using the @ operator, anywhere.

  * Support for a `{with}` block - in conjunction with the @ operator, this would enable you to change the scope of the @ operator, e.g.: `{with $product}{@name} price: {@price}{/with}` would be equivalent to `{@product.name} price: {@product.price}`

Ideas for the future:

  * add `{debug}` command - helper function for template developers; displays a list of all assigned template variables.

  * Add `{default}` command, e.g.: `{default $name='value'}` assigns 'value' to $name, if $name does not have a value.

  * Add template selection, e.g.: `{include "more_specific","less_specific"}` would include the first template if it exists, otherwise fall back to the second template, etc. Alternatively, `{select $tpl from "more_specific","less_specific"}` would select the templatename and place it in `$tpl`. Alternatively, user block selection, based on the same principle. Alternatively, support for section inheritance and overrides. (the latter could break line-number relationships between template source and compiled template)

Ideas I'm having second thoughts about:

  * Introduce namespacing for blocks and commands, e.g.: `{use form}{input name="test"}{/use}` would be equivalent to `{form:input name="test"}`, and blocks would have the opportunity to change the default namespace. Namespacing would reduce the chance of collisions between user plugins.

  * Add {section} block, e.g.: `{section head}...{/section}` captures the content within the section separately from the output of the template - that content can then be retrieved with `$outline->getSection('head')`. This command would enable a more structured way to capture partial output to be inserted (by means of code) other places than a page's main output.

  * Apply default modifiers to variable-output, e.g.:

> `raw output: {$message}, escaped output: {$message|html}`

> could instead be written as:

> `{apply html}raw output: {=$message}, escaped output: {$message}{/apply}`

> Unlike {modify}, which applies modifiers to the output, {apply} can be used to add default modifiers to all applicable template tags.

> The "=" symbol in {=$message} indicates that you want to bypass any default modifiers - in this example, _don't_ apply the html modifier.

> This is useful when reusing the same modifier a lot, particularly when you need to escape or encode lots of variables for HTML output.

> The syntax is up for discussion. Also, whether default modifiers should be applied before or after local modifiers. Probably after - so that default modifiers always run last, and can't interfere with other local modifiers.