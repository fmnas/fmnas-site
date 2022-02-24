# image-size

This Cloud Run container gets the size of an image, to reduce load on DreamHost and improve compatibility with newer 
image formats such as HEIC and WebP.

## Resources

I suggest running this with 2 vCPU / 4 GiB / 4 concurrency to reduce OOM and optimize costs in the face of large HEIC files.

### Benchmark results in Docker container

```
Image       | Filesize | Size      | 1 requests                            | 2 requests                            | 3 requests                            | 5 requests                            | Est. max concurrency
------------|----------|-----------|---------------------------------------|---------------------------------------|---------------------------------------|---------------------------------------|---------------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 1941 ms, avg 1941 ms (1.50 GB) | 2/2 in 1983 ms, avg 1983 ms (1.86 GB) | 3/3 in 2093 ms, avg 2092 ms (2.57 GB) | 5/5 in 2491 ms, avg 2491 ms (4.75 GB) |                   10
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 189 ms, avg 189 ms (1013 MB)   | 2/2 in 167 ms, avg 165 ms (1.12 GB)   | 3/3 in 192 ms, avg 192 ms (1.20 GB)   | 5/5 in 193 ms, avg 192 ms (1.39 GB)   |                   10
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 1960 ms, avg 1960 ms (1.51 GB) | 2/2 in 2010 ms, avg 2009 ms (2.78 GB) | 3/3 in 2170 ms, avg 2163 ms (2.20 GB) | 5/5 in 2445 ms, avg 2444 ms (5.02 GB) |                   10
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 317 ms, avg 317 ms (1.35 GB)   | 2/2 in 358 ms, avg 358 ms (1.68 GB)   | 3/3 in 363 ms, avg 363 ms (2.02 GB)   | 5/5 in 358 ms, avg 357 ms (3.61 GB)   |                   10
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 2107 ms, avg 2107 ms (1.43 GB) | 2/2 in 2090 ms, avg 2090 ms (2.05 GB) | 3/3 in 2247 ms, avg 2244 ms (2.73 GB) | 5/5 in 2507 ms, avg 2506 ms (5.36 GB) |                   10
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 115 ms, avg 115 ms (1.07 GB)   | 2/2 in 116 ms, avg 116 ms (971 MB)    | 3/3 in 119 ms, avg 118 ms (985 MB)    | 5/5 in 113 ms, avg 112 ms (977 MB)    |                   10
```

### Benchmark results in Cloud Code container

(outdated)

```
Image       | Filesize | Size      | 1x                          | 2x                      | 3x                      | Est. max concurrency
------------|----------|-----------|-----------------------------|-------------------------|-------------------------|---------------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 2358 ms, avg 2358 ms | 0/2 in 0 ms, avg NaN ms | 0/3 in 0 ms, avg NaN ms |                    1
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 228 ms, avg 228 ms   | 0/2 in 0 ms, avg NaN ms | 0/3 in 0 ms, avg NaN ms |                    1
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 2386 ms, avg 2386 ms | 0/2 in 0 ms, avg NaN ms | 0/3 in 0 ms, avg NaN ms |                    1
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 398 ms, avg 398 ms   | 0/2 in 0 ms, avg NaN ms | 0/3 in 0 ms, avg NaN ms |                    1
george.heif | 3.6 MB   | 4624x3468 | 1/1 in 519 ms, avg 519 ms   | 0/2 in 0 ms, avg NaN ms | 0/3 in 0 ms, avg NaN ms |                    1
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 2675 ms, avg 2675 ms | 0/2 in 0 ms, avg NaN ms | 0/3 in 0 ms, avg NaN ms |                    1
```

### Benchmark results on Cloud Run with 1 vCPU, 2 GiB RAM

(outdated)

```
Image       | Filesize | Size      | 1 requests                    | 2 requests                    | 3 requests                  | Est. max concurrency
------------|----------|-----------|-------------------------------|-------------------------------|-----------------------------|---------------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 10105 ms, avg 10105 ms | 2/2 in 12301 ms, avg 11239 ms | 0/3 in 0 ms, avg NaN ms     |                    2
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 779 ms, avg 779 ms     | 0/2 in 0 ms, avg NaN ms       | 0/3 in 0 ms, avg NaN ms     |                    1
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 12159 ms, avg 12159 ms | 0/2 in 0 ms, avg NaN ms       | 0/3 in 0 ms, avg NaN ms     |                    1
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 1364 ms, avg 1364 ms   | 2/2 in 1279 ms, avg 1256 ms   | 1/3 in 1326 ms, avg 1326 ms |                    2
george.heif | 3.6 MB   | 4624x3468 | 1/1 in 2983 ms, avg 2983 ms   | 2/2 in 63078 ms, avg 33012 ms | 1/3 in 2771 ms, avg 2771 ms |                    2
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 12796 ms, avg 12796 ms | 0/2 in 0 ms, avg NaN ms       | 0/3 in 0 ms, avg NaN ms     |                    1
```

### Benchmark results on Cloud Run with 2 vCPU, 4 GiB RAM

```

```

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
