#!/usr/bin/env bash
set -euo pipefail
root="$(cd "$(dirname "$0")/.." && pwd)"
cd "$root"
chmod +x .githooks/pre-commit .githooks/pre-push scripts/install-hooks.sh
git config core.hooksPath .githooks
echo "hooksPath=$(git config core.hooksPath)"
