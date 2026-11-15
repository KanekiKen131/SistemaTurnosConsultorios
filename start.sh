#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")"

PORT="${PORT:-8000}"

echo "Sistema de Turnos para Consultorios"
echo "Servidor:   http://localhost:${PORT}/"
echo "Pantalla TV: http://<IP-DEL-SERVIDOR>:${PORT}/?action=pantalla"
echo "Presione Ctrl+C para detener."
echo

exec php -S 0.0.0.0:"${PORT}" -t public
