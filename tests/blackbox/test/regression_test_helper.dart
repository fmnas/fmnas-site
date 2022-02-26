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

import 'package:test/test.dart';

import '../bin/results.dart';
import '../bin/service.dart';

// If (new value)/(old value) goes above this threshold, fail the test.
const maxFailThreshold = 2.0;
const avgFailThreshold = 1.1;
const ramFailThreshold = 1.5;

// If (new value)/(old value) goes below this threshold, update the goldens.
const maxUpdateThreshold = 0.7;
const avgUpdateThreshold = 0.9;
const ramUpdateThreshold = 0.5;

// Averages of fewer than this many data points won't be compared.
const avgCompareThreshold = 3;
// Durations under this many milliseconds won't be compared.
const msCompareThreshold = 100;

bool regressionTest(ImageResult imageResult, ImageResult goldenImageResult) {
  var ok = true;
  imageResult.parallel.columns.forEach((parallelism, result) {
    // Test request success.
    if (result.failed > 0) {
      ok = false;
    }
    test('$parallelism requests for ${imageResult.name} succeeded',
        () => expect(result.succeeded, equals(parallelism)));

    ParallelResult? goldenParallelResult =
        goldenImageResult.parallel.columns[parallelism];
    if (goldenParallelResult != null) {
      if (result.max >= msCompareThreshold) {
        int maxThreshold =
            (goldenParallelResult.max * maxFailThreshold).round();
        test(
            '$parallelism requests for ${imageResult.name} took under $maxThreshold ms',
            () => expect(result.max, lessThanOrEqualTo(maxThreshold)));
        if (result.max > maxThreshold) {
          ok = false;
        }
        if (result.max <
            (goldenParallelResult.max * maxUpdateThreshold).round()) {
          goldenParallelResult.max = result.max;
        }
      }

      if (parallelism >= avgCompareThreshold &&
          result.avg >= msCompareThreshold) {
        int avgThreshold =
            (goldenParallelResult.avg * avgFailThreshold).round();
        test(
            '$parallelism requests for ${imageResult.name} averaged under $avgThreshold ms',
            () => expect(result.avg, lessThanOrEqualTo(avgThreshold)));
        if (result.avg > avgThreshold) {
          ok = false;
        }
        if (result.avg <
            (goldenParallelResult.avg * avgUpdateThreshold).round()) {
          goldenParallelResult.total = result.total;
        }
      }

      if (goldenParallelResult.ram != null && result.ram != null) {
        int ramThreshold =
            (goldenParallelResult.ram! * ramFailThreshold).round();
        test(
            '$parallelism requests for ${imageResult.name} used under ${ramThreshold ~/ 1024} MiB',
            () => expect(result.ram, lessThanOrEqualTo(ramThreshold)));
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

  return ok;
}
