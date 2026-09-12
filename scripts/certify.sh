#!/usr/bin/env bash
# Certificación fail-closed: C1–C7 ∧ P1–P3 ∧ no-absorción.
# Un rojo detiene. El LLM no firma.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

STAMP="$(date -u +%Y%m%dT%H%M%SZ)"
EVIDENCE="${EVIDENCE:-$ROOT/var/evidence/$STAMP}"
mkdir -p "$EVIDENCE"

fail() { echo "NO-GO: $*" >&2; echo "verdict: NO-GO" >"$EVIDENCE/verdict.txt"; exit 1; }

command -v php >/dev/null || fail "php no está en PATH"
command -v composer >/dev/null || fail "composer no está en PATH"
php -r 'exit(version_compare(PHP_VERSION, "8.2.0", ">=") ? 0 : 1);' \
  || fail "PHP >= 8.2 requerido (hay $(php -r 'echo PHP_VERSION;'))"

test -f packages/clarity-nucleo/composer.json || fail "falta packages/clarity-nucleo (paso 2 del protocolo)"
test -f config/packages/nucleo.yaml || fail "falta punto de extensión config/packages/nucleo.yaml"
test -f tests/Nucleo/RequireKernelTest.php || fail "falta tests/Nucleo/RequireKernelTest.php"

# Briefing React no puede ser el producto
if test -f src/routes/amenazas.tsx || test -f src/lib/threats.ts; then
  fail "este árbol es el briefing, no alquiler-equipos-sonido"
fi

# Kernel no vive en App\
if grep -R --include='*.php' -F 'namespace Clarity\Nucleo\Context' src >/dev/null 2>&1; then
  fail "src/ absorbe Clarity\\Nucleo\\Context — el producto requiere, no copia"
fi

echo "== C1–C7 kernel =="
(
  cd packages/clarity-nucleo
  composer install --no-interaction --prefer-dist
  composer test -- --log-junit "$EVIDENCE/kernel-junit.xml" --testdox-text "$EVIDENCE/kernel-testdox.txt"
) || fail "kernel C1–C7 en rojo"

echo "== P1–P3 producto =="
composer show clarity/nucleo | tee "$EVIDENCE/composer-show.txt" | grep -q '0.2.0' \
  || fail "clarity/nucleo 0.2.0 no está requerido"
php bin/phpunit tests/Nucleo \
  --log-junit "$EVIDENCE/product-junit.xml" \
  --testdox-text "$EVIDENCE/product-testdox.txt" \
  || fail "producto P1–P3 en rojo"

echo "== estático: infra no entra en el kernel =="
if grep -RIn --include='*.php' -E '\bStripe\b|\bPDO\b|\bHttpRequest\b' packages/clarity-nucleo/src \
    | grep -v PaymentStub >/dev/null; then
  fail "el kernel nombra infraestructura (Stripe/PDO/HttpRequest)"
fi

{
  echo "product: alquiler-equipos-sonido"
  echo "kernel: clarity/nucleo 0.2.0"
  echo "head_product: $(git rev-parse HEAD 2>/dev/null || echo unknown)"
  echo "head_kernel_pkg: $(git log -1 --format=%H -- packages/clarity-nucleo 2>/dev/null || echo path-package)"
  echo "certified_at: $(date -u +%Y-%m-%dT%H:%M:%SZ)"
  echo "certifier: ${USER:-unknown}"
  echo "verdict: PENDING"
} | tee "$EVIDENCE/manifest.txt"

git rev-parse HEAD >"$EVIDENCE/product.HEAD" 2>/dev/null || true

if command -v shasum >/dev/null; then
  HASH=shasum
  HASH_ARGS=(-a 256)
elif command -v sha256sum >/dev/null; then
  HASH=sha256sum
  HASH_ARGS=()
else
  fail "ni shasum ni sha256sum"
fi

(
  cd "$EVIDENCE"
  $HASH "${HASH_ARGS[@]}" \
    kernel-junit.xml kernel-testdox.txt \
    product-junit.xml product-testdox.txt \
    composer-show.txt manifest.txt \
    >SHA256SUMS
)

echo
echo "Pack de evidencia: $EVIDENCE"
echo "Manifiesto en PENDING. El arquitecto firma GO o NO-GO — no el modelo."
echo "Cadena: amenaza → frontera → control → prueba → evidencia"
