#!/usr/bin/env bash
# Creează pachetul instalabil dist/mod_megameniu-<versiune>.zip
set -euo pipefail
cd "$(dirname "$0")"

VERSION=$(sed -n 's:.*<version>\(.*\)</version>.*:\1:p' mod_megameniu.xml | head -1)
OUT="dist/mod_megameniu-${VERSION}.zip"

# Verificări rapide înainte de împachetare
for f in $(find . -path ./tests -prune -o -name '*.php' -print); do php -l "$f" >/dev/null; done
php -r 'exit(simplexml_load_file("mod_megameniu.xml") ? 0 : 1);'

mkdir -p dist
rm -f "$OUT"
zip -qr -X "$OUT" mod_megameniu.xml mod_megameniu.php src tmpl media language -x '*.DS_Store'
echo "Pachet creat: $OUT"
unzip -l "$OUT"
