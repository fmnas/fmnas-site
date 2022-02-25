# Copyright 2022 Google LLC
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.


# Dockerfile for Docker Compose tests. Not required for local development or deployment.

FROM dart
WORKDIR /tests/blackbox
COPY blackbox/pubspec.lock blackbox/pubspec.yaml ./
RUN dart pub get
COPY data/images /tests/data/images/
COPY blackbox/goldens goldens/
COPY blackbox/test test/
COPY blackbox/bin bin/
