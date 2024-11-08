# image-size

Deprecated; use resize-image instead

TODO: remove image-size container

This Cloud Run container gets the size of an image, to reduce load on DreamHost and improve compatibility with newer 
image formats such as HEIC and WebP.

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

## Running locally

### With Docker

Cloud Code extension containers are limited to 2 GiB RAM and crash readily if there are many concurrent requests.
In lieu of local load balancing between containers, you can instead use "Run image-size on port 50001" or
`docker run -p 50001:8080 image-size` to run a single container with unlimited resources.

### With Cloud Code

The "Run image-size" IntelliJ run configuration uses the Cloud Code extension to run the service.

If it doesn't work with managed dependencies, try installing the latest minikube, skaffold, and kubectl locally and
setting the dependency paths manually in the extension settings.

This uses an automatically selected ephemeral port. To forward a specific port to the service, use
`kubectl port-forward service/image-size $PORT:8080`. Another IntelliJ run configuration is included to forward
port 50001 to image-size. This must be run while image-size is running.

### Testing

```shell
curl -v -F 'image=@/path/to/in.jpg' http://localhost:50001 
```

Or use the image-size task in public/tester.php.

There are blackbox tests for this in /tests/blackbox/test/image_size_test.dart.

## Resource provisioning

I suggest running this with 2 vCPU / 4 GiB to prevent OOM.

This configuration seems to work fine with high concurrency values on Cloud run (see benchmark results).

Performance isn't a big concern here, as this doesn't block any user flows (assuming the async image tag generation
is working right in the admin interface).

There is a benchmark for this at /tests/blackbox/bin/image_size.dart.

### Benchmark results in Docker container (local)

```
Image       | Filesize | Size      | 1 requests                            | 2 requests                            | 5 requests                            | 10 requests                             | 25 requests                              | Max concurrency
------------|----------|-----------|---------------------------------------|---------------------------------------|---------------------------------------|-----------------------------------------|------------------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 1912 ms, avg 1912 ms (750 MB)  | 2/2 in 2017 ms, avg 2016 ms (1.53 GB) | 5/5 in 2339 ms, avg 2338 ms (4.01 GB) | 10/10 in 3676 ms, avg 3674 ms (5.40 GB) | 25/25 in 8981 ms, avg 8979 ms (14.20 GB) |              50
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 158 ms, avg 158 ms (1.92 GB)   | 2/2 in 201 ms, avg 201 ms (1.65 GB)   | 5/5 in 196 ms, avg 195 ms (2.29 GB)   | 10/10 in 244 ms, avg 242 ms (2.83 GB)   | 25/25 in 502 ms, avg 497 ms (5.92 GB)    |              50
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 1960 ms, avg 1960 ms (1.96 GB) | 2/2 in 2056 ms, avg 2056 ms (2.71 GB) | 5/5 in 2436 ms, avg 2436 ms (4.63 GB) | 10/10 in 3823 ms, avg 3822 ms (8.41 GB) | 25/25 in 8915 ms, avg 8910 ms (15.46 GB) |              50
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 322 ms, avg 322 ms (2.33 GB)   | 2/2 in 340 ms, avg 338 ms (2.61 GB)   | 5/5 in 385 ms, avg 385 ms (3.95 GB)   | 10/10 in 579 ms, avg 578 ms (6.23 GB)   | 25/25 in 1444 ms, avg 1441 ms (13.59 GB) |              50
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 2022 ms, avg 2022 ms (2.19 GB) | 2/2 in 1996 ms, avg 1995 ms (3.03 GB) | 5/5 in 2447 ms, avg 2447 ms (6.08 GB) | 10/10 in 4007 ms, avg 4006 ms (8.75 GB) | 25/25 in 9349 ms, avg 9346 ms (17.45 GB) |              50
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 78 ms, avg 78 ms (2.74 GB)     | 2/2 in 104 ms, avg 104 ms (2.34 GB)   | 5/5 in 110 ms, avg 109 ms (2.49 GB)   | 10/10 in 149 ms, avg 147 ms (3.47 GB)   | 25/25 in 375 ms, avg 373 ms (5.20 GB)    |              50
```

