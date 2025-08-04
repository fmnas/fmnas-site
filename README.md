# fmnas-site

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

## Architecture

### Public site

The public site is generated from the files in `public/`. When the repo is updated, this is done using the
`public-site` workflow.

First, a **[Handlebars](https://handlebarsjs.com)** input object is read from the `config.json` found in the GCS bucket
and used to compile *.hbs files to HTML. (See `config_sample.json` for the schema.)

Generally, template `public/foo.hbs` will be compiled to a file `/foo` and uploaded to GCS.

Templates in `public/templates` are used for dynamic content:

* `listings.hbs` and `listing.hbs` for objects from the Firestore `listings` collection.
  The Markdown `description` of each listing is first rendered in-place by passing first through Handlebars (with
  additional partials from `admin/templates`) and then through [marked](https://github.com/markedjs/marked).
* `blog.hbs` and `blog_post.hbs` for objects from the Firestore `blog` collection.
* `form.hbs` for objects from the Firestore `forms` collection.

Template rendering occurs within the Cloud Run function `ssg`, which generates file groups requested from a PubSub
queue. This queue is written by the GitHub Action public-site when the repository is updated, and by the other functions
in `/functions/template_rendering.ts` in response to GCS and Firestore updates.

Typescript and SCSS files are compiled to .js and .css within the GitHub Action `public-site`.

[**Vue**](https://vuejs.org) 3 is used in the admin interface. The admin site is still deployed to DreamHost for now.

## Development

### Admin site dev server

Make sure you have Application Default Credentials:

```shell
gcloud auth application-default login --impersonate-service-account dev-site@fmnas-automation.iam.gserviceaccount.com
```

Then run the dev server with:

```shell
project=fmnas-automation bucket=fmnas_test database=fmnas-test \
RESIZE_ENDPOINT=$(gcloud run services describe resize-photo-test --format 'value(status.url)' --region us-west1) \
asm_db_host=asm.forgetmenotshelter.org asm_db=asm asm_db_user=fmnas_web asm_db_pass=... \
npm --workspace=admin run dev
```

## Deployment

### Manual deployment

Install Node dependencies:

```shell
nvm use
npm install
```

Compile stylesheets:

```shell
npx sass --style=compressed public:public
```

Compile scripts:

```shell
npm run build
```

Upload the generated files to the GCS bucket:

```shell
gcloud storage rsync ./public gs://fmnas_test/ --recursive --cache-control no-cache
```

Deploy the `resize-photo` Cloud Function:

```shell
gcloud run deploy resize-photo-test \
  --source functions \
  --function resize-photo \
  --base-image nodejs22 \
  --region us-west1 \
  --no-allow-unauthenticated \
  --timeout 10 --concurrency 3
```

Deploy the admin site:

```shell
gcloud beta run deploy fmnas-admin-test \
  --source admin \
  --region us-west1 \
  --no-allow-unauthenticated --iap \
  --automatic-updates --base-image nodejs22 \
  --timeout 3600 \
  --set-env-vars "project=fmnas-automation" \
  --set-env-vars "bucket=fmnas_test" \
  --set-env-vars "database=fmnas-test" \
  --set-env-vars "RESIZE_ENDPOINT=$(gcloud run services describe resize-photo-test --format 'value(status.url)' --region us-west1)" \
  --set-env-vars "asm_db_host=asm.forgetmenotshelter.org" \
  --set-env-vars "asm_db=asm" \
  --set-env-vars "asm_db_user=fmnas_web" \
  --set-env-vars 'asm_db_pass=...'
```

GET /api/render on the admin site to generate the static files.

### Automatic deployment

GitHub Actions are used to automatically deploy the `main` branch to the prod site and the `test` branch to the test
site. See the Workflow section above for more details.

The following workflows in `.github/workflows` are used for deployment:

* `deploy-gcp-{prod,test}.yml` - Deploys Google Cloud Platform services from gcp/.
* `deploy-{prod,test}.yml` - Builds and deploys the website to Dreamhost, then invalidates server caches as necessary.

#### Variables

The following [repository variables](https://github.com/fmnas/fmnas-site/settings/variables/actions) are required:

* `TEST_DATABASE`: The Firestore database ID for the test site (`gcloud firestore databases list`)
* `PROD_DATABASE`: The Firestore database ID for the prod site (`gcloud firestore databases list`)
* `TEST_BUCKET`: The GCP bucket name for the test site (`gcloud compute backend-buckets list`)
* `PROD_BUCKET`: The GCP bucket name for the prod site (`gcloud compute backend-buckets list`)
* `GCP_REGION`: The GCP region for Cloud Run (i.e. `us-west1`)
* `GCP_PROJECT`: The GCP project name (`fmnas-automation`)
* `GCP_IDENTITY_PROVIDER`: The GCP identity provider
  for [Workload Identity Federation](https://github.com/google-github-actions/auth#setup) (
  `projects/602944024639/locations/global/workloadIdentityPools/github-actions/providers/github-actions-provider`)
* `GCP_SERVICE_ACCOUNT`: The GCP service account
  for [Workload Identity Federation](https://github.com/google-github-actions/auth#setup) (
  `github-actions@fmnas-automation.iam.gserviceaccount.com`)
* `ASM_HOST`: The hostname for the ASM server (`asm.forgetmenotshelter.org`)
* `ASM_DB`: The MySQL database for ASM on `ASM_HOST` (`asm`)
* `ASM_DB_USER`: The MySQL user for ASM on `ASM_HOST` (`fmnas_web`)

#### Secrets

The following [repository secrets](https://github.com/fmnas/fmnas-site/settings/secrets/actions) are required:

* `ASM_DB_PASS`: The MySQL password for `ASM_DB_USER`

### GCP notes

Worth noting these routing rules on the [load balancer](https://console.cloud.google.com/net-services/loadbalancing/details/httpAdvanced/fmnas-lb?project=fmnas-automation) for forgetmenotshelter.org:

```yaml
defaultService: projects/fmnas-automation/global/backendBuckets/fmnas-prod
name: prod-matcher
routeRules:
  - description: Redirect old Cats pages
    matchRules:
      - prefixMatch: /Cats
    priority: 3
    urlRedirect:
      prefixRedirect: /cats
  - description: Redirect old Dogs pages
    matchRules:
      - prefixMatch: /Dogs
    priority: 4
    urlRedirect:
      prefixRedirect: /dogs
  - description: Redirect old Application
    matchRules:
      - prefixMatch: /Application
    priority: 5
    urlRedirect:
      pathRedirect: /application
  - description: Redirect old application subpages
    matchRules:
      - prefixMatch: /application/
    priority: 6
    urlRedirect:
      pathRedirect: /application
  - description: Redirect assets/assets
    matchRules:
      - prefixMatch: /assets/assets/
    priority: 7
    urlRedirect:
      prefixRedirect: /assets/
  - description: Rewrite index.htm
    matchRules:
      - pathTemplateMatch: /index.htm
    priority: 100
    service: projects/fmnas-automation/global/backendBuckets/fmnas-prod
    routeAction:
      urlRewrite:
        pathTemplateRewrite: /
  - description: Rewrite */index.htm
    matchRules:
      - pathTemplateMatch: /{a=*}/index.htm
    priority: 101
    service: projects/fmnas-automation/global/backendBuckets/fmnas-prod
    routeAction:
      urlRewrite:
        pathTemplateRewrite: /{a}
  - description: Rewrite */*/index.htm
    matchRules:
      - pathTemplateMatch: /{a=*}/{b=*}/index.htm
    priority: 102
    service: projects/fmnas-automation/global/backendBuckets/fmnas-prod
    routeAction:
      urlRewrite:
        pathTemplateRewrite: /{a}/{b}
  - description: Rewrite index.php
    matchRules:
      - pathTemplateMatch: /index.php
    priority: 103
    service: projects/fmnas-automation/global/backendBuckets/fmnas-prod
    routeAction:
      urlRewrite:
        pathTemplateRewrite: /
  - description: Rewrite */index.php
    matchRules:
      - pathTemplateMatch: /{a=*}/index.php
    priority: 104
    service: projects/fmnas-automation/global/backendBuckets/fmnas-prod
    routeAction:
      urlRewrite:
        pathTemplateRewrite: /{a}
  - description: Rewrite */*/index.php
    matchRules:
      - pathTemplateMatch: /{a=*}/{b=*}/index.php
    priority: 105
    service: projects/fmnas-automation/global/backendBuckets/fmnas-prod
    routeAction:
      urlRewrite:
        pathTemplateRewrite: /{a}/{b}
  - description: Rewrite trailing slash
    matchRules:
      - pathTemplateMatch: /{path=*}/
    priority: 106
    service: projects/fmnas-automation/global/backendBuckets/fmnas-prod
    routeAction:
      urlRewrite:
        pathTemplateRewrite: /{path}
  - description: Rewrite trailing slash on subdirectory
    matchRules:
      - pathTemplateMatch: /{a=*}/{b=*}/
    priority: 107
    service: projects/fmnas-automation/global/backendBuckets/fmnas-prod
    routeAction:
      urlRewrite:
        pathTemplateRewrite: /{a}/{b}
  - description: default static bucket
    matchRules:
      - pathTemplateMatch: /**
    priority: 200
    service: projects/fmnas-automation/global/backendBuckets/fmnas-prod
```
