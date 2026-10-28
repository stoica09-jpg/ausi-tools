#!/usr/bin/env bash
# Builds one installable Joomla package per extension folder into dist/
# (mod_*, plg_*, com_*, tpl_*, lib_*, pkg_*), e.g. dist/mod_adrian_cookie.zip.
# Every extension folder must contain a CITESTE-MA.txt; it is packed at the zip root.
set -euo pipefail

cd "$(dirname "$0")"
mkdir -p dist

shopt -s nullglob
built=0

for dir in mod_*/ plg_*/ com_*/ tpl_*/ lib_*/ pkg_*/; do
	ext="${dir%/}"

	if [[ ! -f "$ext/CITESTE-MA.txt" ]]; then
		echo "ERROR: $ext/CITESTE-MA.txt is missing" >&2
		exit 1
	fi

	rm -f "dist/$ext.zip"
	# The manifest must sit at the root of the zip, so zip the folder's contents.
	(cd "$ext" && zip -r -X -q "../dist/$ext.zip" . -x '.*')
	echo "Built dist/$ext.zip"
	built=$((built + 1))
done

[[ $built -gt 0 ]] || { echo "No extension folders found" >&2; exit 1; }
