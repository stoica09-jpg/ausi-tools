#!/bin/sh
# Builds the installable package (component + 2 plugins):
#   dist/pkg_adrianjdownloads-<version>.zip
set -e
cd "$(dirname "$0")"
VERSION=$(sed -n 's:.*<version>\(.*\)</version>.*:\1:p' package/pkg_adrianjdownloads.xml | head -1)
TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT

(cd com_adrianjdownloads && zip -qr "$TMP/com_adrianjdownloads.zip" . -x '*.DS_Store')
(cd plg_content_adrianjdownloads && zip -qr "$TMP/plg_content_adrianjdownloads.zip" . -x '*.DS_Store')
(cd plg_editors-xtd_adrianjdownloads && zip -qr "$TMP/plg_editors-xtd_adrianjdownloads.zip" . -x '*.DS_Store')
cp package/pkg_adrianjdownloads.xml package/script.php com_adrianjdownloads/citestema.txt "$TMP/"

mkdir -p dist
rm -f dist/*.zip
(cd "$TMP" && zip -qr - .) > "dist/pkg_adrianjdownloads-$VERSION.zip"
echo "dist/pkg_adrianjdownloads-$VERSION.zip"
