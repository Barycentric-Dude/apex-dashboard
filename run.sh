#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
HOST="${HOST:-127.0.0.1}"
PORT="${PORT:-8080}"

echo "Starting Apex Fire IoT Dashboard at http://${HOST}:${PORT}"
cd "$ROOT_DIR"
exec php -S "${HOST}:${PORT}" -t public
