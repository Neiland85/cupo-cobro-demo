# Cupo & Cobro

## Consistencia operativa en flujos de reserva y pago

**Technical exercise · Architecture · Idempotency · Failure handling**

**Neil Muñoz Lago — Systems Architect · Backend & Critical Systems**

---

## 1. The problem

In a reservation platform, payment is only one part of the transaction.

A typical flow connects availability, reservation, payment provider, customer confirmation, settlement and payout. Each boundary can fail independently, and information can arrive late or be replayed.

A representative failure looks like this:

```text
Customer attempts reservation
        ↓
Capacity is held
        ↓
PSP does not answer in time
        ↓
Payment becomes ambiguous
        ↓
Hold expires
        ↓
Capacity becomes available again
        ↓
A late PSP confirmation arrives
```

The architectural question is not simply **“did the payment succeed?”**.

It is:

> **What state is the system actually in, what can still be safely done, and who owns the resolution?**

---

## 2. The thesis

The exercise treats inconsistent states as first-class operational conditions rather than exceptional branches hidden inside application code.

The model is built around five ideas:

**Explicit state · idempotency · ownership · detection · resolution**

Evidence is added so that the resulting operational decision can be traced.

---

## 3. UNKNOWN is a real state

A timeout is not equivalent to a rejection.

```text
OK       → confirmed
REJECT   → rejected
TIMEOUT  → UNKNOWN
```

The implementation represents this as `PAY_UNKNOWN`.

That matters because the PSP may later report a successful capture after the local timeout. Converting the timeout immediately into `REJECT` would erase information that the system may subsequently need to reconcile.

**Demonstrated:** timeout → `PAY_UNKNOWN`; later PSP information is handled explicitly.

---

## 4. Late PSP confirmation

The critical case is a payment confirmation arriving after the capacity hold has expired.

The exercise does **not** silently recreate the expired reservation.

Instead it detects:

`LATE_PSP_NO_CUPO`

and exposes an explicit resolution path:

`REFUND_OVERSELL`

The important invariant is operational:

> A late provider response must not be allowed to reclaim capacity that the system has already released and potentially reassigned.

---

## 5. Idempotency

A payment operation can be retried or delivered more than once.

The exercise uses an idempotency key as part of the payment invariant:

```text
First capture
    → PAY_OK
    → one ledger entry

Replay with same key
    → DUPLICATE_CAPTURE_SAME_KEY
    → no second financial decision
    → NO-GO condition
```

A recorded verification run executed the replay scenario with **18 tests / 112 assertions**, including the duplicate-capture behavior.

---

## 6. Refund is not payout

A refund and a payout are different operational decisions.

The exercise therefore models dependencies such as:

- `REFUND_NEEDS_PAYOUT_HOLD`
- `PAYOUT_ON_DISPUTE`
- `PAYOUT_WITHHELD`

This prevents a successful refund from being interpreted automatically as permission to release funds downstream.

---

## 7. Detection and ownership

The system names inconsistent states rather than leaving them as generic exceptions.

Examples include:

- `PSP_OK_LEDGER_UNKNOWN`
- `LATE_PSP_NO_CUPO`
- `LEDGER_CAPTURED_PSP_MISSING`
- `PSP_CAPTURED_CUPO_FREE`
- `CUPO_HELD_PSP_REJECT`
- `DUPLICATE_CAPTURE_SAME_KEY`
- `PAYOUT_ON_DISPUTE`
- `CAPTURE_WITHOUT_FISCAL`

These conditions can then be associated with an owner and a resolution path.

This is the distinction between **detecting that something is wrong** and **having an operational model for what happens next**.

---

## 8. Architecture

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
  idempotency
  ownership
  detection
  evidence
  trust boundary
```

The implementation keeps the domain kernel separated from the demo adapters. Payment is represented through a `PaymentPort` boundary rather than coupling the core model directly to a specific provider.

The exercise also includes a trust/capability boundary so that untrusted web content cannot directly issue privileged capabilities.

---

## 9. Evidence

Operational evidence is represented explicitly and hash-linked.

The purpose is not to claim legal or regulatory validity. It is to demonstrate a technical mechanism for preserving traceability between an operational event and its recorded evidence.

---

## 10. What is actually demonstrated

**IMPLEMENTED / DEMONSTRATED**

- Explicit `PAY_UNKNOWN` state.
- Timeout handling.
- Late PSP detection after capacity expiry.
- Explicit late-payment resolution path.
- Idempotency/replay behavior.
- Duplicate capture detection.
- Refund/payout dependency handling.
- Dispute-related payout blocking.
- Detector codes and ownership.
- Trust/capability boundary.
- Hash-linked evidence records.
- PHPUnit behavioral tests and executable demo stories.

**DESIGN / EVOLUTION**

- Real external PSP integration.
- Distributed multi-node coordination.
- Production persistence and failure recovery.
- Production observability and operational tooling.
- Load/performance validation.
- Security assurance and formal certification.
- Production deployment and infrastructure hardening.

The distinction is deliberate: the exercise demonstrates the **behavioral model and architecture**, not a claim of production readiness.

---

## 11. Why this matters for a reservation platform

The same consistency problem appears whenever multiple systems own different parts of a transaction:

```text
availability ↔ reservation ↔ payment ↔ provider ↔ settlement
```

The architecture therefore focuses less on the happy path and more on the point where two systems disagree.

That is where overbooking, duplicate charges, incorrect refunds, premature payouts and manual reconciliation can emerge.

The exercise's approach is to make those states **explicit, detectable, owned and resolvable**.

---

## 12. Repository

**Source:** https://github.com/Neiland85/cupo-cobro-demo

**Default branch:** `main`

The repository is public and contains the implementation, tests, demo scripts and supporting documentation.

A short live-demo video accompanies the repository separately.

---

## 13. Closing

> **A timeout should create a state to resolve, not a decision to hide.**

That principle is the center of the exercise.