### Benchmark results in Cloud Code container with 2 GiB RAM

```
Image       | Filesize | Size      | 1 requests                  | 2 requests                  | 5 requests                | 10 requests                 | 25 requests            | Max concurrency
------------|----------|-----------|-----------------------------|-----------------------------|---------------------------|-----------------------------|------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 2372 ms, avg 2372 ms | 2/2 in 4728 ms, avg 4728 ms | 0/5 in 0 ms, avg 0 ms     | 0/10 in 0 ms, avg 0 ms      | 0/25 in 0 ms, avg 0 ms |               2
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 177 ms, avg 177 ms   | 2/2 in 225 ms, avg 208 ms   | 5/5 in 496 ms, avg 480 ms | 10/10 in 995 ms, avg 988 ms | 0/25 in 0 ms, avg 0 ms |              12
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 2338 ms, avg 2338 ms | 2/2 in 4664 ms, avg 4661 ms | 0/5 in 0 ms, avg 0 ms     | 0/10 in 0 ms, avg 0 ms      | 0/25 in 0 ms, avg 0 ms |               2
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 396 ms, avg 396 ms   | 2/2 in 393 ms, avg 384 ms   | 0/5 in 0 ms, avg 0 ms     | 0/10 in 0 ms, avg 0 ms      | 0/25 in 0 ms, avg 0 ms |               3
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 2584 ms, avg 2584 ms | 2/2 in 5162 ms, avg 5156 ms | 0/5 in 0 ms, avg 0 ms     | 0/10 in 0 ms, avg 0 ms      | 0/25 in 0 ms, avg 0 ms |               2
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 76 ms, avg 76 ms     | 2/2 in 127 ms, avg 127 ms   | 5/5 in 214 ms, avg 213 ms | 10/10 in 588 ms, avg 587 ms | 0/25 in 0 ms, avg 0 ms |              17
```

### Benchmark results on Cloud Run with 1 vCPU, 2 GiB RAM, concurrency limit 50

```
Image       | Filesize | Size      | 1 requests                    | 2 requests                    | 5 requests                    | 10 requests                    | 25 requests                     | Max concurrency
------------|----------|-----------|-------------------------------|-------------------------------|-------------------------------|--------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 11964 ms, avg 11964 ms | 2/2 in 26310 ms, avg 26100 ms | 2/5 in 12877 ms, avg 12725 ms | 2/10 in 12871 ms, avg 12256 ms | 10/25 in 29029 ms, avg 16585 ms |               4
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 679 ms, avg 679 ms     | 2/2 in 717 ms, avg 703 ms     | 5/5 in 791 ms, avg 706 ms     | 10/10 in 2063 ms, avg 1102 ms  | 25/25 in 3147 ms, avg 1701 ms   |              50
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 10936 ms, avg 10936 ms | 2/2 in 24896 ms, avg 24635 ms | 5/5 in 25948 ms, avg 22473 ms | 6/10 in 25438 ms, avg 16789 ms | 9/25 in 28717 ms, avg 17254 ms  |               8
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 1300 ms, avg 1300 ms   | 2/2 in 1361 ms, avg 1321 ms   | 5/5 in 2865 ms, avg 2440 ms   | 10/10 in 2877 ms, avg 2312 ms  | 25/25 in 5115 ms, avg 2793 ms   |              37
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 11855 ms, avg 11855 ms | 2/2 in 26675 ms, avg 26394 ms | 5/5 in 26802 ms, avg 23323 ms | 7/10 in 26757 ms, avg 23895 ms | 10/25 in 29229 ms, avg 17208 ms |               8
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 546 ms, avg 546 ms     | 2/2 in 550 ms, avg 531 ms     | 5/5 in 890 ms, avg 761 ms     | 10/10 in 2460 ms, avg 1483 ms  | 25/25 in 5327 ms, avg 2925 ms   |              50
```

