# cupo-cobro-demo

Banco de ensayo de **cupo** y **cobro**. No es producción.

El catálogo es alquiler de equipos de sonido. Lo que se prueba no es el catálogo:
es que un asiento no se venda dos veces y que un cobro con la misma key no sea
otro viajero.

## Qué invariantes se ensayan

- **Cupo:** `hold(slot, key)` reserva una plaza con TTL (900s). Misma key viva → `HOLD_REPLAY`, no consume otra plaza.
- **Cobro:** primer checkout abre `PAY_UNKNOWN` y, si el PSP responde ok, `capture` → `PAY_OK` (`captures=1`).
- **Replay:** segundo capture con la misma key no se disfraza de éxito. Cuenta `captures=2`, emite `DUPLICATE_CAPTURE_SAME_KEY` y `phaseNoGo`.
- **Refund:** replay de reembolso ya hecho → `REFUND_REPLAY`. Payout del martes en fichero → `REFUND_NEEDS_PAYOUT_HOLD`. Si procede, suelta cupo.

No es una caché del primer JSON del PSP. El segundo capture es un incidente a propósito: alguien saltó la capa de key.

## Qué no es replay

| Caso | Keys | Qué es |
| --- | --- | --- |
| A hace timeout, B compra | A ≠ B | conflicto de cupo / oversell |
| Retry de timeout con otra key | k1 luego k2 | segundo acto; el código no lo impide |
| Hold caducado y A vuelve | misma key, TTL muerto | hold nuevo, no `HOLD_REPLAY` |

## Cómo verlo

```bash
git clone https://github.com/Neiland85/cupo-cobro-demo.git
cd cupo-cobro-demo
composer install
php bin/console nucleo:demo replay
Criterio esperado (el que cuenta):
text1. PAY_OK captures=1
2. DUPLICATE_CAPTURE_SAME_KEY captures=2 nogo=YES

 [OK] una key, un asiento, segundo acto = NO-GO
PHP 8.5 + Symfony 7.1 pueden soltar un muro de Deprecated: SplObjectStorage y un aviso de debug.xml en el post-install. Eso no es el ensayo. El criterio es el bloque [OK] de arriba.
App web (catálogo / reservas / admin), si quieres el envoltorio de producto:
Bash# .env.local → DATABASE_URL
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
symfony server:start
Dónde está el mecanismo





























PiezaSitioCupo in-memorysrc/Nucleo/Demo/SlotStore.phpCobro / adaptersrc/Nucleo/PaymentAdapter.phpReplay narradoscripts/demo_replay_story.phpFlujo de keysdocs/FLUJO_IDEMPOTENCIA.mdKernel (gate, pipe)packages/clarity-nucleo
El producto en src/ es sonido, no turismo. El ensayo es el mismo tipo de problema que cualquier checkout con aforo: dos clics, webhook duplicado, reembolso después de un fichero de payout.
Límites

No es la pasarela de producción.
El ledger de demo no sustituye Doctrine ni el HoldPipe de producción.
