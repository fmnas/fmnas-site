# image-size

This Cloud Run container gets the size of an image, to reduce load on DreamHost and improve compatibility with newer 
image formats such as HEIC and WebP.

## Resources

I suggest running this with 2 vCPU / 4 GiB to prevent OOM. Google seems to handle large concurrency values well - this
scales to 20+ instances when making 100 simultaneous requests to resize a large image.

Performance for image-size is a much lower priority than resize-image, since the former never blocks user flow
(assuming the asynchronous image tag generation is working right in the admin site).

### Benchmark results in Docker container (local)

```
Image       | Filesize | Size      | 1 requests                            | 2 requests                            | 5 requests                            | 10 requests                             | 25 requests                              | Est. max concurrency
------------|----------|-----------|---------------------------------------|---------------------------------------|---------------------------------------|-----------------------------------------|------------------------------------------|---------------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 1912 ms, avg 1912 ms (750 MB)  | 2/2 in 2017 ms, avg 2016 ms (1.53 GB) | 5/5 in 2339 ms, avg 2338 ms (4.01 GB) | 10/10 in 3676 ms, avg 3674 ms (5.40 GB) | 25/25 in 8981 ms, avg 8979 ms (14.20 GB) |                   50
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 158 ms, avg 158 ms (1.92 GB)   | 2/2 in 201 ms, avg 201 ms (1.65 GB)   | 5/5 in 196 ms, avg 195 ms (2.29 GB)   | 10/10 in 244 ms, avg 242 ms (2.83 GB)   | 25/25 in 502 ms, avg 497 ms (5.92 GB)    |                   50
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 1960 ms, avg 1960 ms (1.96 GB) | 2/2 in 2056 ms, avg 2056 ms (2.71 GB) | 5/5 in 2436 ms, avg 2436 ms (4.63 GB) | 10/10 in 3823 ms, avg 3822 ms (8.41 GB) | 25/25 in 8915 ms, avg 8910 ms (15.46 GB) |                   50
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 322 ms, avg 322 ms (2.33 GB)   | 2/2 in 340 ms, avg 338 ms (2.61 GB)   | 5/5 in 385 ms, avg 385 ms (3.95 GB)   | 10/10 in 579 ms, avg 578 ms (6.23 GB)   | 25/25 in 1444 ms, avg 1441 ms (13.59 GB) |                   50
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 2022 ms, avg 2022 ms (2.19 GB) | 2/2 in 1996 ms, avg 1995 ms (3.03 GB) | 5/5 in 2447 ms, avg 2447 ms (6.08 GB) | 10/10 in 4007 ms, avg 4006 ms (8.75 GB) | 25/25 in 9349 ms, avg 9346 ms (17.45 GB) |                   50
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 78 ms, avg 78 ms (2.74 GB)     | 2/2 in 104 ms, avg 104 ms (2.34 GB)   | 5/5 in 110 ms, avg 109 ms (2.49 GB)   | 10/10 in 149 ms, avg 147 ms (3.47 GB)   | 25/25 in 375 ms, avg 373 ms (5.20 GB)    |                   50
```

### Benchmark results in Cloud Code container with 2 GiB RAM



### Benchmark results on Cloud Run with 1 vCPU, 2 GiB RAM, concurrency limit 50

```
Image       | Filesize | Size      | 1 requests                    | 2 requests                    | 5 requests                    | 10 requests                    | 25 requests                     | Est. max concurrency
------------|----------|-----------|-------------------------------|-------------------------------|-------------------------------|--------------------------------|---------------------------------|---------------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 11964 ms, avg 11964 ms | 2/2 in 26310 ms, avg 26100 ms | 2/5 in 12877 ms, avg 12725 ms | 2/10 in 12871 ms, avg 12256 ms | 10/25 in 29029 ms, avg 16585 ms |                    4
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 679 ms, avg 679 ms     | 2/2 in 717 ms, avg 703 ms     | 5/5 in 791 ms, avg 706 ms     | 10/10 in 2063 ms, avg 1102 ms  | 25/25 in 3147 ms, avg 1701 ms   |                   50
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 10936 ms, avg 10936 ms | 2/2 in 24896 ms, avg 24635 ms | 5/5 in 25948 ms, avg 22473 ms | 6/10 in 25438 ms, avg 16789 ms | 9/25 in 28717 ms, avg 17254 ms  |                    8
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 1300 ms, avg 1300 ms   | 2/2 in 1361 ms, avg 1321 ms   | 5/5 in 2865 ms, avg 2440 ms   | 10/10 in 2877 ms, avg 2312 ms  | 25/25 in 5115 ms, avg 2793 ms   |                   37
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 11855 ms, avg 11855 ms | 2/2 in 26675 ms, avg 26394 ms | 5/5 in 26802 ms, avg 23323 ms | 7/10 in 26757 ms, avg 23895 ms | 10/25 in 29229 ms, avg 17208 ms |                    8
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 546 ms, avg 546 ms     | 2/2 in 550 ms, avg 531 ms     | 5/5 in 890 ms, avg 761 ms     | 10/10 in 2460 ms, avg 1483 ms  | 25/25 in 5327 ms, avg 2925 ms   |                   50
```

