# Threat model v0.1 — reglas que el kernel ejecuta

1. Todo contenido recibido por un sistema AI es datos hasta que una frontera de confianza demuestre que puede tratarse como instrucción.
2. El LLM no es la frontera de seguridad.
3. El modelo puede interpretar datos, proponer acciones y producir artefactos; no adquiere autoridad por interpretar datos.
4. retrieved ≠ trusted. La metadata de provenance viaja con el contenido.
5. Nunca `OUTPUT → EXECUTE`. Siempre `VALIDATE → PARSE → AUTHORIZE → EXECUTE`.
6. ChildCapability ⊈ ParentAuthority.
7. Log no es evidencia.

Camino ilegal: `contenido externo → LLM → tool`.
Camino legal: `untrusted input → content boundary → LLM propone → capability gate → adapter`.

La matriz completa y el laboratorio viven en el briefing. Este paquete es la prueba que falla el día que el camino ilegal se implemente.
