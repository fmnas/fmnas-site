# GCP services

These are Google Cloud Platform services used for tasks that DreamHost can't handle.

## Development

An IntelliJ/GoLand config is included in the fmnas-site project.

The "Run local servers" multirun workflow runs all the GCP services as well as a local Vite server for the admin site.

These functions are deployed by the deploy-gcp-{prod,test} GitHub Actions workflows.

## Granting roles to the service account

The service account needs the `roles/run.admin`, `roles/artifactregistry.admin`, `roles/cloudfunctions.admin`
and `roles/iam.serviceAccountUser` roles:

```shell
gcloud projects add-iam-policy-binding fmnas-automation --member="serviceAccount:github-actions@fmnas-automation.iam.gserviceaccount.com" --role=roles/run.admin
gcloud projects add-iam-policy-binding fmnas-automation --member="serviceAccount:github-actions@fmnas-automation.iam.gserviceaccount.com" --role=roles/artifactregistry.admin
gcloud projects add-iam-policy-binding fmnas-automation --member="serviceAccount:github-actions@fmnas-automation.iam.gserviceaccount.com" --role=roles/cloudfunctions.admin
gcloud projects add-iam-policy-binding fmnas-automation --member="serviceAccount:github-actions@fmnas-automation.iam.gserviceaccount.com" --role=roles/iam.serviceAccountUser
```
