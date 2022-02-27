# resize-image

This Cloud Run container resizes images, to reduce load on DreamHost and improve compatibility with newer image formats
such as HEIC and WebP.

## Manual deployment

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

## Running locally

### With Docker

Cloud Code extension containers are limited to 2 GiB RAM and crash readily if there are many concurrent requests. In
lieu of local load balancing between containers, you can instead use "Run resize-image on port 50000" or
`docker run -p 50000:8080 -m 40g resize-image` to run a single container with 40GB memory (adjust as appropriate).

### With Cloud Code

The "Run resize-image" IntelliJ run configuration uses the Cloud Code extension to run the service.

If it doesn't work with managed dependencies, try installing the latest minikube, skaffold, and kubectl locally and
setting the dependency paths manually in the extension settings.

This uses an automatically selected ephemeral port. To forward a specific port to the service, use
`kubectl port-forward service/resize-image $PORT:8080`. Another IntelliJ run configuration is included to forward port
50000 to resize-image.

### Testing

```shell
curl -v -F height=200 -F 'image=@/path/to/in.jpg' http://localhost:50000 > out.jpg
```

Or use the resize-image task in public/tester.php.

There are blackbox tests for this in /tests/blackbox/test/resize_image_test.dart.

## Resource provisioning

I suggest running this with 4 vCPU / 4 GiB, and limited to 1 concurrent request per container.

There are benchmarks for this at /tests/blackbox/bin/resize_image.dart and /tests/blackbox/bin/image_filters.dart.

Performance is important here, as this blocks application submission after uploading attachments.

Informed by the benchmark results, this blocking flow uses the filter HERMITE, and non-blocking flows use the filter
LANCZOS (LANCZOS2 is not supported locally on dreamhost yet).

### Benchmark results in Docker container (local)

```
Results for height 64:
Image       | Filesize | Size      | 1 requests                            | 2 requests                            | 5 requests                            | 10 requests                             | Max concurrency
------------|----------|-----------|---------------------------------------|---------------------------------------|---------------------------------------|-----------------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 3558 ms, avg 3558 ms (835 MB)  | 2/2 in 3634 ms, avg 3620 ms (1.76 GB) | 5/5 in 4179 ms, avg 4155 ms (4.44 GB) | 10/10 in 5786 ms, avg 5710 ms (8.51 GB) |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 511 ms, avg 511 ms (1.02 GB)   | 2/2 in 491 ms, avg 489 ms (1.17 GB)   | 5/5 in 534 ms, avg 525 ms (1.52 GB)   | 10/10 in 697 ms, avg 638 ms (2.53 GB)   |              10
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 3611 ms, avg 3611 ms (1.36 GB) | 2/2 in 3644 ms, avg 3622 ms (2.25 GB) | 5/5 in 4170 ms, avg 4100 ms (4.38 GB) | 10/10 in 5841 ms, avg 5745 ms (8.45 GB) |              10
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 1390 ms, avg 1390 ms (1.58 GB) | 2/2 in 1426 ms, avg 1425 ms (1.73 GB) | 5/5 in 1529 ms, avg 1529 ms (3.48 GB) | 10/10 in 2029 ms, avg 1997 ms (6.45 GB) |              10
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 3820 ms, avg 3820 ms (1.48 GB) | 2/2 in 3953 ms, avg 3937 ms (2.15 GB) | 5/5 in 4409 ms, avg 4338 ms (4.88 GB) | 10/10 in 6235 ms, avg 6157 ms (9.02 GB) |              10
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 361 ms, avg 361 ms (1.42 GB)   | 2/2 in 349 ms, avg 349 ms (1.38 GB)   | 5/5 in 396 ms, avg 396 ms (1.77 GB)   | 10/10 in 674 ms, avg 673 ms (2.34 GB)   |              10
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 29 ms, avg 29 ms (742 MB)      | 2/2 in 21 ms, avg 20 ms (737 MB)      | 5/5 in 29 ms, avg 23 ms (727 MB)      | 10/10 in 30 ms, avg 25 ms (734 MB)      |              10

Results for height 192:
Image       | Filesize | Size      | 1 requests                            | 2 requests                            | 5 requests                            | 10 requests                             | Max concurrency
------------|----------|-----------|---------------------------------------|---------------------------------------|---------------------------------------|-----------------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 3372 ms, avg 3372 ms (1.51 GB) | 2/2 in 3568 ms, avg 3556 ms (2.38 GB) | 5/5 in 3941 ms, avg 3894 ms (5.08 GB) | 10/10 in 5856 ms, avg 5743 ms (9.31 GB) |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 461 ms, avg 461 ms (1.60 GB)   | 2/2 in 481 ms, avg 475 ms (1.52 GB)   | 5/5 in 502 ms, avg 492 ms (2.03 GB)   | 10/10 in 627 ms, avg 557 ms (3.01 GB)   |              10
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 3420 ms, avg 3420 ms (1.82 GB) | 2/2 in 3540 ms, avg 3525 ms (2.49 GB) | 5/5 in 4061 ms, avg 3966 ms (4.84 GB) | 10/10 in 5887 ms, avg 5731 ms (9.07 GB) |              10
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 1587 ms, avg 1587 ms (2.00 GB) | 2/2 in 1510 ms, avg 1508 ms (2.21 GB) | 5/5 in 1738 ms, avg 1585 ms (3.95 GB) | 10/10 in 2067 ms, avg 1976 ms (6.97 GB) |              10
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 3511 ms, avg 3511 ms (1.93 GB) | 2/2 in 3704 ms, avg 3660 ms (2.61 GB) | 5/5 in 4172 ms, avg 4084 ms (5.32 GB) | 10/10 in 5956 ms, avg 5916 ms (8.99 GB) |              10
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 359 ms, avg 359 ms (1.72 GB)   | 2/2 in 356 ms, avg 354 ms (1.87 GB)   | 5/5 in 381 ms, avg 373 ms (2.06 GB)   | 10/10 in 605 ms, avg 474 ms (2.82 GB)   |              10
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 22 ms, avg 22 ms (1.05 GB)     | 2/2 in 24 ms, avg 23 ms (1.03 GB)     | 5/5 in 30 ms, avg 24 ms (1002 MB)     | 10/10 in 36 ms, avg 31 ms (1005 MB)     |              10

Results for height 300:
Image       | Filesize | Size      | 1 requests                            | 2 requests                            | 5 requests                            | 10 requests                             | Max concurrency
------------|----------|-----------|---------------------------------------|---------------------------------------|---------------------------------------|-----------------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 3204 ms, avg 3204 ms (1.79 GB) | 2/2 in 3297 ms, avg 3291 ms (2.89 GB) | 5/5 in 3830 ms, avg 3798 ms (5.32 GB) | 10/10 in 5834 ms, avg 5728 ms (8.95 GB) |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 486 ms, avg 486 ms (1.89 GB)   | 2/2 in 490 ms, avg 486 ms (1.84 GB)   | 5/5 in 511 ms, avg 500 ms (2.32 GB)   | 10/10 in 674 ms, avg 572 ms (3.37 GB)   |              10
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 3250 ms, avg 3250 ms (2.15 GB) | 2/2 in 3304 ms, avg 3287 ms (2.88 GB) | 5/5 in 3835 ms, avg 3794 ms (5.33 GB) | 10/10 in 5982 ms, avg 5682 ms (9.38 GB) |              10
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 1412 ms, avg 1412 ms (2.34 GB) | 2/2 in 1431 ms, avg 1404 ms (2.72 GB) | 5/5 in 1663 ms, avg 1587 ms (4.24 GB) | 10/10 in 2226 ms, avg 2074 ms (7.29 GB) |              10
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 3388 ms, avg 3388 ms (2.26 GB) | 2/2 in 3636 ms, avg 3535 ms (2.96 GB) | 5/5 in 3985 ms, avg 3931 ms (5.27 GB) | 10/10 in 6002 ms, avg 5925 ms (9.43 GB) |              10
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 370 ms, avg 370 ms (2.08 GB)   | 2/2 in 369 ms, avg 369 ms (2.20 GB)   | 5/5 in 484 ms, avg 400 ms (2.46 GB)   | 10/10 in 631 ms, avg 539 ms (3.06 GB)   |              10
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 27 ms, avg 27 ms (1.33 GB)     | 2/2 in 28 ms, avg 27 ms (1.33 GB)     | 5/5 in 43 ms, avg 38 ms (1.30 GB)     | 10/10 in 42 ms, avg 32 ms (1.30 GB)     |              10

Results for height 480:
Image       | Filesize | Size      | 1 requests                            | 2 requests                            | 5 requests                            | 10 requests                             | Max concurrency
------------|----------|-----------|---------------------------------------|---------------------------------------|---------------------------------------|-----------------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 3337 ms, avg 3337 ms (2.18 GB) | 2/2 in 3276 ms, avg 3263 ms (2.97 GB) | 5/5 in 3765 ms, avg 3663 ms (5.62 GB) | 10/10 in 5855 ms, avg 5746 ms (9.61 GB) |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 546 ms, avg 546 ms (2.07 GB)   | 2/2 in 540 ms, avg 539 ms (2.22 GB)   | 5/5 in 562 ms, avg 555 ms (2.63 GB)   | 10/10 in 736 ms, avg 622 ms (3.50 GB)   |              10
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 3124 ms, avg 3124 ms (2.36 GB) | 2/2 in 3246 ms, avg 3202 ms (3.06 GB) | 5/5 in 3849 ms, avg 3776 ms (5.50 GB) | 10/10 in 5926 ms, avg 5893 ms (9.61 GB) |              10
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 1301 ms, avg 1301 ms (2.49 GB) | 2/2 in 1327 ms, avg 1316 ms (2.69 GB) | 5/5 in 1478 ms, avg 1397 ms (4.56 GB) | 10/10 in 1977 ms, avg 1893 ms (7.68 GB) |              10
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 3186 ms, avg 3186 ms (2.36 GB) | 2/2 in 3319 ms, avg 3316 ms (3.07 GB) | 5/5 in 3906 ms, avg 3867 ms (5.59 GB) | 10/10 in 6330 ms, avg 5946 ms (9.61 GB) |              10
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 408 ms, avg 408 ms (2.05 GB)   | 2/2 in 432 ms, avg 420 ms (2.10 GB)   | 5/5 in 499 ms, avg 443 ms (2.41 GB)   | 10/10 in 637 ms, avg 515 ms (3.17 GB)   |              10
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 36 ms, avg 36 ms (1.46 GB)     | 2/2 in 37 ms, avg 36 ms (1.38 GB)     | 5/5 in 55 ms, avg 46 ms (1.34 GB)     | 10/10 in 57 ms, avg 50 ms (1.34 GB)     |              10

Results for height 2160:
Image       | Filesize | Size      | 1 requests                            | 2 requests                            | 5 requests                            | 10 requests                              | Max concurrency
------------|----------|-----------|---------------------------------------|---------------------------------------|---------------------------------------|------------------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 3656 ms, avg 3656 ms (2.41 GB) | 2/2 in 3766 ms, avg 3764 ms (3.49 GB) | 5/5 in 4315 ms, avg 4203 ms (6.70 GB) | 10/10 in 5887 ms, avg 5633 ms (12.11 GB) |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 851 ms, avg 851 ms (2.27 GB)   | 2/2 in 895 ms, avg 882 ms (2.50 GB)   | 5/5 in 941 ms, avg 919 ms (3.44 GB)   | 10/10 in 1051 ms, avg 989 ms (5.35 GB)   |              10
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 3631 ms, avg 3631 ms (2.67 GB) | 2/2 in 3731 ms, avg 3700 ms (3.66 GB) | 5/5 in 4194 ms, avg 4176 ms (6.76 GB) | 10/10 in 5730 ms, avg 5686 ms (12.12 GB) |              10
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 1763 ms, avg 1763 ms (2.61 GB) | 2/2 in 1847 ms, avg 1832 ms (3.20 GB) | 5/5 in 1937 ms, avg 1884 ms (5.78 GB) | 10/10 in 2059 ms, avg 2016 ms (10.14 GB) |              10
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 3805 ms, avg 3805 ms (2.66 GB) | 2/2 in 3906 ms, avg 3867 ms (3.60 GB) | 5/5 in 4371 ms, avg 4273 ms (6.77 GB) | 10/10 in 6310 ms, avg 5945 ms (12.07 GB) |              10
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 704 ms, avg 704 ms (2.25 GB)   | 2/2 in 718 ms, avg 715 ms (2.49 GB)   | 5/5 in 749 ms, avg 740 ms (3.33 GB)   | 10/10 in 918 ms, avg 829 ms (4.96 GB)    |              10
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 46 ms, avg 46 ms (1.62 GB)     | 2/2 in 45 ms, avg 45 ms (1.56 GB)     | 5/5 in 77 ms, avg 57 ms (1.51 GB)     | 10/10 in 82 ms, avg 58 ms (1.47 GB)      |              10

Results for height 4320:
Image       | Filesize | Size      | 1 requests                            | 2 requests                            | 5 requests                            | 10 requests                              | Max concurrency
------------|----------|-----------|---------------------------------------|---------------------------------------|---------------------------------------|------------------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 4851 ms, avg 4851 ms (2.88 GB) | 2/2 in 4858 ms, avg 4840 ms (4.41 GB) | 5/5 in 5512 ms, avg 5486 ms (8.93 GB) | 10/10 in 7519 ms, avg 7145 ms (16.27 GB) |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1449 ms, avg 1449 ms (2.39 GB) | 2/2 in 1488 ms, avg 1481 ms (2.88 GB) | 5/5 in 1551 ms, avg 1527 ms (4.40 GB) | 10/10 in 1922 ms, avg 1658 ms (7.27 GB)  |              10
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 4841 ms, avg 4841 ms (3.26 GB) | 2/2 in 4928 ms, avg 4923 ms (4.60 GB) | 5/5 in 5338 ms, avg 5299 ms (9.05 GB) | 10/10 in 6889 ms, avg 6797 ms (16.57 GB) |              10
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 2771 ms, avg 2771 ms (3.22 GB) | 2/2 in 2836 ms, avg 2831 ms (4.23 GB) | 5/5 in 3022 ms, avg 3002 ms (8.00 GB) | 10/10 in 3519 ms, avg 3302 ms (14.48 GB) |              10
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 4862 ms, avg 4862 ms (3.27 GB) | 2/2 in 4995 ms, avg 4989 ms (4.61 GB) | 5/5 in 5477 ms, avg 5452 ms (9.03 GB) | 10/10 in 7482 ms, avg 7272 ms (16.61 GB) |              10
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1483 ms, avg 1483 ms (2.67 GB) | 2/2 in 1111 ms, avg 1104 ms (2.85 GB) | 5/5 in 1272 ms, avg 1179 ms (4.18 GB) | 10/10 in 1387 ms, avg 1315 ms (6.40 GB)  |              10
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 60 ms, avg 60 ms (1.58 GB)     | 2/2 in 47 ms, avg 46 ms (1.58 GB)     | 5/5 in 51 ms, avg 47 ms (1.55 GB)     | 10/10 in 75 ms, avg 61 ms (1.55 GB)      |              10

Results for height 100000:
Image       | Filesize | Size      | 1 requests                            | 2 requests                            | 5 requests                             | 10 requests                      | Max concurrency
------------|----------|-----------|---------------------------------------|---------------------------------------|----------------------------------------|----------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 7110 ms, avg 7110 ms (3.77 GB) | 2/2 in 7053 ms, avg 7032 ms (5.99 GB) | 5/5 in 8112 ms, avg 7856 ms (12.63 GB) | 0/10 in 0 ms, avg 0 ms (3.10 GB) |               5
pigeons.jpg | 12.1 MB  | 4656x3492 | 0/1 in 0 ms, avg 0 ms (3.57 GB)       | 0/2 in 0 ms, avg 0 ms (3.57 GB)       | 0/5 in 0 ms, avg 0 ms (3.68 GB)        | 0/10 in 0 ms, avg 0 ms (4.03 GB) |               1
train.heic  | 16.4 MB  | 9248x6936 | 0/1 in 0 ms, avg 0 ms (4.03 GB)       | 0/2 in 0 ms, avg 0 ms (4.08 GB)       | 0/5 in 0 ms, avg 0 ms (4.34 GB)        | 0/10 in 0 ms, avg 0 ms (4.85 GB) |               1
litter.jpg  | 12.6 MB  | 8384x6035 | 0/1 in 0 ms, avg 0 ms (4.85 GB)       | 0/2 in 0 ms, avg 0 ms (4.85 GB)       | 0/5 in 0 ms, avg 0 ms (4.85 GB)        | 0/10 in 0 ms, avg 0 ms (5.19 GB) |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 0/1 in 0 ms, avg 0 ms (5.19 GB)       | 0/2 in 0 ms, avg 0 ms (5.24 GB)       | 0/5 in 0 ms, avg 0 ms (5.52 GB)        | 0/10 in 0 ms, avg 0 ms (6.10 GB) |               1
george.jpg  | 1.4 MB   | 4322x3289 | 0/1 in 0 ms, avg 0 ms (6.10 GB)       | 0/2 in 0 ms, avg 0 ms (6.10 GB)       | 0/5 in 0 ms, avg 0 ms (6.10 GB)        | 0/10 in 0 ms, avg 0 ms (6.10 GB) |               1
callie.jpg  | 352.6 KB | 800x600   | 0/1 in 0 ms, avg 0 ms (6.10 GB)       | 0/2 in 0 ms, avg 0 ms (6.10 GB)       | 0/5 in 0 ms, avg 0 ms (6.10 GB)        | 0/10 in 0 ms, avg 0 ms (6.10 GB) |               1
```

