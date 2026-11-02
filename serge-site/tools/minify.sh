#!/bin/bash
# Минимизация CSS и JS: из assets/css/style.css и assets/js/*.js делает *.min.css / *.min.js рядом.
# Нужны Node.js и esbuild:  npm i -g esbuild   (или  npx esbuild ...)
# Исходники править как обычно, после правок запускать этот скрипт. Сайт сам берёт .min-файлы, если они есть
# (включается в includes/config.php: ASSET_MIN). Результат расчётов от минимизации не меняется.
set -e
cd "$(dirname "$0")/.."
ES=${ESBUILD:-esbuild}
$ES assets/css/style.css --minify --outfile=assets/css/style.min.css
for f in assets/js/main.js assets/js/catalog.js assets/js/carousel.js assets/js/calc.js; do
  $ES "$f" --minify --target=es5 --outfile="${f%.js}.min.js"
done
ls -la assets/css/*.min.css assets/js/*.min.js
