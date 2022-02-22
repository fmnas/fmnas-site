Include this file at the top of a form page, then set values of $this->formConfig.

The `<form>` element will be replaced with an `<article>` element. All `<script>` elements will be removed unless they
have an explicit falsy data-remove attribute.

Within the `<form>` element:
All `<input>` and `<select>` elements will be replaced with `<span>` elements, except:
`input[type="button"]`, `input[type="submit"]`, `input[type="reset"]`, `input[type="password"]`, `input[type="hidden"]`,
and `input[type="image"]` will be removed unless they have an explicit falsy `data-remove` attribute. See below for
special considerations for `input[type="file"]`, `input[type="radio"]`, and `input[type="checkbox"]`. All `<textarea>`
elements will be replaced with `<pre>` elements. All `<fieldset>` elements will be replaced with `<section>` elements
containing `<h3>` headers. All `<button>` elements will be removed unless they have an explicit falsy `data-remove`
attribute, in which case they will be replaced with `<span>` elements. All `<label>` elements will be replaced
with `span` elements. Any inner text will itself be in a `span` element with a
`data-type="label-text"` attribute as well, and the outer `span` element will have a `data-input-type` attribute with
the type of the corresponding `input` element if found.

TODO [#73]: Replace datalist elements with ul elements (hidden by default). All `<datalist>` elements will be removed
unless they have an explicit falsy `data-remove` attribute, in which case they will be replaced with `<ul>`
elements. `<option>` elements therein will be replaced with `<li>` elements, and any contained within an `<optgroup>`
element will have a `data-optgroup` attribute with the `optgroup` label (the `optgroup` is removed).

All generated elements will have a `data-type` attribute with the original tag (`"form"`, `"input"`, etc.). For elements
generated from an `<input>` element, the `data-input-type` attribute will be set with the original type.

Elements with a truthy `data-remove` attribute will be removed from the rendered email. Elements with a
truthy `data-ignore` attribute will not be modified, though they will still be removed if an ancestor has a
truthy `data-remove`.

Inputs with a name ending in `[]` will be removed in the rendered email unless they have an explicit falsy `data-remove`
attribute. To handle such repeated inputs, add an element with a `data-foreach` attribute. For example:

```html

<ul>
	<li><input type="text" name="cats[]" value="Stephen">
	<li><input type="text" name="cats[]" value="Jonathan">
	<li><input type="text" name="cats[]" value="Mittens">
	<li data-foreach="cats">
</ul>
```

`data-foreach` may also be used with `data-as`, in which case the value will be accessible through `data-value`:

```html

<li data-foreach="cats" data-as="cat">Cat named <span data-value="cat"></span>
```

The value can also be applied to an element's `href` attribute using `data-href`:

```html
<a data-href="customer_website">Visit the customer website</a>
```

Note that `data-value` and `data-href` are mutually exclusive.

To add output only used in the rendered email, use the `data-hidden` attribute. Elements with this attribute will be
hidden on the form page by injected CSS.

```html
<p data-hidden>Thank you for submitting the form! A copy of the received data is below.
```

To use values from the form to generate output, use `data-value`, `data-if`, `data-operator`, `data-rhs`, and
`data-rhs-value`:

```html
<input type="checkbox" name="display_message" value="1">
<p data-if="display_message">This text will be displayed if the checkbox is checked.

	<input type="number" name="number_of_horses">
<p data-if="number_of_horses" data-operator="gt" data-rhs="100">Whoa! That's a lot of horses.
<p data-if="number_of_horses" data-operator="lt" data-rhs="0">That is an unreasonable number of horses.

	<input type="number" name="number_of_dogs">
<p data-if="number_of_horses" data-rhs-value="number_of_dogs">There are the same number of horses and dogs.
```

Valid values for `data-operator` are: `gt`, `lt`, `eq`, `ge`, `le`, `ne`

To use values from the arrays provided by the callbacks in `$this->formConfig`, append `-config` to the attribute where
a form value or plain text value would otherwise be provided:

```html
<p data-if-config="user-email">Thanks for submitting the form!
<p data-if-config="operator-email"><span data-value="name"></span> submitted the following form.
<p data-if-config="user-email" data-operator="eq" data-rhs-config="operator-email">Hey, that's me!
```

To transform the inner HTML of an element after all other processing is complete, add a `data-transformer` attribute:

```html
<input type="email" name="email" value="example@example.com" data-transformer="email-link">
```

This must correspond to an entry in the `$this->formConfig->transformers` associative array, which is a closure that
transforms one HTML string into another HTML string. The default `$this->formConfig` includes the following
transformers:

- `email-link`: wraps an email address in a mailto link (useful with `input[type="email"]`)
- `tel-link`: wraps a phone number in a tel link (useful with `input[type="tel"]`)
- `link`: wraps a URL in a link (useful with `input[type="url"]`)
  Note that the output of a data transformer must be valid in the `text/xml` serialization of HTML5 (XHTML5), not just
  as `text/html`. In practice, this generally just means you must close tags: `<br />` rather than `<br>`.

`data-transformer-if`, `data-transformer-if-config`, `data-transformer-operator`, `data-transformer-rhs`,
`data-transformer-rhs-value`, and `data-transformer-rhs-config` may be used to conditionally apply the transformer
specified by `data-transformer`.

All elements with the following attributes will be hidden on the form page by injected CSS, unless a falsy value is
explicitly given for `data-hidden`:

* `data-hidden`
* `data-foreach`
* `data-foreach-config`
* `data-if`
* `data-if-config`
* `data-value`
* `data-value-config`
* `data-href`
* `data-href-config`

When processing a file input with `data-foreach`, `data-value`, etc., the file metadata array is passed through a file
transformer to compute the output value. The file transformers are closures in `$this->formConfig->fileTransformers`.
The default file transformers are:

* `name`: the filename (default if no `data-file-transformer` is given)
* `size`: the file size in bytes
* `type`: the file MIME type
* `path`: the path to the file on the server
* `dump`: a dump of the file metadata
* `image`: an inline image (unsupported by many mail clients)
* `thumbnail`: an inline thumbnail with height 64 (unsupported by many mail clients)

To replace filenames with a hash of the file, set `FormEmailConfig::hashFilenames` to `HashOptions::NO` (default),
`HashOptions::SAVED_ONLY`, `HashOptions::EMAIL_ONLY`, or `HashOptions::YES`. If not `NO`, `$metadata["original"]` will
be the original filename and `$metadata["hash"]` will be the hash filename. If `EMAIL_ONLY` or `YES`
, `$metadata["name"]` will be changed to the hashed name.

To output the files to a directory on the server, set `FormEmailConfig::fileDir` to a directory, or a closure that takes
the file metadata array. This will create `$metadata["path"]` for the file.

To prevent the files from being attached to the email, set `FormEmailConfig::attachFiles` to false, or a closure that
takes the file metadata array. If this directory is served by a web server, you may want to create a link to the file.
Since the script is unaware of where the directory can be found, you will have to write your own file transformer to do
this:

```php
$this->formConfig->fileTransformers["url"] = function(array $metadata): string {
  return "https://example.com/uploads/" . $metadata["name"];
}
```

Note that the output of a file transformer must be valid in the `text/xml` serialization of HTML5 (XHTML5), not just
as `text/html`. In practice, this generally just means you must close tags: `<br />` rather than `<br>`.

The file metadata array has the standard `$_FILES` fields, plus `"input"` (the input name) and sometimes `"original"`,
`"hash"`, `"path"`, and `"attached"`.

You may want to prevent files from being attached only if they exceed a certain size, or have some other characteristic.
This can be accomplished by setting `FormEmailConfig::attachFiles` to a closure:

```php
$formEmailConfig->attachFiles = function(array $metadata): bool {
   if ($metadata["size"] > 1048576) {
     // Don't attach any files over 1MB.
     return false;
   }
   $remaining_size = 0;
   for ($_FILES as $file_input) {
     if (is_array($file_input["size"])) {
       foreach ($file_input["size"] as $size) {
         if ($size < 1048576) {
           $remaining_size += $size;
         }
       }
     } else if ($file_input["size"] < 1048576) {
       $remaining_size += $file_input["size"];
     }
   }
   // Don't attach any files if the combined size of all files under 1MB is over 5MB.
   if ($remaining_size > 5 * 1048576) {
     return false;
   }
   return true;
}
```

The same applies to `FormEmailConfig::fileDir`. If this returns a truthy string for a file, it is used as the fileDir.
`FormEmailConfig::hashFilenames` may also be a closure that returns a HashOptions value.

Note that the `attachFiles` closure is the last to be calculated, so it has access to the hash and path values if
needed. For example, you could attach all files that were not saved to the server with:

```php
$formEmailConfig->attachFiles = function(array $metadata): bool {
  return !($file["path"] ?? false);
}
```

To convert files to another type or otherwise transform the files themselves, use a file converter. File converters are
closures `FormEmailConfig::fileConverter` that take a reference to the metadata array. Note that this conversion takes
place _before_ `FormEmailConfig::hashFilenames`. By default, this conversion is scoped to a single email. To use the
converted files as the input files for subsequent emails, set `FormEmailConfig::globalConversion` to true. You may also
use `FormEmailConfig::fileConverters` to specify a converter that applies to the complete list of files rather than one
file at a time (useful for parallel processing of files with multi-curl). This should update `$file["tmp_name"]` if a
new file is created.

To validate files server-side before saving or attaching them, set `$this->formConfig->fileValidator` to a closure. Any
files that return `false` will be ignored.

```php
$this->formConfig->fileValidator = function(array $metadata): bool {
  if ($metadata["error"]) {
    return false;
  }
  $finfo = new finfo(FILEINFO_MIME_TYPE);
  return str_starts_with($finfo->file($metadata["tmp_name"]), "image/");
}
```

The default fileValidator simply returns `!$metadata["error"]`.

For radio buttons and checkboxes, the output span for each button will contain the submitted value and have
`data-selected="1"` or `data-selected="0"`. Additionally, the output span for any associated labels will have the
data-selected attribute as well. These conditions should be handled by a stylesheet so the rendered email is coherent.
Some suggested CSS rules:

```css
span[data-type="input"][data-input-type="radio"],
span[data-type="input"][data-input-type="checkbox"],
span[data-type="label"][data-input-type="radio"][data-selected="0"] {
	display: none;
}

span[data-type="label"][data-input-type="checkbox"][data-selected="0"]::before {
	content: "☐ ";
}

span[data-type="label"][data-input-type="checkbox"][data-selected="1"]::before {
	content: "☑ ";
}
```

For any other changes to the email before sending it, a custom penultimate transformation may be supplied to
FormEmailConfig::emailTransformation. This closure will be given the DOMDocument and the attachments array.

TODO [#69]: Add unit tests for the form processor. TODO [#70]: Split the form processor into a separate repo?
TODO [#340]: Refactor the form processor into a forms directory. TODO [#341]: Make server-side style inlining a default
behavior of the form processor.
