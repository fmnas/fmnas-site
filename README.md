# fmnas-site

[![GCP (prod)](https://img.shields.io/endpoint?url=https://gist.githubusercontent.com/TortoiseWrath/e38e961e5c08b2bdf4d78c800d851203/raw/gcp-prod.json)](https://github.com/fmnas/fmnas-site/actions/workflows/deploy-gcp-prod.yml)
[![deploy (prod)](https://img.shields.io/endpoint?url=https://gist.githubusercontent.com/TortoiseWrath/e38e961e5c08b2bdf4d78c800d851203/raw/deploy-prod.json)](https://github.com/fmnas/fmnas-site/actions/workflows/deploy-prod.yml)
[![GCP (test)](https://img.shields.io/endpoint?url=https://gist.githubusercontent.com/TortoiseWrath/e38e961e5c08b2bdf4d78c800d851203/raw/gcp-test.json)](https://github.com/fmnas/fmnas-site/actions/workflows/deploy-gcp-test.yml)
[![deploy (test)](https://img.shields.io/endpoint?url=https://gist.githubusercontent.com/TortoiseWrath/e38e961e5c08b2bdf4d78c800d851203/raw/deploy-test.json)](https://github.com/fmnas/fmnas-site/actions/workflows/deploy-test.yml)
[![public site status](https://img.shields.io/website?down_color=critical&label=public&up_color=090&url=https%3A%2F%2Fforgetmenotshelter.org)](https://forgetmenotshelter.org)
[![admin status](https://img.shields.io/website?down_color=inactive&down_message=%233&label=admin&up_color=090&up_message=up&url=https%3A%2F%2Fadmin.forgetmenotshelter.org)](https://admin.forgetmenotshelter.org)
[![ASM status](https://img.shields.io/website?down_color=critical&label=asm3&up_color=090&url=http%3A%2F%2Fasm.forgetmenotshelter.org)](http://asm.forgetmenotshelter.org)
[![backup status](https://img.shields.io/endpoint?url=https://gist.githubusercontent.com/TortoiseWrath/e38e961e5c08b2bdf4d78c800d851203/raw/backups.json)](https://github.com/fmnas/fmnas-site/actions/workflows/backups.yml)  
[![gamma progress](https://img.shields.io/github/milestones/progress/fmnas/fmnas-site/5?color=5021da)](https://github.com/fmnas/fmnas-site/milestone/5)
[![release progress](https://img.shields.io/github/milestones/progress/fmnas/fmnas-site/3?color=5021da)](https://github.com/fmnas/fmnas-site/milestone/3)
[![handoff progress](https://img.shields.io/github/milestones/progress/fmnas/fmnas-site/7?color=5021da)](https://github.com/fmnas/fmnas-site/milestone/7)
[![other issues](https://img.shields.io/github/milestones/issues-open/fmnas/fmnas-site/6?color=5021da&label=other)](https://github.com/fmnas/fmnas-site/milestone/6)

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

### Disclaimer

Many files in this repository contain a Google license header. **This is not an officially supported Google product.**
Google owns the copyright to much of this code because it was written by a Google employee.

## Development

### Requirements

To get a local server running, you will need:

* Apache (or Litespeed, etc.)
	* Debian packages: `apache2 libapache2-mod-php`
* PHP 8.1 and dependencies noted below
	* Debian packages: `php php-gd php-mbstring php-mysql php-xml php-imagick php-curl php-sqlite3`
* MySQL or MariaDB
	* Debian packages: `mariadb-server` then run `mysql_secure_installation`
* cURL on PATH
	* Debian packages: `curl`
* Node
	* I suggest using NVM and enabling [deep shell integration](https://github.com/nvm-sh/nvm#deeper-shell-integration) to
	  avoid using the wrong node version.
* [Composer](https://getcomposer.org/download/)
* [Docker and Compose](https://docs.docker.com/compose/install/linux/#install-using-the-repository)
* You may want to install the faster Dart version of [Sass](https://sass-lang.com/install):
	* install the [Dart SDK](https://dart.dev/get-dart) and run `dart pub global activate sass`
* [Go](https://go.dev/doc/install)

The [Dart SDK](https://dart.dev/get-dart) is required to run the blackbox tests locally.

### Workflow

The repository includes configs for IntelliJ IDEA Ultimate.

The "Run local servers" multirun workflow runs all the GCP services as well as a local Vite server for the admin site.

The `main` branch contains the stable [prod site](https://forgetmenotshelter.org), while the `test` branch contains the
unstable [test site](http://fmnas.org).

When making changes, first create a development branch from the test branch: `git checkout -b dev test`

Then either merge this branch into `test` and push (`git checkout test; git pull; git merge dev; git push`), or push
your dev branch (`git push --set-upstream origin dev`) and create a pull request on GitHub to merge this branch
into `test`.

After testing the changes in the live test site environment, create a pull request on GitHub to merge the branch into
`main`.

### Initial setup

If developing on a Windows host, please clone the repo in WSL instead of on the Windows filesystem to avoid breaking
file permissions.

I also strongly recommend running IntelliJ under WSL using JetBrains Gateway rather than running IntelliJ directly on
the Windows host. I tried the latter setup and found it was difficult to get all run configs, file watchers, etc.
working correctly and ultimately gave up after trying to get it to use a remote Dart SDK to run the tests. Just run the
IDE on Linux. It's easier.

#### Apache setup

First, you'll want to create two hostnames in your hosts file - one for the public site and one for the admin site. I
use `public.fmnas` and `admin.fmnas`.

On the hosts where you'll be running a browser, add to the hosts file
(/etc/hosts or C:\Windows\System32\drivers\etc\hosts):

```
::1 public.fmnas
::1 admin.fmnas
```

(connecting to it via IPv4 doesn't seem to work right on Windows hosts. I don't care enough to figure out why) 

Whitelist the repo directory in /etc/apache2/apache2.conf (or equivalent listed under "Document Roots"
at http://localhost):

```apacheconf
<Directory /path/to/fmnas-site/>
	Options Indexes FollowSymLinks
	AllowOverride All
	Require all granted
</Directory>
```

To make everything work smoothly, we have to enable HTTPS on the local server, so generate local certificates for each
hostname.

Install mkcert (`sudo apt install mkcert && mkcert -install`).

To trust the certs on a Windows host with mkcert under WSL, also install mkcert on Windows and share the CA certs
between the two installations. You can use Chocolatey if you're into that sort of thing, but I just installed it from
the binaries at https://github.com/FiloSottile/mkcert/releases
(run `.\mkcert-*-windows-amd64.exe -install` to install the CA).

Run `mkcert -CAROOT` on each installation to find where the CA certs are kept (or just trust me that they're in
%LocalAppData%\mkcert and ~/.local/share/mkcert), then copy the generated certs from Windows to WSL (going the other way
doesn't work, I guess because it copies the certs somewhere else internally on Windows?).

Then create the certificates for each site somewhere on the Linux host:

```shell
mkcert public.fmnas
mkcert admin.fmnas
```

and create the sites in an apache site conf (e.g. /etc/apache2/sites-enabled/fmnas.conf) like so:

```
<VirtualHost *:443>
    ServerName public.fmnas
    DocumentRoot /path/to/fmnas-site/public
    SSLEngine on
    SSLCertificateFile "/path/to/public.fmnas.pem"
    SSLCertificateKeyFile "/path/to/public.fmnas-key.pem"
</VirtualHost>
<VirtualHost *:443>
    ServerName admin.fmnas
    DocumentRoot /path/to/fmnas-site/admin
    SSLEngine on
    SSLCertificateFile "/path/to/admin.fmnas.pem"
    SSLCertificateKeyFile "/path/to/admin.fmnas-key.pem"
</VirtualHost>
```

and enable it (e.g. `sudo a2ensite fmnas`).

Give www-data group ownership of the stuff so it can write to it:
```shell
chgrp -R www-data /path/to/fmnas-site 
```

Enable the important modules:
```shell
sudo a2enmod php8.2
sudo a2enmod rewrite
sudo a2enmod ssl
```

Then restart apache (`sudo service apache2 restart`).

#### Database setup

Create a local database, import the schema, and add appropriate constants:

```shell
mysql -u root -p -e 'CREATE DATABASE fmnas_test;'
mysql -u root -p fmnas_test < schema.sql
ts-node handleparse.ts config.sql.hbs --address="address to animal shelter" --admin_domain="admin.fmnas" \
--default_email_user="mail" --fax="fax number" --longname="formal name of shelter" --phone="phone number" \
--phone_intl="phone number (international prefixed)" --public_domain="public.fmnas" \
--shortname="name of animal shelter" --transport_date="2023-09-16"
mysql -u root -p fmnas_test < config.sql
```

I usually just use the root user for local development because I'm a naughty boy, but if you want you can make one.

#### Initial build

Run:

* `npm install` for Node dependencies
* Create the config file (change values as appropriate):
  ```shell
  ts-node handleparse.ts secrets/config.php.hbs --db_name=database --db_username=username --db_pass=password \
  --db_host=localhost \
  --image_size_endpoint=https://localhost:50000 --resize_image_endpoint=https://localhost:50001
  ```
* `composer install` for PHP dependencies
* `sass public:public` for public site stylesheets
* `npm run build` for public site scripts
* `npx vite build --mode development admin/client` for the admin site

#### WSL port forwarding

To access a vite dev server, you'll have to forward the port from the Windows host to the VM:

```powershell
netsh interface portproxy add v4tov4 listenport=50080 connectport=50080 connectaddress=(wsl hostname -I)
```

#### GCP setup

To get GCP ready for local development, you'll need:

* The [gcloud CLI](https://cloud.google.com/sdk/docs/install)
* golang 1.19+ ([instructions](https://tecadmin.net/install-go-on-debian/))
* ImageMagick 7+ ([instructions](https://www.tecmint.com/install-imagemagick-on-debian-ubuntu/)) 

#### Final steps and testing

Navigate to https://public.fmnas to generate `generated.php` which is needed by the admin site.

Navigate to https://admin.fmnas (or whatever hostname you used) and insert a fake animal.

### Watch and build

The IntelliJ config includes file watchers to automatically build files. To do this manually, run:

* `sass -w public:public` for public site stylesheets
* `tsc -w -p public` for public site scripts
* `vite build -w --mode development admin/client` for the admin site
* `cd tests/blackbox && dart run build_runner watch` for the blackbox tests

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

### Tests

#### Blackbox tests

There are blackbox tests for the GCP services located in tests/blackbox.

#### Repo state tests

The `.github/workflows/check-repo.yml` workflow checks that the repo is in a good state before merging. All of the
following checks must pass before merging a PR into `main`:

* All file watchers are enabled
	* Checks that `.idea/watcherTasks.xml` contains no disabled file watchers (these can be inadvertently disabled by
	  IntelliJ due to local configuration errors).
* All files added by Sean contain a copyright header
	* Checks that a copyright header is included in all source code files added by Sean.
	* Required because Sean's commits are copyrighted by Google and they want these.
	* If Sean did not author any commits in the PR, this should always pass.
* `admin/.htaccess` looks like `admin/dev.sh` is not running
	* Checks that `admin/.htaccess` doesn't contain any uncommented `dev.sh add` lines or commented `dev.sh remove` lines.
* The uploaded branch has origin/main and origin/test as ancestors.

### TODOs

The `.github/workflows/todo-issues.yml` workflow creates issues from TODOs added in `tests`, and closes issues for
removed TODOs.

Don't try to change the name of one of the issues this creates. It will get changed back on the next push.

### Backups

The `.github/workflows/backups.yml` workflow is used for nightly backups of untracked files on the FMNAS server.

### Updating schema.sql and config.sql.hbs

schema.sql is exported with `mysqldump --no-create-db --no-data [dbname] | grep -v DEFINER > schema.sql`.

config.sql.hbs is made from a config.sql exported
with `mysqldump --no-create-db --no-create-info --skip-triggers --skip-extended-insert [dbname] > config.sql`.

## Deployment

### Automatic deployment

GitHub Actions are used to automatically deploy the `main` branch to the prod site and the `test` branch to the test
site. See the Workflow section above for more details.

The following workflows in `.github/workflows` are used for deployment:

* `deploy-gcp-{prod,test}.yml` - Deploys Google Cloud Platform services from gcp/.
* `deploy-{prod,test}.yml` - Builds and deploys the website to Dreamhost, then invalidates server caches as necessary.

#### Secrets

* `TEST_SFTP_HOST`: The SFTP host for deploying the test site (`fmnas.org`)
* `TEST_SFTP_USER`: The SSH user for `TEST_SFTP_HOST`
* `TEST_SFTP_PASS`: The SSH password for `TEST_SFTP_USER`
* `TEST_SITE_ROOT`: The absolute path to the test site root on `TEST_SFTP_HOST` (one level above `public`, with no
  trailing slash)
* `PROD_SFTP_HOST`: The SFTP host for deploying the prod site (`forgetmenotshelter.org`)
* `PROD_SFTP_USER`: The SSH user for `PROD_SFTP_HOST`
* `PROD_SFTP_PASS`: The SSH password for `PROD_SFTP_USER`
* `PROD_SITE_ROOT`: The absolute path to the test site root on `TEST_SFTP_HOST` (one level above `public`, with no
  trailing slash)
* `TEST_DB_NAME`: The MySQL database for the test site (`fmnas_testing`)
* `PROD_DB_NAME`: The MySQL database for the prod site (`fmnas`)
* `DB_USERNAME`: The MySQL user for `TEST_DB_NAME` and `PROD_DB_NAME`
* `DB_PASS`: The MySQL password for `DB_USERNAME`
* `DB_HOST`: The MySQL server (`mysql.forgetmenotshelter.org`)
* `HTTP_CREDENTIALS`: HTTP basic auth credentials for the admin API (`username:password`)
* `TEST_ADMIN_DOMAIN`: The domain of the test admin site (`admin.fmnas.org`)
* `PROD_ADMIN_DOMAIN`: The domain of the prod site (`admin.forgetmenotshelter.org`)
* `RESIZE_IMAGE_REPO`: The Artifact Registry repository
  for `resize-image` (`us-central1-docker.pkg.dev/fmnas-automation/resize-image-docker`)
* `RESIZE_IMAGE_TEST_ENDPOINT`: The HTTPS endpoint mapped
  to `resize-image-test` (`https://resize-image-test.gcp.forgetmenotshelter.org`)
* `RESIZE_IMAGE_PROD_ENDPOINT`: The HTTPS endpoint mapped
  to `resize-image` (`https://resize-image.gcp.forgetmenotshelter.org`)
* `IMAGE_SIZE_REPO`: The Artifact Registry repository
  for `image-size` (`us-central1-docker.pkg.dev/fmnas-automation/image-size-docker`)
* `IMAGE_SIZE_TEST_ENDPOINT`: The HTTPS endpoint mapped
  to `image-size-test` (`https://image-size-test.gcp.forgetmenotshelter.org`)
* `IMAGE_SIZE_PROD_ENDPOINT`: The HTTPS endpoint mapped
  to `image-size` (`https://image-size.gcp.forgetmenotshelter.org`)
* `ASM_WEB_DB`: The MySQL database with replicated ASM tables (see #314) for the import backend (`asm_web`)
* `ASM_WEB_HOST`: The MySQL host for `ASM_WEB_DB` (`fmnas.forgetmenotshelter.org`)
* `ASM_WEB_USER`: The MySQL user for `ASM_WEB_DB` (`fmnas_asm`)
* `ASM_WEB_PASS`: The MySQL password for `ASM_WEB_USER`
* `TORTOISEWRATH_GIST_TOKEN`: A PAT to update gists created by @TortoiseWrath (used for badges)
* `PERSISTENCE_TOKEN`: A token for @aaimio/set-peristent-value (used for badges)
* `PROD_GA_ID`: The Google Analytics ID for the prod site (`G-3YRWV82YZX`)
* `TEST_GA_ID`: The Google Analytics ID for the test site (`G-E73F6XEPY7`)

##### Org secrets

* `ASM_HOST`: The SSH hostname for the ASM server
* `ASM_SSH_KEY`: A private key to get into `ASM_HOST`
* `ASM_KNOWN_HOSTS`: Known hosts entry for `ASM_HOST`
* `ASM_SSH_USER`: The SSH user for `ASM_HOST`
* `ASM_DB_USER`: The MySQL user for ASM on `ASM_HOST`
* `ASM_DB_PASS`: The MySQL password for `ASM_DB_USER`
* `ASM_DB`: The MySQL database for ASM on `ASM_HOST`
* `S3_PROVIDER`: The S3 provider for backups (`Scaleway`)
* `S3_ACCESS_KEY`: The access key for `S3_PROVIDER`
* `S3_SECRET_KEY`: The secret key for `S3_PROVIDER`
* `S3_REGION`: The bucket region for `S3_PROVIDER` (`fr-par`)
* `S3_ENDPOINT`: The endpoint for `S3_BUCKET` (`s3.fr-par.scw.cloud`)
* `ASSETS_BUCKET`: The assets bucket name (`fmnas-assets`)
* `DATA_BUCKET`: The data bucket name (`fmnas-data`)
	* This should have a lifecycle rule to delete old backups
* `BLOG_DB`: The blog database name on `DB_HOST`
* `GCP_IDENTITY_PROVDER`: The GCP identity provider
  for [Workload Identity Federation](https://github.com/google-github-actions/auth#setup) (`projects/602944024639/locations/global/workloadIdentityPools/github-actions/providers/github-actions-provider`)
* `GCP_SERVICE_ACCOUNT`: The GCP service account
  for [Workload Identity Federation](https://github.com/google-github-actions/auth#setup) (`github-actions@fmnas-automation.iam.gserviceaccount.com`)
* `GCP_PROJECT`: The GCP project name (`fmnas-automation`)
* `GCP_REGION`: The GCP project region (`us-central1`)

##### Repo secrets

### Manual deployment

#### Requirements (build server/local machine)

* Node & NPM (see development requirements above)

#### Requirements (web server)

* Linux (any POSIX-compatible OS should work)
* Apache (Litespeed or any other web server with .htaccess and PHP support should work)
* PHP 8.1
	* ImageMagick
	* GD
		* libJPEG
		* libPNG
	* mysqli
	* mbstring
	* imagick
	* curl
	* php-xml
	* sqlite3
	* Composer
	* Needs shell access (with `shell_exec`) and the following executables in PATH:
		* `curl` to request caching uploaded images
* MySQL or MariaDB
* Composer

#### Build

On the build machine:

* Install NPM build dependencies: `npm install --only=dev`
* Install PHP dependencies: `composer build`
* Build the stylesheets for the public site: `npx sass --style=compressed public:public`
* Build the scripts for the public site: `npm run build`
* Build the admin site client: `npx vite build admin/client`
* Set the config values in config.php.
	* Run, for instance:
	  ```shell
		npx ts-node handleparse.ts secrets/config.php.hbs --db_name=database --db_username=username --db_pass=password \
		--db_host=localhost \
		--image_size_endpoint=https://image-size.gcp.forgetmenotshelter.org \
		--resize_image_endpoint=https://resize-image.gcp.forgetmenotshelter.org
		```
	* Alternatively, copy `secrets/config_sample.php` to `secrets/config.php` and update the configuration values
	  manually.
* Update the public web templates in the `src/templates` and `src/errors` directories as desired.
	* The current templates rely on the presence of `/assets/adopted.jpg` and `/assets/logo.png` in the public site.

#### Deploy

For initial deployment, import `schema.sql` into the MySQL database, then compile `config.sql.hbs` with config values
and import that.

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

**PHP** is used as the backend language to simplify deployment to Dreamhost shared hosting. This was a mistake.

This was a mistake. Now, a bunch of things are running on GCP anyway since it turns out old-school shared hosting wasn't
quite powerful enough to do everything we wanted. So it's a horrible mess of PHP code calling GCP functions and vice
versa. C'est la vie.

[**Vue**](https:/vuejs.org) 3 is used in the admin interface.

The public site is vanilla PHP, TypeScript compiled to vanilla JS, and SCSS.

On the server side, listings are first compiled with [lightncandy](https://github.com/zordius/lightncandy), then parsed
with [Parsedown](https://parsedown.org/). Any PHP code embedded in listings will **not** be executed on the server. The
resulting HTML is cached; the cached assets are automatically deleted when listings are updated through the admin
interface, but must be manually deleted if changes are made to the asset or corresponding database records outside the
admin interface.

<!-- The admin interface WYSIWYG editor is [Toast UI Editor](https://ui.toast.com/tui-editor/).-->

The layout templates in `src/templates` are written in vanilla PHP/HTML.

The public site targets ES2019 and Chrome 79/Safari 11.1/Firefox 75.  
The admin site targets ES2020 and Chrome 80/Firefox 74.

### Server-side dependencies

`src/generated.php` is automatically generated by `src/generator.php` when it is needed.
`src/generated.php` should be deleted when changes are made to the database outside the admin interface
or `src/generator.php` is updated.