### Benchmark results on Cloud Run with 1 vCPU, 4 GiB RAM, concurrency limit 100

```
Image       | Filesize | Size      | 1 requests                    | 2 requests                    | 5 requests                    | 10 requests                     | 25 requests                     | Max concurrency
------------|----------|-----------|-------------------------------|-------------------------------|-------------------------------|---------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 10629 ms, avg 10629 ms | 2/2 in 23457 ms, avg 23247 ms | 5/5 in 49998 ms, avg 41774 ms | 10/10 in 47507 ms, avg 33983 ms | 25/25 in 59974 ms, avg 43137 ms |             100
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 840 ms, avg 840 ms     | 2/2 in 970 ms, avg 809 ms     | 5/5 in 844 ms, avg 754 ms     | 10/10 in 1403 ms, avg 831 ms    | 25/25 in 1659 ms, avg 1008 ms   |             100
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 11715 ms, avg 11715 ms | 2/2 in 24789 ms, avg 24551 ms | 5/5 in 38877 ms, avg 32395 ms | 10/10 in 52909 ms, avg 32162 ms | 25/25 in 57172 ms, avg 31388 ms |              99
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 1405 ms, avg 1405 ms   | 2/2 in 1582 ms, avg 1408 ms   | 5/5 in 1583 ms, avg 1431 ms   | 10/10 in 1897 ms, avg 1490 ms   | 25/25 in 4760 ms, avg 2554 ms   |             100
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 13880 ms, avg 13880 ms | 1/2 in 12646 ms, avg 12646 ms | 5/5 in 29514 ms, avg 26284 ms | 10/10 in 27402 ms, avg 19976 ms | 25/25 in 60165 ms, avg 36415 ms |              25
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 495 ms, avg 495 ms     | 2/2 in 568 ms, avg 499 ms     | 5/5 in 1545 ms, avg 1044 ms   | 10/10 in 1460 ms, avg 674 ms    | 25/25 in 2479 ms, avg 1026 ms   |             100
```

### Benchmark results on Cloud Run with 2 vCPU, 4 GiB RAM, concurrency limit 100

```
Image       | Filesize | Size      | 1 requests                  | 2 requests                    | 5 requests                    | 10 requests                     | 25 requests                     | Max concurrency
------------|----------|-----------|-----------------------------|-------------------------------|-------------------------------|---------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 6644 ms, avg 6644 ms | 2/2 in 11785 ms, avg 11568 ms | 5/5 in 26077 ms, avg 21966 ms | 10/10 in 27032 ms, avg 22694 ms | 25/25 in 28382 ms, avg 18779 ms |             100
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 721 ms, avg 721 ms   | 2/2 in 755 ms, avg 680 ms     | 5/5 in 665 ms, avg 614 ms     | 10/10 in 803 ms, avg 657 ms     | 25/25 in 1280 ms, avg 817 ms    |             100
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 7060 ms, avg 7060 ms | 2/2 in 11887 ms, avg 11636 ms | 5/5 in 18192 ms, avg 15305 ms | 10/10 in 24953 ms, avg 17097 ms | 25/25 in 25996 ms, avg 19080 ms |             100
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 1161 ms, avg 1161 ms | 2/2 in 1274 ms, avg 1234 ms   | 5/5 in 1414 ms, avg 1288 ms   | 10/10 in 2414 ms, avg 1615 ms   | 25/25 in 3884 ms, avg 1942 ms   |             100
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 7092 ms, avg 7092 ms | 2/2 in 12154 ms, avg 11910 ms | 5/5 in 21040 ms, avg 15004 ms | 10/10 in 20796 ms, avg 16411 ms | 25/25 in 30479 ms, avg 16284 ms |             100 
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 609 ms, avg 609 ms   | 2/2 in 583 ms, avg 567 ms     | 5/5 in 646 ms, avg 553 ms     | 10/10 in 640 ms, avg 515 ms     | 25/25 in 1152 ms, avg 681 ms    |             100
```

