# Protocolo de integración NÚCLEO → alquiler-equipos-sonido

Orden fijo. Un rojo detiene. El briefing no entra en `src/`.
`cat <<'EOF'` solo en los fallbacks marcados. El kernel no se pega a mano.

Cadena de certificación:

```
amenaza → frontera → control → prueba → evidencia → GO/NO-GO (arquitecto)
```

Tres árboles. Nunca un merge.

| Árbol | Qué es | Qué no es |
|---|---|---|
| Briefing NÚCLEO | significado, mapa, laboratorio | código de dominio PHP |
| `clarity/nucleo` 0.2.0 | contratos ejecutables | Symfony, Stripe, SQL |
| alquiler-equipos-sonido | producto de sonido | turismo, ni el briefing |

Sustituye rutas si las tuyas difieren. Los corchetes de `[GROK]` exigen comillas.

```bash
export PRODUCT="$HOME/Projects/GITHUB-PROJECTS-2026/GOVERNANZA_ORGANIZATIVA/Bootstrap/alquiler-equipos-sonido"
export KERNEL="$HOME/Projects/GITHUB-PROJECTS-2026/GOVERNANZA_ORGANIZATIVA/Bootstrap/nucleo-php-roots"
export GROK_WS="$HOME/Documents/b[GROK]theNew-WokeUp/y_clientes/y1_shakers/y1.1_Carlos/screenshots-alvara-enterview/Y2_PHP-Roots/7cCK83zfyh1rYCU7-grok-workspace"
export EVIDENCE="$PRODUCT/var/evidence/$(date -u +%Y%m%dT%H%M%SZ)"
```

Si `GROK_WS` **es** el mismo git que `PRODUCT` (el `==` del corte), apunta `PRODUCT` ahí **después** de verificar que no es el briefing:

```bash
if [ -f "$GROK_WS/src/routes/amenazas.tsx" ] || [ -f "$GROK_WS/src/lib/threats.ts" ]; then
  echo "NO-GO: GROK_WS es el briefing. PRODUCT es alquiler-equipos-sonido. No mezclar."
  exit 1
fi
if [ -d "$GROK_WS/.git" ] && [ -f "$GROK_WS/composer.json" ]; then
  export PRODUCT="$GROK_WS"
  export EVIDENCE="$PRODUCT/var/evidence/$(date -u +%Y%m%dT%H%M%SZ)"
fi
```

## 0. Precondiciones

```bash
php -v | head -1
php -r 'exit(version_compare(PHP_VERSION,"8.2.0",">=")?0:1);'
composer -V | head -1
git -C "$PRODUCT" status --porcelain
git -C "$PRODUCT" rev-parse --abbrev-ref HEAD
test ! -f "$PRODUCT/src/routes/amenazas.tsx"
```

Esperado: PHP ≥ 8.2, Composer 2, rama `main`, working tree vacío (o `git stash`), sin rutas React de briefing.

Si el árbol de producto está sucio:

```bash
git -C "$PRODUCT" stash push -u -m "pre-nucleo-$(date -u +%Y%m%dT%H%M%SZ)"
```

## 1. Kernel canónico (no el briefing)

Fuente: `Neiland85/nucleo-php-roots` (`clarity/nucleo` 0.2.0).
No clones el workspace del briefing. No copies `src/routes`.

```bash
if [ ! -d "$KERNEL/.git" ]; then
  mkdir -p "$(dirname "$KERNEL")"
  git clone git@github.com:Neiland85/nucleo-php-roots.git "$KERNEL"
else
  git -C "$KERNEL" fetch origin
  git -C "$KERNEL" checkout main
  git -C "$KERNEL" pull --ff-only origin main
fi
git -C "$KERNEL" describe --always --dirty
test -f "$KERNEL/src/Content/Boundary.php"
test -f "$KERNEL/src/HoldPipe/Pipe.php"
test -f "$KERNEL/tests/PipeTest.php"
```

HEAD canónico esperado (o posterior): `a90d309f3c4ff44fda1dfa77beac815a2292d2cd`.

## 2. Producto: requerir el paquete (path repo)

El kernel vive en `packages/clarity-nucleo`. Composer lo requiere. `src/` no lo absorbe.

```bash
git -C "$PRODUCT" fetch origin
git -C "$PRODUCT" checkout main
git -C "$PRODUCT" pull --ff-only origin main
mkdir -p "$PRODUCT/packages"
rsync -a --delete \
  --exclude vendor --exclude .git --exclude .phpunit.cache \
  --exclude composer.lock \
  "$KERNEL"/ "$PRODUCT/packages/clarity-nucleo/"
printf 'canonical: Neiland85/nucleo-php-roots\ncommit: %s\nsynced_at: %s\n' \
  "$(git -C "$KERNEL" rev-parse HEAD)" \
  "$(date -u +%Y-%m-%dT%H:%M:%SZ)" \
  > "$PRODUCT/packages/clarity-nucleo/SOURCE.txt"
test -f "$PRODUCT/packages/clarity-nucleo/src/HoldPipe/Pipe.php"
test -f "$PRODUCT/packages/clarity-nucleo/src/Evidence/Chain.php"
test -f "$PRODUCT/packages/clarity-nucleo/src/Context/Catalog.php"
```

