# Certificación del producto

Puerta fail-closed. Sin pack de evidencia no hay GO.

Cadena:

```
amenaza → frontera → control → prueba → evidencia → GO/NO-GO
```

Ejecutar `./scripts/certify.sh` (pasos 4–7 de `docs/INTEGRATION.md`). Un rojo detiene.

## C1–C7 (kernel)

Ver `packages/clarity-nucleo/docs/CERTIFICATION.md`.

| ID | Amenaza | Frontera | Control | Prueba |
|---|---|---|---|---|
| C1 | Fusión de contextos | Domain | ningún `use` cruzado | `test_a_context_does_not_import_another_context` |
| C2 | Infra en el dominio | Infrastructure | no Stripe/PDO/HttpRequest | `test_domain_does_not_name_infrastructure` |
| C3 | Replay de cobro | Application | Idempotency-Key | `test_happy_path_confirms_and_replay_keeps_the_same_id` |
| C4 | Timeout → confirmed | Evidence | unknown ≠ confirmed | `test_timeout_is_unknown_not_confirmed` |
| C5 | Agente comprometido | Capability | allowlist fail-closed | `test_compromised_agent_does_not_touch_the_domain` |
| C6 | Escalada de hijo | Policy | child ⊆ parent | `test_subagent_is_blocked_at_delegation` |
| C7 | Indirect prompt injection | Trust / Content | retrieved ≠ trusted | `test_external_web_is_blocked_at_content_boundary` |

## P1–P3 (producto)

| ID | Qué demuestra | Dónde |
|---|---|---|
| P1 | `clarity/nucleo` está requerido, no copiado a `App\` | `composer show` + `tests/Nucleo` |
| P2 | contenido untrusted no instruye | `test_untrusted_web_cannot_instruct` |
| P3 | `src/` no declara `Clarity\Nucleo\Context` | `test_product_src_does_not_absorb_kernel_contexts` |

## Veredicto

GO = C1–C7 ∧ P1–P3 ∧ manifiesto firmado.
NO-GO = cualquier rojo, o el briefing mezclado en `src/` / `assets/`.

Firmante: arquitecto. No el modelo.
El repositorio prueba. El LLM no certifica.
