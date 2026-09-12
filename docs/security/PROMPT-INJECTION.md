# Prompt injection

Directa: el usuario altera el prompt. Frontera: Input.
Indirecta: web/PDF/email/RAG secuestra el agente. Frontera: Trust. Crítica.

Este producto trata la inyección indirecta como cambio de arquitectura, no como
filtro de strings. El usuario puede pedir «analiza esta web»; el atacante
controla la página; el modelo puede proponer `RefundPayment`; el content
boundary niega; el dominio no nace.

Prueba: `test_external_web_is_blocked_at_content_boundary`.
