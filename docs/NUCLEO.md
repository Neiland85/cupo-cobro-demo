# NÚCLEO en este producto

Este repositorio es el producto de alquiler de equipos de sonido.

No es el briefing. No es el kernel. El kernel está en `packages/clarity-nucleo`
(`clarity/nucleo` 0.2.0) y se **requiere**.

| Capa | Dónde | Invariante |
|---|---|---|
| Significado | briefing NÚCLEO | hipótesis, laboratorio |
| Ejecución | `packages/clarity-nucleo` | Gate, Boundary, Pipe, Evidence |
| Producto | `src/` | sonido, no turismo |

`Clarity\Nucleo\Context\*` son invariantes de corte. No son entidades Doctrine
del catálogo de altavoces. El bounded context de este producto se nombra cuando
exista inventario real, no copiando `Catalog`.

Punto de extensión: `config/packages/nucleo.yaml`.
