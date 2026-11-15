#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")"

NAME="SistemaTurnosConsultorios"
OUT="dist"
ROOT="$(pwd)"

if ! command -v zip >/dev/null 2>&1; then
    echo "Error: se requiere el comando 'zip' para generar el paquete." >&2
    exit 1
fi

STAGE="$(mktemp -d)"
trap 'rm -rf "$STAGE"' EXIT

mkdir -p "$STAGE/$NAME" "$OUT"

cp -R app config public README.md INSTALACION.md start.sh start.bat package.sh "$STAGE/$NAME/"
chmod +x "$STAGE/$NAME/start.sh" "$STAGE/$NAME/package.sh"

rm -f "$OUT/$NAME.zip"
(cd "$STAGE" && zip -rq "$ROOT/$OUT/$NAME.zip" "$NAME")

echo "Paquete generado: $OUT/$NAME.zip"