### Benchmark results on Cloud Run with 4 vCPU, 4 GiB RAM, concurrency limit 100

```
Image       | Filesize | Size      | 1 requests                  | 2 requests                  | 5 requests                    | 10 requests                      | 25 requests                      | Max concurrency
------------|----------|-----------|-----------------------------|-----------------------------|-------------------------------|----------------------------------|----------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 4191 ms, avg 4191 ms | 2/2 in 6800 ms, avg 6775 ms | 5/5 in 12033 ms, avg 10101 ms | 10/10 in 12330 ms, avg 9310 ms   | 25/25 in 12438 ms, avg 10228 ms  |             100
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 525 ms, avg 525 ms   | 2/2 in 653 ms, avg 636 ms   | 5/5 in 672 ms, avg 625 ms     | 10/10 in 890 ms, avg 774 ms      | 25/25 in 1584 ms, avg 960 ms     |             100
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 4083 ms, avg 4083 ms | 2/2 in 6393 ms, avg 6380 ms | 5/5 in 9288 ms, avg 7315 ms   | 10/10 in 10872 ms, avg 7398 ms   | 25/25 in 13819 ms, avg 10860 ms  |             100
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 1236 ms, avg 1236 ms | 2/2 in 1273 ms, avg 1212 ms | 5/5 in 1579 ms, avg 1363 ms   | 10/10 in 1828 ms, avg 1444 ms    | 25/25 in 2776 ms, avg 2129 ms    |             100
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 4225 ms, avg 4225 ms | 2/2 in 6664 ms, avg 6625 ms | 5/5 in 13524 ms, avg 11639 ms | 10/10 in 15961 ms, avg 10637 ms  | 25/25 in 15642 ms, avg 13806 ms  |             100
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 561 ms, avg 561 ms   | 2/2 in 527 ms, avg 493 ms   | 5/5 in 579 ms, avg 499 ms     | 10/10 in 605 ms, avg 502 ms      | 25/25 in 1215 ms, avg 757 ms     |             100
```

### Benchmark results on Cloud Run with 4 vCPU, 8 GiB RAM, concurrency limit 100

```
Image       | Filesize | Size      | 1 requests                  | 2 requests                  | 5 requests                    | 10 requests                     | 25 requests                     | Est. max concurrency
------------|----------|-----------|-----------------------------|-----------------------------|-------------------------------|---------------------------------|---------------------------------|---------------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 5124 ms, avg 5124 ms | 2/2 in 7639 ms, avg 7503 ms | 5/5 in 13839 ms, avg 11464 ms | 10/10 in 13844 ms, avg 11709 ms | 25/25 in 14300 ms, avg 10827 ms |                  100
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 623 ms, avg 623 ms   | 2/2 in 759 ms, avg 685 ms   | 5/5 in 877 ms, avg 667 ms     | 10/10 in 856 ms, avg 654 ms     | 25/25 in 1476 ms, avg 864 ms    |                  100
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 4110 ms, avg 4110 ms | 2/2 in 6173 ms, avg 5936 ms | 5/5 in 11073 ms, avg 9343 ms  | 10/10 in 11984 ms, avg 7754 ms  | 25/25 in 15824 ms, avg 10076 ms |                   99
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 1409 ms, avg 1409 ms | 2/2 in 1305 ms, avg 1267 ms | 5/5 in 1305 ms, avg 1251 ms   | 10/10 in 2345 ms, avg 1420 ms   | 25/25 in 2374 ms, avg 1553 ms   |                  100
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 4660 ms, avg 4660 ms | 2/2 in 7689 ms, avg 7517 ms | 5/5 in 10958 ms, avg 8241 ms  | 10/10 in 11621 ms, avg 7250 ms  | 25/25 in 17138 ms, avg 10088 ms |                  100
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 499 ms, avg 499 ms   | 2/2 in 642 ms, avg 609 ms   | 5/5 in 593 ms, avg 501 ms     | 10/10 in 789 ms, avg 547 ms     | 25/25 in 926 ms, avg 674 ms     |                  100
```

