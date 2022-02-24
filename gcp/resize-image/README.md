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

Cloud Code extension containers are limited to 2 GiB RAM and crash readily if there are many concurrent requests.
In lieu of local load balancing between containers, you can instead use "Run resize-image on port 50000" or
`docker run -p 50000:8080 resize-image` to run a single container with unlimited resources.

### With Cloud Code

The "Run resize-image" IntelliJ run configuration uses the Cloud Code extension to run the service.

If it doesn't work with managed dependencies, try installing the latest minikube, skaffold, and kubectl locally and
setting the dependency paths manually in the extension settings.

This uses an automatically selected ephemeral port. To forward a specific port to the service, use
`kubectl port-forward service/resize-image $PORT:8080`. Another IntelliJ run configuration is included to forward
port 50000 to resize-image.

### Testing

```shell
curl -v -F height=200 -F 'image=@/path/to/in.jpg' http://localhost:50000 > out.jpg
```

Or use the resize-image task in public/tester.php.

There are blackbox tests for this in /tests/blackbox/test/resize_image_test.dart.

## Resource provisioning

I suggest running this with 4 vCPU / 8 GiB to prevent OOM and optimize performance.

Performance is important here, as this blocks application submission after uploading attachments.

This blocking flow uses filter TODO; non-blocking flows use the default filter LANCZOS.

There are benchmarks for this at /tests/blackbox/bin/resize_image.dart and /tests/blackbox/bin/image_filters.dart.

### Benchmark results in Docker container (local), default filter

```

```

### Benchmark results in Cloud Code container with 2 GiB RAM, default filter

```
TODO
```

### Benchmark results on Cloud Run with 2 vCPU, 4 GiB RAM, concurrency limit 100, default filter

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

### Benchmark results on Cloud Run with 2 vCPU, 8 GiB RAM, concurrency limit 100, default filter

```
TODO
```

### Benchmark results on Cloud Run with 4 vCPU, 8 GiB RAM, concurrency limit 100, default filter

```
TODO
```

### Benchmark results on Cloud Run with 4 vCPU, 16 GiB RAM, concurrency limit 100, default filter

```
TODO
```

### Benchmark results in Docker container on GitHub Actions (used for regression tests), default filter

```
TODO
```

### Filter benchmark results (local)

TODO

### Filter benchmark results on Cloud Run with 2 vCPU, 8 GiB RAM, concurrency limit 100

TODO

