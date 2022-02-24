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

import 'dart:collection';
import 'dart:convert';
import 'dart:io';

import 'package:dio/dio.dart';

class ParallelResult {
  ParallelResult(this.display, this.failed);

  String display;
  bool failed;
}

class ParallelResults {
  SplayTreeMap<int, ParallelResult> columns = SplayTreeMap();
  int parallelLimit = 0;
  int? memory;
}

abstract class Service {
  Service(this.endpoint, this.name,
      {this.enableMemory = true, ResponseType type = ResponseType.json})
      : dio = Dio(BaseOptions(
          connectTimeout: 120000,
          receiveTimeout: 120000,
          responseType: type,
        )),
        pid = enableMemory ? dockerPid(name) ?? nodePid(endpoint) : null {
    print('Connecting to $name at $endpoint');
  }

  final String name;
  final String endpoint;
  final Dio dio;
  final int? pid;
  final bool enableMemory;
  Process? monitor;

  static int? dockerPid(String name) {
    final command = 'docker top $name | awk \'{ print \$2 }\' | grep -v PID';
    final String output = Process.runSync('bash', ['-c', command]).stdout;
    final pid = output.trim().isEmpty ? null : int.tryParse(output.trim());
    print(pid == null ? 'Didn\'t find Docker $name' : 'Found docker pid $pid');
    return pid;
  }

  static int? nodePid(String endpoint) {
    final port = Uri.parse(endpoint).port;
    final command =
        "netstat -anp | perl -F'[/\\s]' -lane 'print \$F[-2] if /^tcp.+:$port.+LISTEN/'";
    final String output = Process.runSync('bash', ['-c', command]).stdout;
    final pid = output.trim().isEmpty ? null : int.tryParse(output.trim());
    print(pid == null ? 'Didn\'t find Node $endpoint' : 'Found node pid $pid');
    return pid;
  }

  Future<void> startMemoryMonitoring() async {
    if (pid == null || !enableMemory) {
      return;
    }
    final loop = 'while true; do ps -ho rss $pid; sleep 0.1; done';
    monitor = await Process.start('bash', ['-c', loop]);
  }

  Future<int?> getPeakMemory() async {
    if (monitor == null) {
      return null;
    }
    monitor!.kill();
    int maxMemory = 0;
    await monitor!.stdout
        .transform(const Utf8Decoder())
        .transform(const LineSplitter())
        .listen((line) {
      final memory = int.tryParse(line);
      if (memory != null && memory > maxMemory) {
        maxMemory = memory;
      }
    }).asFuture();
    monitor = null;
    return maxMemory;
  }

  Future<void> waitForService() async {
    if (pid != null) {
      // Wait for GC
      sleep(Duration(seconds: 3));
    }
    var down = true;
    while (down) {
      try {
        try {
          await dio.get(endpoint);
        } on DioError catch (e) {
          if (e.response?.statusCode != 400) {
            // It is good to get 400, means it's working.
            rethrow;
          }
        }
        down = false;
      } on Exception {
        sleep(Duration(milliseconds: 100));
      }
    }
  }

  Future<Response> request(Future<FormData> Function() data) async {
    return dio.post(endpoint, data: await data());
  }

  Future<Duration> benchmark(Future<FormData> Function() data) async {
    final stopwatch = Stopwatch();
    final response = await dio.post(endpoint, data: await data(),
        onSendProgress: (int sent, int total) {
      if (sent == total) {
        stopwatch.start();
      }
    }, onReceiveProgress: (int received, int total) {
      if (received > 0) {
        stopwatch.stop();
      }
    });
    if (response.statusCode == null || response.statusCode! > 200) {
      throw Exception('Response status was ${response.statusCode ?? 'null'}');
    }
    return stopwatch.elapsed;
  }

  Future<ParallelResults> benchmarkParallel(Future<FormData> Function() data,
      Iterable<int> parallelColumns, int binarySearchLimit) async {
    final result = ParallelResults();

    // Benchmark with predefined parallelism values
    for (final parallelism in parallelColumns) {
      await waitForService();
      await startMemoryMonitoring();
      print('Making $parallelism $name requests');
      final List<Future<Duration>> futures =
          List.generate(parallelism, (_) => benchmark(data), growable: false);
      var failed = 0;
      var succeeded = 0;
      var totalDuration = 0;
      var maxDuration = 0;
      await Future.wait(futures.map((Future<Duration> future) async {
        try {
          final ms = (await future).inMilliseconds;
          if (ms > maxDuration) {
            maxDuration = ms;
          }
          totalDuration += ms;
          succeeded++;
        } on Exception {
          failed++;
        }
      }));
      if (failed + succeeded != parallelism) {
        throw StateError(
            'Failed $failed + succeeded $succeeded != $parallelism');
      }
      if (failed > 0) {
        await waitForService();
      }
      var display =
          '$succeeded/$parallelism in $maxDuration ms, avg ${totalDuration ~/ (succeeded == 0 ? 1 : succeeded)} ms';
      final memory = await getPeakMemory();
      if (memory != null) {
        final mb = memory ~/ 1024;
        final displayMemory =
            mb > 1024 ? (mb / 1024).toStringAsFixed(2) + ' GB' : '$mb MB';
        display += ' ($displayMemory)';
      }
      result.columns[parallelism] = ParallelResult(display, failed > 0);
    }

    // Binary search to estimate max concurrency
    int lower = 1;
    int upper = binarySearchLimit;
    result.columns.forEach((count, parallelResult) {
      if (count <= upper && parallelResult.failed) {
        upper = count - 1;
      }
      if (count > lower && !parallelResult.failed) {
        lower = count;
      }
    });
    while (lower < upper) {
      await waitForService();
      int est = upper == binarySearchLimit ? upper : (lower + upper) ~/ 2;
      if (est == lower && est == upper - 1) {
        est++;
      }
      print('Making $est $name requests (binary search)');
      final List<Future<Response>> futures =
          List.generate(est, (_) => request(data), growable: false);
      var failed = false;
      await Future.wait(futures.map((Future<Response> future) async {
        try {
          await future;
        } on Exception {
          failed = true;
        }
      }));
      if (failed) {
        print('$est requests failed');
        upper = est - 1;
      } else {
        print('$est requests succeeded');
        lower = est;
      }
    }
    result.parallelLimit = lower;

    return result;
  }
}
