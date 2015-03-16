# Outline #

Outline is a template engine for php.

### Download the [current release version](http://code.google.com/p/php-outline/wiki/History) from "featured downloads" on the right ###

Or click the "Source" tab to obtain the latest (unstable/development) version.

#### Notes about [version 2.0](http://code.google.com/p/php-outline/wiki/Outline_2) ####

Version 2.0 development has been stalled for some time, but the engine itself is actually stable. There has been no official release, because the engine itself is not terribly useful stand-alone; version 2.0 was designed to integrate with third-party frameworks, and as such does not provide many of the features that made version 1 useful in plain PHP projects, where it worked not only as a template engine, but provided other view-engine features like caching and view/file-mapping. Development will resume when the need arises.

<table cellpadding='0' border='0' cellspacing='0'><tr><td><a href='https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=LWLRLSPJBK8NL&lc=US&item_name=mindplay%2edk&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted'><img src='https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif' alt='Please Donate' border='0' /></a></td><td><wiki:gadget url="http://www.ohloh.net/p/50589/widgets/project_partner_badge.xml" height="53"  border="0" /></td><td><wiki:gadget url="http://www.ohloh.net/p/50589/widgets/project_users_logo.xml" height="43"  border="0" /></td></tr></table>

Unlike other template engines, Outline is fast and light _by design_ - rather than trying to reinvent the wheel, this engine tries to leverage the power of the template engine you already know: php.

Outline templates, once compiled, execute nearly as fast as native php templates. The engine itself consists of a very tiny runtime, resulting in minimal CPU and memory overhead - benchmarks have shown that Outline is probably faster than any other native php template engine.

## Project Goals ##

Outline implements a practical, familiar template syntax, in about 1000 lines of code, giving you close to all of the functionality found in major template engines such as [Smarty](http://smarty.php.net), [Template Lite](http://templatelite.sourceforge.net) or [Savant](http://phpsavant.com) - all of the usual stuff like commands, blocks, modifiers, inserts, compiled templates, multi-level caching and so on.

### Key differences from other engines ###

The major difference between Outline and most of the major template engines, is that it makes no attempts to implement template security, and it does not validate the parts of the syntax that it borrows from php. This makes it primarily a template engine for use by php developers and _trusted_ template developers - you can't safely allow guests to write or upload custom templates.

Apart from that, many commands have a considerably shorter syntax than most major template engines - and a syntax that is more familiar to php developers. Outline takes the practical, simple parts of php, simplifies their syntax, and combines it with the most common template syntax elements from large engines.

<wiki:gadget url="http://google-code-feed-gadget.googlecode.com/svn/trunk/gadget.xml" up\_feeds="http://groups.google.com/group/php-outline/feed/rss\_v2\_0\_msgs.xml" width="780" height="340" border="0" up\_showaddbutton="0"/>