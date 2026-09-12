# Untrusted content

`retrieved ≠ trusted`.

```
{ content, source, trust, provenance, retrieved_at, hash }
```

Memoria persistente distingue OBSERVATION, FACT, USER-PROVIDED,
SYSTEM-CONFIG, MODEL-INFERENCE, UNTRUSTED-CONTENT.

Prohibido: documento externo → memoria de confianza, sin transición explícita.

Owning code: `Boundary::admit($provenance, $trust)`.
Solo `provenance=operator` y `trust=trusted` instruyen.
