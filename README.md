# fmnas-site

[![GCP (prod)](https://img.shields.io/endpoint?url=https://gist.githubusercontent.com/TortoiseWrath/e38e961e5c08b2bdf4d78c800d851203/raw/gcp-prod.json)](https://github.com/fmnas/fmnas-site/actions/workflows/deploy-gcp-prod.yml)
[![deploy (prod)](https://img.shields.io/endpoint?url=https://gist.githubusercontent.com/TortoiseWrath/e38e961e5c08b2bdf4d78c800d851203/raw/deploy-prod.json)](https://github.com/fmnas/fmnas-site/actions/workflows/deploy-prod.yml)
[![GCP (test)](https://img.shields.io/endpoint?url=https://gist.githubusercontent.com/TortoiseWrath/e38e961e5c08b2bdf4d78c800d851203/raw/gcp-test.json)](https://github.com/fmnas/fmnas-site/actions/workflows/deploy-gcp-test.yml)
[![deploy (test)](https://img.shields.io/endpoint?url=https://gist.githubusercontent.com/TortoiseWrath/e38e961e5c08b2bdf4d78c800d851203/raw/deploy-test.json)](https://github.com/fmnas/fmnas-site/actions/workflows/deploy-test.yml)  
[![gamma progress](https://img.shields.io/github/milestones/progress/fmnas/fmnas-site/5)](https://github.com/fmnas/fmnas-site/milestone/5)
[![release progress](https://img.shields.io/github/milestones/progress/fmnas/fmnas-site/3)](https://github.com/fmnas/fmnas-site/milestone/3)
[![handoff progress](https://img.shields.io/github/milestones/progress/fmnas/fmnas-site/7)](https://github.com/fmnas/fmnas-site/milestone/7)
[![other issues](https://img.shields.io/github/milestones/issues-open/fmnas/fmnas-site/6?color=blue&label=none)](https://github.com/fmnas/fmnas-site/milestone/6)  
[![public site status](https://img.shields.io/website?down_color=critical&label=public&up_color=5021da&url=https%3A%2F%2Fforgetmenotshelter.org)](https://forgetmenotshelter.org)
![ASM status](https://img.shields.io/website?down_color=critical&label=asm3&up_color=009d00&url=http%3A%2F%2Fasm.forgetmenotshelter.org)
![admin status](https://img.shields.io/website?down_color=important&label=admin&up_color=informational&url=https%3A%2F%2Fadmin.forgetmenotshelter.org)
[![backup status](https://img.shields.io/endpoint?url=https://gist.githubusercontent.com/TortoiseWrath/e38e961e5c08b2bdf4d78c800d851203/raw/backups.json)](https://github.com/fmnas/fmnas-site/actions/workflows/backups.yml)



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
	* Debian packages: `php php-gd php-mbstring php-mysql php-xml php-imagick php-curl`
* cURL on PATH
* Node
	* I suggest using NVM and enabling [deep shell integration](https://github.com/nvm-sh/nvm#deeper-shell-integration) to
	  avoid using the wrong node version.
* Composer
* You may want to install the faster Dart version of [Sass](https://sass-lang.com/install):
	* install the [Dart SDK](https://dart.dev/get-dart) and run `dart pub global activate sass`

### Workflow

The repository includes configs for IntelliJ/PHPStorm.

The following plugins are required for full-stack development in IntelliJ IDEA Ultimate:
* [.ignore](https://plugins.jetbrains.com/plugin/7495--ignore)
* [Apache config (.htaccess)](https://plugins.jetbrains.com/plugin/6834-apache-config--htaccess-)
* [Cloud Code](https://plugins.jetbrains.com/plugin/8079-cloud-code)
* [File Watchers](https://plugins.jetbrains.com/plugin/7177-file-watchers)
* [Go](https://plugins.jetbrains.com/plugin/9568-go)
* [Handlebars/Mustache](https://plugins.jetbrains.com/plugin/6884-handlebars-mustache)
* [Multirun](https://plugins.jetbrains.com/plugin/7248-multirun)
* [PHP](https://plugins.jetbrains.com/plugin/6610-php)
* [Vue.js](https://plugins.jetbrains.com/plugin/9442-vue-js)
  
The "Run local servers" multirun workflow runs all the GCP services as well as a local Vite server for the admin site.

The `main` branch contains the stable [prod site](https://forgetmenotshelter.org), while the `test` branch contains the
unstable [test site](http://fmnas.org).

When making changes, first create a development branch from the test branch: `git checkout -b dev test`

Then either merge this branch into `test` and push (`git checkout test; git pull; git merge dev; git push`), or push
your dev branch (`git push --set-upstream origin dev`) and create a pull request on GitHub to merge this branch
into `test`.

After testing the changes in the live test site environment, create a pull request on GitHub to merge the branch into
`main`.

The `.github/workflows/sync-test.yml` workflow merges `main` back into `test` after each merged PR. You should
then `git fetch` and rebase your dev branch onto `origin/test` before another PR. Or if developing directly on `test`
, `git pull` to get the merge commit.

### Initial build

After checking out the repository, run:

* `npm install` for Node dependencies
* Create the config file (change values as appropriate):
  * ```shell
    ts-node handleparse.ts secrets/config.php.hbs --db_name=database --db_username=username --db_pass=password \
    --db_host=localhost --smtp_host=localhost --smtp_auth=false --smtp_port=25 \
    --image_size_endpoint=https://localhost:50000 --resize_image_endpoint=https://localhost:50001 \
    --print_pdf_endpoint=https://localhost:50002 --minify_html_endpoint=https://localhost:50003
  ```
* `composer install` for PHP dependencies
* `sass public:public` for public site stylesheets
* `tsc -p public` for public site scripts
* `vite build --mode development admin/client` for the admin site

### Watch and build

The IntelliJ config includes file watchers to automatically build files. To do this manually, run:

* `sass -w public:public` for public site stylesheets
* `tsc -w -p public` for public site scripts
* `vite build -w --mode development admin/client` for the admin site

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

## Deployment

### Automatic deployment

GitHub Actions are used to automatically deploy the `main` branch to the prod site and the `test` branch to the test
site. See the Workflow section above for more details.

The following workflows in `.github/workflows` are used for deployment:

* `deploy-gcp-{prod,test}.yml` - Deploys Google Cloud Platform services from gcp/.
* `deploy-{prod,test}.yml` - Builds and deploys the website to Dreamhost, and regenerates src/generated.php.
* `regenerate-images-{prod,test}.yml` - Calls the API to regenerate cached images when the image scaling code (
  src/resize.php) changes.

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
* `HTTP_CREDENTIALS`: The HTTP basic auth credentials to get into the test site and `regen_images`
  endpoint (`username:password`)
* `TEST_SITE_URL`: The URL of the test site (`http://fmnas.org/`)
* `PROD_SITE_URL`: The URL of the prod site (`https://forgetmenotshelter.org/`)
* `TEST_IMAGES_API`: The URL to the test site `regen_images` endpoint (`https://admin.fmnas.org/api/regen_images`)
* `PROD_IMAGES_API`: The URL to the prod site `regen_images`
  endpoint (`https://admin.forgetmenotshelter.org/api/regen_images`)
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
* `PRINT_PDF_ENDPOINT`: The HTTPS endpoint for `print-pdf` (`https://us-central1-fmnas-automation.cloudfunctions.net/print-pdf`)
* `PRINT_PDF_TEST_ENDPOINT`: The HTTPS endpoint for `print-pdf-test` (`https://us-central1-fmnas-automation.cloudfunctions.net/print-pdf-test`)
* `MINIFY_HTML_ENDPOINT`: The HTTPS endpoint for `minify-html` (`https://us-central1-fmnas-automation.cloudfunctions.net/minify-html`)
* `MINIFY_HTML_TEST_ENDPOINT`: The HTTPS endpoint for `minify-html-test` (`https://us-central1-fmnas-automation.cloudfunctions.net/minify-html-test`)
* `ASM_WEB_DB`: The MySQL database with replicated ASM tables (see #314) for the import backend (`asm_web`) 
* `ASM_WEB_HOST`: The MySQL host for `ASM_WEB_DB` (`fmnas.forgetmenotshelter.org`)
* `ASM_WEB_USER`: The MySQL user for `ASM_WEB_DB` (`fmnas_asm`)
* `ASM_WEB_PASS`: The MySQL password for `ASM_WEB_USER`
* `TORTOISEWRATH_GIST_TOKEN`: A PAT to update gists created by @TortoiseWrath (used for badges)
* `PERSISTENCE_TOKEN` A token for @aaimio/set-peristent-value (used for badges)

##### Org secrets

* `SMTP_HOST`: The SMTP host to use when sending email (`smtp.gmail.com`)
* `SMTP_AUTH`: Whether `SMTP_HOST` requires auth (`true`)
* `SMTP_SECURITY`: Security type for `SMTP_HOST` (`tls`)
* `SMTP_PORT`: The port for `SMTP_HOST` (`587`)
* `SMTP_USERNAME`: The username for `SMTP_HOST`
	* FMNAS: Use the apps account
* `SMTP_PASSWORD`: The password for `SMTP_HOST`
* `TODO_ACTIONS_MONGO_URL`: The [MongoDB](https://cloud.mongodb.com/v2/) connector URL for
  todo-actions (`mongodb+srv://...`)
	* FMNAS: Google log in with the apps account, use the FMNASGitHubTodos database
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
	* Composer
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
		--db_host=localhost --smtp_host=smtp.gmail.com --smtp_auth=true --smtp_security=tls --smtp_port=587 \
		--smtp_username=me@gmail.com --smtp_password=password \
		--image_size_endpoint=https://image-size.gcp.forgetmenotshelter.org \
		--resize_image_endpoint=https://resize-image.gcp.forgetmenotshelter.org \
		--print_pdf_endpoint=https://us-central1-fmnas-automation.cloudfunctions.net/print-pdf \
  	--minify_html_endpoint=https://us-central1-fmnas-automation.cloudfunctions.net/minify-html
		```
	* Alternatively, copy `secrets/config_sample.php` to `secrets/config.php` and update the configuration values
	  manually.
* Update the public web templates in the `src/templates` and `src/errors` directories as desired.
	* The current templates rely on the presence of `/assets/adopted.jpg` and `/assets/logo.png` in the public site.

#### Deploy

For initial deployment, import `schema.sql` into the MySQL database.

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
