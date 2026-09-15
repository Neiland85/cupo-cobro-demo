# Cupo & Cobro

## Consistencia operativa en flujos de reserva y pago

Technical exercise focused on **idempotency, explicit payment uncertainty, capacity ownership and operational resolution of inconsistent states**.

The core question is simple:

> What should a reservation platform do when the payment provider and the internal ledger no longer agree?

This repository is a **demonstrated technical exercise**, not a production payment system.

---

## What the exercise demonstrates

### 1. UNKNOWN is an explicit state

A payment timeout is not treated as an automatic rejection or approval.

```text
OK       → confirmed
REJECT   → rejected
TIMEOUT  → UNKNOWN
```

`PAY_UNKNOWN` keeps the ambiguity explicit so later PSP information can be reconciled without silently inventing a state.

### 2. Late PSP responses cannot reclaim released capacity

A payment confirmation may arrive after the reservation hold has expired.

The system detects this condition as `LATE_PSP_NO_CUPO` instead of silently restoring the expired hold.

The demonstrated resolution path is `REFUND_OVERSELL`.

### 3. Idempotency is enforced as an operational invariant

A replay using the same `Idempotency-Key` must not create a second financial ledger entry.

The demo exposes `DUPLICATE_CAPTURE_SAME_KEY` and marks the resulting flow as a no-go condition.

### 4. Refund and payout are separate decisions

A refund does not automatically imply that a payout is safe.

The exercise explicitly models conditions such as:

- `REFUND_NEEDS_PAYOUT_HOLD`
- `PAYOUT_ON_DISPUTE`
- `PAYOUT_WITHHELD`

### 5. Detection is part of the architecture

The system contains explicit detector codes for mismatches such as:

- `PSP_OK_LEDGER_UNKNOWN`
- `LATE_PSP_NO_CUPO`
- `LEDGER_CAPTURED_PSP_MISSING`
- `PSP_CAPTURED_CUPO_FREE`
- `CUPO_HELD_PSP_REJECT`
- `DUPLICATE_CAPTURE_SAME_KEY`
- `PAYOUT_ON_DISPUTE`
- `CAPTURE_WITHOUT_FISCAL`

The purpose is operational: a mismatch becomes a named condition that can be owned, investigated and resolved.

---

## Architecture at a glance

```text
Availability
     ↓
   Hold
     ↓
  Payment  ←→  PSP
     ↓
 Confirmation
     ↓
Reservation
     ↓
 Settlement
     ↓
  Payout

Cross-cutting:
  idempotency · ownership · detection · evidence · trust boundary
```

The implementation separates the domain kernel from the demo adapters and uses a `PaymentPort` abstraction for the payment provider boundary.

Evidence records are hash-linked so that operational events can be traced without making the evidence mechanism part of the payment provider itself.

---

## Demonstrated scenarios

| Scenario | Demonstrated behavior |
|---|---|
| PSP timeout | `PAY_UNKNOWN`; no implicit success/rejection |
| Late PSP after hold expiry | `LATE_PSP_NO_CUPO` |
| Valid late-payment resolution | `REFUND_OVERSELL` |
| Same idempotency key replay | `DUPLICATE_CAPTURE_SAME_KEY`; no second ledger entry |
| Refund with unresolved payout dependency | `REFUND_NEEDS_PAYOUT_HOLD` |
| Disputed payment | `PAYOUT_ON_DISPUTE` / `PAYOUT_WITHHELD` |
| Untrusted capability request | blocked by trust/capability boundary |

---

## Verification

The repository contains PHPUnit tests and executable demo stories for the core scenarios.

One recorded verification run executed:

```text
18 tests
112 assertions
OK
```

The repository also contains the documented certification gate in `scripts/certify.sh`. That gate should be treated as **documentation of the verification procedure**, not as a claim that every certification step has been executed in every environment.

---

## Important boundaries

This repository does **not** claim to demonstrate:

- a real external PSP integration;
- production-grade distributed locking or multi-node coordination;
- production database durability under failure;
- certification, regulatory compliance or legal validity;
- load/performance benchmarks;
- production security assurance;
- a complete production deployment.

The payment provider in the exercise is represented by demo/in-memory components. The value of the exercise is the **state model, invariants, failure semantics and operational resolution model**.

---

## Technical context

- PHP
- Symfony
- PHPUnit
- PostgreSQL/Docker configuration
- Domain-oriented kernel (`clarity/nucleo`)
- Payment provider abstraction (`PaymentPort`)
- Explicit state and detector codes
- Hash-linked evidence records

The exercise deliberately keeps the demonstrated core small enough to inspect and verify.

---

## Documentation

- [`docs/FLUJO_IDEMPOTENCIA.md`](docs/FLUJO_IDEMPOTENCIA.md) — replay/idempotency flow.
- `docs/` — additional architecture and scenario documentation.
- `tests/` — executable behavioral evidence.
- `scripts/` — demo and verification entry points.

---

## Demo

A short live-demo video accompanies this repository and walks through the main inconsistent-state scenarios and their resolution paths.

---

## Author

**Neil Muñoz Lago**  
Systems Architect · Backend & Critical Systems  
Clarity Structures Digital S.L.

GitHub: https://github.com/Neiland85
