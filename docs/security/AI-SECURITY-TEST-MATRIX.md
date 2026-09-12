# AI security test matrix

Cada amenaza termina en prueba, no en un párrafo.

```
Attack → expected behavior → boundary crossed?
      → capability granted? → action executed?
      → data leaked? → evidence generated?
```

| Amenaza | Esperado | Prueba |
|---|---|---|
| Indirect injection | block en content boundary, sin reserva | C7 |
| Tool injection / refund | DENIED si no es payment.agent | C5, P2 |
| Subagente | block en delegación | C6 |
| Timeout PSP | unknown, no confirmed | C4 |
| Replay | misma fila | C3 |
| Output → execute | no hay camino | C2, C7 |

Certificación: `docs/CERTIFICATION.md`.
