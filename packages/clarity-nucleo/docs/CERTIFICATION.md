# Certificación — puerta fail-closed

GO solo si las siete pruebas son verdes y el pack de evidencia está íntegro.

## Cadena

```
amenaza → frontera → control → prueba → evidencia
```

| ID | Amenaza | Frontera | Control | Prueba |
|---|---|---|---|---|
| C1 | Fusión de contextos | Domain | ningún `use` cruzado | `test_a_context_does_not_import_another_context` |
| C2 | Infra en el dominio | Infrastructure | no Stripe/PDO/HttpRequest | `test_domain_does_not_name_infrastructure` |
| C3 | Replay de cobro | Application | Idempotency-Key | `test_happy_path_confirms_and_replay_keeps_the_same_id` |
| C4 | Timeout → confirmed | Evidence | unknown ≠ confirmed | `test_timeout_is_unknown_not_confirmed` |
| C5 | Agente comprometido | Capability | allowlist fail-closed | `test_compromised_agent_does_not_touch_the_domain` |
| C6 | Escalada de hijo | Policy | child ⊆ parent | `test_subagent_is_blocked_at_delegation` |
| C7 | Indirect prompt injection | Trust / Content | retrieved ≠ trusted | `test_external_web_is_blocked_at_content_boundary` |

## Veredicto

- **GO** — C1–C7 verdes, cadena de evidencia íntegra, `src/` del producto no absorbe el kernel
- **NO-GO** — cualquier rojo, o evidencia ausente, o `Context\*` copiado a `App\`

El LLM no firma. El arquitecto firma. El repositorio prueba.
