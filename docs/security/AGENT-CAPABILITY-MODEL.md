# Agent capability model

Los agentes son operadores de capacidades, no propietarios de autoridad.

A4/A5 (pago, credenciales) no dependen del razonamiento del modelo.
ChildCapability ⊈ ParentAuthority.
`CapturePayment` y `RefundPayment` solo `payment.agent`.
`RotateCredentials` nunca.

Owning code: `Clarity\Nucleo\Capability\Gate`.
Pruebas: C5, C6, P2.
