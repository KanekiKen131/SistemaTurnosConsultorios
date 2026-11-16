#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")"

PHP_CACHE=".cache/php-8.5.12-nts-Win32-vs17-x64.zip"
PHP_URL="https://downloads.php.net/~windows/releases/archives/php-8.5.12-1-nts-Win32-vs17-x64.zip"
PHP_SHA256="2a36a2edcb7a5b9252f638fd5e4cf84d7f6e066092654220dc0b63e5cf4e9723"

VC_CACHE=".cache/vc_redist.x64.exe"
VC_URL="https://aka.ms/vs/17/release/vc_redist.x64.exe"
VC_SHA256="cc0ff0eb1dc3f5188ae6300faef32bf5beeba4bdd6e8e445a9184072096b713b"

OUT_NAME="SistemaTurnosConsultorios-Windows-x64.zip"

for command in curl unzip sha256sum zip; do
    if ! command -v "$command" >/dev/null 2>&1; then
        echo "Error: se requiere el comando '$command'." >&2
        exit 1
    fi
done

mkdir -p .cache runtime

ensure_download() {
    local url="$1" dest="$2" hash="$3"

    if [ -f "$dest" ] && echo "$hash  $dest" | sha256sum -c - >/dev/null 2>&1; then
        return
    fi

    rm -f "$dest"
    curl -fL --retry 3 -o "$dest" "$url"
    echo "$hash  $dest" | sha256sum -c -
}

ensure_download "$PHP_URL" "$PHP_CACHE" "$PHP_SHA256"
ensure_download "$VC_URL" "$VC_CACHE" "$VC_SHA256"

rm -rf runtime/php
mkdir -p runtime/php
unzip -q "$PHP_CACHE" -d runtime/php
cp portable/php.ini runtime/php/php.ini
cp "$VC_CACHE" runtime/vc_redist.x64.exe

INCLUDE_RUNTIME=1 OUT_NAME="$OUT_NAME" ./package.sh

echo "Listo: dist/$OUT_NAME"
