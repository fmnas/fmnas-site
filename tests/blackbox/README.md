# Blackbox tests

These tests make requests against HTTP services.

## Build

Build generated files with `dart run build_runner build`.

## Tests

Run all tests with `dart test` 
(this requires that all services are running on localhost on the default ports mentioned below).

### resize_image_test

Tests /gcp/resize-image. Run with `dart test test/resize_image_test.dart`.

To run with a non-default endpoint, set the `RESIZE_IMAGE_ENDPOINT` environment variable.

#### Updating goldens

`dart run test/resize_image_test.dart --update [endpoint]`

### image_size_test

Tests /gcp/image-size. Run with `dart test test/image_size_test.dart`.

To run with a non-default endpoint, set the `IMAGE_SIZE_ENDPOINT` environment variable.

## Benchmarks

Run all benchmarks with `dart run`
(this requires that all services are running on localhost on the default ports mentioned below).

### Memory monitoring

Memory monitoring depends on docker, netstat, grep, awk, and perl.

### resize_image

Run with `dart run :resize_image`.

Note that when running against a local docker container, this will use a LOT of memory and perhaps crash everything.
You can limit this with the parallel requests and max concurrency arguments. See the benchmark results in the 
resize-image docs for estimated memory usage.

#### Arguments

* Endpoint (`--endpoint`, `-e`): defaults to `http://localhost:50000`.
* Heights (`--height`, `-h`): defaults to 64,192,300,480,2160,4320,100000.
* Concurrency search limit (`--max`, `-n`): defaults to 50.
* Parallel requests (positional arguments): defaults to `1 2 5 10 25`.
* Disable memory profiling (`--no-memory`)
* Filter (`--filter`, `-f`): the imagemagick filter to use.

### image_filters

Run with `dart run :image_filters`.

#### Arguments
* Endpoint (`--endpoint`, `-e`): defaults to `http://localhost:50000`.
* Height (`--height`, `-h`): defaults to 1080.
* Disable memory profiling (`--no-memory`)

### image_size

Run with `dart run :image_size`.

#### Arguments

* Endpoint (`--endpoint`, `-e`): defaults to `http://localhost:50000`.
* Concurrency search limit (`--max`, `-n`): defaults to 25.
* Parallel requests (positional arguments): defaults to `1 2 5 10 25`.
* Disable memory profiling (`--no-memory`)
