These are my thoughts about building an extension for forms/widgets with Outline.

The general idea, is to facilitate form rendering, validation, and parsing, and to be able to build with all of these features from a single source. Some form frameworks attempt to automate, which leads to limitations - my objective is not to automate, but to simplify.

First, let me point out that such an extension would not be purely MVC, since business concerns such as validation and parsing would be configured in the template.

While it would be possible to build a form-handling framework that was strictly MVC, for example by separating configuration of widgets from the form template itself, this would probably not be practical, since features such as parsing and validation are logically tied 1:1 to the form elements in the view, and are not really concerns that can logically be separated at all.

Hence, the separation of concerns will happen within a widget class, where rendering of the widget, parsing, and validation, can be separated and fully MVC.

Form templates use widgets as a building block for a high-level specification that results in forms, parsers and validators.

### Practical Concerns ###

What would a form template look like?

First off, instead of using regular HTML form elements, we would use template-based commands and widgets - for example:

```
{form name="user" action=$user_action}
  <table>
    <tr>
      <td>Name:</td>
      <td>{widget:text name="name"}</td>
    </tr>
    <tr>
      <td>E-mail address:</td>
      <td>{widget:email name="email"}</td>
    </tr>
    <tr>
      <td>Password:</td>
      <td>{widget:password name="password"}</td>
    </tr>
  </table>
{/form}
```

The `{form}` block replaces the normal `<form>` tag, and produces basically the same output, but at the same time creates a form instance and attaches it to the template engine.

A set of widgets replace the standard form elements - the `{widget:text}` command, for example, replaces the `<input type="text">` element, but registers a widget instance and attaches it to the form object of the template engine.

Higher-order widgets (compared to HTML) can also be implemented - for example `{widget:email}` is an extension of `{widget:text}`, which inherits all of it's capabilities in terms of rendering and parsing, but adds simple validation that asserts that the user input resembles an e-mail address.

### Usage ###

Using a form template is different from using a regular template, because we are no longer just dealing with a presentation or view - a form adds behavior, usually parsing and validation.

Once the form has been rendered, in the same way we render any other template, we need to parse and validate the posted information, which means that we need a means of constructing the form and widget instances without rendering anything.

For example:

```
<?php

$tpl = new OutlineTpl('user_edit');

$form = $tpl->forms->user;

if ($form->validate()) {
  ...
}

?>
```

The forms plugin and widget framework aside, this relies on a couple of features not currently present in Outline.

First, the "forms" collection would be a "virtual" collection - not an actual object property, but a callback registered by the forms plugin, implemented using magic accessors. Accessing it actually invokes a method in the forms plugin, which returns an object, which in turn, implements a magic accessor that provides access to any forms implemented by that template.

Secondly, the template engine would need to support a new method, namely load(), which would load the template without rendering it. The `forms` accessor-callback would invoke this method to construct the form and widget instances.

### Validation, error handling and fieldsets ###

The form and widget classes must support form-related error handling, and error reporting.

Note that widgets, as such, can only implement widget-level validation, and not "high-level" validation based on rules about the values of other widgets. For example, if the value of one widget determines allowed values in another widget, this must be implemented in the form controller.

The two most common forms of error reporting include a list of errors at the top of the form, and inline error reporting, where errors related to a particular widget are displayed next to that widget, possibly with the widget itself highlighted by using a different background color, or or red border, etc.

These forms of error reporting must be supported, but we should attempt to leave the form rendering sufficiently open to support any kind of error handling.

To keep form generation as flexible as possible, we do not want to introduce any sort of fixed fieldset model. For example, many form generation frameworks associate a label and description with every widget, and often support only a single template for rendering each field. This leads to fast construction of forms, but often results in "bureaucratic" looking forms with little variation, and makes it hard to handle edge cases.

Using the `{block}` feature, we can avoid this rigid model, while offering the same flexibility and speed. And at the same time, we can implement any kind of error reporting. For example, here is the "user" form again, this time with inline error reporting and simple fieldsets with a title:

```
{block:field title='' widget='' error=''}
  {if $error}
    <tr>
      <td colspan="2">{$error}</td>
    </tr>
  {/if}
  <tr>
    <td>{$title}:</td>
    <td>{$widget}</td>
  </tr>
{/block}

{form name="user" action=$user_action block=field}
  <table>
    {widget:text title="Name" name="name"}
    {widget:email title="E-mail address" name="email"}
    {widget:password title="Password" name="password"}
  </table>
{/form}
```

Notice the addition of `block=field` in the form - this defines a default block to use when rendering widgets - each `{widget}` command can freely override the `block` attribute to handle edge cases. If no `block` argument is given to the {form} attribute, widgets will render normally, without using a block.

Note that a user-block intended for use with widgets needs to have the `widget` and `error` arguments, which are automatically provided by the `{widget}` command when rendering with a user-block.