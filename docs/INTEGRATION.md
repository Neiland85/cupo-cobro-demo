# Protocolo de integración NÚCLEO → alquiler-equipos-sonido

Orden fijo. Un rojo detiene. El briefing no entra en `src/`.

Sustituye `PRODUCT` y `KERNEL` si tus rutas difieren.

```bash
export PRODUCT="$HOME/Projects/GITHUB-PROJECTS-2026/GOVERNANZA_ORGANIZATIVA/Bootstrap/alquiler-equipos-sonido"
export KERNEL="$HOME/Projects/GITHUB-PROJECTS-2026/GOVERNANZA_ORGANIZATIVA/Bootstrap/nucleo-php-roots"
export EVIDENCE="$PRODUCT/var/evidence/$(date -u +%Y%m%dT%H%M%SZ)"
```

## 0. Precondiciones

```bash
php -v | head -1          # ≥ 8.2
composer -V | head -1     # 2.x
git -C "$PRODUCT" status --porcelain   # vacío, o stash
git -C "$PRODUCT" rev-parse --abbrev-ref HEAD   # main
```

## 1. Kernel canónico (no el briefing)

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
```

## 2. Producto: requerir el paquete (path repo)

El kernel vive en `packages/clarity-nucleo`. Composer lo requiere. `src/` no lo absorbe.

```bash
git -C "$PRODUCT" pull --ff-only origin main
test -f "$PRODUCT/packages/clarity-nucleo/composer.json"
grep -q 'clarity/nucleo' "$PRODUCT/composer.json"
```

Si el pull aún no trae el paquete (working tree anterior a 0.2.0):

```bash
mkdir -p "$PRODUCT/packages"
rsync -a --delete \
  --exclude vendor --exclude .git --exclude .phpunit.cache \
  "$KERNEL"/ "$PRODUCT/packages/clarity-nucleo/"
```

## 3. Composer — lock de frontera

```bash
cd "$PRODUCT"
composer update clarity/nucleo --no-interaction --prefer-dist
composer show clarity/nucleo
```

Esperado: `clarity/nucleo 0.2.0`.

## 4. Validación kernel (C1–C7)

```bash
cd "$PRODUCT/packages/clarity-nucleo"
composer install --no-interaction
mkdir -p "$EVIDENCE"
composer test -- --log-junit "$EVIDENCE/kernel-junit.xml" --testdox-text "$EVIDENCE/kernel-testdox.txt"
```

Cero fallos. Si uno falla: NO-GO. No continuar.

## 5. Validación producto (require, no absorción)

```bash
cd "$PRODUCT"
php bin/phpunit tests/Nucleo \
  --log-junit "$EVIDENCE/product-junit.xml" \
  --testdox-text "$EVIDENCE/product-testdox.txt"
```

## 6. Controles estáticos de no-absorción

```bash
cd "$PRODUCT"
! grep -R "namespace Clarity\\\\Nucleo\\\\Context" src --include='*.php'
! grep -Rin --include='*.php' -E '\bStripe\b|\bPDO\b|\bHttpRequest\b' packages/clarity-nucleo/src \
  | grep -v PaymentStub
test -f config/packages/nucleo.yaml
```

## 7. Pack de evidencia

```bash
cd "$PRODUCT"
{
  echo "product: alquiler-equipos-sonido"
  echo "kernel: clarity/nucleo 0.2.0"
  echo "head_product: $(git rev-parse HEAD)"
  echo "head_kernel_pkg: $(git log -1 --format=%H -- packages/clarity-nucleo 2>/dev/null || echo path-package)"
  echo "certified_at: $(date -u +%Y-%m-%dT%H:%M:%SZ)"
  echo "certifier: $USER"
  echo "verdict: PENDING"
} > "$EVIDENCE/manifest.txt"

shasum -a 256 \
  "$EVIDENCE/kernel-junit.xml" \
  "$EVIDENCE/kernel-testdox.txt" \
  "$EVIDENCE/product-junit.xml" \
  "$EVIDENCE/product-testdox.txt" \
  "$EVIDENCE/manifest.txt" \
  > "$EVIDENCE/SHA256SUMS"

git -C "$PRODUCT" rev-parse HEAD > "$EVIDENCE/product.HEAD"
```

## 8. Certificación

Abrir `docs/CERTIFICATION.md`. Firmar GO / NO-GO en el manifiesto.

```bash
# Solo el arquitecto, después de leer C1–C7:
printf '\nverdict: GO\nsigned: %s\n' "$USER" >> "$EVIDENCE/manifest.txt"
```

Commit del require (no del briefing):

```bash
cd "$PRODUCT"
git add composer.json composer.lock packages/clarity-nucleo \
  config/packages/nucleo.yaml tests/Nucleo docs \
  .github/workflows/certify.yml
git status
git commit -m "require clarity/nucleo 0.2.0 — content boundary, fail-closed gate"
git push origin main
```

## Prohibido

- `cp` del briefing React a `public/` o `assets/`
- mover `Context\Catalog` a `src/Entity`
- `composer require` de Stripe dentro del kernel
- certificar con tests en rojo
- tratar `var/cache` o `.env` como evidencia
