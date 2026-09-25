# cupo-cobro-demo

Banco de ensayo de **cupo** y **cobro**. No es producción.

El catálogo es alquiler de equipos de sonido.
Lo que se prueba no es el catálogo: un asiento no se vende dos veces
y un cobro con la misma key no es otro viajero.

## Qué invariantes se ensayan

- **Cupo:** `hold(slot, key)` reserva una plaza con TTL (900s). Misma key viva → `HOLD_REPLAY`, no consume otra plaza.
- **Cobro:** primer checkout abre `PAY_UNKNOWN` y, si el PSP responde ok, `capture` → `PAY_OK` (`captures=1`).
- **Replay:** segundo capture con la misma key no se disfraza de éxito. Cuenta `captures=2`, emite `DUPLICATE_CAPTURE_SAME_KEY` y `phaseNoGo`.
- **Refund:** replay de reembolso ya hecho → `REFUND_REPLAY`. Payout del martes en fichero → `REFUND_NEEDS_PAYOUT_HOLD`. Si procede, suelta cupo.

No es una caché del primer JSON del PSP.
El segundo capture es un incidente a propósito: alguien saltó la capa de key.

## Qué no es replay

| Caso | Keys | Qué es |
| --- | --- | --- |
| A hace timeout, B compra | A ≠ B | conflicto de cupo / oversell |
| Retry de timeout con otra key | k1 luego k2 | segundo acto; el código no lo impide |
| Hold caducado y A vuelve | misma key, TTL muerto | hold nuevo, no `HOLD_REPLAY` |

## Cómo verlo

git clone https://github.com/Neiland85/cupo-cobro-demo.git
cd cupo-cobro-demo
composer install
php bin/console nucleo:demo replay
textCriterio esperado (el que cuenta):

PAY_OK captures=1
DUPLICATE_CAPTURE_SAME_KEY captures=2 nogo=YES

[OK] una key, un asiento, segundo acto = NO-GO
textPHP 8.5 + Symfony 7.1 pueden soltar un muro de `Deprecated: SplObjectStorage`
y un aviso de `debug.xml` en el `post-install`.
Eso no es el ensayo. El criterio es el bloque `[OK]`.

App web (catálogo / reservas / admin):
.env.local → DATABASE_URL
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
symfony server:start
text## Dónde está el mecanismo

| Pieza | Sitio |
| --- | --- |
| Cupo in-memory | `src/Nucleo/Demo/SlotStore.php` |
| Cobro / adapter | `src/Nucleo/PaymentAdapter.php` |
| Replay narrado | `scripts/demo_replay_story.php` |
| Flujo de keys | `docs/FLUJO_IDEMPOTENCIA.md` |
| Kernel (gate, pipe) | `packages/clarity-nucleo` |

El producto en `src/` es sonido, no turismo.
El ensayo es el mismo problema que cualquier checkout con aforo:
dos clics, webhook duplicado, reembolso después de un fichero de payout.

## Límites

- No es la pasarela de producción.
- El ledger de demo no sustituye Doctrine ni el `HoldPipe` de producción.
