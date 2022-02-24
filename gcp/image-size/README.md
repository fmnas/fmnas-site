# image-size

This Cloud Run container gets the size of an image, to reduce load on DreamHost and improve compatibility with newer 
image formats such as HEIC and WebP.

## Resources

I suggest running this with 2 vCPU / 4 GiB to prevent OOM. Google seems to handle large concurrency values well - this
scales to 20+ instances when making 100 simultaneous requests to resize a large image.

### Benchmark results in Docker container



### Benchmark results in Cloud Code container with 2 GiB RAM



### Benchmark results on Cloud Run with 1 vCPU, 2 GiB RAM, concurrency limit 100



### Benchmark results on Cloud Run with 2 vCPU, 4 GiB RAM, concurrency limit 100


## Manual deployment

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

## Running locally

### With Cloud Code

The "Run image-size" IntelliJ run configuration uses the Cloud Code extension to run the service.

If it doesn't work with managed dependencies, try installing the latest minikube, skaffold, and kubectl locally and
setting the dependency paths manually in the extension settings.

This uses an automatically selected ephemeral port. To forward a specific port to the service, use
`kubectl port-forward service/image-size $PORT:8080`. Another IntelliJ run configuration is included to forward
port 50001 to image-size. This must be run while image-size is running.

### With Docker

`docker run -p 50001:8080 image-size`

### Testing

```shell
curl -v -F 'image=@/path/to/in.jpg' http://localhost:50001 
```

Or use the image-size task in public/tester.php.

There are blackbox tests for this in /tests/blackbox/test/image_size_test.dart.
