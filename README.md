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

To get a local server running, you will need:

* Apache (or Litespeed, etc.)
	* Debian packages: `apache2 libapache2-mod-php`
* PHP 8.1 and dependencies noted below
	* Debian packages: `php php-gd php-mbstring php-mysql php-xml`
* curl
* Node
	* I suggest using NVM and enabling [deep shell integration](https://github.com/nvm-sh/nvm#deeper-shell-integration) to
	  avoid using the wrong node version.
* You may want to install the faster Dart version of [Sass](https://sass-lang.com/install):
	* install the [Dart SDK](https://dart.dev/get-dart) and run `dart pub global activate sass`
	* Or with Homebrew: `brew install sass/sass/sass`

### Workflow

The repository includes configs for PHPStorm/IntelliJ.

The `main` branch contains the stable [prod site](https://forgetmenotshelter.org), while the `test` branch contains the
unstable [test site](http://fmnas.org).

When making changes, first create a development branch from the test branch: `git checkout -b dev test`

Then either merge this branch into `test` and push (`git checkout test; git pull; git merge dev; git push`), or push
your dev branch (`git push --set-upstream origin dev`) and create a pull request on GitHub to merge this branch
into `test`.

After testing the changes in the live test site environment, create a pull request on GitHub to merge the branch into
`main`.

### Watch and build

The PHPStorm config includes file watchers to automatically build files. To do this manually, run:

* `sass -w public:public` for public site stylesheets
* `tsc -w -p public` for public site scripts
* `vite build -w --mode development admin/client` for the admin site
	* Note that all three commands must be run before the admin site works properly.

Prefix these commands with `npx` to use the local version of the CLIs from Node.

#### Hot reloading for admin site

To run a Vue dev server with hot reloading, run `admin/dev.sh` in a terminal. This does the following:

* Instructs git to ignore changes to `admin/.htaccess`.
* Modifies `admin/.htaccess` to route /assets requests to localhost:3000 and use `loader.html` instead of `index.html`
  as the Vue entry point.
* Starts `vite admin/client` to serve Vue assets on localhost:3000.
* Waits for vite to die.
* Unmodifies `admin/.htaccess`.
* Instructs git not to ignore changes to `admin/.htaccess`.

If the script is terminated abnormally, run it again so the cleanup steps run.

`admin/loader.html` should be kept in sync with `admin/client/index.html`.
<!-- TODO [#142]: Add a status check for admin/dev.sh sync and teardown -->

## Deployment

### Automatic deployment

GitHub Actions are used to automatically deploy the `main` branch to the prod site and the `test` branch to the test
site. See the Workflow section above for more details.

### Manual deployment

#### Requirements (build server/local machine)

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
	* Needs shell access (with `shell_exec`) and the following executables in PATH:
		* `curl` to request caching uploaded images
* MySQL or MariaDB

#### Build

On the build machine:

* Install NPM build dependencies: `npm install --only=dev`
* Build the stylesheets for the public site: `npx sass --style=compressed public:public`
* Build the scripts for the public site: `npx tsc -p public`
* Build the admin site client: `npx vite build admin/client`
* Set the config values in config.php.
  * Run, for instance:
    ```shell
    npx ts-node handleparse.ts secrets/config.php.hbs --db_name=database --db_username=username --db_pass=password \
    --db_host=localhost --phpmailer_path="/path/to/PHPMailer" --html5_php_path="/path/to/html5-php" \
    --smtp_host=smtp.gmail.com --smtp_auth=true --smtp_security=tls --smtp_port=587 --smtp_username=me@gmail.com \
    --smtp_password=password
    ```
  * Alternatively, copy `secrets/config_sample.php` to `secrets/config.php` and update the configuration values
    manually.
* Update the public web templates in the `src/templates` and `src/errors` directories as desired.
  * The current templates rely on the presence of `/assets/adopted.jpg` and `/assets/logo.png` in the public site.

#### Deploy

For initial deployment, import `schema.sql` into the MySQL database

Upload the project and all built files to the web server.

* Point a domain to the `public` directory (this will be the public web root)
* Point a domain to the `admin` directory (this will be the admin interface root)

Additional static site content can be added by simply placing it in the `public` directory. Files and directories that
exist in this directory can be accessed at their natural URLs.

## Listings

Pet listing source code is stored as assets. These assets are **[Handlebars](https://handlebarsjs.com)** templates which
should yield **[Github Flavored Markdown](https://github.github.com/gfm/)**
(which [may include arbitrary HTML](https://github.github.com/gfm/#raw-html); the tagfilter extension is **not** used).

The template context is of the type Pet. Some useful properties include:

* name: string
* dob: string ("2020-03-28")
* sex: Sex
	* name: string ("male")
* species: Species
	* name: string ("cat")
	* plural: string ("cats")
* fee: string ("$40")

## Architecture

**PHP** is used as the backend language to simplify deployment to Dreamhost shared hosting.

[**Vue**](https:/vuejs.org) 3 is used in the admin interface. The public site is vanilla PHP, TypeScript compiled to
vanilla JS, and SCSS.

On the server side, listings are first compiled with [lightncandy](https://github.com/zordius/lightncandy), then parsed
with [Parsedown](https://parsedown.org/). Any PHP code embedded in listings will **not** be executed on the server. The
resulting HTML is cached (TODO); the cached assets are automatically deleted when listings are updated through the admin
interface, but must be manually deleted if changes are made to the asset or corresponding database records outside the
admin interface.

The client-side editor is [Toast UI Editor](https://ui.toast.com/tui-editor/).

The layout templates in `src/templates` are written in vanilla PHP/HTML.

### Server-side dependencies

`src/generated.php` is automatically generated by `src/generator.php` when it is needed.
`src/generated.php` should be deleted when changes are made to the database outside the admin interface
or `src/generator.php` is updated.