### Benchmark results in Cloud Code container with 2 GiB RAM

```
TODO
```

### Cloud Run benchmark result

TODO

#### 2 vCPU, 4 GiB RAM, concurrency limit 100

```
Results for height 64:
Image       | Filesize | Size      | 1 requests                    | 2 requests                    | 5 requests                    | 10 requests                     | 25 requests                     | Max concurrency
------------|----------|-----------|-------------------------------|-------------------------------|-------------------------------|---------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 10844 ms, avg 10844 ms | 2/2 in 17863 ms, avg 17862 ms | 5/5 in 34854 ms, avg 29627 ms | 10/10 in 41720 ms, avg 27655 ms | 25/25 in 35910 ms, avg 19420 ms |             100
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1256 ms, avg 1256 ms   | 2/2 in 1503 ms, avg 1412 ms   | 5/5 in 1389 ms, avg 1313 ms   | 10/10 in 1452 ms, avg 1363 ms   | 25/25 in 3616 ms, avg 1932 ms   |             100
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 10686 ms, avg 10686 ms | 2/2 in 10514 ms, avg 10199 ms | 5/5 in 17167 ms, avg 15616 ms | 10/10 in 28324 ms, avg 21110 ms | 25/25 in 37120 ms, avg 29986 ms |             100
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 4236 ms, avg 4236 ms   | 2/2 in 3713 ms, avg 3679 ms   | 5/5 in 4023 ms, avg 3850 ms   | 10/10 in 4424 ms, avg 4160 ms   | 25/25 in 11531 ms, avg 7218 ms  |             100
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 9960 ms, avg 9960 ms   | 2/2 in 11734 ms, avg 11418 ms | 5/5 in 25415 ms, avg 19444 ms | 10/10 in 23196 ms, avg 15898 ms | 25/25 in 27986 ms, avg 16412 ms |             100
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1743 ms, avg 1743 ms   | 2/2 in 1848 ms, avg 1846 ms   | 5/5 in 1947 ms, avg 1845 ms   | 10/10 in 2051 ms, avg 1792 ms   | 25/25 in 3478 ms, avg 2368 ms   |             100

Results for height 192:
Image       | Filesize | Size      | 1 requests                    | 2 requests                    | 5 requests                    | 10 requests                     | 25 requests                     | Max concurrency
------------|----------|-----------|-------------------------------|-------------------------------|-------------------------------|---------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 10038 ms, avg 10038 ms | 2/2 in 10187 ms, avg 10141 ms | 5/5 in 16309 ms, avg 15048 ms | 10/10 in 20438 ms, avg 13682 ms | 25/25 in 28633 ms, avg 15149 ms |             100
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1593 ms, avg 1593 ms   | 2/2 in 1427 ms, avg 1395 ms   | 5/5 in 1777 ms, avg 1584 ms   | 10/10 in 2348 ms, avg 1814 ms   | 25/25 in 4859 ms, avg 3136 ms   |             100
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 9884 ms, avg 9884 ms   | 2/2 in 14497 ms, avg 14483 ms | 5/5 in 15548 ms, avg 14475 ms | 10/10 in 23247 ms, avg 16324 ms | 25/25 in 27563 ms, avg 16570 ms |             100
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 3822 ms, avg 3822 ms   | 2/2 in 4048 ms, avg 3963 ms   | 5/5 in 4094 ms, avg 3960 ms   | 10/10 in 4891 ms, avg 4501 ms   | 25/25 in 14496 ms, avg 8559 ms  |             100
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 11073 ms, avg 11073 ms | 2/2 in 16006 ms, avg 16005 ms | 5/5 in 16985 ms, avg 15518 ms | 10/10 in 28074 ms, avg 17913 ms | 25/25 in 26535 ms, avg 15899 ms |             100
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1850 ms, avg 1850 ms   | 2/2 in 1915 ms, avg 1881 ms   | 5/5 in 2961 ms, avg 2259 ms   | 10/10 in 2060 ms, avg 1881 ms   | 25/25 in 2402 ms, avg 1898 ms   |             100

Results for height 300:
Image       | Filesize | Size      | 1 requests                  | 2 requests                    | 5 requests                    | 10 requests                     | 25 requests                     | Max concurrency
------------|----------|-----------|-----------------------------|-------------------------------|-------------------------------|---------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 9282 ms, avg 9282 ms | 2/2 in 15393 ms, avg 15338 ms | 5/5 in 15904 ms, avg 14660 ms | 10/10 in 20292 ms, avg 14484 ms | 25/25 in 25853 ms, avg 15147 ms |              99
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1354 ms, avg 1354 ms | 2/2 in 1547 ms, avg 1545 ms   | 5/5 in 1946 ms, avg 1851 ms   | 10/10 in 3069 ms, avg 2539 ms   | 25/25 in 3723 ms, avg 2140 ms   |             100
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 9521 ms, avg 9521 ms | 2/2 in 15663 ms, avg 15656 ms | 5/5 in 16322 ms, avg 15192 ms | 10/10 in 25618 ms, avg 20078 ms | 25/25 in 26506 ms, avg 14704 ms |              98
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 3836 ms, avg 3836 ms | 2/2 in 5136 ms, avg 5066 ms   | 5/5 in 4619 ms, avg 4518 ms   | 10/10 in 5398 ms, avg 4902 ms   | 25/25 in 10604 ms, avg 6537 ms  |             100
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 9822 ms, avg 9822 ms | 2/2 in 15041 ms, avg 15008 ms | 5/5 in 16626 ms, avg 13218 ms | 10/10 in 20017 ms, avg 18670 ms | 25/25 in 27719 ms, avg 15947 ms |             100
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1937 ms, avg 1937 ms | 2/2 in 2016 ms, avg 1965 ms   | 5/5 in 1954 ms, avg 1721 ms   | 10/10 in 2420 ms, avg 2031 ms   | 25/25 in 21592 ms, avg 15049 ms |              48

Results for height 480:
Image       | Filesize | Size      | 1 requests                    | 2 requests                    | 5 requests                    | 10 requests                     | 25 requests                     | Max concurrency
------------|----------|-----------|-------------------------------|-------------------------------|-------------------------------|---------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 10083 ms, avg 10083 ms | 2/2 in 16062 ms, avg 15949 ms | 5/5 in 15952 ms, avg 12814 ms | 10/10 in 18679 ms, avg 17475 ms | 25/25 in 37245 ms, avg 18681 ms |              91
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1497 ms, avg 1497 ms   | 2/2 in 1551 ms, avg 1537 ms   | 5/5 in 2809 ms, avg 2332 ms   | 10/10 in 2032 ms, avg 1846 ms   | 25/25 in 5960 ms, avg 3092 ms   |             100
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 10583 ms, avg 10583 ms | 2/2 in 16541 ms, avg 16518 ms | 5/5 in 16741 ms, avg 15488 ms | 10/10 in 16995 ms, avg 15373 ms | 25/25 in 28163 ms, avg 17920 ms |              85
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 3864 ms, avg 3864 ms   | 2/2 in 4262 ms, avg 4258 ms   | 5/5 in 5030 ms, avg 4820 ms   | 10/10 in 11120 ms, avg 6240 ms  | 25/25 in 8407 ms, avg 5499 ms   |             100
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 10419 ms, avg 10419 ms | 2/2 in 17022 ms, avg 16999 ms | 5/5 in 18460 ms, avg 16997 ms | 10/10 in 34658 ms, avg 25672 ms | 25/25 in 36328 ms, avg 21761 ms |              79
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1832 ms, avg 1832 ms   | 2/2 in 1629 ms, avg 1628 ms   | 5/5 in 2246 ms, avg 1899 ms   | 10/10 in 3131 ms, avg 2642 ms   | 25/25 in 4787 ms, avg 2907 ms   |              76

Results for height 2160:
Image       | Filesize | Size      | 1 requests                    | 2 requests                    | 5 requests                    | 10 requests                     | 25 requests                     | Max concurrency
------------|----------|-----------|-------------------------------|-------------------------------|-------------------------------|---------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 10737 ms, avg 10737 ms | 2/2 in 17322 ms, avg 17308 ms | 5/5 in 27718 ms, avg 23611 ms | 10/10 in 18517 ms, avg 16155 ms | 25/25 in 29185 ms, avg 16428 ms |              25
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 2884 ms, avg 2884 ms   | 2/2 in 3077 ms, avg 3066 ms   | 5/5 in 3316 ms, avg 3204 ms   | 10/10 in 6027 ms, avg 4410 ms   | 24/25 in 9194 ms, avg 5402 ms   |              16
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 10677 ms, avg 10677 ms | 2/2 in 17022 ms, avg 16978 ms | 5/5 in 19578 ms, avg 17960 ms | 10/10 in 25646 ms, avg 18731 ms | 25/25 in 28808 ms, avg 17146 ms |              25
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 5406 ms, avg 5406 ms   | 2/2 in 5399 ms, avg 5377 ms   | 5/5 in 5918 ms, avg 5699 ms   | 4/10 in 8875 ms, avg 6536 ms    | 19/25 in 13625 ms, avg 7602 ms  |               8
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 11448 ms, avg 11448 ms | 2/2 in 18298 ms, avg 18291 ms | 5/5 in 19398 ms, avg 17702 ms | 6/10 in 18984 ms, avg 16389 ms  | 17/25 in 32343 ms, avg 20416 ms |               7
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 2424 ms, avg 2424 ms   | 2/2 in 2965 ms, avg 2957 ms   | 5/5 in 2978 ms, avg 2912 ms   | 10/10 in 10288 ms, avg 7922 ms  | 4/25 in 3118 ms, avg 3062 ms    |              22

Results for height 4320:
Image       | Filesize | Size      | 1 requests                    | 2 requests                    | 5 requests                    | 10 requests                     | 25 requests                     | Max concurrency
------------|----------|-----------|-------------------------------|-------------------------------|-------------------------------|---------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 14963 ms, avg 14963 ms | 2/2 in 20909 ms, avg 20875 ms | 5/5 in 21530 ms, avg 19981 ms | 6/10 in 28174 ms, avg 19449 ms  | 18/25 in 28848 ms, avg 18356 ms |               5
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 4856 ms, avg 4856 ms   | 2/2 in 4854 ms, avg 4824 ms   | 5/5 in 5201 ms, avg 5049 ms   | 10/10 in 14595 ms, avg 10716 ms | 25/25 in 14579 ms, avg 8098 ms  |             100
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 14323 ms, avg 14323 ms | 2/2 in 20737 ms, avg 20697 ms | 2/5 in 22760 ms, avg 22695 ms | 7/10 in 27341 ms, avg 19104 ms  | 7/25 in 27028 ms, avg 17721 ms  |               2
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 8333 ms, avg 8333 ms   | 2/2 in 10444 ms, avg 10431 ms | 0/5 in 0 ms, avg 0 ms         | 4/10 in 16765 ms, avg 10826 ms  | 5/25 in 10178 ms, avg 9463 ms   |               3
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 14653 ms, avg 14653 ms | 2/2 in 22402 ms, avg 22381 ms | 1/5 in 15461 ms, avg 15461 ms | 2/10 in 16114 ms, avg 16113 ms  | 6/25 in 16782 ms, avg 16501 ms  |               2
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 3701 ms, avg 3701 ms   | 2/2 in 4490 ms, avg 4452 ms   | 5/5 in 11239 ms, avg 11128 ms | 0/10 in 0 ms, avg 0 ms          | 0/25 in 0 ms, avg 0 ms          |               8

Results for height 100000:
Image       | Filesize | Size      | 1 requests                    | 2 requests                    | 5 requests                    | 10 requests                     | 25 requests                     | Max concurrency
------------|----------|-----------|-------------------------------|-------------------------------|-------------------------------|---------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 21677 ms, avg 21677 ms | 0/2 in 0 ms, avg 0 ms         | 1/5 in 22747 ms, avg 22747 ms | 7/10 in 23920 ms, avg 23229 ms  | 13/25 in 26159 ms, avg 24211 ms |               1
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 5138 ms, avg 5138 ms   | 2/2 in 5560 ms, avg 5543 ms   | 5/5 in 14408 ms, avg 14116 ms | 10/10 in 16510 ms, avg 11792 ms | 24/25 in 19732 ms, avg 14744 ms |              24
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 22994 ms, avg 22994 ms | 0/2 in 0 ms, avg 0 ms         | 3/5 in 35683 ms, avg 34648 ms | 6/10 in 27384 ms, avg 24243 ms  | 7/25 in 29412 ms, avg 26007 ms  |               1
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 13506 ms, avg 13506 ms | 2/2 in 15725 ms, avg 15722 ms | 2/5 in 13967 ms, avg 13949 ms | 4/10 in 14794 ms, avg 14683 ms  | 5/25 in 15358 ms, avg 14661 ms  |               2
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 22094 ms, avg 22094 ms | 0/2 in 0 ms, avg 0 ms         | 5/5 in 25985 ms, avg 24017 ms | 6/10 in 25346 ms, avg 23958 ms  | 13/25 in 25381 ms, avg 23550 ms |               5
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 4074 ms, avg 4074 ms   | 2/2 in 4169 ms, avg 4150 ms   | 5/5 in 4351 ms, avg 4052 ms   | 10/10 in 6322 ms, avg 5316 ms   | 25/25 in 12515 ms, avg 10682 ms |              26
```

#### 2 vCPU, 8 GiB RAM, concurrency limit 100

