#!/bin/sh
# Runs a real WebsiteBaker (latest full version) with MariaDB in Docker.
# It installs WebsiteBaker, uploads the module zip through the backend (so
# CodeGuard checks it), saves the settings and checks the tag on the home page.
# Usage: ./scripts/verify-docker.sh
set -e
cd "$(dirname "$0")/.."
ZIP=$(./scripts/build-zip.sh)
WB_DOWNLOAD="${WB_DOWNLOAD:-https://addon.websitebaker.org/en/browse-add-ons/?download=0A0A0E56}"
PORT="${PORT:-8089}"
NET=pa-wb-test
WORK=$(mktemp -d)
cleanup() { docker rm -f pa-wb-web wbdb >/dev/null 2>&1 || true; docker network rm $NET >/dev/null 2>&1 || true; rm -rf "$WORK"; }
trap cleanup EXIT
cleanup; WORK=$(mktemp -d)

curl -fsSL -A "Mozilla/5.0" -o "$WORK/wb.zip" "$WB_DOWNLOAD"
mkdir "$WORK/core" && (cd "$WORK/core" && unzip -q ../wb.zip)

cat > "$WORK/Dockerfile" <<'DOCKER'
FROM php:8.3-apache
RUN apt-get update -qq && DEBIAN_FRONTEND=noninteractive apt-get install -y -qq libzip-dev libpng-dev >/dev/null \
 && docker-php-ext-install -j4 mysqli pdo_mysql zip gd >/dev/null
DOCKER
docker build -q -t pa-wb-php "$WORK" >/dev/null

docker network create $NET >/dev/null
docker run -d --name wbdb --network $NET -e MARIADB_RANDOM_ROOT_PASSWORD=1 -e MARIADB_DATABASE=wb \
  -e MARIADB_USER=wb -e MARIADB_PASSWORD=wbpw mariadb:11 >/dev/null
docker run -d --name pa-wb-web --network $NET -p "$PORT:80" -v "$WORK/core":/src:ro pa-wb-php \
  sh -c 'cp -a /src/. /var/www/html/ && chown -R www-data:www-data /var/www/html && apache2-foreground' >/dev/null

for i in $(seq 1 60); do
  docker exec wbdb mariadb -uwb -pwbpw -e 'select 1' wb >/dev/null 2>&1 && curl -fs -o /dev/null "http://localhost:$PORT/install/index.php?sessions_checked=true" && break
  sleep 1
done

python3 scripts/verify_wb.py "http://localhost:$PORT" "$ZIP"

if docker logs pa-wb-web 2>&1 | grep -i "privatus" | grep -qiE "warning|fatal|deprecated|notice"; then
  docker logs pa-wb-web 2>&1 | grep -i privatus
  echo "FAIL PHP messages from the module"; exit 1
fi
echo "ok   no PHP messages from the module"
