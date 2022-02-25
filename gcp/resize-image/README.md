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

I suggest running this with 4 vCPU / 8 GiB to prevent OOM and optimize performance.

Performance is important here, as this blocks application submission after uploading attachments.

Informed by the benchmark results, this blocking flow uses the filter HERMITE, and non-blocking flows use the filter
LANCZOS (LANCZOS2 is not supported locally on dreamhost yet).

There are benchmarks for this at /tests/blackbox/bin/resize_image.dart and /tests/blackbox/bin/image_filters.dart.

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

### Benchmark results on Cloud Run with 2 vCPU, 4 GiB RAM, concurrency limit 100

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

### Benchmark results on Cloud Run with 2 vCPU, 8 GiB RAM, concurrency limit 100

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
litter.jpg  | 12.6 MB  |           | 1/1 in 8142 ms, avg 8142 ms   | 2/2 in 8848 ms, avg 8822 ms   | 5/5 in 10072 ms, avg 9805 ms  | 10/10 in 10173 ms, avg 9864 ms  | 25/25 in 11212 ms, avg 9590 ms  |              43
dubai.heic  | 18.6 MB  |           | 1/1 in 12014 ms, avg 12014 ms | 2/2 in 15472 ms, avg 15416 ms | 5/5 in 18957 ms, avg 17761 ms | 10/10 in 19325 ms, avg 15679 ms | 25/25 in 23823 ms, avg 18543 ms |             100
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

### Benchmark results on Cloud Run with 4 vCPU, 8 GiB RAM, concurrency limit 100

```
TODO
```

### Benchmark results on Cloud Run with 4 vCPU, 16 GiB RAM, concurrency limit 100

```
TODO
```

### Benchmark results in Docker container on GitHub Actions (used for regression tests), lanczos

```
TODO
```

### Benchmark results in Docker container on GitHub Actions (used for regression tests), hermite

```
TODO
```

### Filter benchmark results (local)

| Filter             | puget.heic | pigeons.jpg | train.heic | litter.jpg | dubai.heic | george.jpg | callie.jpg |
|--------------------|------------|-------------|------------|------------|------------|------------|------------|
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
|--------------------|------------|-------------|------------|------------|------------|------------|------------|---------|
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
|--------------------|------------|-------------|------------|------------|------------|------------|------------|
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
|--------------------|------------|-------------|------------|------------|------------|------------|------------|---------|
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
