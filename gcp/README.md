# GCP services

These are Google Cloud Platform services used for tasks that DreamHost can't handle.

## Development

An IntelliJ/GoLand config is included in the fmnas-site project.

The "Run local servers" multirun workflow runs all the GCP services as well as a local Vite server for the admin site.

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

I suggest running this with 2 vCPU / 4 GiB / 4 concurrency to reduce OOM and optimize costs.

### Running locally

The "Run resize-image" IntelliJ run configuration uses the Cloud Code extension to run the service on port 591.

If it doesn't work with managed dependencies, try installing the latest minikube, skaffold, and kubectl locally and
setting the dependency paths manually in the extension settings.

This uses an automatically selected ephemeral port. To forward a specific port to the service, use
`kubectl port-forward service/resize-image $PORT:8080`. Another IntelliJ run configuration is included to forward
port 50000 to resize-image. This must be run while image-size is running.

### Testing

```shell
curl -v -F height=200 -F 'image=@/path/to/in.jpg' http://localhost:591 > out.jpg
```

## image-size

This Cloud Run container gets the size of an image in cases (such as HEIC and WebP) where the ImageMagick version on
Dreamhost is too outdated to read the image or otherwise fails.

### Manual deployment

```shell
cd gcp/image-size
gcloud auth configure-docker us-central1-docker.pkg.dev
docker build -t us-central1-docker.pkg.dev/fmnas-automation/resize-image-docker/image-size:test .
docker push us-central1-docker.pkg.dev/fmnas-automation/image-size-docker/image-size:test
gcloud run deploy image-size-test \
  --image us-central1-docker.pkg.dev/fmnas-automation/image-size-docker/image-size:test
gcloud artifacts docker images list us-central1-docker.pkg.dev/fmnas-automation/image-size-docker/image-size \
  --include-tags | awk '$3 ~ /^2/ { print $2 }' | xargs -I % gcloud artifacts docker images delete \
  us-central1-docker.pkg.dev/fmnas-automation/image-size-docker/image-size@%
```

I suggest running this with 1 vCPU / 2 GiB / 4 concurrency to reduce OOM and optimize costs.

### Running locally

The "Run image-size" IntelliJ run configuration uses the Cloud Code extension to run the service. 

If it doesn't work with managed dependencies, try installing the latest minikube, skaffold, and kubectl locally and 
setting the dependency paths manually in the extension settings.

This uses an automatically selected ephemeral port. To forward a specific port to the service, use
`kubectl port-forward service/image-size $PORT:8080`. Another IntelliJ run configuration is included to forward
port 50001 to image-size. This must be run while image-size is running.

### Testing

```shell
curl -v -F 'image=@/path/to/in.jpg' http://localhost:8008 
```

## print-pdf

This Cloud Function converts an uploaded HTML file to pdf.

### Manual deployment

```shell
cd gcp/print-pdf
npm run compile
gcloud functions deploy print-pdf-test --entry-point printPdf
```

### Running locally

The "Run print-pdf on port 50002" IntelliJ run configuration runs `PORT=50002 npm run watch` in the `gcp/print-pdf`
directory.

### Testing

```shell
curl -v -F 'html=@/path/to/in.html' http://localhost:50002 > out.pdf 
```

## Granting roles to the service account

The service account needs the `roles/run.admin`, `roles/artifactregistry.admin`, `roles/cloudfunctions.admin`
and `roles/iam.serviceAccountUser` roles:

```shell
gcloud projects add-iam-policy-binding fmnas-automation --member="serviceAccount:github-actions@fmnas-automation.iam.gserviceaccount.com" --role=roles/run.admin
gcloud projects add-iam-policy-binding fmnas-automation --member="serviceAccount:github-actions@fmnas-automation.iam.gserviceaccount.com" --role=roles/artifactregistry.admin
gcloud projects add-iam-policy-binding fmnas-automation --member="serviceAccount:github-actions@fmnas-automation.iam.gserviceaccount.com" --role=roles/cloudfunctions.admin
gcloud projects add-iam-policy-binding fmnas-automation --member="serviceAccount:github-actions@fmnas-automation.iam.gserviceaccount.com" --role=roles/iam.serviceAccountUser
```

## minify-html

This Cloud Function minifies an uploaded static HTML file and inlines stylesheets.

@import rules are removed.

### Manual deployment

```shell
cd gcp/minify-html
npm run compile
gcloud functions deploy minify-html-test --entry-point minify
```

### Running locally

The "Run minify-html on port 50003" IntelliJ run configuration runs `PORT=50003 npm run watch` in the `gcp/minify-html`
directory.

### Testing

```shell
curl -v -F 'html=@/path/to/in.html' http://localhost:50003 > out.html 
```
