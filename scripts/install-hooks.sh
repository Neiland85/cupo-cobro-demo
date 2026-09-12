#!/usr/bin/env bash
# Un solo camino: Husky apunta a .husky, que llama a .githooks.
set -euo pipefail
root="$(cd "$(dirname "$0")/.." && pwd)"
cd "$root"
chmod +x .githooks/pre-commit .githooks/pre-push .husky/pre-commit .husky/pre-push

if ! command -v npm >/dev/null; then
  echo "NO-GO: npm no está en PATH. Sin Node, usa: git config core.hooksPath .githooks" >&2
  git config core.hooksPath .githooks
  echo "hooksPath=$(git config core.hooksPath) (fallback sin Husky)"
  exit 0
fi

npm install --no-fund --no-audit
git config core.hooksPath .husky
echo "hooksPath=$(git config core.hooksPath)"
