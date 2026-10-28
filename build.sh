#!/usr/bin/env bash
# Builds installable Joomla packages into dist/:
#   dist/mod_adrian_cookie.zip
#   dist/mod_adrian_accessibility_tools.zip
set -euo pipefail

cd "$(dirname "$0")"
mkdir -p dist

for module in mod_adrian_cookie mod_adrian_accessibility_tools; do
	rm -f "dist/$module.zip"
	# The manifest must sit at the root of the zip, so zip the folder's contents.
	(cd "$module" && zip -r -X -q "../dist/$module.zip" . -x '.*')
	echo "Built dist/$module.zip"
done
