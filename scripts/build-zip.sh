#!/bin/sh
# Builds the install zip for WebsiteBaker: dist/privatus_analytics-<version>.zip
# The files sit at the root of the zip, as the WebsiteBaker installer expects.
set -e
cd "$(dirname "$0")/.."
VERSION=$(sed -n "s/^\$module_version *= *'\([^']*\)'.*/\1/p" privatus_analytics/info.php)
OUT="dist/privatus_analytics-$VERSION.zip"
mkdir -p dist
rm -f "$OUT"
cp LICENSE privatus_analytics/LICENSE.txt
(cd privatus_analytics && zip -q -X -r "../$OUT" info.php index.php include.php install.php upgrade.php uninstall.php tool.php PrivatusAnalytics.php LICENSE.txt languages)
rm -f privatus_analytics/LICENSE.txt
echo "$OUT"
