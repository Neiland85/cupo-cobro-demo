# NÚCLEO — kernel PHP (`clarity/nucleo` 0.2.0)

Paquete de ejecución. No es el briefing. No es `alquiler-equipos-sonido`.

```
UNTRUSTED INPUT → CONTENT BOUNDARY → LLM propone
  → CAPABILITY GATE → POLICY → AUTHORIZATION
  → ADAPTER → VERIFICATION → RECONCILIATION → EVIDENCE
```

El modelo interpreta datos. No adquiere autoridad por interpretarlos.

## Tres artefactos

| Artefacto | Rol | Qué no es |
|---|---|---|
| Briefing NÚCLEO | Significado, mapa, laboratorio | Código de dominio PHP |
| `clarity/nucleo` | Contratos ejecutables y tests de frontera | Symfony, Stripe, SQL |
| alquiler-equipos-sonido | Producto de alquiler de sonido | Turismo, ni este kernel |

El producto **requiere** este paquete. No lo absorbe en `src/`.

`Context\*` aquí es hipótesis de corte (invariantes). No es el catálogo de altavoces.

## Certificar

```bash
composer install
composer test
```

Falla el día que:

- un contexto importe a otro
- el dominio nombre Stripe, PDO o HttpRequest
- contenido `external_web` atraviese como instrucción
- un subagente escale por encima del padre
- un timeout se confirme «por si acaso»

Versión: `0.2.0` — content boundary + `RefundPayment` fail-closed.
