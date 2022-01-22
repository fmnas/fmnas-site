# fmnas-site

This repository contains source code for the website of the
[Forget Me Not Animal Shelter](https://forgetmenotshelter.org)
in Republic, WA.

## License

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
License as published by the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program. If not,
see <https://www.gnu.org/licenses/>.

## Development

### Requirements

The repository includes configs for PHPStorm/IntelliJ.

To get a local server running, you will need:

* Apache (or Litespeed, etc.)
  * Debian packages: `apache2 libapache2-mod-php`
* PHP 8.1 and dependencies noted below
  * Debian packages: `php php-gd php-mbstring php-mysql php-xml`
* curl
* Node
  * Suggest using NVM: `nvm install` (this will install and use the Node version specified in .nvmrc)
* You may want to install the faster Dart version of [Sass](https://sass-lang.com/install):
  * install the [Dart SDK](https://dart.dev/get-dart) and run `dart pub global activate sass`
  * Or with Homebrew: `brew install sass/sass/sass`

### Watch and build

The PHPStorm config includes file watchers to automatically build files. To do this manually, run:

* `sass -w public:public` for public site stylesheets

## Deployment

### Automatic deployment

TODO: Set up automatic deployment

### Manual deployment

#### Requirements (build server/local machine)

<!-- @todo Add requirements for vue build server --> 

* Node & NPM (see development requirements above)

#### Requirements (web server)

* Linux (any POSIX-compatible OS should work)
* Apache (Litespeed or any other web server with .htaccess and PHP support should work)
* PHP 8.1
	* GD
		* libJPEG
		* libPNG
	* mysqli
	* mbstring
	* php-xml
	* PHPMailer (tested with 6.4.1)
	* [html5-php](https://github.com/Masterminds/html5-php) (tested with 2.7.5)
	* Needs shell access (with `shell_exec`) and `curl` in PATH to automatically fetch server-side dependencies
* MySQL (MariaDB should work)

#### Build

On the build machine:

* Install NPM build dependencies: `npm install --only=dev`
* Build the stylesheets for the public site: `sass --style=compressed public:public`
* Copy `secrets/config_sample.php` to `secrets/config.php` and update the configuration values
* Update the public web templates in the `src/templates` and `src/errors` directories as desired
  * The current templates rely on the presence of `/assets/adopted.jpg` and `/assets/logo.png` in the public site

<!-- @todo Minify JS and HTML -->

#### Deploy

For initial deployment, import `schema.sql` into the MySQL database

Upload the project and all built files to the web server.

* Point a domain to the `public` directory (this will be the public web root)
* Point a domain to the `admin` directory (this will be the admin interface root)

Additional static site content can be added by simply placing it in the `public` directory. Files and directories that
exist in this directory can be accessed at their natural URLs.

## Listings

(TODO)

Pet listing source code is stored as assets. These assets are **[Handlebars](https://handlebarsjs.com)** templates which
should yield **[Github Flavored Markdown](https://github.github.com/gfm/)** (
which [may include arbitrary HTML](https://github.github.com/gfm/#raw-html); the tagfilter extension is **not** used).

The following variables are likely to prove useful in listings:

* **pet**: Pet
	* Properties:
		* name: string
		* dob: string ("2020-03-28")
		* sex: Sex
			* name: string ("male")
		* species: Species
			* name: string ("cat")
			* plural: string ("cats")
		* fee: string ("$40")
	* Methods:
		* age(): string ("2 years old")
		* sex(): string ("male")
		* species(): string ("kitten")
* **litter**: Litter | null (TODO)
	* Properties: pets (a list of Pets)

## Technologies

**PHP** is used as the backend language to simplify deployment to Dreamhost shared hosting.

[**Vue**](https:/vuejs.org) 3 is used in the admin interface. To ensure that deployment and maintenance remain as simple
as possible due to the limited resources available, JS/CSS are used rather than TS/SCSS, etc.,
and [vue3-sfc-loader](https://github.com/FranckFreiburger/vue3-sfc-loader)
is used instead of Webpack and node.js.

On the server side, listings are first compiled with [lightncandy](https://github.com/zordius/lightncandy), then parsed
with [Parsedown](https://parsedown.org/). Any PHP code embedded in listings will **not** be executed on the server (
@todo verify). The resulting HTML is cached (TODO); the cached assets are automatically deleted when listings are
updated through the admin interface, but must be manually deleted if changes are made to the asset or corresponding
database records outside the admin interface.

<!-- The client-side editor is [Toast UI Editor](https://ui.toast.com/tui-editor/). Need an extension for Handlebars support. Maybe use StackEdit or something instead.
CKEditor, Simditor -->
<!-- https://softwarerecs.stackexchange.com/questions/5746/markdown-editor-for-windows-with-live-rendering-in-the-editing-pane-not-in-a-se -->

The layout templates in `src/templates` are written in vanilla PHP/HTML.

### Server-side dependencies

`src/generated.php` is automatically generated by `src/generator.php` when it is needed.
`src/generated.php` should be deleted when changes are made to the database outside the admin interface
or `src/generator.php` is updated.

The listing parser (`src/parser.php`) depends on
[**lightncandy**](https://github.com/zordius/lightncandy) and
[**Parsedown**](https://parsedown.org/). These should be fetched by `src/dependencies.php` when they are first required.

To update the dependencies, run `src/update_dependencies.php` or simply delete the existing `src/ligntncandy`
and `src/parsedown`
directories. (@todo Will need a way to automate periodic updates someday)

### Client-side dependencies

The admin interface imports [**Vue**](https://vuejs.org) from unpkg.
<!-- The listing editor imports 
[**Toast UI Editor**](https://ui.toast.com/tui-editor/)
from the Toast CDN. -->

The error pages in `src/errors` use images from [**http.cat**](https://http.cat).