Si no hay `rsync`:

```bash
rm -rf "$PRODUCT/packages/clarity-nucleo"
mkdir -p "$PRODUCT/packages/clarity-nucleo"
cp -R "$KERNEL"/. "$PRODUCT/packages/clarity-nucleo/"
rm -rf "$PRODUCT/packages/clarity-nucleo/.git" \
       "$PRODUCT/packages/clarity-nucleo/vendor" \
       "$PRODUCT/packages/clarity-nucleo/.phpunit.cache"
```

### 2b. Fallback: `composer.json` sin path require

Solo si `grep -q 'clarity/nucleo' "$PRODUCT/composer.json"` falla. No reescribas el JSON a mano.

```bash
python3 - <<'PY'
import json, os, sys
p = os.environ["PRODUCT"] + "/composer.json"
with open(p) as f:
    data = json.load(f)
repos = data.setdefault("repositories", [])
if not any(r.get("url") == "packages/clarity-nucleo" for r in repos if isinstance(r, dict)):
    repos.insert(0, {"type": "path", "url": "packages/clarity-nucleo", "options": {"symlink": False}})
data.setdefault("require", {})["clarity/nucleo"] = "0.2.0"
with open(p, "w") as f:
    json.dump(data, f, indent=4)
    f.write("\n")
print("require clarity/nucleo 0.2.0 — path packages/clarity-nucleo")
PY
```

### 2c. Fallback: punto de extensión y prueba P1–P3

Solo si faltan tras el pull. `mkdir -p` y `cat <<'EOF'` — no copies esto a `src/`.

```bash
mkdir -p "$PRODUCT/config/packages" "$PRODUCT/tests/Nucleo" "$PRODUCT/docs/security" "$PRODUCT/scripts"
```

`config/packages/nucleo.yaml` — si no existe:

```bash
cat > "$PRODUCT/config/packages/nucleo.yaml" <<'EOF'
# Punto de extensión explícito. El producto requiere el kernel; no lo reimplementa.
services:
    Clarity\Nucleo\Capability\Gate:
        public: true
    Clarity\Nucleo\Content\Boundary:
        public: true
    Clarity\Nucleo\HoldPipe\Pipe:
        public: true
EOF
```

`tests/Nucleo/RequireKernelTest.php` — si no existe:

```bash
cat > "$PRODUCT/tests/Nucleo/RequireKernelTest.php" <<'EOF'
<?php

declare(strict_types=1);

namespace App\Tests\Nucleo;

use Clarity\Nucleo\Capability\Gate;
use Clarity\Nucleo\Content\Boundary;
use PHPUnit\Framework\TestCase;

final class RequireKernelTest extends TestCase
{
    public function test_untrusted_web_cannot_instruct(): void
    {
        $decision = (new Boundary())->admit('external_web', 'untrusted');
        self::assertFalse($decision->allowed);
        self::assertSame('DENIED', $decision->authorization);
        self::assertSame('untrusted-content', $decision->policy);
    }

    public function test_rental_supervisor_cannot_refund(): void
    {
        $decision = (new Gate())->decide('rental.supervisor', 'RefundPayment');
        self::assertFalse($decision->allowed);
        self::assertSame('DENIED', $decision->authorization);
    }

    public function test_product_src_does_not_absorb_kernel_contexts(): void
    {
        $root = dirname(__DIR__, 2).'/src';
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
        foreach ($it as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $src = file_get_contents($file->getPathname());
            self::assertNotFalse($src);
            self::assertStringNotContainsString(
                'namespace Clarity\\Nucleo\\Context',
                $src,
                $file->getFilename().' absorbe un contexto del kernel',
            );
        }
    }
}
EOF
```

`scripts/certify.sh` — si no existe:

```bash
cat > "$PRODUCT/scripts/certify.sh" <<'EOF'
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
EOF
chmod +x "$PRODUCT/scripts/certify.sh"
```

## 3. Composer — lock de frontera

```bash
cd "$PRODUCT"
composer update clarity/nucleo --no-interaction --prefer-dist
composer show clarity/nucleo
```

Esperado: `clarity/nucleo 0.2.0`. El lock entra en git. El kernel no.

## 4. Validación kernel (C1–C7)

```bash
mkdir -p "$EVIDENCE"
cd "$PRODUCT/packages/clarity-nucleo"
composer install --no-interaction --prefer-dist
composer test -- --log-junit "$EVIDENCE/kernel-junit.xml" --testdox-text "$EVIDENCE/kernel-testdox.txt"
```