### Benchmark results on Cloud Run with 2 vCPU, 4 GiB RAM, concurrency limit 100

```
Image       | Filesize | Size      | 1 requests                  | 2 requests                    | 5 requests                    | 10 requests                     | 25 requests                     | Est. max concurrency
------------|----------|-----------|-----------------------------|-------------------------------|-------------------------------|---------------------------------|---------------------------------|---------------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 6644 ms, avg 6644 ms | 2/2 in 11785 ms, avg 11568 ms | 5/5 in 26077 ms, avg 21966 ms | 10/10 in 27032 ms, avg 22694 ms | 25/25 in 28382 ms, avg 18779 ms |                  100
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 721 ms, avg 721 ms   | 2/2 in 755 ms, avg 680 ms     | 5/5 in 665 ms, avg 614 ms     | 10/10 in 803 ms, avg 657 ms     | 25/25 in 1280 ms, avg 817 ms    |                  100
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 7060 ms, avg 7060 ms | 2/2 in 11887 ms, avg 11636 ms | 5/5 in 18192 ms, avg 15305 ms | 10/10 in 24953 ms, avg 17097 ms | 25/25 in 25996 ms, avg 19080 ms |                  100
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 1161 ms, avg 1161 ms | 2/2 in 1274 ms, avg 1234 ms   | 5/5 in 1414 ms, avg 1288 ms   | 10/10 in 2414 ms, avg 1615 ms   | 25/25 in 3884 ms, avg 1942 ms   |                  100
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 7092 ms, avg 7092 ms | 2/2 in 12154 ms, avg 11910 ms | 5/5 in 21040 ms, avg 15004 ms | 10/10 in 20796 ms, avg 16411 ms | 25/25 in 30479 ms, avg 16284 ms |                  100 
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 609 ms, avg 609 ms   | 2/2 in 583 ms, avg 567 ms     | 5/5 in 646 ms, avg 553 ms     | 10/10 in 640 ms, avg 515 ms     | 25/25 in 1152 ms, avg 681 ms    |                  100
```

### Benchmark results on Cloud Run with 4 vCPU, 4 GiB RAM, concurrency limit 100

```
Image       | Filesize | Size      | 1 requests                  | 2 requests                  | 5 requests                    | 10 requests                      | 25 requests                      | Est. max concurrency
------------|----------|-----------|-----------------------------|-----------------------------|-------------------------------|----------------------------------|----------------------------------|---------------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 4191 ms, avg 4191 ms | 2/2 in 6800 ms, avg 6775 ms | 5/5 in 12033 ms, avg 10101 ms | 10/10 in 12330 ms, avg 9310 ms   | 25/25 in 12438 ms, avg 10228 ms  |                  100
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 525 ms, avg 525 ms   | 2/2 in 653 ms, avg 636 ms   | 5/5 in 672 ms, avg 625 ms     | 10/10 in 890 ms, avg 774 ms      | 25/25 in 1584 ms, avg 960 ms     |                  100
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 4083 ms, avg 4083 ms | 2/2 in 6393 ms, avg 6380 ms | 5/5 in 9288 ms, avg 7315 ms   | 10/10 in 10872 ms, avg 7398 ms   | 25/25 in 13819 ms, avg 10860 ms  |                  100
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 1236 ms, avg 1236 ms | 2/2 in 1273 ms, avg 1212 ms | 5/5 in 1579 ms, avg 1363 ms   | 10/10 in 1828 ms, avg 1444 ms    | 25/25 in 2776 ms, avg 2129 ms    |                  100
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 4225 ms, avg 4225 ms | 2/2 in 6664 ms, avg 6625 ms | 5/5 in 13524 ms, avg 11639 ms | 10/10 in 15961 ms, avg 10637 ms  | 25/25 in 15642 ms, avg 13806 ms  |                  100
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 561 ms, avg 561 ms   | 2/2 in 527 ms, avg 493 ms   | 5/5 in 579 ms, avg 499 ms     | 10/10 in 605 ms, avg 502 ms      | 25/25 in 1215 ms, avg 757 ms     |                  100
```

### Benchmark results on Cloud Run with 4 vCPU, 8 GiB RAM, concurrency limit 100

### Benchmark results in Docker container on GitHub Actions (used for regression tests)


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