```
Results for filter point:
Image       | Filesize | Size      | 1 requests                    | Max concurrency
------------|----------|-----------|-------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 10714 ms, avg 10714 ms |               1
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1631 ms, avg 1631 ms   |               1
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 9728 ms, avg 9728 ms   |               1
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 7446 ms, avg 7446 ms   |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 12642 ms, avg 12642 ms |               1
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1329 ms, avg 1329 ms   |               1
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 333 ms, avg 333 ms     |               1

Results for filter box:
Image       | Filesize | Size      | 1 requests                    | Max concurrency
------------|----------|-----------|-------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 10269 ms, avg 10269 ms |               1
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1530 ms, avg 1530 ms   |               1
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 10406 ms, avg 10406 ms |               1
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 4015 ms, avg 4015 ms   |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 10278 ms, avg 10278 ms |               1
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1290 ms, avg 1290 ms   |               1
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 274 ms, avg 274 ms     |               1

Results for filter triangle:
Image       | Filesize | Size      | 1 requests                    | Max concurrency
------------|----------|-----------|-------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 10236 ms, avg 10236 ms |               1
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1504 ms, avg 1504 ms   |               1
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 10221 ms, avg 10221 ms |               1
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 3993 ms, avg 3993 ms   |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 10384 ms, avg 10384 ms |               1
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1243 ms, avg 1243 ms   |               1
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 309 ms, avg 309 ms     |               1

Results for filter hermite:
Image       | Filesize | Size      | 1 requests                    | Max concurrency
------------|----------|-----------|-------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 13281 ms, avg 13281 ms |               1
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 2057 ms, avg 2057 ms   |               1
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 9715 ms, avg 9715 ms   |               1
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 3816 ms, avg 3816 ms   |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 10291 ms, avg 10291 ms |               1
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1262 ms, avg 1262 ms   |               1
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 301 ms, avg 301 ms     |               1

Results for filter hanning:
Image       | Filesize | Size      | 1 requests                    | Max concurrency
------------|----------|-----------|-------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 10130 ms, avg 10130 ms |               1
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1451 ms, avg 1451 ms   |               1
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 10439 ms, avg 10439 ms |               1
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 3740 ms, avg 3740 ms   |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 9965 ms, avg 9965 ms   |               1
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1259 ms, avg 1259 ms   |               1
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 307 ms, avg 307 ms     |               1

Results for filter hamming:
Image       | Filesize | Size      | 1 requests                  | Max concurrency
------------|----------|-----------|-----------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 9763 ms, avg 9763 ms |               1
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1444 ms, avg 1444 ms |               1
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 9635 ms, avg 9635 ms |               1
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 4163 ms, avg 4163 ms |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 9833 ms, avg 9833 ms |               1
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1283 ms, avg 1283 ms |               1
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 266 ms, avg 266 ms   |               1

Results for filter blackman:
Image       | Filesize | Size      | 1 requests                    | Max concurrency
------------|----------|-----------|-------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 9680 ms, avg 9680 ms   |               1
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1484 ms, avg 1484 ms   |               1
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 9922 ms, avg 9922 ms   |               1
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 3890 ms, avg 3890 ms   |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 10349 ms, avg 10349 ms |               1
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1241 ms, avg 1241 ms   |               1
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 249 ms, avg 249 ms     |               1

Results for filter gaussian:
Image       | Filesize | Size      | 1 requests                    | Max concurrency
------------|----------|-----------|-------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 10730 ms, avg 10730 ms |               1
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1459 ms, avg 1459 ms   |               1
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 10526 ms, avg 10526 ms |               1
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 3796 ms, avg 3796 ms   |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 10116 ms, avg 10116 ms |               1
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1276 ms, avg 1276 ms   |               1
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 296 ms, avg 296 ms     |               1

Results for filter quadratic:
Image       | Filesize | Size      | 1 requests                    | Max concurrency
------------|----------|-----------|-------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 9849 ms, avg 9849 ms   |               1
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1448 ms, avg 1448 ms   |               1
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 9720 ms, avg 9720 ms   |               1
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 3827 ms, avg 3827 ms   |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 14105 ms, avg 14105 ms |               1
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1901 ms, avg 1901 ms   |               1
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 1515 ms, avg 1515 ms   |               1

Results for filter cubic:
Image       | Filesize | Size      | 1 requests                    | Max concurrency
------------|----------|-----------|-------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 9830 ms, avg 9830 ms   |               1
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1463 ms, avg 1463 ms   |               1
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 9672 ms, avg 9672 ms   |               1
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 3757 ms, avg 3757 ms   |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 10835 ms, avg 10835 ms |               1
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1294 ms, avg 1294 ms   |               1
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 281 ms, avg 281 ms     |               1

Results for filter catrom:
Image       | Filesize | Size      | 1 requests                    | Max concurrency
------------|----------|-----------|-------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 9998 ms, avg 9998 ms   |               1
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1446 ms, avg 1446 ms   |               1
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 9673 ms, avg 9673 ms   |               1
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 3884 ms, avg 3884 ms   |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 10431 ms, avg 10431 ms |               1
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1272 ms, avg 1272 ms   |               1
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 271 ms, avg 271 ms     |               1

Results for filter mitchell:
Image       | Filesize | Size      | 1 requests                    | Max concurrency
------------|----------|-----------|-------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 10118 ms, avg 10118 ms |               1
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 2107 ms, avg 2107 ms   |               1
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 9313 ms, avg 9313 ms   |               1
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 3785 ms, avg 3785 ms   |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 9917 ms, avg 9917 ms   |               1
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1313 ms, avg 1313 ms   |               1
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 278 ms, avg 278 ms     |               1

Results for filter jinc:
Image       | Filesize | Size      | 1 requests                    | Max concurrency
------------|----------|-----------|-------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 9866 ms, avg 9866 ms   |               1
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1492 ms, avg 1492 ms   |               1
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 9948 ms, avg 9948 ms   |               1
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 3849 ms, avg 3849 ms   |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 11299 ms, avg 11299 ms |               1
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1295 ms, avg 1295 ms   |               1
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 274 ms, avg 274 ms     |               1

Results for filter sinc:
Image       | Filesize | Size      | 1 requests                    | Max concurrency
------------|----------|-----------|-------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 10707 ms, avg 10707 ms |               1
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1573 ms, avg 1573 ms   |               1
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 10247 ms, avg 10247 ms |               1
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 4291 ms, avg 4291 ms   |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 10823 ms, avg 10823 ms |               1
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1261 ms, avg 1261 ms   |               1
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 295 ms, avg 295 ms     |               1

Results for filter sinc_fast:
Image       | Filesize | Size      | 1 requests                    | Max concurrency
------------|----------|-----------|-------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 10425 ms, avg 10425 ms |               1
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1521 ms, avg 1521 ms   |               1
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 11129 ms, avg 11129 ms |               1
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 13177 ms, avg 13177 ms |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 12076 ms, avg 12076 ms |               1
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1535 ms, avg 1535 ms   |               1
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 248 ms, avg 248 ms     |               1

Results for filter kaiser:
Image       | Filesize | Size      | 1 requests                    | Max concurrency
------------|----------|-----------|-------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 10162 ms, avg 10162 ms |               1
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1587 ms, avg 1587 ms   |               1
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 11035 ms, avg 11035 ms |               1
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 4149 ms, avg 4149 ms   |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 10515 ms, avg 10515 ms |               1
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1259 ms, avg 1259 ms   |               1
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 249 ms, avg 249 ms     |               1

Results for filter welsh:
Image       | Filesize | Size      | 1 requests                    | Max concurrency
------------|----------|-----------|-------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 26758 ms, avg 26758 ms |               1
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1461 ms, avg 1461 ms   |               1
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 29913 ms, avg 29913 ms |               1
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 3876 ms, avg 3876 ms   |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 10604 ms, avg 10604 ms |               1
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1233 ms, avg 1233 ms   |               1
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 277 ms, avg 277 ms     |               1

Results for filter parzen:
Image       | Filesize | Size      | 1 requests                    | Max concurrency
------------|----------|-----------|-------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 9961 ms, avg 9961 ms   |               1
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1619 ms, avg 1619 ms   |               1
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 10236 ms, avg 10236 ms |               1
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 3767 ms, avg 3767 ms   |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 10669 ms, avg 10669 ms |               1
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1158 ms, avg 1158 ms   |               1
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 271 ms, avg 271 ms     |               1

Results for filter bohman:
Image       | Filesize | Size      | 1 requests                    | Max concurrency
------------|----------|-----------|-------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 10372 ms, avg 10372 ms |               1
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1478 ms, avg 1478 ms   |               1
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 9619 ms, avg 9619 ms   |               1
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 3757 ms, avg 3757 ms   |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 10080 ms, avg 10080 ms |               1
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1142 ms, avg 1142 ms   |               1
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 252 ms, avg 252 ms     |               1

Results for filter bartlett:
Image       | Filesize | Size      | 1 requests                    | Max concurrency
------------|----------|-----------|-------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 12747 ms, avg 12747 ms |               1
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 3209 ms, avg 3209 ms   |               1
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 10087 ms, avg 10087 ms |               1
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 3881 ms, avg 3881 ms   |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 9896 ms, avg 9896 ms   |               1
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1137 ms, avg 1137 ms   |               1
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 241 ms, avg 241 ms     |               1

Results for filter lagrange:
Image       | Filesize | Size      | 1 requests                    | Max concurrency
------------|----------|-----------|-------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 10021 ms, avg 10021 ms |               1
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1459 ms, avg 1459 ms   |               1
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 9434 ms, avg 9434 ms   |               1
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 3717 ms, avg 3717 ms   |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 10094 ms, avg 10094 ms |               1
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1128 ms, avg 1128 ms   |               1
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 260 ms, avg 260 ms     |               1

Results for filter lanczos:
Image       | Filesize | Size      | 1 requests                    | Max concurrency
------------|----------|-----------|-------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 12710 ms, avg 12710 ms |               1
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 5532 ms, avg 5532 ms   |               1
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 9844 ms, avg 9844 ms   |               1
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 3648 ms, avg 3648 ms   |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 9928 ms, avg 9928 ms   |               1
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1149 ms, avg 1149 ms   |               1
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 241 ms, avg 241 ms     |               1

Results for filter lanczos_sharp:
Image       | Filesize | Size      | 1 requests                    | Max concurrency
------------|----------|-----------|-------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 9884 ms, avg 9884 ms   |               1
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1483 ms, avg 1483 ms   |               1
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 9875 ms, avg 9875 ms   |               1
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 3700 ms, avg 3700 ms   |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 10084 ms, avg 10084 ms |               1
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1189 ms, avg 1189 ms   |               1
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 238 ms, avg 238 ms     |               1

Results for filter lanczos2:
Image       | Filesize | Size      | 1 requests                    | Max concurrency
------------|----------|-----------|-------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 10329 ms, avg 10329 ms |               1
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1633 ms, avg 1633 ms   |               1
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 10094 ms, avg 10094 ms |               1
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 3915 ms, avg 3915 ms   |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 10453 ms, avg 10453 ms |               1
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1272 ms, avg 1272 ms   |               1
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 275 ms, avg 275 ms     |               1

Results for filter lanczos2_sharp:
Image       | Filesize | Size      | 1 requests                    | Max concurrency
------------|----------|-----------|-------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 9008 ms, avg 9008 ms   |               1
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1466 ms, avg 1466 ms   |               1
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 9710 ms, avg 9710 ms   |               1
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 3841 ms, avg 3841 ms   |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 10758 ms, avg 10758 ms |               1
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1159 ms, avg 1159 ms   |               1
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 234 ms, avg 234 ms     |               1

Results for filter robidoux:
Image       | Filesize | Size      | 1 requests                    | Max concurrency
------------|----------|-----------|-------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 9833 ms, avg 9833 ms   |               1
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1454 ms, avg 1454 ms   |               1
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 12566 ms, avg 12566 ms |               1
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 8864 ms, avg 8864 ms   |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 9236 ms, avg 9236 ms   |               1
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1262 ms, avg 1262 ms   |               1
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 277 ms, avg 277 ms     |               1

Results for filter robidoux_sharp:
Image       | Filesize | Size      | 1 requests                    | Max concurrency
------------|----------|-----------|-------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 9155 ms, avg 9155 ms   |               1
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1449 ms, avg 1449 ms   |               1
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 10518 ms, avg 10518 ms |               1
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 4371 ms, avg 4371 ms   |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 10872 ms, avg 10872 ms |               1
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1141 ms, avg 1141 ms   |               1
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 223 ms, avg 223 ms     |               1

Results for filter cosine:
Image       | Filesize | Size      | 1 requests                    | Max concurrency
------------|----------|-----------|-------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 9654 ms, avg 9654 ms   |               1
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1463 ms, avg 1463 ms   |               1
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 9868 ms, avg 9868 ms   |               1
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 3821 ms, avg 3821 ms   |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 17027 ms, avg 17027 ms |               1
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1215 ms, avg 1215 ms   |               1
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 254 ms, avg 254 ms     |               1

Results for filter spline:
Image       | Filesize | Size      | 1 requests                    | Max concurrency
------------|----------|-----------|-------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 10388 ms, avg 10388 ms |               1
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1608 ms, avg 1608 ms   |               1
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 10617 ms, avg 10617 ms |               1
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 4054 ms, avg 4054 ms   |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 11596 ms, avg 11596 ms |               1
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1354 ms, avg 1354 ms   |               1
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 389 ms, avg 389 ms     |               1

Results for filter sentinel:
Image       | Filesize | Size      | 1 requests                    | Max concurrency
------------|----------|-----------|-------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 10274 ms, avg 10274 ms |               1
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 1457 ms, avg 1457 ms   |               1
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 9893 ms, avg 9893 ms   |               1
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 3829 ms, avg 3829 ms   |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 10695 ms, avg 10695 ms |               1
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1202 ms, avg 1202 ms   |               1
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 221 ms, avg 221 ms     |               1

Results for filter lanczos_radius:
Image       | Filesize | Size      | 1 requests                    | Max concurrency
------------|----------|-----------|-------------------------------|----------------
puget.heic  | 16.7 MB  | 9248x6936 | 1/1 in 10630 ms, avg 10630 ms |               1
pigeons.jpg | 12.1 MB  | 4656x3492 | 1/1 in 2197 ms, avg 2197 ms   |               1
train.heic  | 16.4 MB  | 9248x6936 | 1/1 in 9633 ms, avg 9633 ms   |               1
litter.jpg  | 12.6 MB  | 8384x6035 | 1/1 in 4183 ms, avg 4183 ms   |               1
dubai.heic  | 18.6 MB  | 9248x6936 | 1/1 in 11466 ms, avg 11466 ms |               1
george.jpg  | 1.4 MB   | 4322x3289 | 1/1 in 1263 ms, avg 1263 ms   |               1
callie.jpg  | 352.6 KB | 800x600   | 1/1 in 384 ms, avg 384 ms     |               1
```

### Filter benchmark results in Docker container on GitHub Actions (used for regression tests)

TODO

### Subjective results of which filters are acceptable for application attachments

TODO
