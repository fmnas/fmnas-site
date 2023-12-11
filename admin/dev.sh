#!/usr/bin/env bash

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

# shellcheck disable=SC1090
. ~/.nvm/nvm.sh
trap -- '' SIGINT SIGTERM
git update-index --assume-unchanged admin/.htaccess
sed -i -E 's/^# (.+ # dev.sh add *)$/\1/;s/^([^#].+ # dev.sh remove *)$/# \1/' admin/.htaccess
npx vite admin/client
sed -i -E 's/^# (.+ # dev.sh remove *)$/\1/;s/^([^#].+ # dev.sh add *)$/# \1/' admin/.htaccess
git update-index --no-assume-unchanged admin/.htaccess