### Benchmark results on Cloud Run with 4 vCPU, 16 GiB RAM, concurrency limit 100

```
Image       | Filesize | Size      | 1 requests                  | 2 requests                  | 5 requests                    | 10 requests                     | 25 requests                     | Max concurrency
------------|----------|-----------|-----------------------------|-----------------------------|-------------------------------|---------------------------------|---------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 4599 ms, avg 4599 ms | 2/2 in 8200 ms, avg 7978 ms | 5/5 in 13092 ms, avg 10792 ms | 10/10 in 13183 ms, avg 11600 ms | 25/25 in 16135 ms, avg 12082 ms |             100
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 601 ms, avg 601 ms   | 2/2 in 766 ms, avg 666 ms   | 5/5 in 719 ms, avg 651 ms     | 10/10 in 929 ms, avg 680 ms     | 25/25 in 1153 ms, avg 821 ms    |             100
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 4363 ms, avg 4363 ms | 2/2 in 7275 ms, avg 7272 ms | 5/5 in 13886 ms, avg 11361 ms | 10/10 in 10581 ms, avg 6977 ms  | 25/25 in 13354 ms, avg 8888 ms  |             100
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 1348 ms, avg 1348 ms | 2/2 in 1300 ms, avg 1247 ms | 5/5 in 1490 ms, avg 1365 ms   | 10/10 in 2591 ms, avg 1443 ms   | 25/25 in 2443 ms, avg 1592 ms   |              99
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 4555 ms, avg 4555 ms | 2/2 in 7395 ms, avg 7188 ms | 5/5 in 10856 ms, avg 9244 ms  | 10/10 in 11957 ms, avg 9029 ms  | 25/25 in 14578 ms, avg 9909 ms  |             100
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 623 ms, avg 623 ms   | 2/2 in 810 ms, avg 772 ms   | 5/5 in 545 ms, avg 474 ms     | 10/10 in 674 ms, avg 481 ms     | 25/25 in 948 ms, avg 668 ms     |             100
```

### Benchmark results in Docker container on GitHub Actions (used for regression tests)

For the latest results, see /tests/blackbox/goldens/image_size_regression_test.json.

```
Image       | Filesize | Size      | 1 requests        | 2 requests                            | 3 requests                              | Max concurrency
------------|----------|-----------|-------------------|---------------------------------------|-----------------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 4200 ms (1014 MB) | 2/2 in 7813 ms, avg 7811 ms (1.72 GB) | 3/3 in 11796 ms, avg 11794 ms (2.04 GB) |               3
train.heic  | 16.4 MB  | 9248x6936 | 4210 ms (1.02 GB) | 2/2 in 7772 ms, avg 7771 ms (1.70 GB) | 3/3 in 11827 ms, avg 11809 ms (2.01 GB) |               3
callie.jpg  | 352.6 KB | 800x600   | 25 ms (357 MB)    | 2/2 in 27 ms, avg 24 ms (356 MB)      | 3/3 in 30 ms, avg 22 ms (328 MB)        |               3
dubai.heic  | 18.6 MB  | 9248x6936 | 4591 ms (979 MB)  | 2/2 in 8585 ms, avg 8583 ms (1.67 GB) | 3/3 in 13010 ms, avg 12983 ms (2.24 GB) |               3
george.jpg  | 1.4 MB   | 4322x3289 | 152 ms (480 MB)   | 2/2 in 160 ms, avg 159 ms (580 MB)    | 3/3 in 249 ms, avg 245 ms (582 MB)      |               3
litter.jpg  | 12.6 MB  | 8384x6035 | 602 ms (745 MB)   | 2/2 in 630 ms, avg 621 ms (1.32 GB)   | 3/3 in 1026 ms, avg 1015 ms (1.80 GB)   |               3
pigeons.jpg | 12.1 MB  | 4656x3492 | 287 ms (479 MB)   | 2/2 in 311 ms, avg 310 ms (563 MB)    | 3/3 in 451 ms, avg 443 ms (689 MB)      |               3
```
