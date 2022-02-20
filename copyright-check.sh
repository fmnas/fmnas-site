#!/usr/bin/env bash

failed=0

# All files added between main and HEAD
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

	# If Sean created the file
	if [[ $(git log --format=format:%aE | tail -1) = "sean@forgetmenotshelter.org" ]]; then
		# Check for a license header
		if head -3 "$file" | grep -qE 'Copyright 20[0-9]{2} Google LLC'; then
			echo "License header found in $file"
		else
			echo "License header not found in $file" 1>&2
			((failed++))
		fi
	fi
done <<< "$(git diff --name-only --diff-filter=A main HEAD)"

# Status code is the number of failed files
exit "$failed"
