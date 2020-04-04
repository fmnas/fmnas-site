#!/usr/bin/env sh
cd "${0%/*}" || {
  echo >&2 "Failed to change directory to ${0%/*}"
  exit 1
}
tarball=$(curl -s "https://api.github.com/repos/$1/$2/releases/latest" | grep tarball_url | cut -d \" -f 4)
if [ -z "$tarball" ]; then
  echo >&2 "Failed to fetch latest release of $1/$2 from github."
  exit 2
fi
echo "Tarball URL is $tarball";
if [ -e "$2/VERSION" ]; then
  existing="$(cat "$2/VERSION")"
  if [ "$tarball" = "$existing" ]; then
    echo "Latest version already downloaded. Exiting."
    exit 0
  fi
fi
rm -r "${2:?}/" 2>/dev/null
echo "Downloading tarball..."
curl -sLo "$2.tgz" "$tarball" &&
  echo "Making directory..." &&
  mkdir "$2" &&
  echo "Extracting tarball..." &&
  tar -xzf "$2.tgz" -C "$2" --strip-components=1 &&
  echo "Removing downloaded tarball..." &&
  rm "$2.tgz" &&
  echo "Saving version number..." &&
  echo "$tarball" >"$2/VERSION" &&
  echo "Successfully fetched latest version of $1/$2"
