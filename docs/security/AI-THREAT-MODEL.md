# AI threat model

Regla: el LLM no es la frontera de seguridad.

Todo contenido (usuario, web, PDF, email, RAG, imagen, tool output) es datos
hasta que `Content\Boundary` demuestre que puede tratarse como instrucción.

Camino ilegal: contenido externo → LLM → tool.
Camino legal: untrusted input → content boundary → LLM propone → capability gate → adapter.

Owning code: `Clarity\Nucleo\Content\Boundary`, `Clarity\Nucleo\Capability\Gate`.
Prueba: C7, P2.
