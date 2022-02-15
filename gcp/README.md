# GCP services

These are Google Cloud Platform services used for tasks that DreamHost can't handle.

## Development

An IntelliJ/GoLand config is included in the fmnas-site project.

These functions are deployed by the deploy-gcp-{prod,test} GitHub Actions workflows.

## resize-image

This Cloud Run container resizes images in cases (such as HEIC and WebP) where the ImageMagick version on Dreamhost is 
too outdated to read the original image or otherwise fails.

### Manual deployment

```shell
cd gcp/resize-image
gcloud auth configure-docker us-central1-docker.pkg.dev
docker build -t us-central1-docker.pkg.dev/fmnas-automation/resize-image-docker/resize-image:test .
docker push us-central1-docker.pkg.dev/fmnas-automation/resize-image-docker/resize-image:test
gcloud run deploy resize-image-test \
  --image us-central1-docker.pkg.dev/fmnas-automation/resize-image-docker/resize-image:test
gcloud artifacts docker images list us-central1-docker.pkg.dev/fmnas-automation/resize-image-docker/resize-image \
  --include-tags | awk '$3 ~ /^2/ { print $2 }' | xargs -I % gcloud artifacts docker images delete \
  us-central1-docker.pkg.dev/fmnas-automation/resize-image-docker/resize-image@%
```

### Testing

```shell
curl -v -F height=200 -F 'image=@/path/to/in.jpg' https://resize-image-test.gcp.forgetmenotshelter.org > out.jpg
```

## image-size

This Cloud Run container gets the size of an image in cases (such as HEIC and WebP) where the ImageMagick version on 
Dreamhost is too outdated to read the image or otherwise fails.

## Granting roles to the service account

The service account needs the `roles/run.admin`, `roles/artifactregistry.admin`, and `roles/iam.serviceAccountUser` roles:

```shell
gcloud projects add-iam-policy-binding fmnas-automation --member="serviceAccount:github-actions@fmnas-automation.iam.gserviceaccount.com" --role=roles/run.admin
gcloud projects add-iam-policy-binding fmnas-automation --member="serviceAccount:github-actions@fmnas-automation.iam.gserviceaccount.com" --role=roles/artifactregistry.admin
gcloud projects add-iam-policy-binding fmnas-automation --member="serviceAccount:github-actions@fmnas-automation.iam.gserviceaccount.com" --role=roles/iam.serviceAccountUser
```