```
Results for height 64:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 5 requests                    | 10 requests                     | 25 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|-------------------------------|---------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 9955 ms    | 2/2 in 15547 ms, avg 15543 ms | 5/5 in 24369 ms, avg 21064 ms | 10/10 in 24517 ms, avg 15986 ms | 25/25 in 40996 ms, avg 31517 ms |             100
pigeons.jpg | 12.1 MB  | 4656x3492 | 1247 ms    | 2/2 in 1365 ms, avg 1355 ms   | 5/5 in 1417 ms, avg 1326 ms   | 10/10 in 1434 ms, avg 1336 ms   | 25/25 in 1663 ms, avg 1475 ms   |             100
train.heic  | 16.4 MB  | 9248x6936 | 9958 ms    | 2/2 in 15743 ms, avg 15740 ms | 5/5 in 15176 ms, avg 14154 ms | 10/10 in 38470 ms, avg 32492 ms | 25/25 in 37053 ms, avg 25590 ms |             100
litter.jpg  | 12.6 MB  | 8384x6035 | 3700 ms    | 2/2 in 3801 ms, avg 3755 ms   | 5/5 in 4697 ms, avg 3934 ms   | 10/10 in 4467 ms, avg 3912 ms   | 25/25 in 16334 ms, avg 10459 ms |             100
dubai.heic  | 18.6 MB  | 9248x6936 | 10612 ms   | 2/2 in 10460 ms, avg 10298 ms | 5/5 in 17096 ms, avg 15442 ms | 10/10 in 27017 ms, avg 19008 ms | 25/25 in 38081 ms, avg 24097 ms |             100
george.jpg  | 1.4 MB   | 4322x3289 | 1606 ms    | 2/2 in 1701 ms, avg 1690 ms   | 5/5 in 1877 ms, avg 1732 ms   | 10/10 in 1970 ms, avg 1731 ms   | 25/25 in 3223 ms, avg 2116 ms   |             100
callie.jpg  | 352.6 KB | 800x600   | 152 ms     | 2/2 in 203 ms, avg 184 ms     | 5/5 in 173 ms, avg 162 ms     | 10/10 in 154 ms, avg 146 ms     | 25/25 in 163 ms, avg 146 ms     |             100

Results for height 192:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 5 requests                    | 10 requests                     | 25 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|-------------------------------|---------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 9562 ms    | 2/2 in 9884 ms, avg 9646 ms   | 5/5 in 34064 ms, avg 29067 ms | 10/10 in 34217 ms, avg 21859 ms | 25/25 in 35080 ms, avg 22887 ms |             100
pigeons.jpg | 12.1 MB  | 4656x3492 | 1422 ms    | 2/2 in 1735 ms, avg 1708 ms   | 5/5 in 1570 ms, avg 1442 ms   | 10/10 in 5827 ms, avg 4255 ms   | 25/25 in 5881 ms, avg 3427 ms   |             100
train.heic  | 16.4 MB  | 9248x6936 | 10076 ms   | 2/2 in 15022 ms, avg 14999 ms | 5/5 in 15250 ms, avg 12197 ms | 10/10 in 17356 ms, avg 17149 ms | 25/25 in 34975 ms, avg 25601 ms |             100
litter.jpg  | 12.6 MB  | 8384x6035 | 4213 ms    | 2/2 in 4254 ms, avg 4137 ms   | 5/5 in 4944 ms, avg 4604 ms   | 10/10 in 5147 ms, avg 4717 ms   | 25/25 in 15431 ms, avg 9770 ms  |             100
dubai.heic  | 18.6 MB  | 9248x6936 | 10121 ms   | 2/2 in 15583 ms, avg 15581 ms | 5/5 in 16937 ms, avg 15548 ms | 10/10 in 17411 ms, avg 12324 ms | 25/25 in 19093 ms, avg 14422 ms |             100
george.jpg  | 1.4 MB   | 4322x3289 | 1869 ms    | 2/2 in 1933 ms, avg 1863 ms   | 5/5 in 1932 ms, avg 1835 ms   | 10/10 in 1910 ms, avg 1830 ms   | 25/25 in 2571 ms, avg 2096 ms   |             100
callie.jpg  | 352.6 KB | 800x600   | 181 ms     | 2/2 in 173 ms, avg 167 ms     | 5/5 in 178 ms, avg 175 ms     | 10/10 in 201 ms, avg 182 ms     | 25/25 in 350 ms, avg 233 ms     |             100

Results for height 300:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 5 requests                    | 10 requests                     | 25 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|-------------------------------|---------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 9705 ms    | 2/2 in 9363 ms, avg 9303 ms   | 5/5 in 15979 ms, avg 13714 ms | 10/10 in 32969 ms, avg 27988 ms | 25/25 in 35076 ms, avg 22996 ms |             100
pigeons.jpg | 12.1 MB  | 4656x3492 | 1367 ms    | 2/2 in 1426 ms, avg 1377 ms   | 5/5 in 4064 ms, avg 3919 ms   | 10/10 in 5368 ms, avg 4369 ms   | 25/25 in 6366 ms, avg 5405 ms   |             100
train.heic  | 16.4 MB  | 9248x6936 | 10167 ms   | 2/2 in 14430 ms, avg 14429 ms | 5/5 in 29979 ms, avg 25792 ms | 10/10 in 35032 ms, avg 22175 ms | 25/25 in 37051 ms, avg 19841 ms |             100
litter.jpg  | 12.6 MB  | 8384x6035 | 3819 ms    | 2/2 in 4172 ms, avg 4158 ms   | 5/5 in 4662 ms, avg 4536 ms   | 10/10 in 7041 ms, avg 5323 ms   | 25/25 in 16840 ms, avg 10153 ms |             100
dubai.heic  | 18.6 MB  | 9248x6936 | 9546 ms    | 2/2 in 15432 ms, avg 15428 ms | 5/5 in 15922 ms, avg 12688 ms | 10/10 in 18119 ms, avg 17348 ms | 25/25 in 26312 ms, avg 17449 ms |             100
george.jpg  | 1.4 MB   | 4322x3289 | 1736 ms    | 2/2 in 1836 ms, avg 1800 ms   | 5/5 in 1857 ms, avg 1764 ms   | 10/10 in 2293 ms, avg 1960 ms   | 25/25 in 3764 ms, avg 2191 ms   |             100
callie.jpg  | 352.6 KB | 800x600   | 237 ms     | 2/2 in 242 ms, avg 242 ms     | 5/5 in 240 ms, avg 235 ms     | 10/10 in 258 ms, avg 236 ms     | 25/25 in 540 ms, avg 379 ms     |             100

Results for height 480:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 5 requests                    | 10 requests                     | 25 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|-------------------------------|---------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 9290 ms    | 2/2 in 14771 ms, avg 14758 ms | 5/5 in 15592 ms, avg 14458 ms | 10/10 in 26163 ms, avg 20403 ms | 25/25 in 34128 ms, avg 20588 ms |             100
pigeons.jpg | 12.1 MB  | 4656x3492 | 1507 ms    | 2/2 in 1540 ms, avg 1490 ms   | 5/5 in 1535 ms, avg 1487 ms   | 10/10 in 2175 ms, avg 1913 ms   | 25/25 in 2778 ms, avg 2205 ms   |             100
train.heic  | 16.4 MB  | 9248x6936 | 9906 ms    | 2/2 in 14612 ms, avg 14576 ms | 5/5 in 16131 ms, avg 14958 ms | 10/10 in 17572 ms, avg 15434 ms | 25/25 in 17758 ms, avg 13430 ms |             100
litter.jpg  | 12.6 MB  | 8384x6035 | 3784 ms    | 2/2 in 4125 ms, avg 4076 ms   | 5/5 in 4047 ms, avg 3941 ms   | 10/10 in 5758 ms, avg 4790 ms   | 25/25 in 16774 ms, avg 8227 ms  |             100
dubai.heic  | 18.6 MB  | 9248x6936 | 9989 ms    | 2/2 in 15225 ms, avg 15200 ms | 5/5 in 24424 ms, avg 18955 ms | 10/10 in 17868 ms, avg 16416 ms | 25/25 in 19591 ms, avg 13822 ms |             100
george.jpg  | 1.4 MB   | 4322x3289 | 1788 ms    | 2/2 in 1872 ms, avg 1812 ms   | 5/5 in 1857 ms, avg 1684 ms   | 10/10 in 2723 ms, avg 1908 ms   | 25/25 in 6222 ms, avg 2860 ms   |              96
callie.jpg  | 352.6 KB | 800x600   | 346 ms     | 2/2 in 372 ms, avg 364 ms     | 5/5 in 368 ms, avg 362 ms     | 10/10 in 449 ms, avg 395 ms     | 25/25 in 436 ms, avg 398 ms     |             100

Results for height 2160:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 5 requests                    | 10 requests                     | 25 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|-------------------------------|---------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 10035 ms   | 2/2 in 15553 ms, avg 15542 ms | 5/5 in 26205 ms, avg 22593 ms | 10/10 in 38849 ms, avg 28754 ms | 25/25 in 40050 ms, avg 28589 ms |              95
pigeons.jpg | 12.1 MB  | 4656x3492 | 2527 ms    | 2/2 in 2736 ms, avg 2703 ms   | 5/5 in 3013 ms, avg 2827 ms   | 10/10 in 3190 ms, avg 2831 ms   | 25/25 in 3996 ms, avg 3223 ms   |             100
train.heic  | 16.4 MB  | 9248x6936 | 10524 ms   | 2/2 in 15522 ms, avg 15498 ms | 5/5 in 17729 ms, avg 16450 ms | 10/10 in 19609 ms, avg 18099 ms | 25/25 in 38776 ms, avg 22327 ms |             100
litter.jpg  | 12.6 MB  | 8384x6035 | 4719 ms    | 2/2 in 5246 ms, avg 5210 ms   | 5/5 in 5726 ms, avg 5503 ms   | 10/10 in 6338 ms, avg 5598 ms   | 25/25 in 17279 ms, avg 8451 ms  |             100
dubai.heic  | 18.6 MB  | 9248x6936 | 11594 ms   | 2/2 in 20174 ms, avg 20122 ms | 5/5 in 18794 ms, avg 17393 ms | 10/10 in 19798 ms, avg 16478 ms | 25/25 in 26794 ms, avg 17950 ms |             100
george.jpg  | 1.4 MB   | 4322x3289 | 2274 ms    | 2/2 in 2257 ms, avg 2238 ms   | 5/5 in 2491 ms, avg 2339 ms   | 10/10 in 3776 ms, avg 2856 ms   | 25/25 in 19936 ms, avg 11381 ms |              60
callie.jpg  | 352.6 KB | 800x600   | 489 ms     | 2/2 in 492 ms, avg 491 ms     | 5/5 in 500 ms, avg 491 ms     | 10/10 in 544 ms, avg 512 ms     | 25/25 in 652 ms, avg 546 ms     |             100

Results for height 4320:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 5 requests                    | 10 requests                     | 25 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|-------------------------------|---------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 14279 ms   | 2/2 in 20737 ms, avg 20686 ms | 5/5 in 31291 ms, avg 27078 ms | 10/10 in 48025 ms, avg 30822 ms | 25/25 in 41609 ms, avg 24012 ms |              97
pigeons.jpg | 12.1 MB  | 4656x3492 | 4275 ms    | 2/2 in 4645 ms, avg 4599 ms   | 5/5 in 4494 ms, avg 4421 ms   | 10/10 in 5296 ms, avg 4745 ms   | 25/25 in 12167 ms, avg 6442 ms  |             100
train.heic  | 16.4 MB  | 9248x6936 | 13917 ms   | 2/2 in 19256 ms, avg 19211 ms | 5/5 in 21634 ms, avg 20292 ms | 10/10 in 45109 ms, avg 29897 ms | 25/25 in 52690 ms, avg 27466 ms |             100
litter.jpg  | 12.6 MB  | 8384x6035 | 8098 ms    | 2/2 in 8920 ms, avg 8893 ms   | 5/5 in 9094 ms, avg 8828 ms   | 10/10 in 87861 ms, avg 25136 ms | 25/25 in 37327 ms, avg 16725 ms |              34
dubai.heic  | 18.6 MB  | 9248x6936 | 14303 ms   | 2/2 in 21158 ms, avg 21098 ms | 5/5 in 21925 ms, avg 20566 ms | 10/10 in 22035 ms, avg 18133 ms | 25/25 in 51253 ms, avg 24030 ms |             100
george.jpg  | 1.4 MB   | 4322x3289 | 3716 ms    | 2/2 in 3973 ms, avg 3953 ms   | 5/5 in 3893 ms, avg 3672 ms   | 10/10 in 9138 ms, avg 5998 ms   | 7/25 in 5717 ms, avg 4692 ms    |              20
callie.jpg  | 352.6 KB | 800x600   | 705 ms     | 2/2 in 715 ms, avg 714 ms     | 3/5 in 715 ms, avg 711 ms     | 8/10 in 1194 ms, avg 843 ms     | 24/25 in 2095 ms, avg 1295 ms   |               2

Results for height 100000:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 5 requests                    | 10 requests                     | 25 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|-------------------------------|---------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 20801 ms   | 2/2 in 26493 ms, avg 26419 ms | 5/5 in 72534 ms, avg 42075 ms | 10/10 in 51968 ms, avg 33244 ms | 24/25 in 43057 ms, avg 25611 ms |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 4884 ms    | 2/2 in 5245 ms, avg 5238 ms   | 3/5 in 5290 ms, avg 5174 ms   | 8/10 in 6156 ms, avg 5329 ms    | 22/25 in 15092 ms, avg 8422 ms  |               4
train.heic  | 16.4 MB  | 9248x6936 | 20936 ms   | 2/2 in 26883 ms, avg 26849 ms | 3/5 in 23102 ms, avg 22159 ms | 10/10 in 47231 ms, avg 31075 ms | 11/25 in 56019 ms, avg 34711 ms |              10
litter.jpg  | 12.6 MB  | 8384x6035 | 0/1        | 1/2 in 12226 ms               | 3/5 in 19386 ms, avg 19368 ms | 3/10 in 22728 ms, avg 22726 ms  | 22/25 in 59707 ms, avg 43851 ms |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 20870 ms   | 2/2 in 27479 ms, avg 27475 ms | 4/5 in 28919 ms, avg 25856 ms | 8/10 in 33891 ms, avg 26465 ms  | 7/25 in 67322 ms, avg 44806 ms  |               3
george.jpg  | 1.4 MB   | 4322x3289 | 4069 ms    | 1/2 in 4095 ms                | 5/5 in 4151 ms, avg 4131 ms   | 8/10 in 5712 ms, avg 4647 ms    | 7/25 in 27278 ms, avg 26759 ms  |               5
callie.jpg  | 352.6 KB | 800x600   | 27867 ms   | 2/2 in 749 ms, avg 740 ms     | 3/5 in 833 ms, avg 771 ms     | 8/10 in 980 ms, avg 868 ms      | 22/25 in 1763 ms, avg 1285 ms   |               4
```


#### 4 vCPU, 8 GiB RAM, concurrency limit 100


