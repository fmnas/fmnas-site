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
* wkhtmltopdf
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

Prefix these commands with `npx` to use the local version of the CLIs from Node.

## Deployment

### Automatic deployment

GitHub Actions are used to automatically deploy the `main` branch to the prod site and the `test` branch to the test
site. See the Workflow section above for more details.

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
	* Needs shell access (with `shell_exec`) and the following executables in PATH:
		* `curl` to automatically fetch server-side dependencies
		* `wkhtmltopdf` to render PDF versions of applications
* MySQL (MariaDB should work)

#### Build

On the build machine:

* Install NPM build dependencies: `npm install --only=dev`
* Build the stylesheets for the public site: `npx sass --style=compressed public:public`
* Build the scripts for the public site: `tsc -p public`
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

<!-- @todo Minify JS and HTML -->

#### Deploy

For initial deployment, import `schema.sql` into the MySQL database

Upload the project and all built files to the web server.

* Point a domain to the `public` directory (this will be the public web root)
* Point a domain to the `admin` directory (this will be the admin interface root)

Additional static site content can be added by simply placing it in the `public` directory. Files and directories that
exist in this directory can be accessed at their natural URLs.
