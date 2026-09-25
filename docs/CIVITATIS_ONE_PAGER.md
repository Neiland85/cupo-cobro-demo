# Cupo & Cobro

## Consistencia operativa en flujos de reserva y pago

**Technical exercise · Architecture · Idempotency · Failure handling**  
**Neil Muñoz Lago — Systems Architect · Backend & Critical Systems**

---

## 1. The problem

In a reservation platform, payment is only one part of the transaction. Availability, reservation, payment provider, customer confirmation, settlement and payout can each fail independently, and information can arrive late or be replayed.

A representative failure is:

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
Capacity is released
        ↓
A late PSP confirmation arrives
```

The architectural question is not simply **“did the payment succeed?”**. It is:

> **What state is the system actually in, what can still be safely done, and who owns the resolution?**

---

## 2. The thesis

The exercise treats inconsistent states as first-class operational conditions rather than exceptions hidden inside application code.

The model is built around:

**Explicit state · idempotency · ownership · detection · resolution**

Evidence is added so the resulting operational decision can be traced.

---

## 3. UNKNOWN is a real state

A timeout is not equivalent to a rejection.

```text
OK       → confirmed
REJECT   → rejected
TIMEOUT  → UNKNOWN
```

The implementation represents this as `PAY_UNKNOWN`.

This matters because the PSP may later report a successful capture after the local timeout. Converting the timeout immediately into `REJECT` would erase information needed for reconciliation.

**Demonstrated:** timeout → `PAY_UNKNOWN`; later PSP information is handled explicitly.

---

## 4. Late PSP confirmation

If a payment confirmation arrives after the capacity hold has expired, the exercise does **not** silently recreate the expired reservation.

It detects:

`LATE_PSP_NO_CUPO`

and exposes the resolution path:

`REFUND_OVERSELL`

The operational invariant is:

> A late provider response must not reclaim capacity that the system has already released and potentially reassigned.

---

## 5. Idempotency

A payment operation can be retried or delivered more than once.

```text
First capture
    → PAY_OK
    → one ledger entry

Replay with same key
    → DUPLICATE_CAPTURE_SAME_KEY
    → NO-GO condition
```

A recorded verification run executed the replay scenario with **18 tests / 112 assertions**, including duplicate-capture behavior.

---

## 6. Refund is not payout

A refund and a payout are different operational decisions. The exercise models dependencies such as:

- `REFUND_NEEDS_PAYOUT_HOLD`
- `PAYOUT_ON_DISPUTE`
- `PAYOUT_WITHHELD`

A successful refund therefore does not automatically imply that funds are safe to release downstream.

---

## 7. Detection and ownership

The system names inconsistent states instead of leaving them as generic exceptions.

Examples:

- `PSP_OK_LEDGER_UNKNOWN`
- `LATE_PSP_NO_CUPO`
- `LEDGER_CAPTURED_PSP_MISSING`
- `PSP_CAPTURED_CUPO_FREE`
- `CUPO_HELD_PSP_REJECT`
- `DUPLICATE_CAPTURE_SAME_KEY`
- `PAYOUT_ON_DISPUTE`
- `CAPTURE_WITHOUT_FISCAL`

The distinction is between **detecting that something is wrong** and **having an operational model for what happens next**.

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
  idempotency · ownership · detection · evidence · trust boundary
```

The implementation keeps the domain kernel separated from demo adapters. Payment is represented through a `PaymentPort` boundary rather than coupling the core model directly to a provider.

The exercise also includes a trust/capability boundary so untrusted web content cannot directly issue privileged capabilities.

---

## 9. Evidence

Operational evidence is represented explicitly and hash-linked.

This is a technical traceability mechanism. It is **not** presented as legal or regulatory certification.

---

## 10. What is actually demonstrated

### IMPLEMENTED / DEMONSTRATED

- Explicit `PAY_UNKNOWN` state and timeout handling.
- Late PSP detection after capacity expiry.
- Explicit late-payment resolution path.
- Idempotency/replay behavior and duplicate capture detection.
- Refund/payout dependency handling.
- Dispute-related payout blocking.
- Detector codes and ownership.
- Trust/capability boundary.
- Hash-linked evidence records.
- PHPUnit behavioral tests and executable demo stories.

### DESIGN / EVOLUTION

- Real external PSP integration.
- Distributed multi-node coordination.
- Production persistence and failure recovery.
- Production observability and operational tooling.
- Load/performance validation.
- Security assurance and formal certification.
- Production deployment and infrastructure hardening.

The distinction is deliberate: the exercise demonstrates the **behavioral model and architecture**, not production readiness.

---

## 11. Why this matters for a reservation platform

```text
availability ↔ reservation ↔ payment ↔ provider ↔ settlement
```

The architecture focuses less on the happy path and more on the point where two systems disagree. That is where overbooking, duplicate charges, incorrect refunds, premature payouts and manual reconciliation can emerge.

The approach is to make those states **explicit, detectable, owned and resolvable**.

---

## 12. Repository and demo

**Source:** https://github.com/Neiland85/cupo-cobro-demo

The public repository contains the implementation, tests, demo scripts and supporting documentation.

A short live-demo video accompanies the repository separately and walks through the main inconsistent-state scenarios and their resolution paths.

---

## 13. Closing

> **A timeout should create a state to resolve, not a decision to hide.**

That principle is the center of the exercise.