```
Results for height 64:
Image       | Filesize | Size      | 1 requests                  | 2 requests                    | 5 requests                    | 10 requests                     | 25 requests                     | Max concurrency
------------|----------|-----------|-----------------------------|-------------------------------|-------------------------------|---------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 8407 ms, avg 8407 ms | 2/2 in 7848 ms, avg 7834 ms   | 5/5 in 11448 ms, avg 9745 ms  | 10/10 in 14873 ms, avg 13754 ms | 25/25 in 18985 ms, avg 16708 ms |             100
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1359 ms, avg 1359 ms | 2/2 in 1348 ms, avg 1264 ms   | 5/5 in 1321 ms, avg 1294 ms   | 10/10 in 1499 ms, avg 1384 ms   | 25/25 in 2099 ms, avg 1609 ms   |             100
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 7499 ms, avg 7499 ms | 2/2 in 10084 ms, avg 10043 ms | 5/5 in 18643 ms, avg 16197 ms | 10/10 in 20345 ms, avg 18448 ms | 25/25 in 24514 ms, avg 17357 ms |             100
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 3421 ms, avg 3421 ms | 2/2 in 3630 ms, avg 3534 ms   | 5/5 in 4279 ms, avg 3680 ms   | 10/10 in 4926 ms, avg 4163 ms   | 25/25 in 9419 ms, avg 7521 ms   |             100
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 7602 ms, avg 7602 ms | 2/2 in 11515 ms, avg 11492 ms | 5/5 in 10557 ms, avg 9993 ms  | 10/10 in 18737 ms, avg 15215 ms | 25/25 in 20168 ms, avg 15291 ms |             100
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1600 ms, avg 1600 ms | 2/2 in 1684 ms, avg 1660 ms   | 5/5 in 1790 ms, avg 1650 ms   | 10/10 in 1837 ms, avg 1748 ms   | 25/25 in 2444 ms, avg 2044 ms   |             100
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 150 ms, avg 150 ms   | 2/2 in 137 ms, avg 136 ms     | 5/5 in 154 ms, avg 142 ms     | 10/10 in 178 ms, avg 150 ms     | 25/25 in 171 ms, avg 151 ms     |             100

Results for height 192:
Image       | Filesize | Size      | 1 requests                  | 2 requests                    | 5 requests                    | 10 requests                     | 25 requests                     | Max concurrency
------------|----------|-----------|-----------------------------|-------------------------------|-------------------------------|---------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 7384 ms, avg 7384 ms | 2/2 in 7529 ms, avg 7144 ms   | 5/5 in 16716 ms, avg 14747 ms | 10/10 in 19364 ms, avg 17659 ms | 25/25 in 16956 ms, avg 10512 ms |             100
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1304 ms, avg 1304 ms | 2/2 in 1392 ms, avg 1377 ms   | 5/5 in 1539 ms, avg 1423 ms   | 10/10 in 1906 ms, avg 1776 ms   | 25/25 in 3052 ms, avg 2148 ms   |             100
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 7067 ms, avg 7067 ms | 2/2 in 9881 ms, avg 9864 ms   | 5/5 in 13258 ms, avg 12144 ms | 10/10 in 17262 ms, avg 14193 ms | 25/25 in 17863 ms, avg 13536 ms |             100
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 4046 ms, avg 4046 ms | 2/2 in 3554 ms, avg 3538 ms   | 5/5 in 4119 ms, avg 3946 ms   | 10/10 in 5487 ms, avg 5131 ms   | 25/25 in 5865 ms, avg 4925 ms   |             100
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 7381 ms, avg 7381 ms | 2/2 in 10934 ms, avg 10912 ms | 5/5 in 15472 ms, avg 14412 ms | 10/10 in 19506 ms, avg 15765 ms | 25/25 in 18915 ms, avg 12876 ms |             100
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1643 ms, avg 1643 ms | 2/2 in 1643 ms, avg 1463 ms   | 5/5 in 2029 ms, avg 1921 ms   | 10/10 in 2121 ms, avg 1766 ms   | 25/25 in 2666 ms, avg 2043 ms   |              97
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 175 ms, avg 175 ms   | 2/2 in 179 ms, avg 177 ms     | 5/5 in 203 ms, avg 182 ms     | 10/10 in 190 ms, avg 177 ms     | 25/25 in 221 ms, avg 184 ms     |             100

Results for height 300:
Image       | Filesize | Size      | 1 requests                  | 2 requests                    | 5 requests                    | 10 requests                     | 25 requests                     | Max concurrency
------------|----------|-----------|-----------------------------|-------------------------------|-------------------------------|---------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 7304 ms, avg 7304 ms | 2/2 in 10405 ms, avg 10392 ms | 5/5 in 14804 ms, avg 13382 ms | 10/10 in 19929 ms, avg 17723 ms | 25/25 in 17509 ms, avg 12936 ms |             100
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1327 ms, avg 1327 ms | 2/2 in 1364 ms, avg 1335 ms   | 5/5 in 2158 ms, avg 2020 ms   | 10/10 in 2392 ms, avg 2253 ms   | 25/25 in 2823 ms, avg 2292 ms   |             100
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 8036 ms, avg 8036 ms | 2/2 in 12719 ms, avg 12695 ms | 5/5 in 16650 ms, avg 15038 ms | 10/10 in 20117 ms, avg 18125 ms | 25/25 in 19834 ms, avg 16897 ms |              95
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 3840 ms, avg 3840 ms | 2/2 in 5327 ms, avg 5320 ms   | 5/5 in 6051 ms, avg 5603 ms   | 10/10 in 6301 ms, avg 6058 ms   | 25/25 in 5738 ms, avg 4804 ms   |             100
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 7754 ms, avg 7754 ms | 2/2 in 11098 ms, avg 11062 ms | 5/5 in 14760 ms, avg 13540 ms | 10/10 in 15215 ms, avg 13796 ms | 25/25 in 18061 ms, avg 11806 ms |              99
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1553 ms, avg 1553 ms | 2/2 in 1580 ms, avg 1536 ms   | 5/5 in 2070 ms, avg 1937 ms   | 10/10 in 2140 ms, avg 1876 ms   | 25/25 in 5261 ms, avg 2937 ms   |             100
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 338 ms, avg 338 ms   | 2/2 in 357 ms, avg 347 ms     | 5/5 in 374 ms, avg 354 ms     | 10/10 in 380 ms, avg 360 ms     | 25/25 in 629 ms, avg 501 ms     |             100

Results for height 480:
Image       | Filesize | Size      | 1 requests                  | 2 requests                    | 5 requests                    | 10 requests                     | 25 requests                     | Max concurrency
------------|----------|-----------|-----------------------------|-------------------------------|-------------------------------|---------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 7540 ms, avg 7540 ms | 2/2 in 10123 ms, avg 10121 ms | 5/5 in 13775 ms, avg 12666 ms | 10/10 in 14189 ms, avg 13326 ms | 25/25 in 18396 ms, avg 14035 ms |              61
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1502 ms, avg 1502 ms | 2/2 in 1702 ms, avg 1699 ms   | 5/5 in 2036 ms, avg 1937 ms   | 10/10 in 2534 ms, avg 2460 ms   | 25/25 in 2998 ms, avg 2514 ms   |             100
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 8040 ms, avg 8040 ms | 2/2 in 10770 ms, avg 10759 ms | 5/5 in 13497 ms, avg 12478 ms | 10/10 in 14238 ms, avg 12923 ms | 25/25 in 19268 ms, avg 15228 ms |             100
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 4013 ms, avg 4013 ms | 2/2 in 4826 ms, avg 4814 ms   | 5/5 in 4702 ms, avg 4630 ms   | 10/10 in 5535 ms, avg 5226 ms   | 25/25 in 7904 ms, avg 6549 ms   |             100
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 8451 ms, avg 8451 ms | 2/2 in 11224 ms, avg 11181 ms | 5/5 in 14739 ms, avg 13595 ms | 10/10 in 15258 ms, avg 13426 ms | 25/25 in 21506 ms, avg 15982 ms |             100
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 2421 ms, avg 2421 ms | 2/2 in 2129 ms, avg 1828 ms   | 5/5 in 3176 ms, avg 2409 ms   | 10/10 in 3926 ms, avg 3030 ms   | 25/25 in 11067 ms, avg 8121 ms  |             100
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 425 ms, avg 425 ms   | 2/2 in 468 ms, avg 443 ms     | 5/5 in 457 ms, avg 455 ms     | 10/10 in 520 ms, avg 460 ms     | 25/25 in 634 ms, avg 533 ms     |             100

Results for height 2160:
Image       | Filesize | Size      | 1 requests                  | 2 requests                    | 5 requests                    | 10 requests                     | 25 requests                     | Max concurrency
------------|----------|-----------|-----------------------------|-------------------------------|-------------------------------|---------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 8864 ms, avg 8864 ms | 2/2 in 11727 ms, avg 11702 ms | 5/5 in 14687 ms, avg 13551 ms | 10/10 in 19385 ms, avg 16019 ms | 25/25 in 20173 ms, avg 16222 ms |             100
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 2569 ms, avg 2569 ms | 2/2 in 2828 ms, avg 2805 ms   | 5/5 in 3430 ms, avg 3339 ms   | 10/10 in 4159 ms, avg 4010 ms   | 25/25 in 5036 ms, avg 3969 ms   |             100
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 8960 ms, avg 8960 ms | 2/2 in 11819 ms, avg 11799 ms | 5/5 in 14579 ms, avg 13401 ms | 10/10 in 14989 ms, avg 13888 ms | 25/25 in 18974 ms, avg 15587 ms |             100
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 4861 ms, avg 4861 ms | 2/2 in 4965 ms, avg 4959 ms   | 5/5 in 5775 ms, avg 5624 ms   | 10/10 in 6829 ms, avg 6489 ms   | 25/25 in 10293 ms, avg 8576 ms  |             100
dubai.heic  | 18.6 MB  | 9248x6936 | 0/1 in 0 ms, avg 0 ms       | 2/2 in 12186 ms, avg 12096 ms | 5/5 in 15658 ms, avg 14384 ms | 10/10 in 19508 ms, avg 16231 ms | 23/25 in 19980 ms, avg 17488 ms |              10
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 2383 ms, avg 2383 ms | 2/2 in 2284 ms, avg 2277 ms   | 5/5 in 3484 ms, avg 3298 ms   | 10/10 in 3175 ms, avg 2871 ms   | 25/25 in 11413 ms, avg 7770 ms  |              27
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 485 ms, avg 485 ms   | 2/2 in 489 ms, avg 488 ms     | 5/5 in 521 ms, avg 500 ms     | 10/10 in 820 ms, avg 550 ms     | 25/25 in 673 ms, avg 573 ms     |             100

Results for height 4320:
Image       | Filesize | Size      | 1 requests                    | 2 requests                    | 5 requests                    | 10 requests                     | 25 requests                     | Max concurrency
------------|----------|-----------|-------------------------------|-------------------------------|-------------------------------|---------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 11965 ms, avg 11965 ms | 2/2 in 14934 ms, avg 14919 ms | 5/5 in 18159 ms, avg 17015 ms | 10/10 in 21540 ms, avg 18263 ms | 25/25 in 21948 ms, avg 17235 ms |             100
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 4106 ms, avg 4106 ms   | 2/2 in 4871 ms, avg 4862 ms   | 5/5 in 5165 ms, avg 5047 ms   | 10/10 in 5646 ms, avg 5266 ms   | 25/25 in 8040 ms, avg 6503 ms   |             100
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 11887 ms, avg 11887 ms | 2/2 in 15036 ms, avg 15013 ms | 5/5 in 18102 ms, avg 16882 ms | 10/10 in 21434 ms, avg 18577 ms | 24/25 in 22712 ms, avg 19138 ms |              24
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 8142 ms, avg 8142 ms   | 2/2 in 8848 ms, avg 8822 ms   | 5/5 in 10072 ms, avg 9805 ms  | 10/10 in 10173 ms, avg 9864 ms  | 25/25 in 11212 ms, avg 9590 ms  |              43
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 12014 ms, avg 12014 ms | 2/2 in 15472 ms, avg 15416 ms | 5/5 in 18957 ms, avg 17761 ms | 10/10 in 19325 ms, avg 15679 ms | 25/25 in 23823 ms, avg 18543 ms |             100
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 3831 ms, avg 3831 ms   | 2/2 in 3692 ms, avg 3650 ms   | 5/5 in 3912 ms, avg 3839 ms   | 10/10 in 5039 ms, avg 4710 ms   | 5/25 in 4007 ms, avg 3937 ms    |              24
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 714 ms, avg 714 ms     | 2/2 in 689 ms, avg 682 ms     | 5/5 in 712 ms, avg 702 ms     | 10/10 in 754 ms, avg 737 ms     | 25/25 in 1029 ms, avg 863 ms    |             100

Results for height 100000:
Image       | Filesize | Size      | 1 requests                    | 2 requests                    | 5 requests                    | 10 requests                     | 25 requests                     | Max concurrency
------------|----------|-----------|-------------------------------|-------------------------------|-------------------------------|---------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 19813 ms, avg 19813 ms | 2/2 in 24940 ms, avg 24845 ms | 5/5 in 29646 ms, avg 27933 ms | 9/10 in 30830 ms, avg 24566 ms  | 24/25 in 28148 ms, avg 22484 ms |               5
pigeons.jpg | 12.1 MB  | 4656x3492 | 0/1 in 0 ms, avg 0 ms         | 0/2 in 0 ms, avg 0 ms         | 0/5 in 0 ms, avg 0 ms         | 8/10 in 14696 ms, avg 12029 ms  | 22/25 in 8034 ms, avg 6121 ms   |               1
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 19805 ms, avg 19805 ms | 2/2 in 24773 ms, avg 24719 ms | 5/5 in 32093 ms, avg 26119 ms | 10/10 in 23932 ms, avg 21189 ms | 24/25 in 31582 ms, avg 26042 ms |              17
litter.jpg  | 12.6 MB  | 8384x6035 | 0/1 in 0 ms, avg 0 ms         | 0/2 in 0 ms, avg 0 ms         | 0/5 in 0 ms, avg 0 ms         | 0/10 in 0 ms, avg 0 ms          | 0/25 in 0 ms, avg 0 ms          |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 0/1 in 0 ms, avg 0 ms         | 0/2 in 0 ms, avg 0 ms         | 2/5 in 26727 ms, avg 26661 ms | 8/10 in 24169 ms, avg 22453 ms  | 8/25 in 40661 ms, avg 30414 ms  |               1
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 4112 ms, avg 4112 ms   | 2/2 in 4024 ms, avg 3995 ms   | 5/5 in 6379 ms, avg 6182 ms   | 10/10 in 13588 ms, avg 13311 ms | 21/25 in 8564 ms, avg 6923 ms   |              20
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 705 ms, avg 705 ms     | 2/2 in 759 ms, avg 743 ms     | 5/5 in 27429 ms, avg 11358 ms | 10/10 in 1348 ms, avg 935 ms    | 25/25 in 1145 ms, avg 876 ms    |             100
```

#### 4 vCPU, 16 GiB RAM, concurrency limit 100

