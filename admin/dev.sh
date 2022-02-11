#!/usr/bin/env bash
trap -- '' SIGINT SIGTERM
git update-index --assume-unchanged admin/.htaccess
sed -i -E 's/^# (.+ # dev.sh add *)$/\1/;s/^([^#].+ # dev.sh remove *)$/# \1/' admin/.htaccess
npx vite admin/client
sed -i -E 's/^# (.+ # dev.sh remove *)$/\1/;s/^([^#].+ # dev.sh add *)$/# \1/' admin/.htaccess
git update-index --no-assume-unchanged admin/.htaccess
