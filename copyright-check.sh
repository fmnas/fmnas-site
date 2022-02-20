#!/usr/bin/env bash

failed=0

while read -r file; do
	# Skip binary files
	if ! grep -qI . "$file"; then
		continue
	fi

	# Skip generated, non-code, and template files
	allow_regex='^\.idea/|\.(lock|json|md|txt)$|\/go\.(mod|sum)|^admin/templates/|^src/templates/$'
	if [[ "$file" =~ $allow_regex ]]; then
		continue
	fi

	if [[ $(git log --format=format:%aE | tail -1) = "sean@forgetmenotshelter.org" ]]; then
		if head -3 "$file" | grep -qE 'Copyright 20[0-9]{2} Google LLC'; then
			echo "License header found in $file"
		else
			echo "Error: License header not found in $file" 1>&2
			((failed++))
		fi
	fi
done <<< "$(git diff --name-only --diff-filter=A origin/main HEAD)"

# Status code is the number of failed files
exit "$failed"