```
Results for height 64:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 5 requests                    | 10 requests                     | 25 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|-------------------------------|---------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 7682 ms    | 2/2 in 9923 ms, avg 9916 ms   | 5/5 in 9913 ms, avg 9391 ms   | 10/10 in 17501 ms, avg 14539 ms | 25/25 in 17497 ms, avg 12931 ms |             100
pigeons.jpg | 12.1 MB  | 4656x3492 | 1382 ms    | 2/2 in 1329 ms, avg 1302 ms   | 5/5 in 1820 ms, avg 1748 ms   | 10/10 in 1556 ms, avg 1404 ms   | 25/25 in 2373 ms, avg 1615 ms   |             100
train.heic  | 16.4 MB  | 9248x6936 | 7643 ms    | 2/2 in 10488 ms, avg 10449 ms | 5/5 in 13668 ms, avg 11704 ms | 10/10 in 13665 ms, avg 12163 ms | 25/25 in 15279 ms, avg 12870 ms |             100
litter.jpg  | 12.6 MB  | 8384x6035 | 3494 ms    | 2/2 in 4564 ms, avg 4501 ms   | 5/5 in 5330 ms, avg 4952 ms   | 10/10 in 5549 ms, avg 4943 ms   | 25/25 in 7327 ms, avg 5840 ms   |             100
dubai.heic  | 18.6 MB  | 9248x6936 | 8353 ms    | 2/2 in 7991 ms, avg 7981 ms   | 5/5 in 12779 ms, avg 11956 ms | 10/10 in 19618 ms, avg 17130 ms | 25/25 in 20485 ms, avg 15575 ms |             100
george.jpg  | 1.4 MB   | 4322x3289 | 1666 ms    | 2/2 in 1648 ms, avg 1643 ms   | 5/5 in 1670 ms, avg 1629 ms   | 10/10 in 1853 ms, avg 1692 ms   | 25/25 in 2507 ms, avg 2077 ms   |             100
callie.jpg  | 352.6 KB | 800x600   | 160 ms     | 2/2 in 330 ms, avg 326 ms     | 5/5 in 350 ms, avg 262 ms     | 10/10 in 378 ms, avg 319 ms     | 25/25 in 466 ms, avg 425 ms     |             100

Results for height 192:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 5 requests                    | 10 requests                     | 25 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|-------------------------------|---------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 9149 ms    | 2/2 in 10647 ms, avg 10630 ms | 5/5 in 13542 ms, avg 12442 ms | 10/10 in 16543 ms, avg 13681 ms | 25/25 in 17757 ms, avg 14608 ms |             100
pigeons.jpg | 12.1 MB  | 4656x3492 | 1352 ms    | 2/2 in 1477 ms, avg 1416 ms   | 5/5 in 1479 ms, avg 1429 ms   | 10/10 in 1889 ms, avg 1594 ms   | 25/25 in 3008 ms, avg 2406 ms   |             100
train.heic  | 16.4 MB  | 9248x6936 | 7710 ms    | 2/2 in 9647 ms, avg 9640 ms   | 5/5 in 14273 ms, avg 13012 ms | 10/10 in 15398 ms, avg 14455 ms | 25/25 in 18788 ms, avg 13183 ms |             100
litter.jpg  | 12.6 MB  | 8384x6035 | 3762 ms    | 2/2 in 3764 ms, avg 3761 ms   | 5/5 in 5123 ms, avg 4921 ms   | 10/10 in 5554 ms, avg 5030 ms   | 25/25 in 7867 ms, avg 6032 ms   |             100
dubai.heic  | 18.6 MB  | 9248x6936 | 7862 ms    | 2/2 in 11354 ms, avg 11351 ms | 5/5 in 14870 ms, avg 13724 ms | 10/10 in 18026 ms, avg 16560 ms | 25/25 in 18051 ms, avg 13452 ms |              99
george.jpg  | 1.4 MB   | 4322x3289 | 1745 ms    | 2/2 in 1769 ms, avg 1759 ms   | 5/5 in 1849 ms, avg 1783 ms   | 10/10 in 2038 ms, avg 1899 ms   | 25/25 in 2745 ms, avg 2353 ms   |             100
callie.jpg  | 352.6 KB | 800x600   | 221 ms     | 2/2 in 227 ms, avg 227 ms     | 5/5 in 247 ms, avg 232 ms     | 10/10 in 237 ms, avg 227 ms     | 25/25 in 252 ms, avg 230 ms     |             100

Results for height 300:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 5 requests                    | 10 requests                     | 25 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|-------------------------------|---------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 7628 ms    | 2/2 in 10705 ms, avg 10689 ms | 5/5 in 13293 ms, avg 12403 ms | 10/10 in 16919 ms, avg 14196 ms | 25/25 in 16198 ms, avg 12632 ms |             100
pigeons.jpg | 12.1 MB  | 4656x3492 | 1457 ms    | 2/2 in 1855 ms, avg 1854 ms   | 5/5 in 2099 ms, avg 1975 ms   | 10/10 in 1901 ms, avg 1752 ms   | 25/25 in 3176 ms, avg 2415 ms   |             100
train.heic  | 16.4 MB  | 9248x6936 | 7642 ms    | 2/2 in 10646 ms, avg 10596 ms | 5/5 in 13783 ms, avg 12766 ms | 10/10 in 17753 ms, avg 15033 ms | 25/25 in 21126 ms, avg 13589 ms |             100
litter.jpg  | 12.6 MB  | 8384x6035 | 4144 ms    | 2/2 in 4115 ms, avg 4104 ms   | 5/5 in 4319 ms, avg 4221 ms   | 10/10 in 5133 ms, avg 4681 ms   | 25/25 in 9082 ms, avg 6784 ms   |             100
dubai.heic  | 18.6 MB  | 9248x6936 | 7759 ms    | 2/2 in 10776 ms, avg 10737 ms | 5/5 in 13971 ms, avg 12903 ms | 10/10 in 15197 ms, avg 13688 ms | 25/25 in 18509 ms, avg 12581 ms |             100
george.jpg  | 1.4 MB   | 4322x3289 | 1856 ms    | 2/2 in 1995 ms, avg 1888 ms   | 5/5 in 2810 ms, avg 2552 ms   | 10/10 in 2502 ms, avg 2186 ms   | 25/25 in 2659 ms, avg 2192 ms   |             100
callie.jpg  | 352.6 KB | 800x600   | 266 ms     | 2/2 in 275 ms, avg 272 ms     | 5/5 in 274 ms, avg 273 ms     | 10/10 in 306 ms, avg 282 ms     | 25/25 in 321 ms, avg 292 ms     |             100

Results for height 480:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 5 requests                    | 10 requests                     | 25 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|-------------------------------|---------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 7472 ms    | 2/2 in 10810 ms, avg 10793 ms | 5/5 in 13967 ms, avg 12865 ms | 10/10 in 14295 ms, avg 12828 ms | 25/25 in 17596 ms, avg 13698 ms |             100
pigeons.jpg | 12.1 MB  | 4656x3492 | 1389 ms    | 2/2 in 1449 ms, avg 1448 ms   | 5/5 in 2049 ms, avg 1982 ms   | 10/10 in 2231 ms, avg 2103 ms   | 25/25 in 2186 ms, avg 1979 ms   |             100
train.heic  | 16.4 MB  | 9248x6936 | 8371 ms    | 2/2 in 10929 ms, avg 10924 ms | 5/5 in 13759 ms, avg 12643 ms | 10/10 in 17507 ms, avg 14661 ms | 25/25 in 21908 ms, avg 15183 ms |             100
litter.jpg  | 12.6 MB  | 8384x6035 | 3910 ms    | 2/2 in 4047 ms, avg 4046 ms   | 5/5 in 4915 ms, avg 4604 ms   | 10/10 in 5602 ms, avg 5145 ms   | 25/25 in 6995 ms, avg 5603 ms   |             100
dubai.heic  | 18.6 MB  | 9248x6936 | 8364 ms    | 2/2 in 10951 ms, avg 10948 ms | 5/5 in 14733 ms, avg 13628 ms | 10/10 in 15613 ms, avg 13837 ms | 25/25 in 18727 ms, avg 13198 ms |             100
george.jpg  | 1.4 MB   | 4322x3289 | 1510 ms    | 2/2 in 1516 ms, avg 1498 ms   | 5/5 in 2019 ms, avg 1831 ms   | 10/10 in 1920 ms, avg 1687 ms   | 25/25 in 4200 ms, avg 2903 ms   |             100
callie.jpg  | 352.6 KB | 800x600   | 413 ms     | 2/2 in 374 ms, avg 373 ms     | 5/5 in 399 ms, avg 381 ms     | 10/10 in 427 ms, avg 384 ms     | 25/25 in 448 ms, avg 410 ms     |             100

Results for height 2160:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 5 requests                    | 10 requests                     | 25 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|-------------------------------|---------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 8452 ms    | 2/2 in 11252 ms, avg 11192 ms | 5/5 in 14006 ms, avg 13086 ms | 10/10 in 16726 ms, avg 14892 ms | 25/25 in 18885 ms, avg 14360 ms |             100
pigeons.jpg | 12.1 MB  | 4656x3492 | 2554 ms    | 2/2 in 2660 ms, avg 2654 ms   | 5/5 in 3041 ms, avg 2879 ms   | 10/10 in 3458 ms, avg 3139 ms   | 25/25 in 4635 ms, avg 3684 ms   |             100
train.heic  | 16.4 MB  | 9248x6936 | 8493 ms    | 2/2 in 10985 ms, avg 10960 ms | 5/5 in 14800 ms, avg 13703 ms | 10/10 in 16577 ms, avg 14941 ms | 25/25 in 18130 ms, avg 13516 ms |             100
litter.jpg  | 12.6 MB  | 8384x6035 | 4811 ms    | 2/2 in 5270 ms, avg 5233 ms   | 5/5 in 6526 ms, avg 6245 ms   | 10/10 in 7322 ms, avg 6756 ms   | 25/25 in 10536 ms, avg 7850 ms  |             100
dubai.heic  | 18.6 MB  | 9248x6936 | 8564 ms    | 2/2 in 12161 ms, avg 12143 ms | 5/5 in 15561 ms, avg 14274 ms | 10/10 in 19587 ms, avg 16413 ms | 25/25 in 24428 ms, avg 16180 ms |             100
george.jpg  | 1.4 MB   | 4322x3289 | 2106 ms    | 2/2 in 2209 ms, avg 2190 ms   | 5/5 in 2396 ms, avg 2265 ms   | 10/10 in 3113 ms, avg 2640 ms   | 25/25 in 4570 ms, avg 3824 ms   |              64
callie.jpg  | 352.6 KB | 800x600   | 462 ms     | 2/2 in 462 ms, avg 461 ms     | 5/5 in 493 ms, avg 474 ms     | 10/10 in 503 ms, avg 472 ms     | 25/25 in 518 ms, avg 479 ms     |             100

Results for height 4320:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 5 requests                    | 10 requests                     | 25 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|-------------------------------|---------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 12100 ms   | 2/2 in 14594 ms, avg 14561 ms | 5/5 in 20044 ms, avg 18534 ms | 10/10 in 21843 ms, avg 20412 ms | 25/25 in 25756 ms, avg 19323 ms |             100
pigeons.jpg | 12.1 MB  | 4656x3492 | 4050 ms    | 2/2 in 4356 ms, avg 4315 ms   | 5/5 in 5190 ms, avg 5047 ms   | 10/10 in 5347 ms, avg 4993 ms   | 25/25 in 5579 ms, avg 5005 ms   |             100
train.heic  | 16.4 MB  | 9248x6936 | 11576 ms   | 2/2 in 14473 ms, avg 14414 ms | 5/5 in 18707 ms, avg 17589 ms | 10/10 in 20243 ms, avg 16428 ms | 25/25 in 27151 ms, avg 19170 ms |             100
litter.jpg  | 12.6 MB  | 8384x6035 | 8826 ms    | 2/2 in 8819 ms, avg 8808 ms   | 5/5 in 9076 ms, avg 8945 ms   | 10/10 in 10667 ms, avg 9918 ms  | 23/25 in 93455 ms, avg 17107 ms |              22
dubai.heic  | 18.6 MB  | 9248x6936 | 12712 ms   | 2/2 in 16047 ms, avg 16012 ms | 5/5 in 19847 ms, avg 18588 ms | 10/10 in 26026 ms, avg 20520 ms | 25/25 in 23534 ms, avg 18198 ms |             100
george.jpg  | 1.4 MB   | 4322x3289 | 3396 ms    | 2/2 in 3438 ms, avg 3417 ms   | 5/5 in 3685 ms, avg 3546 ms   | 10/10 in 4353 ms, avg 3924 ms   | 25/25 in 11156 ms, avg 7073 ms  |              25
callie.jpg  | 352.6 KB | 800x600   | 517 ms     | 2/2 in 524 ms, avg 523 ms     | 5/5 in 540 ms, avg 537 ms     | 10/10 in 864 ms, avg 591 ms     | 25/25 in 9976 ms, avg 3247 ms   |             100

Results for height 100000:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 5 requests                    | 10 requests                     | 25 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|-------------------------------|---------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 18577 ms   | 2/2 in 23115 ms, avg 23112 ms | 5/5 in 27499 ms, avg 25530 ms | 10/10 in 31708 ms, avg 25806 ms | 25/25 in 23709 ms, avg 20658 ms |              25
pigeons.jpg | 12.1 MB  | 4656x3492 | 69003 ms   | 2/2 in 5303 ms, avg 5065 ms   | 3/5 in 5215 ms, avg 5189 ms   | 4/10 in 6042 ms, avg 5991 ms    | 3/25 in 5305 ms, avg 5143 ms    |               2
train.heic  | 16.4 MB  | 9248x6936 | 20360 ms   | 2/2 in 22852 ms, avg 22851 ms | 1/5 in 19239 ms               | 2/10 in 22060 ms, avg 21921 ms  | 0/25                            |               2
litter.jpg  | 12.6 MB  | 8384x6035 | 0/1        | 0/2                           | 0/5                           | 1/10 in 13048 ms                | 0/25                            |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 0/1        | 0/2                           | 0/5                           | 0/10                            | 0/25                            |               1
george.jpg  | 1.4 MB   | 4322x3289 | 3594 ms    | 2/2 in 4415 ms, avg 4231 ms   | 0/5                           | 10/10 in 4605 ms, avg 4171 ms   | 25/25 in 5177 ms, avg 4243 ms   |              25
callie.jpg  | 352.6 KB | 800x600   | 725 ms     | 0/2                           | 5/5 in 745 ms, avg 717 ms     | 10/10 in 720 ms, avg 699 ms     | 25/25 in 793 ms, avg 727 ms     |              25
```

#### 1 vCPU, 4 GiB RAM, concurrency limit 1

```
Results for height 64:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 10 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 15347 ms   | 2/2 in 16457 ms, avg 15488 ms | 10/10 in 17481 ms, avg 15643 ms |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 1927 ms    | 2/2 in 1756 ms, avg 1679 ms   | 10/10 in 1896 ms, avg 1764 ms   |              10
train.heic  | 16.4 MB  | 9248x6936 | 15282 ms   | 2/2 in 15871 ms, avg 15594 ms | 10/10 in 17633 ms, avg 16264 ms |              10
litter.jpg  | 12.6 MB  | 8384x6035 | 4440 ms    | 2/2 in 4056 ms, avg 3932 ms   | 10/10 in 6463 ms, avg 5367 ms   |              10
dubai.heic  | 18.6 MB  | 9248x6936 | 14971 ms   | 2/2 in 16820 ms, avg 16307 ms | 10/10 in 20802 ms, avg 18312 ms |              10
george.jpg  | 1.4 MB   | 4322x3289 | 2662 ms    | 2/2 in 2779 ms, avg 2717 ms   | 10/10 in 3370 ms, avg 2941 ms   |              10
callie.jpg  | 352.6 KB | 800x600   | 875 ms     | 2/2 in 865 ms, avg 863 ms     | 10/10 in 1286 ms, avg 941 ms    |              10

Results for height 192:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 10 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 14105 ms   | 2/2 in 15086 ms, avg 15020 ms | 10/10 in 17715 ms, avg 16218 ms |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 2575 ms    | 2/2 in 3255 ms, avg 2570 ms   | 10/10 in 1868 ms, avg 1749 ms   |              10
train.heic  | 16.4 MB  | 9248x6936 | 13518 ms   | 2/2 in 14283 ms, avg 14258 ms | 10/10 in 17298 ms, avg 15967 ms |              10
litter.jpg  | 12.6 MB  | 8384x6035 | 4067 ms    | 2/2 in 4152 ms, avg 4145 ms   | 10/10 in 4908 ms, avg 4403 ms   |              10
dubai.heic  | 18.6 MB  | 9248x6936 | 14709 ms   | 2/2 in 15590 ms, avg 14936 ms | 10/10 in 18793 ms, avg 17103 ms |              10
george.jpg  | 1.4 MB   | 4322x3289 | 1980 ms    | 2/2 in 2082 ms, avg 2069 ms   | 10/10 in 2593 ms, avg 2326 ms   |              10
callie.jpg  | 352.6 KB | 800x600   | 367 ms     | 2/2 in 408 ms, avg 401 ms     | 10/10 in 727 ms, avg 503 ms     |              10

Results for height 300:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 10 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 14932 ms   | 2/2 in 14407 ms, avg 14225 ms | 10/10 in 18247 ms, avg 16565 ms |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 1514 ms    | 2/2 in 1484 ms, avg 1464 ms   | 10/10 in 1790 ms, avg 1610 ms   |              10
train.heic  | 16.4 MB  | 9248x6936 | 15123 ms   | 2/2 in 14934 ms, avg 14806 ms | 10/10 in 16958 ms, avg 15903 ms |              10
litter.jpg  | 12.6 MB  | 8384x6035 | 4682 ms    | 2/2 in 4331 ms, avg 4308 ms   | 10/10 in 4659 ms, avg 4178 ms   |              10
dubai.heic  | 18.6 MB  | 9248x6936 | 16508 ms   | 2/2 in 15544 ms, avg 15301 ms | 10/10 in 17472 ms, avg 16297 ms |              10
george.jpg  | 1.4 MB   | 4322x3289 | 1856 ms    | 2/2 in 1847 ms, avg 1826 ms   | 10/10 in 2395 ms, avg 1878 ms   |              10
callie.jpg  | 352.6 KB | 800x600   | 124 ms     | 2/2 in 146 ms, avg 139 ms     | 10/10 in 254 ms, avg 163 ms     |              10

Results for height 480:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 10 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 15154 ms   | 2/2 in 15168 ms, avg 15090 ms | 10/10 in 17296 ms, avg 15819 ms |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 1529 ms    | 2/2 in 1476 ms, avg 1456 ms   | 10/10 in 2867 ms, avg 2214 ms   |              10
train.heic  | 16.4 MB  | 9248x6936 | 14681 ms   | 2/2 in 14801 ms, avg 14493 ms | 10/10 in 17869 ms, avg 15895 ms |              10
litter.jpg  | 12.6 MB  | 8384x6035 | 4117 ms    | 2/2 in 4092 ms, avg 3881 ms   | 10/10 in 5052 ms, avg 4226 ms   |              10
dubai.heic  | 18.6 MB  | 9248x6936 | 16132 ms   | 2/2 in 15960 ms, avg 15340 ms | 10/10 in 17720 ms, avg 16498 ms |              10
george.jpg  | 1.4 MB   | 4322x3289 | 1632 ms    | 2/2 in 1513 ms, avg 1494 ms   | 10/10 in 2655 ms, avg 1762 ms   |              10
callie.jpg  | 352.6 KB | 800x600   | 153 ms     | 2/2 in 149 ms, avg 143 ms     | 10/10 in 200 ms, avg 179 ms     |              10

Results for height 2160:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 10 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 17223 ms   | 2/2 in 15345 ms, avg 15158 ms | 10/10 in 19384 ms, avg 17593 ms |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 2549 ms    | 2/2 in 2815 ms, avg 2811 ms   | 10/10 in 3348 ms, avg 2795 ms   |              10
train.heic  | 16.4 MB  | 9248x6936 | 14834 ms   | 2/2 in 16373 ms, avg 15381 ms | 10/10 in 19201 ms, avg 17164 ms |              10
litter.jpg  | 12.6 MB  | 8384x6035 | 4613 ms    | 2/2 in 5705 ms, avg 5568 ms   | 10/10 in 6400 ms, avg 5675 ms   |              10
dubai.heic  | 18.6 MB  | 9248x6936 | 16314 ms   | 2/2 in 16365 ms, avg 16133 ms | 10/10 in 21373 ms, avg 19329 ms |              10
george.jpg  | 1.4 MB   | 4322x3289 | 2096 ms    | 2/2 in 2241 ms, avg 2176 ms   | 10/10 in 2631 ms, avg 2409 ms   |              10
callie.jpg  | 352.6 KB | 800x600   | 170 ms     | 2/2 in 171 ms, avg 167 ms     | 10/10 in 422 ms, avg 293 ms     |              10

Results for height 4320:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 10 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 20455 ms   | 2/2 in 19252 ms, avg 19073 ms | 10/10 in 22495 ms, avg 21179 ms |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 4094 ms    | 2/2 in 5048 ms, avg 4950 ms   | 10/10 in 5263 ms, avg 4483 ms   |              10
train.heic  | 16.4 MB  | 9248x6936 | 18739 ms   | 2/2 in 19740 ms, avg 19080 ms | 10/10 in 22980 ms, avg 20754 ms |              10
litter.jpg  | 12.6 MB  | 8384x6035 | 8344 ms    | 2/2 in 8954 ms, avg 8824 ms   | 10/10 in 9803 ms, avg 9029 ms   |              10
dubai.heic  | 18.6 MB  | 9248x6936 | 20751 ms   | 2/2 in 21526 ms, avg 20463 ms | 10/10 in 23823 ms, avg 21640 ms |              10
george.jpg  | 1.4 MB   | 4322x3289 | 3489 ms    | 2/2 in 3370 ms, avg 3368 ms   | 10/10 in 4157 ms, avg 3684 ms   |              10
callie.jpg  | 352.6 KB | 800x600   | 188 ms     | 2/2 in 183 ms, avg 174 ms     | 10/10 in 743 ms, avg 338 ms     |              10

Results for height 100000:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 10 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 30477 ms   | 2/2 in 27554 ms, avg 27413 ms | 10/10 in 33489 ms, avg 29705 ms |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 4838 ms    | 2/2 in 4395 ms, avg 4234 ms   | 10/10 in 5093 ms, avg 4368 ms   |              10
train.heic  | 16.4 MB  | 9248x6936 | 26798 ms   | 2/2 in 25138 ms, avg 25004 ms | 10/10 in 31484 ms, avg 27200 ms |              10
litter.jpg  | 12.6 MB  | 8384x6035 | 13018 ms   | 2/2 in 13282 ms, avg 12874 ms | 10/10 in 14190 ms, avg 13186 ms |              10
dubai.heic  | 18.6 MB  | 9248x6936 | 29651 ms   | 2/2 in 29260 ms, avg 28900 ms | 10/10 in 39324 ms, avg 29657 ms |              10
george.jpg  | 1.4 MB   | 4322x3289 | 3847 ms    | 2/2 in 3729 ms, avg 3667 ms   | 10/10 in 3577 ms, avg 3283 ms   |              10
callie.jpg  | 352.6 KB | 800x600   | 534 ms     | 2/2 in 204 ms, avg 196 ms     | 10/10 in 245 ms, avg 216 ms     |              10
```

