#!/usr/bin/env sh
set -eu
# leise: nur ausfuehren, wenn Script existiert
if [ -f package.json ]; then
  npm run -s lint --if-present || true
fi
if [ -f composer.json ]; then
  composer validate --no-check-publish || true
fi