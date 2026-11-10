#!/usr/bin/env bash
# Aplica no servidor as entregas já mergeadas (migrations + seeds aditivos + cache).
# Pré-requisito: código atualizado (git pull / deploy) nesta máquina.
#
# Uso (raiz do Vendix):
#   bash scripts/aplicar-entregas.sh
#   bash scripts/aplicar-entregas.sh --with-build
#   bash scripts/aplicar-entregas.sh --dry-run
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

WITH_BUILD=0
DRY_RUN=0
for arg in "$@"; do
  case "$arg" in
    --with-build) WITH_BUILD=1 ;;
    --dry-run) DRY_RUN=1 ;;
    -h|--help)
      cat <<'EOF'
Uso: bash scripts/aplicar-entregas.sh [--with-build] [--dry-run]

Passos atuais:
  1. php artisan migrate --force
     - 2026_11_10_000001 sale_returns / sale_return_items (devolução parcial)
     - 2026_11_10_000002 sale_exchanges / sale_exchange_items (troca)
  2. php artisan db:seed --class=SalesStockPermissionsSeeder --force
     - concede return-sales ao Admin (idempotente)
  3. php artisan optimize:clear
  4. (opcional --with-build) npm ci && npm run build
EOF
      exit 0
      ;;
    *)
      echo "Argumento desconhecido: $arg" >&2
      exit 1
      ;;
  esac
done

if [[ ! -f artisan ]]; then
  echo "Rode na raiz do Vendix (artisan não encontrado)." >&2
  exit 1
fi

run() {
  echo "+ $*"
  if [[ "$DRY_RUN" -eq 0 ]]; then
    "$@"
  fi
}

echo "==> Entregas Vendix — pós-deploy"
echo "    raiz: $ROOT"
echo

echo "==> 1/3 Migrations aditivas (devolução + troca)"
run php artisan migrate --force

echo "==> 2/3 Permissões de vendas/estoque (return-sales)"
run php artisan db:seed --class=SalesStockPermissionsSeeder --force

echo "==> 3/3 Limpar caches"
run php artisan optimize:clear

if [[ "$WITH_BUILD" -eq 1 ]]; then
  echo "==> Build front (portal/assets)"
  run npm ci
  run npm run build
fi

echo
echo "OK. Checklist manual:"
echo "  [ ] Admin tem return-sales"
echo "  [ ] Venda confirmada → Devolver itens"
echo "  [ ] Venda confirmada → Trocar itens (mesmo valor / mais caro / mais barato)"
echo "  [ ] Comprovante lista devoluções e trocas"
echo
echo "Docs: docs/VALIDACAO-DEVOLUCAO.md | docs/VALIDACAO-TROCA.md"
echo "Alias legado: scripts/aplicar-devolucao-parcial.sh → este script"