#### 2 vCPU, 4 GiB RAM, concurrency limit 1

```
Results for height 64:
Image       | Filesize | Size      | 1 requests | 2 requests                   | 10 requests                     | Max concurrency
------------|----------|-----------|------------|------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 10553 ms   | 2/2 in 9380 ms, avg 9023 ms  | 10/10 in 12992 ms, avg 11403 ms |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 1463 ms    | 2/2 in 1652 ms, avg 1592 ms  | 10/10 in 2244 ms, avg 1858 ms   |              10
train.heic  | 16.4 MB  | 9248x6936 | 8698 ms    | 2/2 in 10483 ms, avg 9493 ms | 10/10 in 12910 ms, avg 11568 ms |              10
litter.jpg  | 12.6 MB  | 8384x6035 | 3621 ms    | 2/2 in 4083 ms, avg 3974 ms  | 10/10 in 5362 ms, avg 4421 ms   |              10
dubai.heic  | 18.6 MB  | 9248x6936 | 8574 ms    | 2/2 in 10411 ms, avg 9700 ms | 10/10 in 13484 ms, avg 12123 ms |              10
george.jpg  | 1.4 MB   | 4322x3289 | 2021 ms    | 2/2 in 1875 ms, avg 1760 ms  | 10/10 in 2597 ms, avg 2236 ms   |              10
callie.jpg  | 352.6 KB | 800x600   | 428 ms     | 2/2 in 414 ms, avg 407 ms    | 10/10 in 441 ms, avg 414 ms     |              10

Results for height 192:
Image       | Filesize | Size      | 1 requests | 2 requests                   | 10 requests                     | Max concurrency
------------|----------|-----------|------------|------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 9788 ms    | 2/2 in 9962 ms, avg 9271 ms  | 10/10 in 11958 ms, avg 11028 ms |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 1372 ms    | 2/2 in 1958 ms, avg 1900 ms  | 10/10 in 3047 ms, avg 2179 ms   |              10
train.heic  | 16.4 MB  | 9248x6936 | 8055 ms    | 2/2 in 8478 ms, avg 8472 ms  | 10/10 in 12324 ms, avg 10407 ms |              10
litter.jpg  | 12.6 MB  | 8384x6035 | 3539 ms    | 2/2 in 3809 ms, avg 3766 ms  | 10/10 in 4726 ms, avg 4320 ms   |              10
dubai.heic  | 18.6 MB  | 9248x6936 | 10147 ms   | 2/2 in 10190 ms, avg 9425 ms | 10/10 in 12067 ms, avg 10854 ms |              10
george.jpg  | 1.4 MB   | 4322x3289 | 1823 ms    | 2/2 in 4778 ms, avg 3259 ms  | 10/10 in 2327 ms, avg 1861 ms   |              10
callie.jpg  | 352.6 KB | 800x600   | 351 ms     | 2/2 in 313 ms, avg 306 ms    | 10/10 in 452 ms, avg 350 ms     |              10

Results for height 300:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 10 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 9610 ms    | 2/2 in 10055 ms, avg 10030 ms | 10/10 in 11869 ms, avg 10758 ms |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 1505 ms    | 2/2 in 1352 ms, avg 1317 ms   | 10/10 in 1507 ms, avg 1384 ms   |              10
train.heic  | 16.4 MB  | 9248x6936 | 9367 ms    | 2/2 in 9710 ms, avg 9608 ms   | 10/10 in 11703 ms, avg 10221 ms |              10
litter.jpg  | 12.6 MB  | 8384x6035 | 4395 ms    | 2/2 in 4538 ms, avg 4403 ms   | 10/10 in 4705 ms, avg 4078 ms   |              10
dubai.heic  | 18.6 MB  | 9248x6936 | 10067 ms   | 2/2 in 12330 ms, avg 11146 ms | 10/10 in 12883 ms, avg 10897 ms |              10
george.jpg  | 1.4 MB   | 4322x3289 | 1689 ms    | 2/2 in 1665 ms, avg 1439 ms   | 10/10 in 2207 ms, avg 1614 ms   |              10
callie.jpg  | 352.6 KB | 800x600   | 368 ms     | 2/2 in 367 ms, avg 365 ms     | 10/10 in 521 ms, avg 441 ms     |              10

Results for height 480:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 10 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 10192 ms   | 2/2 in 9774 ms, avg 8914 ms   | 10/10 in 11148 ms, avg 10389 ms |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 1528 ms    | 2/2 in 1258 ms, avg 1246 ms   | 10/10 in 1651 ms, avg 1506 ms   |              10
train.heic  | 16.4 MB  | 9248x6936 | 10401 ms   | 2/2 in 11001 ms, avg 10989 ms | 10/10 in 12029 ms, avg 10741 ms |              10
litter.jpg  | 12.6 MB  | 8384x6035 | 4076 ms    | 2/2 in 3721 ms, avg 3636 ms   | 10/10 in 4734 ms, avg 4194 ms   |              10
dubai.heic  | 18.6 MB  | 9248x6936 | 10578 ms   | 2/2 in 10825 ms, avg 10369 ms | 10/10 in 11285 ms, avg 10618 ms |              10
george.jpg  | 1.4 MB   | 4322x3289 | 1370 ms    | 2/2 in 1363 ms, avg 1249 ms   | 10/10 in 1852 ms, avg 1450 ms   |              10
callie.jpg  | 352.6 KB | 800x600   | 146 ms     | 2/2 in 176 ms, avg 160 ms     | 10/10 in 219 ms, avg 180 ms     |              10

Results for height 2160:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 10 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 9256 ms    | 2/2 in 10340 ms, avg 9874 ms  | 10/10 in 14492 ms, avg 12407 ms |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 2293 ms    | 2/2 in 2510 ms, avg 2433 ms   | 10/10 in 3021 ms, avg 2623 ms   |              10
train.heic  | 16.4 MB  | 9248x6936 | 10316 ms   | 2/2 in 10782 ms, avg 10422 ms | 9/10 in 11218 ms, avg 10615 ms  |               2
litter.jpg  | 12.6 MB  | 8384x6035 | 5176 ms    | 2/2 in 4864 ms, avg 4862 ms   | 10/10 in 6628 ms, avg 5524 ms   |              10
dubai.heic  | 18.6 MB  | 9248x6936 | 10417 ms   | 2/2 in 11017 ms, avg 10475 ms | 10/10 in 12347 ms, avg 11543 ms |              10
george.jpg  | 1.4 MB   | 4322x3289 | 1844 ms    | 2/2 in 1993 ms, avg 1921 ms   | 10/10 in 2268 ms, avg 2087 ms   |              10
callie.jpg  | 352.6 KB | 800x600   | 168 ms     | 2/2 in 192 ms, avg 181 ms     | 10/10 in 716 ms, avg 624 ms     |              10

Results for height 4320:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 10 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 14074 ms   | 2/2 in 14300 ms, avg 14076 ms | 10/10 in 16526 ms, avg 14629 ms |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 5854 ms    | 2/2 in 4137 ms, avg 4093 ms   | 10/10 in 5462 ms, avg 4573 ms   |              10
train.heic  | 16.4 MB  | 9248x6936 | 14338 ms   | 2/2 in 14005 ms, avg 13285 ms | 10/10 in 16566 ms, avg 14948 ms |              10
litter.jpg  | 12.6 MB  | 8384x6035 | 8203 ms    | 2/2 in 8116 ms, avg 8056 ms   | 10/10 in 9948 ms, avg 8417 ms   |              10
dubai.heic  | 18.6 MB  | 9248x6936 | 14673 ms   | 2/2 in 15158 ms, avg 14704 ms | 10/10 in 17500 ms, avg 15638 ms |              10
george.jpg  | 1.4 MB   | 4322x3289 | 3119 ms    | 2/2 in 3422 ms, avg 3418 ms   | 10/10 in 3320 ms, avg 3172 ms   |              10
callie.jpg  | 352.6 KB | 800x600   | 161 ms     | 2/2 in 181 ms, avg 177 ms     | 10/10 in 774 ms, avg 729 ms     |              10

Results for height 100000:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 10 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 20277 ms   | 2/2 in 22309 ms, avg 22017 ms | 10/10 in 24757 ms, avg 22795 ms |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 4414 ms    | 2/2 in 3961 ms, avg 3867 ms   | 10/10 in 4906 ms, avg 4222 ms   |              10
train.heic  | 16.4 MB  | 9248x6936 | 20184 ms   | 2/2 in 20535 ms, avg 20455 ms | 10/10 in 22966 ms, avg 21834 ms |              10
litter.jpg  | 12.6 MB  | 8384x6035 | 11584 ms   | 2/2 in 12383 ms, avg 12287 ms | 10/10 in 26741 ms, avg 14815 ms |              10
dubai.heic  | 18.6 MB  | 9248x6936 | 21934 ms   | 2/2 in 23881 ms, avg 23127 ms | 10/10 in 27638 ms, avg 23186 ms |              10
george.jpg  | 1.4 MB   | 4322x3289 | 3011 ms    | 2/2 in 3091 ms, avg 3059 ms   | 10/10 in 3328 ms, avg 3131 ms   |              10
callie.jpg  | 352.6 KB | 800x600   | 210 ms     | 2/2 in 508 ms, avg 382 ms     | 10/10 in 664 ms, avg 313 ms     |              10
```

#### 2 vCPU, 8 GiB RAM, concurrency limit 1

```

Results for height 64:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 10 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 10908 ms   | 2/2 in 30959 ms, avg 20696 ms | 10/10 in 11572 ms, avg 10885 ms |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 1480 ms    | 2/2 in 1830 ms, avg 1788 ms   | 10/10 in 2374 ms, avg 1909 ms   |              10
train.heic  | 16.4 MB  | 9248x6936 | 10596 ms   | 2/2 in 10573 ms, avg 10441 ms | 10/10 in 12850 ms, avg 11344 ms |              10
litter.jpg  | 12.6 MB  | 8384x6035 | 3676 ms    | 2/2 in 4141 ms, avg 4060 ms   | 10/10 in 5076 ms, avg 4360 ms   |              10
dubai.heic  | 18.6 MB  | 9248x6936 | 10658 ms   | 2/2 in 11297 ms, avg 11028 ms | 10/10 in 12260 ms, avg 11460 ms |              10
george.jpg  | 1.4 MB   | 4322x3289 | 1844 ms    | 2/2 in 2189 ms, avg 2168 ms   | 10/10 in 2858 ms, avg 2408 ms   |              10
callie.jpg  | 352.6 KB | 800x600   | 388 ms     | 2/2 in 391 ms, avg 390 ms     | 10/10 in 814 ms, avg 782 ms     |              10

Results for height 192:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 10 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 10180 ms   | 2/2 in 10281 ms, avg 9860 ms  | 10/10 in 12678 ms, avg 11210 ms |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 1355 ms    | 2/2 in 1484 ms, avg 1403 ms   | 10/10 in 2801 ms, avg 2258 ms   |              10
train.heic  | 16.4 MB  | 9248x6936 | 10080 ms   | 2/2 in 10541 ms, avg 10409 ms | 10/10 in 11435 ms, avg 10365 ms |              10
litter.jpg  | 12.6 MB  | 8384x6035 | 3797 ms    | 2/2 in 4206 ms, avg 4065 ms   | 10/10 in 4312 ms, avg 4014 ms   |              10
dubai.heic  | 18.6 MB  | 9248x6936 | 9979 ms    | 2/2 in 9959 ms, avg 9807 ms   | 10/10 in 12213 ms, avg 10852 ms |              10
george.jpg  | 1.4 MB   | 4322x3289 | 1706 ms    | 2/2 in 1824 ms, avg 1757 ms   | 10/10 in 2485 ms, avg 2233 ms   |              10
callie.jpg  | 352.6 KB | 800x600   | 320 ms     | 2/2 in 411 ms, avg 388 ms     | 10/10 in 422 ms, avg 369 ms     |              10

Results for height 300:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 10 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 9761 ms    | 2/2 in 10892 ms, avg 10334 ms | 10/10 in 11166 ms, avg 10372 ms |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 1548 ms    | 2/2 in 1969 ms, avg 1877 ms   | 10/10 in 2237 ms, avg 1800 ms   |              10
train.heic  | 16.4 MB  | 9248x6936 | 10065 ms   | 2/2 in 10425 ms, avg 10096 ms | 10/10 in 11869 ms, avg 10372 ms |              10
litter.jpg  | 12.6 MB  | 8384x6035 | 3711 ms    | 2/2 in 3833 ms, avg 3790 ms   | 10/10 in 4925 ms, avg 4056 ms   |              10
dubai.heic  | 18.6 MB  | 9248x6936 | 10485 ms   | 2/2 in 11558 ms, avg 10990 ms | 10/10 in 11125 ms, avg 10726 ms |              10
george.jpg  | 1.4 MB   | 4322x3289 | 1729 ms    | 2/2 in 1791 ms, avg 1781 ms   | 10/10 in 2254 ms, avg 2013 ms   |              10
callie.jpg  | 352.6 KB | 800x600   | 369 ms     | 2/2 in 396 ms, avg 395 ms     | 10/10 in 590 ms, avg 533 ms     |              10

Results for height 480:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 10 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 9915 ms    | 2/2 in 9989 ms, avg 9952 ms   | 10/10 in 10958 ms, avg 10587 ms |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 1610 ms    | 2/2 in 2212 ms, avg 1763 ms   | 10/10 in 1806 ms, avg 1506 ms   |              10
train.heic  | 16.4 MB  | 9248x6936 | 9960 ms    | 2/2 in 10087 ms, avg 9963 ms  | 10/10 in 12229 ms, avg 11090 ms |              10
litter.jpg  | 12.6 MB  | 8384x6035 | 3819 ms    | 2/2 in 4057 ms, avg 3909 ms   | 10/10 in 4231 ms, avg 3798 ms   |              10
dubai.heic  | 18.6 MB  | 9248x6936 | 9542 ms    | 2/2 in 11990 ms, avg 11499 ms | 10/10 in 11841 ms, avg 11427 ms |              10
george.jpg  | 1.4 MB   | 4322x3289 | 1954 ms    | 2/2 in 1541 ms, avg 1492 ms   | 10/10 in 3988 ms, avg 1715 ms   |              10
callie.jpg  | 352.6 KB | 800x600   | 146 ms     | 2/2 in 147 ms, avg 145 ms     | 10/10 in 615 ms, avg 274 ms     |              10

Results for height 2160:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 10 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 10618 ms   | 2/2 in 11148 ms, avg 10842 ms | 10/10 in 12361 ms, avg 11197 ms |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 2316 ms    | 2/2 in 2323 ms, avg 2316 ms   | 10/10 in 2840 ms, avg 2669 ms   |              10
train.heic  | 16.4 MB  | 9248x6936 | 11088 ms   | 2/2 in 11063 ms, avg 10916 ms | 10/10 in 12119 ms, avg 11555 ms |              10
litter.jpg  | 12.6 MB  | 8384x6035 | 4569 ms    | 2/2 in 4606 ms, avg 4535 ms   | 10/10 in 5568 ms, avg 5182 ms   |              10
dubai.heic  | 18.6 MB  | 9248x6936 | 11327 ms   | 2/2 in 12233 ms, avg 11748 ms | 10/10 in 12293 ms, avg 11535 ms |              10
george.jpg  | 1.4 MB   | 4322x3289 | 1981 ms    | 2/2 in 1997 ms, avg 1945 ms   | 10/10 in 2450 ms, avg 2276 ms   |              10
callie.jpg  | 352.6 KB | 800x600   | 218 ms     | 2/2 in 300 ms, avg 248 ms     | 10/10 in 497 ms, avg 366 ms     |              10

Results for height 4320:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 10 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 14826 ms   | 2/2 in 14729 ms, avg 14344 ms | 10/10 in 15647 ms, avg 14952 ms |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 3909 ms    | 2/2 in 6154 ms, avg 5254 ms   | 10/10 in 4743 ms, avg 4219 ms   |              10
train.heic  | 16.4 MB  | 9248x6936 | 15093 ms   | 2/2 in 14864 ms, avg 14742 ms | 10/10 in 17122 ms, avg 15010 ms |              10
litter.jpg  | 12.6 MB  | 8384x6035 | 8779 ms    | 2/2 in 8105 ms, avg 8104 ms   | 10/10 in 8671 ms, avg 8159 ms   |              10
dubai.heic  | 18.6 MB  | 9248x6936 | 14483 ms   | 2/2 in 14734 ms, avg 14698 ms | 10/10 in 16475 ms, avg 15677 ms |              10
george.jpg  | 1.4 MB   | 4322x3289 | 3242 ms    | 2/2 in 3116 ms, avg 3100 ms   | 10/10 in 4666 ms, avg 3837 ms   |              10
callie.jpg  | 352.6 KB | 800x600   | 165 ms     | 2/2 in 190 ms, avg 189 ms     | 10/10 in 252 ms, avg 206 ms     |              10

Results for height 100000:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 10 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 21082 ms   | 2/2 in 22495 ms, avg 22233 ms | 10/10 in 23791 ms, avg 22798 ms |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 4983 ms    | 2/2 in 4733 ms, avg 4337 ms   | 10/10 in 4970 ms, avg 4359 ms   |              10
train.heic  | 16.4 MB  | 9248x6936 | 20890 ms   | 2/2 in 23659 ms, avg 22715 ms | 10/10 in 24713 ms, avg 22834 ms |              10
litter.jpg  | 12.6 MB  | 8384x6035 | 12053 ms   | 2/2 in 13961 ms, avg 13368 ms | 10/10 in 14900 ms, avg 13013 ms |              10
dubai.heic  | 18.6 MB  | 9248x6936 | 23271 ms   | 2/2 in 23275 ms, avg 22831 ms | 10/10 in 24649 ms, avg 22406 ms |              10
george.jpg  | 1.4 MB   | 4322x3289 | 3527 ms    | 2/2 in 3838 ms, avg 3439 ms   | 10/10 in 3733 ms, avg 3248 ms   |              10
callie.jpg  | 352.6 KB | 800x600   | 408 ms     | 2/2 in 411 ms, avg 337 ms     | 10/10 in 725 ms, avg 486 ms     |              10
```

