# Flujo de idempotencia — cobro de marketplace

Banco de ensayo. No es la pasarela de producción.
Una key. Un acto. El segundo no es otro viajero.

Archivos: `src/Nucleo/Demo/SlotStore.php`, `MoneyLedger.php`, `Checkout.php`.
Puerta: `php bin/console nucleo:demo replay`

## El mismo acto

Tripleta: origen + operación + key.

La operación de cobro es `reserve`. La key es `k1`.
Si llega otra vez `k1`, no es otro viajero.

## Camino 1 — primer checkout

1. `hold(slot-a, k1)` → no hay hold vivo → `HOLD_OK` (TTL 900).
2. `openUnknown(k1)` → fila `PAY_UNKNOWN`.
3. PSP `ok` → `capture(k1)` → `captures=1` → `PAY_OK` / `captured`.

Un asiento. Un cobro.

## Camino 2 — replay (doble clic, retry, webhook)

Hold todavía vivo, misma key:

1. `hold` encuentra `k1` con `expires > now` → `HOLD_REPLAY`. No consume otra plaza.
2. `openUnknown(k1)` → la fila existe → no-op.
3. `authorize` otra vez → `capture` → `captures=2` y status ya era `captured`.
4. `DUPLICATE_CAPTURE_SAME_KEY` + `phaseNoGo`.

El cupo hizo idempotencia de verdad.
El libro no devuelve el primer resultado en silencio: cuenta el segundo capture y lo marca incidente.

```
1. PAY_OK captures=1
2. DUPLICATE_CAPTURE_SAME_KEY captures=2 nogo=YES
```

Criterio de detección, no caché de respuesta.

## Camino 3 — refund

- ya `refunded` → `REFUND_REPLAY` (corta; no segundo void)
- `unknown` → `REFUND_UNKNOWN_BLOCKED`
- payout en fichero del martes → `REFUND_NEEDS_PAYOUT_HOLD`
- si no → suelta cupo + `markRefunded`

Aquí sí hay replay de manual.

## Lo que no es replay

| Caso | Keys | Qué es |
|---|---|---|
| A timeout, B compra | A ≠ B | conflicto de cupo / oversell |
| Retry de timeout con otra key | k1 luego k2 | segundo acto; el código no lo impide |
| Hold de A caducado y A vuelve | misma key, TTL muerto | hold nuevo, no HOLD_REPLAY |

## Mesa

Dos capas. El cupo reconoce la key y no vende el asiento otra vez.
El libro, si alguien vuelve a llamar capture, no finge que no pasó: lo nombra y para la fase.

En Adyen el replay de red devolvería el primer JSON.
Aquí el segundo capture es un incidente a propósito: alguien saltó la capa de key.
