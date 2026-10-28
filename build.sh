#!/bin/sh
# Builds the installable package: dist/com_adrianjdownloads-<version>.zip
set -e
cd "$(dirname "$0")"
VERSION=$(sed -n 's:.*<version>\(.*\)</version>.*:\1:p' com_adrianjdownloads/adrianjdownloads.xml | head -1)
mkdir -p dist
rm -f "dist/com_adrianjdownloads-$VERSION.zip"
(cd com_adrianjdownloads && zip -qr "../dist/com_adrianjdownloads-$VERSION.zip" . -x '*.DS_Store')
echo "dist/com_adrianjdownloads-$VERSION.zip"