#### 4 vCPU, 4 GiB RAM, concurrency limit 1

```
Results for height 64:
Image       | Filesize | Size      | 1 requests | 2 requests                  | 10 requests                    | Max concurrency
------------|----------|-----------|------------|-----------------------------|--------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 8569 ms    | 2/2 in 8138 ms, avg 7982 ms | 10/10 in 14977 ms, avg 9752 ms |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 1430 ms    | 2/2 in 1529 ms, avg 1518 ms | 10/10 in 1932 ms, avg 1563 ms  |              10
train.heic  | 16.4 MB  | 9248x6936 | 7359 ms    | 2/2 in 8182 ms, avg 7872 ms | 10/10 in 8542 ms, avg 8190 ms  |              10
litter.jpg  | 12.6 MB  | 8384x6035 | 3487 ms    | 2/2 in 4589 ms, avg 4235 ms | 10/10 in 5436 ms, avg 4709 ms  |              10
dubai.heic  | 18.6 MB  | 9248x6936 | 8433 ms    | 2/2 in 8168 ms, avg 8159 ms | 10/10 in 10047 ms, avg 9089 ms |              10
george.jpg  | 1.4 MB   | 4322x3289 | 2374 ms    | 2/2 in 2234 ms, avg 2225 ms | 10/10 in 2809 ms, avg 2383 ms  |              10
callie.jpg  | 352.6 KB | 800x600   | 565 ms     | 2/2 in 529 ms, avg 521 ms   | 10/10 in 576 ms, avg 548 ms    |              10

Results for height 192:
Image       | Filesize | Size      | 1 requests | 2 requests                  | 10 requests                    | Max concurrency
------------|----------|-----------|------------|-----------------------------|--------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 8019 ms    | 2/2 in 8371 ms, avg 7891 ms | 10/10 in 9683 ms, avg 8667 ms  |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 2124 ms    | 2/2 in 1747 ms, avg 1719 ms | 10/10 in 2077 ms, avg 1661 ms  |              10
train.heic  | 16.4 MB  | 9248x6936 | 8379 ms    | 2/2 in 7749 ms, avg 7476 ms | 10/10 in 10022 ms, avg 8691 ms |              10
litter.jpg  | 12.6 MB  | 8384x6035 | 3639 ms    | 2/2 in 3803 ms, avg 3731 ms | 10/10 in 5545 ms, avg 4908 ms  |              10
dubai.heic  | 18.6 MB  | 9248x6936 | 7439 ms    | 2/2 in 7762 ms, avg 7751 ms | 10/10 in 10555 ms, avg 9710 ms |              10
george.jpg  | 1.4 MB   | 4322x3289 | 1809 ms    | 2/2 in 2324 ms, avg 2152 ms | 10/10 in 2813 ms, avg 2173 ms  |              10
callie.jpg  | 352.6 KB | 800x600   | 148 ms     | 2/2 in 405 ms, avg 400 ms   | 10/10 in 847 ms, avg 537 ms    |              10

Results for height 300:
Image       | Filesize | Size      | 1 requests | 2 requests                  | 10 requests                   | Max concurrency
------------|----------|-----------|------------|-----------------------------|-------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 9244 ms    | 2/2 in 8391 ms, avg 8325 ms | 10/10 in 9107 ms, avg 8425 ms |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 1576 ms    | 2/2 in 1587 ms, avg 1479 ms | 9/10 in 1811 ms, avg 1541 ms  |               2
train.heic  | 16.4 MB  | 9248x6936 | 8165 ms    | 2/2 in 7622 ms, avg 7567 ms | 10/10 in 9373 ms, avg 8254 ms |              10
litter.jpg  | 12.6 MB  | 8384x6035 | 3659 ms    | 2/2 in 4212 ms, avg 4101 ms | 10/10 in 5188 ms, avg 4660 ms |              10
dubai.heic  | 18.6 MB  | 9248x6936 | 8023 ms    | 2/2 in 8769 ms, avg 8492 ms | 10/10 in 9138 ms, avg 8350 ms |              10
george.jpg  | 1.4 MB   | 4322x3289 | 1712 ms    | 2/2 in 1715 ms, avg 1714 ms | 10/10 in 1981 ms, avg 1676 ms |              10
callie.jpg  | 352.6 KB | 800x600   | 182 ms     | 2/2 in 139 ms, avg 134 ms   | 10/10 in 362 ms, avg 278 ms   |              10

Results for height 480:
Image       | Filesize | Size      | 1 requests | 2 requests                  | 10 requests                    | Max concurrency
------------|----------|-----------|------------|-----------------------------|--------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 7952 ms    | 2/2 in 8051 ms, avg 8012 ms | 10/10 in 9326 ms, avg 8465 ms  |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 1363 ms    | 2/2 in 1412 ms, avg 1381 ms | 10/10 in 1878 ms, avg 1570 ms  |              10
train.heic  | 16.4 MB  | 9248x6936 | 8039 ms    | 2/2 in 8774 ms, avg 8738 ms | 10/10 in 9942 ms, avg 9315 ms  |              10
litter.jpg  | 12.6 MB  | 8384x6035 | 3704 ms    | 2/2 in 3785 ms, avg 3745 ms | 10/10 in 5173 ms, avg 3930 ms  |              10
dubai.heic  | 18.6 MB  | 9248x6936 | 8053 ms    | 2/2 in 7958 ms, avg 7857 ms | 10/10 in 10069 ms, avg 8971 ms |              10
george.jpg  | 1.4 MB   | 4322x3289 | 1638 ms    | 2/2 in 1748 ms, avg 1672 ms | 10/10 in 1961 ms, avg 1774 ms  |              10
callie.jpg  | 352.6 KB | 800x600   | 156 ms     | 2/2 in 161 ms, avg 161 ms   | 10/10 in 379 ms, avg 246 ms    |              10

Results for height 2160:
Image       | Filesize | Size      | 1 requests | 2 requests                  | 10 requests                    | Max concurrency
------------|----------|-----------|------------|-----------------------------|--------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 8569 ms    | 2/2 in 9343 ms, avg 9132 ms | 10/10 in 10397 ms, avg 9527 ms |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 2489 ms    | 2/2 in 2351 ms, avg 2300 ms | 10/10 in 3045 ms, avg 2485 ms  |              10
train.heic  | 16.4 MB  | 9248x6936 | 8596 ms    | 2/2 in 8898 ms, avg 8813 ms | 10/10 in 10473 ms, avg 9611 ms |              10
litter.jpg  | 12.6 MB  | 8384x6035 | 4943 ms    | 2/2 in 4596 ms, avg 4573 ms | 10/10 in 5400 ms, avg 4978 ms  |              10
dubai.heic  | 18.6 MB  | 9248x6936 | 8256 ms    | 2/2 in 9666 ms, avg 9305 ms | 10/10 in 9675 ms, avg 9068 ms  |              10
george.jpg  | 1.4 MB   | 4322x3289 | 1973 ms    | 2/2 in 1901 ms, avg 1874 ms | 10/10 in 2170 ms, avg 1995 ms  |              10
callie.jpg  | 352.6 KB | 800x600   | 161 ms     | 2/2 in 169 ms, avg 160 ms   | 10/10 in 215 ms, avg 194 ms    |              10

Results for height 4320:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 10 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 12227 ms   | 2/2 in 12169 ms, avg 11718 ms | 10/10 in 14437 ms, avg 12714 ms |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 4189 ms    | 2/2 in 3838 ms, avg 3780 ms   | 10/10 in 4645 ms, avg 4266 ms   |              10
train.heic  | 16.4 MB  | 9248x6936 | 12454 ms   | 2/2 in 12333 ms, avg 12077 ms | 10/10 in 15478 ms, avg 12943 ms |              10
litter.jpg  | 12.6 MB  | 8384x6035 | 8539 ms    | 2/2 in 9152 ms, avg 8479 ms   | 10/10 in 9843 ms, avg 8978 ms   |              10
dubai.heic  | 18.6 MB  | 9248x6936 | 12560 ms   | 2/2 in 16006 ms, avg 14009 ms | 10/10 in 14268 ms, avg 13485 ms |              10
george.jpg  | 1.4 MB   | 4322x3289 | 3016 ms    | 2/2 in 3378 ms, avg 3224 ms   | 10/10 in 4195 ms, avg 3403 ms   |              10
callie.jpg  | 352.6 KB | 800x600   | 168 ms     | 2/2 in 188 ms, avg 188 ms     | 10/10 in 206 ms, avg 183 ms     |              10

Results for height 100000:
Image       | Filesize | Size      | 1 requests | 2 requests                    | 10 requests                     | Max concurrency
------------|----------|-----------|------------|-------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 18145 ms   | 2/2 in 19382 ms, avg 19228 ms | 10/10 in 24241 ms, avg 20284 ms |              10
pigeons.jpg | 12.1 MB  | 4656x3492 | 4664 ms    | 2/2 in 4496 ms, avg 4345 ms   | 10/10 in 6337 ms, avg 4683 ms   |              10
train.heic  | 16.4 MB  | 9248x6936 | 22162 ms   | 2/2 in 19798 ms, avg 19569 ms | 10/10 in 20867 ms, avg 19793 ms |              10
litter.jpg  | 12.6 MB  | 8384x6035 | 12954 ms   | 2/2 in 13954 ms, avg 12886 ms | 10/10 in 13954 ms, avg 12610 ms |              10
dubai.heic  | 18.6 MB  | 9248x6936 | 21111 ms   | 2/2 in 20404 ms, avg 19771 ms | 10/10 in 22962 ms, avg 20498 ms |              10
george.jpg  | 1.4 MB   | 4322x3289 | 3544 ms    | 2/2 in 3340 ms, avg 3246 ms   | 10/10 in 3773 ms, avg 3214 ms   |              10
callie.jpg  | 352.6 KB | 800x600   | 216 ms     | 2/2 in 222 ms, avg 219 ms     | 10/10 in 698 ms, avg 366 ms     |              10
```

#### 4 vCPU, 8 GiB RAM, concurrency limit 2

```
TODO
```

#### 2 vCPU, 8 GiB RAM, concurrency limit 4

```
TODO
```

#### 4 vCPU, 16 GiB RAM, concurrency limit 4

```
TODO
```

### Benchmark results in Docker container on GitHub Actions (used for regression tests), lanczos

```
Image       | Filesize | Size | 1 requests        | 2 requests                              | 3 requests                              | Max concurrency
------------|----------|------|-------------------|-----------------------------------------|-----------------------------------------|----------------
puget.heic  | 16.7 MB  |      | 9375 ms (1.76 GB) | 2/2 in 14015 ms, avg 13987 ms (3.22 GB) | 3/3 in 21844 ms, avg 21463 ms (4.65 GB) |               3
train.heic  | 16.4 MB  |      | 9662 ms (1.76 GB) | 2/2 in 13886 ms, avg 13595 ms (3.25 GB) | 3/3 in 21581 ms, avg 21233 ms (4.62 GB) |               3
callie.jpg  | 352.6 KB |      | 77 ms (432 MB)    | 2/2 in 93 ms, avg 88 ms (431 MB)        | 3/3 in 137 ms, avg 115 ms (415 MB)      |               3
dubai.heic  | 18.6 MB  |      | 9882 ms (1.79 GB) | 2/2 in 14660 ms, avg 14401 ms (3.21 GB) | 3/3 in 22750 ms, avg 22385 ms (4.58 GB) |               3
george.jpg  | 1.4 MB   |      | 2038 ms (884 MB)  | 2/2 in 2189 ms, avg 2166 ms (1.28 GB)   | 3/3 in 3201 ms, avg 3021 ms (1.69 GB)   |               3
litter.jpg  | 12.6 MB  |      | 5039 ms (1.57 GB) | 2/2 in 5496 ms, avg 5356 ms (2.85 GB)   | 3/3 in 9747 ms, avg 8928 ms (4.06 GB)   |               3
pigeons.jpg | 12.1 MB  |      | 2601 ms (891 MB)  | 2/2 in 2748 ms, avg 2733 ms (1.44 GB)   | 3/3 in 4094 ms, avg 4028 ms (2.03 GB)   |               3
```

### Benchmark results in Docker container on GitHub Actions (used for regression tests), hermite

```
Image       | Filesize | Size | 1 requests        | 2 requests                              | 3 requests                              | Max concurrency
------------|----------|------|-------------------|-----------------------------------------|-----------------------------------------|----------------
puget.heic  | 16.7 MB  |      | 7403 ms (1.91 GB) | 2/2 in 11401 ms, avg 11207 ms (3.32 GB) | 3/3 in 17921 ms, avg 17735 ms (4.83 GB) |               3
train.heic  | 16.4 MB  |      | 7666 ms (1.88 GB) | 2/2 in 11239 ms, avg 11161 ms (3.42 GB) | 3/3 in 18076 ms, avg 17272 ms (4.64 GB) |               3
callie.jpg  | 352.6 KB |      | 586 ms (770 MB)   | 2/2 in 61 ms, avg 59 ms (422 MB)        | 3/3 in 90 ms, avg 76 ms (394 MB)        |               3
dubai.heic  | 18.6 MB  |      | 7865 ms (1.89 GB) | 2/2 in 12082 ms, avg 11985 ms (3.40 GB) | 3/3 in 19955 ms, avg 15842 ms (3.40 GB) |               3
george.jpg  | 1.4 MB   |      | 1502 ms (989 MB)  | 2/2 in 1506 ms, avg 1477 ms (1.37 GB)   | 3/3 in 2307 ms, avg 2080 ms (1.82 GB)   |               3
litter.jpg  | 12.6 MB  |      | 3437 ms (1.68 GB) | 2/2 in 3703 ms, avg 3627 ms (2.98 GB)   | 3/3 in 6590 ms, avg 6086 ms (4.05 GB)   |               3
pigeons.jpg | 12.1 MB  |      | 1861 ms (1.09 GB) | 2/2 in 1942 ms, avg 1934 ms (1.65 GB)   | 3/3 in 2904 ms, avg 2758 ms (2.14 GB)   |               3
```

