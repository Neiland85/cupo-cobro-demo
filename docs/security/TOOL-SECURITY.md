# Tool security

`LLM → function` trata al modelo como autoridad. Prohibido.

```
LLM → proposed action → policy → authorization → capability check → execution
```

«refund customer» ≠ `RefundAuthorized = true`.
El puerto de dinero es `PaymentPort`. El SDK no entra en el dominio.

Pruebas: C2, C5, C7.
