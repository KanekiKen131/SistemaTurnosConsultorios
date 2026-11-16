#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")"

NAME="SistemaTurnosConsultorios"
OUT="dist"
OUT_NAME="${OUT_NAME:-$NAME.zip}"
ROOT="$(pwd)"

if ! command -v zip >/dev/null 2>&1; then
    echo "Error: se requiere el comando 'zip' para generar el paquete." >&2
    exit 1
fi

STAGE="$(mktemp -d)"
trap 'rm -rf "$STAGE"' EXIT

mkdir -p "$STAGE/$NAME" "$OUT"

cp -R app config public README.md INSTALACION.md LEEME.txt start.sh start.bat package.sh "$STAGE/$NAME/"
chmod +x "$STAGE/$NAME/start.sh" "$STAGE/$NAME/package.sh"

if [ "${INCLUDE_RUNTIME:-0}" = "1" ]; then
    if [ ! -d runtime ]; then
        echo "Error: no existe runtime/. Ejecute ./build-portable.sh primero." >&2
        exit 1
    fi
    cp -R runtime "$STAGE/$NAME/"
fi

rm -f "$OUT/$OUT_NAME"
(cd "$STAGE" && zip -rq "$ROOT/$OUT/$OUT_NAME" "$NAME")

echo "Paquete generado: $OUT/$OUT_NAME"