### Filter benchmark results (local)

| Filter             | puget.heic | pigeons.jpg | train.heic | litter.jpg | dubai.heic | george.jpg | callie.jpg |
|:-------------------|-----------:|------------:|-----------:|-----------:|-----------:|-----------:|-----------:|
| **lanczos**        |       3968 |         689 |       4047 |       1588 |       4119 |        563 |         66 |
| point              |       2616 |         343 |       2992 |        609 |       2848 |        280 |         43 |
| box                |       2776 |         365 |       3861 |        719 |       2946 |        276 |         43 |
| triangle           |       2963 |         406 |       3003 |        853 |       3118 |        322 |         65 |
| **hermite**        |       3006 |         416 |       3000 |        860 |       3265 |        315 |         55 |
| **hanning**        |       4452 |         799 |       4411 |       2103 |       4644 |        642 |         75 |
| **hamming**        |       4521 |         826 |       4479 |       2101 |       4779 |        628 |         91 |
| **blackman**       |       4523 |         785 |       4701 |       2141 |       4636 |        638 |         91 |
| gaussian           |       3478 |         532 |       3627 |       1238 |       3833 |        429 |         48 |
| quadratic          |       3238 |         495 |       3255 |       1069 |       3411 |        357 |         58 |
| cubic              |       3500 |         543 |       3664 |       1237 |       3679 |        443 |         60 |
| **catrom**         |       3504 |         541 |       3515 |       1252 |       3661 |        397 |         70 |
| mitchell           |       3503 |         549 |       3542 |       1206 |       3646 |        398 |         59 |
| jinc               |       4039 |         691 |       4123 |       1794 |       4139 |        549 |         75 |
| sinc               |       4419 |         783 |       4555 |       2122 |       4590 |        642 |         76 |
| sinc_fast          |       4491 |         775 |       4469 |       2162 |       4519 |        615 |         67 |
| **kaiser**         |       4427 |         784 |       4448 |       2078 |       4635 |        654 |         84 |
| **welsh**          |       4012 |         662 |       4137 |       1597 |       4099 |        514 |         80 |
| **parzen**         |       4363 |         795 |       4478 |       2099 |       4575 |        673 |         75 |
| **bohman**         |       4388 |         792 |       4422 |       2093 |       4584 |        614 |         75 |
| **bartlett**       |       4425 |         788 |       4539 |       2137 |       4518 |        621 |         64 |
| **lagrange**       |       3530 |         555 |       3541 |       1219 |       3615 |        409 |         59 |
| **lanczos_sharp**  |       3957 |         648 |       4007 |       1573 |       4105 |        509 |         69 |
| **lanczos2**       |       3455 |         521 |       3446 |       1216 |       3619 |        395 |         56 |
| **lanczos2_sharp** |       3376 |         539 |       3498 |       1175 |       3598 |        394 |         48 |
| **robidoux**       |       3435 |         525 |       3518 |       1225 |       3664 |        406 |         62 |
| **robidoux_sharp** |       3552 |         522 |       3575 |       1215 |       3666 |        417 |         54 |
| **cosine**         |       3994 |         653 |       4058 |       1623 |       4123 |        533 |         54 |
| spline             |       3446 |         536 |       3593 |       1225 |       3650 |        410 |         49 |

# @formatter:off
| Filter             | puget.heic | pigeons.jpg | train.heic | litter.jpg | dubai.heic | george.jpg | callie.jpg | Average |
|:-------------------|-----------:|------------:|-----------:|-----------:|-----------:|-----------:|-----------:|--------:|
| **lanczos**        |       100% |        100% |       100% |       100% |       100% |       100% |       100% |    100% |
| point              |        66% |         50% |        74% |        38% |        69% |        50% |        65% |     59% |
| box                |        70% |         53% |        95% |        45% |        72% |        49% |        65% |     64% |
| triangle           |        75% |         59% |        74% |        54% |        76% |        57% |        98% |     70% |
| **hermite**        |        76% |         60% |        74% |        54% |        79% |        56% |        83% |     69% |
| **hanning**        |       112% |        116% |       109% |       132% |       113% |       114% |       114% |    116% |
| **hamming**        |       114% |        120% |       111% |       132% |       116% |       112% |       138% |    120% |
| **blackman**       |       114% |        114% |       116% |       135% |       113% |       113% |       138% |    120% |
| gaussian           |        88% |         77% |        90% |        78% |        93% |        76% |        73% |     82% |
| quadratic          |        82% |         72% |        80% |        67% |        83% |        63% |        88% |     76% |
| cubic              |        88% |         79% |        91% |        78% |        89% |        79% |        91% |     85% |
| **catrom**         |        88% |         79% |        87% |        79% |        89% |        71% |       106% |     85% |
| mitchell           |        88% |         80% |        88% |        76% |        89% |        71% |        89% |     83% |
| jinc               |       102% |        100% |       102% |       113% |       100% |        98% |       114% |    104% |
| sinc               |       111% |        114% |       113% |       134% |       111% |       114% |       115% |    116% |
| sinc_fast          |       113% |        112% |       110% |       136% |       110% |       109% |       102% |    113% |
| **kaiser**         |       112% |        114% |       110% |       131% |       113% |       116% |       127% |    117% |
| **welsh**          |       101% |         96% |       102% |       101% |       100% |        91% |       121% |    102% |
| **parzen**         |       110% |        115% |       111% |       132% |       111% |       120% |       114% |    116% |
| **bohman**         |       111% |        115% |       109% |       132% |       111% |       109% |       114% |    114% |
| **bartlett**       |       112% |        114% |       112% |       135% |       110% |       110% |        97% |    113% |
| **lagrange**       |        89% |         81% |        87% |        77% |        88% |        73% |        89% |     83% |
| **lanczos_sharp**  |       100% |         94% |        99% |        99% |       100% |        90% |       105% |     98% |
| **lanczos2**       |        87% |         76% |        85% |        77% |        88% |        70% |        85% |     81% |
| **lanczos2_sharp** |        85% |         78% |        86% |        74% |        87% |        70% |        73% |     79% |
| **robidoux**       |        87% |         76% |        87% |        77% |        89% |        72% |        94% |     83% |
| **robidoux_sharp** |        90% |         76% |        88% |        77% |        89% |        74% |        82% |     82% |
| **cosine**         |       101% |         95% |       100% |       102% |       100% |        95% |        82% |     96% |
| spline             |        87% |         78% |        89% |        77% |        89% |        73% |        74% |     81% |
# @formatter:on

### Filter benchmark results on Cloud Run with 2 vCPU, 8 GiB RAM

| Filter             | puget.heic | pigeons.jpg | train.heic | litter.jpg | dubai.heic | george.jpg | callie.jpg |
|:-------------------|-----------:|------------:|-----------:|-----------:|-----------:|-----------:|-----------:|
| **lanczos**        |      28218 |        9243 |      30157 |       5201 |      22336 |      25033 |        494 |
| point              |      12482 |        1095 |      16700 |       2313 |      17780 |        990 |        276 |
| box                |      23005 |        1394 |      16870 |       2222 |      17561 |       1175 |        269 |
| triangle           |      11842 |        1313 |      11631 |       2817 |      15205 |        950 |        414 |
| **hermite**        |      11126 |        1511 |      11804 |       2476 |      11754 |       1855 |        327 |
| **hanning**        |      20133 |        2493 |      22764 |       5401 |      15529 |       1933 |        362 |
| **hamming**        |      15285 |        1853 |      13411 |       4938 |      13776 |       1580 |        415 |
| **blackman**       |      13769 |        2034 |      14872 |       5349 |      16429 |       1896 |        334 |
| gaussian           |      19246 |        1391 |       8912 |       3147 |       8841 |       1167 |        330 |
| quadratic          |       8092 |        1316 |      17778 |       2833 |      14005 |       1274 |        297 |
| cubic              |      18866 |        1484 |      19084 |       3539 |      13845 |       1128 |        237 |
| **catrom**         |      19888 |        1623 |      13293 |       3201 |      15586 |       1771 |        268 |
| mitchell           |      16765 |        1976 |      11608 |       3295 |      12061 |       1994 |        276 |
| jinc               |      32286 |        2165 |      15993 |       6559 |      22327 |       2031 |        278 |
| sinc               |      24369 |        1950 |      12345 |       6629 |      13346 |       2259 |        281 |
| sinc_fast          |      16297 |        2329 |      28262 |       5331 |      16010 |       2480 |        355 |
| **kaiser**         |      23292 |        2532 |      21417 |       8305 |      23212 |       1683 |        256 |
| **welsh**          |      20009 |        1821 |      14931 |       4170 |      17431 |       4231 |        268 |
| **parzen**         |      16216 |        1903 |      25107 |       6520 |      22534 |       2507 |        280 |
| **bohman**         |      16019 |        2324 |      13854 |       5701 |      16625 |       1821 |        257 |
| **bartlett**       |      17692 |        2295 |      13374 |       6012 |      18737 |       1794 |        227 |
| **lagrange**       |      18983 |        1542 |      10687 |       2886 |      12225 |       1592 |        342 |
| **lanczos_sharp**  |      19313 |        2649 |      19657 |      13098 |      11698 |       1353 |        206 |
| **lanczos2**       |      26240 |        1679 |      17409 |       3198 |      11842 |       1654 |        235 |
| **lanczos2_sharp** |      25010 |        2028 |      20047 |       4621 |      23094 |       1621 |        380 |
| **robidoux**       |      18399 |        1579 |      16032 |       5134 |      14219 |       1527 |        246 |
| **robidoux_sharp** |      18753 |        2015 |      18162 |       4476 |      13072 |       1342 |        230 |
| **cosine**         |      20342 |        1731 |      12333 |       4415 |      15993 |       1363 |        239 |
| spline             |      18358 |        1918 |      13912 |       3592 |      10261 |       1076 |        234 |

# @formatter:off
| Filter             | puget.heic | pigeons.jpg | train.heic | litter.jpg | dubai.heic | george.jpg | callie.jpg | Average |
|:-------------------|-----------:|------------:|-----------:|-----------:|-----------:|-----------:|-----------:|--------:|
| **lanczos**        |       100% |        100% |       100% |       100% |       100% |       100% |       100% |    100% |
| point              |        44% |         12% |        55% |        44% |        80% |         4% |        56% |     42% |
| box                |        82% |         15% |        56% |        43% |        79% |         5% |        54% |     48% |
| triangle           |        42% |         14% |        39% |        54% |        68% |         4% |        84% |     44% |
| **hermite**        |        39% |         16% |        39% |        48% |        53% |         7% |        66% |     38% |
| **hanning**        |        71% |         27% |        75% |       104% |        70% |         8% |        73% |     61% |
| **hamming**        |        54% |         20% |        44% |        95% |        62% |         6% |        84% |     52% |
| **blackman**       |        49% |         22% |        49% |       103% |        74% |         8% |        68% |     53% |
| gaussian           |        68% |         15% |        30% |        61% |        40% |         5% |        67% |     41% |
| quadratic          |        29% |         14% |        59% |        54% |        63% |         5% |        60% |     41% |
| cubic              |        67% |         16% |        63% |        68% |        62% |         5% |        48% |     47% |
| **catrom**         |        70% |         18% |        44% |        62% |        70% |         7% |        54% |     46% |
| mitchell           |        59% |         21% |        38% |        63% |        54% |         8% |        56% |     43% |
| jinc               |       114% |         23% |        53% |       126% |       100% |         8% |        56% |     69% |
| sinc               |        86% |         21% |        41% |       127% |        60% |         9% |        57% |     57% |
| sinc_fast          |        58% |         25% |        94% |       102% |        72% |        10% |        72% |     62% |
| **kaiser**         |        83% |         27% |        71% |       160% |       104% |         7% |        52% |     72% |
| **welsh**          |        71% |         20% |        50% |        80% |        78% |        17% |        54% |     53% |
| **parzen**         |        57% |         21% |        83% |       125% |       101% |        10% |        57% |     65% |
| **bohman**         |        57% |         25% |        46% |       110% |        74% |         7% |        52% |     53% |
| **bartlett**       |        63% |         25% |        44% |       116% |        84% |         7% |        46% |     55% |
| **lagrange**       |        67% |         17% |        35% |        55% |        55% |         6% |        69% |     44% |
| **lanczos_sharp**  |        68% |         29% |        65% |       252% |        52% |         5% |        42% |     73% |
| **lanczos2**       |        93% |         18% |        58% |        61% |        53% |         7% |        48% |     48% |
| **lanczos2_sharp** |        89% |         22% |        66% |        89% |       103% |         6% |        77% |     65% |
| **robidoux**       |        65% |         17% |        53% |        99% |        64% |         6% |        50% |     51% |
| **robidoux_sharp** |        66% |         22% |        60% |        86% |        59% |         5% |        47% |     49% |
| **cosine**         |        72% |         19% |        41% |        85% |        72% |         5% |        48% |     49% |
| spline             |        65% |         21% |        46% |        69% |        46% |         4% |        47% |     43% |
# @formatter:on

### Subjective results of which filters are acceptable for application attachments

| Filter             | puget.heic | pigeons.jpg | train.heic | litter.jpg | dubai.heic | george.jpg | callie.jpg |
|--------------------|------------|-------------|------------|------------|------------|------------|------------|
| point              |            |             |            |            |            |            | ✔          |
| box                | ✔          | ✔           |            | ✔          | ✔          | ✔          | ✔          |
| triangle           | ✔          | ✔           | ✔          | ✔          | ✔          |            | ✔          |
| **hermite**        | ✔          | ✔           | ✔          | ✔          | ✔          | ✔          | ✔          |
| **hanning**        | ✔          | ✔           | ✔          | ✔          | ✔          | ✔          | ✔          |
| **hamming**        | ✔          | ✔           | ✔          | ✔          | ✔          | ✔          | ✔          |
| **blackman**       | ✔          | ✔           | ✔          | ✔          | ✔          | ✔          | ✔          |
| gaussian           |            | ✔           |            |            |            |            |            |
| quadratic          | ✔          |             | ✔          |            |            | ✔          | ✔          |
| cubic              |            |             |            |            |            |            |            |
| **catrom**         | ✔          | ✔           | ✔          | ✔          | ✔          | ✔          | ✔          |
| mitchell           | ✔          | ✔           | ✔          |            | ✔          | ✔          | ✔          |
| jinc               | ✔          | ✔           | ✔          |            | ✔          |            | ✔          |
| sinc               | ✔          |             | ✔          |            |            | ✔          | ✔          |
| sinc_fast          | ✔          |             | ✔          |            |            | ✔          | ✔          |
| **kaiser**         | ✔          | ✔           | ✔          | ✔          | ✔          | ✔          | ✔          |
| **welsh**          | ✔          | ✔           | ✔          | ✔          | ✔          | ✔          | ✔          |
| **parzen**         | ✔          | ✔           | ✔          | ✔          | ✔          | ✔          | ✔          |
| **bohman**         | ✔          | ✔           | ✔          | ✔          | ✔          | ✔          | ✔          |
| **bartlett**       | ✔          | ✔           | ✔          | ✔          | ✔          | ✔          | ✔          |
| **lagrange**       | ✔          | ✔           | ✔          | ✔          | ✔          | ✔          | ✔          |
| **lanczos**        | ✔          | ✔           | ✔          | ✔          | ✔          | ✔          | ✔          |
| **lanczos_sharp**  | ✔          | ✔           | ✔          | ✔          | ✔          | ✔          | ✔          |
| **lanczos2**       | ✔          | ✔           | ✔          | ✔          | ✔          | ✔          | ✔          |
| **lanczos2_sharp** | ✔          | ✔           | ✔          | ✔          | ✔          | ✔          | ✔          |
| **robidoux**       | ✔          | ✔           | ✔          | ✔          | ✔          | ✔          | ✔          |
| **robidoux_sharp** | ✔          | ✔           | ✔          | ✔          | ✔          | ✔          | ✔          |
| **cosine**         | ✔          | ✔           | ✔          | ✔          | ✔          | ✔          | ✔          |
| spline             | ✔          |             |            |            |            |            |            |
