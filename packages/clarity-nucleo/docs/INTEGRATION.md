# Integración — protocolo

Orden fijo. No saltar un paso. Un paso en rojo detiene la certificación.

## 0. Precondiciones

- PHP ≥ 8.2, Composer 2, Git
- Tres árboles distintos. Nunca un merge de briefing + kernel + producto
- Working tree limpio en el producto

## 1. Kernel canónico

Fuente: `Neiland85/nucleo-php-roots` (`clarity/nucleo` 0.2.0).

El producto no clona el briefing React. Clona o requiere este paquete.

## 2. Requerir, no absorber

En el producto, el kernel vive en `packages/clarity-nucleo` (path repo) o como VCS require.

Prohibido:

- copiar clases a `src/`
- autoload `Clarity\\Nucleo\\` desde `src/`
- importar un contexto del kernel desde otro contexto
- mapear `Context\Catalog` al catálogo de altavoces

Permitido:

- `use Clarity\Nucleo\Content\Boundary`
- `use Clarity\Nucleo\Capability\Gate`
- implementar `PaymentPort` en un adapter del producto

## 3. Punto de extensión

Symfony registra Gate, Boundary y Pipe como servicios. El dominio de sonido habla puertos. No habla el SDK.

## 4. Validación

Kernel: `composer test` dentro del paquete.
Producto: tests que demuestran require (untrusted no instruye) y no-absorción (`src/` no declara `Clarity\Nucleo\Context`).

## 5. Evidencia

PHPUnit JUnit + testdox. Hash de `HEAD`. Fecha. Quién certifica.

## 6. Certificación

Ver `CERTIFICATION.md`. Sin evidencia no hay GO.
