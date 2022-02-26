/*
 * Copyright 2022 Google LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

import 'dart:convert';
import 'dart:io';

import 'package:json_annotation/json_annotation.dart';
import 'package:test/test.dart';

import '../bin/image_size.dart';
import '../bin/results.dart';
import '../bin/service.dart';

part 'image_size_regression_test.g.dart';

const imageDir = '../data/images';
const goldenFile = 'goldens/image_size_regression_test.json';
const parallelColumns = [1, 2, 3];

// If (new value)/(old value) goes above this threshold, fail the test.
const maxFailThreshold = 2.0;
const avgFailThreshold = 1.1;
const ramFailThreshold = 1.1;

// If (new value)/(old value) goes below this threshold, update the goldens.
const maxUpdateThreshold = 0.7;
const avgUpdateThreshold = 0.9;
const ramUpdateThreshold = 0.9;

// Averages of fewer than this many data points won't be compared.
const avgCompareThreshold = 3;
// Durations under this many milliseconds won't be compared.
const msCompareThreshold = 100;

@JsonSerializable()
class ImageSizeRegressionTestEnvResults {
  ImageSizeRegressionTestEnvResults(this.images);
  Map<String, ImageResult> images;
  factory ImageSizeRegressionTestEnvResults.fromJson(Map<String, dynamic> json) => _$ImageSizeRegressionTestEnvResultsFromJson(json);
  Map<String, dynamic> toJson() => _$ImageSizeRegressionTestEnvResultsToJson(this);
}

@JsonSerializable()
class ImageSizeRegressionTestResults {
  ImageSizeRegressionTestResults(this.environments);
  Map<String, ImageSizeRegressionTestEnvResults> environments;
  factory ImageSizeRegressionTestResults.fromJson(Map<String, dynamic> json) => _$ImageSizeRegressionTestResultsFromJson(json);
  Map<String, dynamic> toJson() => _$ImageSizeRegressionTestResultsToJson(this);
}

void main() async {
  final env = Service.environmentKey('IMAGE_SIZE_ENDPOINT');

  final golden = File('goldens/image_size_regression_test.json');
  final goldenValues = golden.existsSync() ? ImageSizeRegressionTestResults.fromJson(jsonDecode(golden.readAsStringSync())) : ImageSizeRegressionTestResults({});

  // If ok is true at the end of all tests, a the golden file will be written.
  // This will have no effect if the golden values don't change past the
  // thresholds configured above.
  var ok = true;
  goldenValues.environments[env] ??= ImageSizeRegressionTestEnvResults({});
  final Map<String, ImageResult> envGoldens = goldenValues.environments[env]!.images;

  final Map<String, ImageResult> results = {};
  await ImageSize.runBenchmark(
    endpoint: Platform.environment['IMAGE_SIZE_ENDPOINT'] ??
        ImageSize.defaultEndpoint,
    parallelColumns: parallelColumns,
    binarySearchLimit: 0,
  ).listen((result) {
    print(result);
    results[result.name] = result;
  }).asFuture();
  ImageResult.printAll(results.values);

  for (final ImageResult imageResult in results.values) {
    final goldenImageResult = envGoldens[imageResult.name] ?? ImageResult();
    goldenImageResult.name = imageResult.name;
    goldenImageResult.size = imageResult.size;
    goldenImageResult.dimensions = imageResult.dimensions;
    goldenImageResult.group = imageResult.group;

    imageResult.parallel.columns.forEach((parallelism, result) {
      // Test request success.
      if (result.failed > 0) {
        ok = false;
      }
      test('$parallelism requests for ${imageResult.name} succeeded', () =>
          expect(result.succeeded, equals(parallelism)));

      ParallelResult? goldenParallelResult = goldenImageResult.parallel
          .columns[parallelism];
      if (goldenParallelResult != null) {
        if (result.max >= msCompareThreshold) {
          int maxThreshold = (goldenParallelResult.max * maxFailThreshold)
              .round();
          test('$parallelism requests for ${imageResult
              .name} took under $maxThreshold ms', () =>
              expect(result.max, lessThanOrEqualTo(maxThreshold)));
          if (result.max > maxThreshold) {
            ok = false;
          }
          if (result.max <
              (goldenParallelResult.max * maxUpdateThreshold).round()) {
            goldenParallelResult.max = result.max;
          }
        }

        if (parallelism >= avgCompareThreshold && result.avg >= msCompareThreshold) {
          int avgThreshold = (goldenParallelResult.avg * avgFailThreshold)
              .round();
          test('$parallelism requests for ${imageResult
              .name} averaged under $avgThreshold ms', () =>
              expect(result.avg, lessThanOrEqualTo(avgThreshold)));
          if (result.avg > avgThreshold) {
            ok = false;
          }
          if (result.avg <
              (goldenParallelResult.avg * avgUpdateThreshold).round()) {
            goldenParallelResult.total = result.total;
          }
        }

        if (goldenParallelResult.ram != null && result.ram != null) {
          int ramThreshold = (goldenParallelResult.ram! * ramFailThreshold)
              .round();
          test('$parallelism requests for ${imageResult
              .name} used under ${ramThreshold ~/ 1024} MiB', () =>
              expect(result.ram, lessThanOrEqualTo(ramThreshold)));
          if (result.ram! > ramThreshold) {
            ok = false;
          }
          if (result.ram! <
              (goldenParallelResult.ram! * ramUpdateThreshold).round()) {
            goldenParallelResult.ram = result.ram;
          }
        } else {
          goldenParallelResult.ram = result.ram;
        }
      } else {
        goldenParallelResult = result;
      }
      goldenImageResult.parallel.columns[parallelism] = goldenParallelResult;
    });

    envGoldens[imageResult.name] = goldenImageResult;
  }

  if (ok) {
    test('Writing new goldens file', () {
      goldenValues.environments[env]!.images = envGoldens;
      golden.writeAsStringSync(
          JsonEncoder.withIndent('\t').convert(goldenValues));
    });
  }
}