| ID | Amenaza | Frontera | Prueba |
|---|---|---|---|
| C1 | Fusión de contextos | Domain | `test_a_context_does_not_import_another_context` |
| C2 | Infra en el dominio | Infrastructure | `test_domain_does_not_name_infrastructure` |
| C3 | Replay de cobro | Application | `test_happy_path_confirms_and_replay_keeps_the_same_id` |
| C4 | Timeout → confirmed | Evidence | `test_timeout_is_unknown_not_confirmed` |
| C5 | Agente comprometido | Capability | `test_compromised_agent_does_not_touch_the_domain` |
| C6 | Escalada de hijo | Policy | `test_subagent_is_blocked_at_delegation` |
| C7 | Indirect prompt injection | Trust / Content | `test_external_web_is_blocked_at_content_boundary` |

Cero fallos. Si uno falla: **NO-GO. No continuar.**

## 5. Validación producto (require, no absorción)

```bash
cd "$PRODUCT"
php bin/phpunit tests/Nucleo \
  --log-junit "$EVIDENCE/product-junit.xml" \
  --testdox-text "$EVIDENCE/product-testdox.txt"
```

| ID | Qué demuestra |
|---|---|
| P1 | `clarity/nucleo` requerido, no copiado a `App\` |
| P2 | contenido untrusted no instruye |
| P3 | `src/` no declara `Clarity\Nucleo\Context` |

## 6. Controles estáticos de no-absorción

```bash
cd "$PRODUCT"
! grep -R --include='*.php' -F 'namespace Clarity\Nucleo\Context' src
! grep -RIn --include='*.php' -E '\bStripe\b|\bPDO\b|\bHttpRequest\b' packages/clarity-nucleo/src \
  | grep -v PaymentStub
test -f config/packages/nucleo.yaml
test ! -f src/routes/amenazas.tsx
```

Atajo (pasos 4–7):

```bash
cd "$PRODUCT"
chmod +x scripts/certify.sh
EVIDENCE="$EVIDENCE" ./scripts/certify.sh
```

## 7. Pack de evidencia

```bash
cd "$PRODUCT"
mkdir -p "$EVIDENCE"
{
  echo "product: alquiler-equipos-sonido"
  echo "kernel: clarity/nucleo 0.2.0"
  echo "head_product: $(git rev-parse HEAD)"
  echo "head_kernel: $(git -C "$KERNEL" rev-parse HEAD)"
  echo "head_kernel_pkg: $(git log -1 --format=%H -- packages/clarity-nucleo 2>/dev/null || echo path-package)"
  echo "certified_at: $(date -u +%Y-%m-%dT%H:%M:%SZ)"
  echo "certifier: $USER"
  echo "verdict: PENDING"
} | tee "$EVIDENCE/manifest.txt"

git rev-parse HEAD > "$EVIDENCE/product.HEAD"
git -C "$KERNEL" rev-parse HEAD > "$EVIDENCE/kernel.HEAD"

( cd "$EVIDENCE" && shasum -a 256 \
    kernel-junit.xml kernel-testdox.txt \
    product-junit.xml product-testdox.txt \
    manifest.txt > SHA256SUMS )
```

`var/evidence/` no se commitea. El manifiesto firmado sí puede archivarse fuera del repo (custody). Log de Monolog no es este pack.

## 8. Certificación

Abrir `docs/CERTIFICATION.md` y `packages/clarity-nucleo/docs/CERTIFICATION.md`.
Leer testdox. El modelo no firma.

```bash
# Solo el arquitecto, después de C1–C7 ∧ P1–P3:
printf '\nverdict: GO\nsigned: %s\nsigned_at: %s\n' "$USER" "$(date -u +%Y-%m-%dT%H:%M:%SZ)" >> "$EVIDENCE/manifest.txt"
( cd "$EVIDENCE" && shasum -a 256 \
    kernel-junit.xml kernel-testdox.txt \
    product-junit.xml product-testdox.txt \
    manifest.txt > SHA256SUMS )
```

Commit del require (no del briefing):

```bash
cd "$PRODUCT"
# evidencia local fuera de git
grep -q '/var/evidence/' .gitignore || printf '\n/var/evidence/\n.phpunit.cache\n.phpunit.result.cache\n' >> .gitignore

git add composer.json composer.lock packages/clarity-nucleo \
  config/packages/nucleo.yaml tests/Nucleo docs scripts \
  .github/workflows/certify.yml .gitignore
git status
git diff --cached --stat
git commit -m "require clarity/nucleo 0.2.0 — content boundary, fail-closed gate"
git push origin main
```

GO = C1–C7 ∧ P1–P3 ∧ manifiesto firmado ∧ `src/` sin `Clarity\Nucleo\Context`.
NO-GO = cualquier rojo, evidencia ausente, o briefing mezclado en `src/` / `assets/`.

## Prohibido

- `cp` del briefing React a `public/` o `assets/`
- mover `Context\Catalog` a `src/Entity`
- `cat` del kernel dentro de `src/`
- `composer require stripe/*` dentro de `packages/clarity-nucleo`
- certificar con tests en rojo
- tratar `var/cache` o `.env` como evidencia
- mapear `Context\*` al catálogo de altavoces
- `OUTPUT → EXECUTE` (el camino legal es `VALIDATE → PARSE → AUTHORIZE → EXECUTE`)
