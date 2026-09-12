# Certificación del producto

Puerta fail-closed. Sin pack de evidencia no hay GO.

## C1–C7 (kernel)

Ver `packages/clarity-nucleo/docs/CERTIFICATION.md`.

## P1–P3 (producto)

| ID | Qué demuestra | Dónde |
|---|---|---|
| P1 | `clarity/nucleo` está requerido, no copiado a `App\` | `composer show` + `tests/Nucleo` |
| P2 | contenido untrusted no instruye | `test_untrusted_web_cannot_instruct` |
| P3 | `src/` no declara `Clarity\Nucleo\Context` | `test_product_src_does_not_absorb_kernel_contexts` |

## Veredicto

GO = C1–C7 ∧ P1–P3 ∧ manifiesto firmado.
NO-GO = cualquier rojo, o el briefing mezclado en `src/` / `assets/`.

Firmante: arquitecto. No el modelo.
