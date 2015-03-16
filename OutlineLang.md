# Considerations #

Simple retrieval of language constants by ID is not a practical way to implement translation, because it results in illegible templates, e.g.:

`<h2>{lang id="welcome"}</h2>`

To know what the language-constant named "welcome" contains, you would have to look it up in a translation table somewhere. This is highly impractical.

Another strategy is to simply replace content based on the content itself, without an identifier:

`<h2>{lang text="Welcome, $username!"}</h2>`

This strategy is actually worse - if you change the text even slightly, you will no longer be able to look up the translations for that string. The association to the translations are lost.

# Proposal #

To fulfill both of these requirements (legible templates and reliable associations), I propose a system where an ID, as well as the original text, is embedded in the template.

As for the exact syntax, maybe it could look something like this:

`<h2>{lang:welcome "Welcome, $username!"}</h2>`

By default, the ID "welcome" will be specific to the current template, but it should also be possible to use IDs that are global, so that certain strings can be shared between templates - like this:

`<h2>{lang:app:product "Product Details:"}</h2>`

This indicates a translation of a string with the ID "product", from the collection named "app".

## Issues ##

What happens if the same ID is used for two different strings? For example:

`<h2>{lang:app:welcome "Welcome, $username!"}</h2>`

And then, somewhere else, in the same template:

`<h2>{lang:app:welcome "Hello, $username!"}</h2>`

They will both translate to the same string, because of the ID. This could cause inconsistencies - if the original text is different from one template to another, the strings will come out different in the original language, but with the same translation.

What would the PHP output look like?

`<?php printf(OutlineLang::translate("app:welcome", "Hello, %s!"), $username); ?>`

There is a number of problems with this idea...

**UNFINISHED**