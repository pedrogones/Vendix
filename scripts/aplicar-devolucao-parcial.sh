#!/usr/bin/env bash
# Legado: redireciona para o script acumulativo de entregas.
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
exec bash "$ROOT/scripts/aplicar-entregas.sh" "$@"
