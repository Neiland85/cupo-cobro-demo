<?php

declare(strict_types=1);

namespace Clarity\Nucleo\Capability;

/**
 * Fail-closed. Un agente no obtiene privilegios por haber recibido una instrucción.
 */
final class Gate
{
    public function decide(string $agent, string $capability, ?string $parentCapability = null): Decision
    {
        if ($parentCapability !== null && $capability !== $parentCapability) {
            return Decision::deny('child-subseteq-parent');
        }

        if ($capability === 'RotateCredentials') {
            return Decision::deny('capability-allowlist');
        }

        if (($capability === 'CapturePayment' || $capability === 'RefundPayment')
            && $agent !== 'payment.agent') {
            return Decision::deny('capability-allowlist');
        }

        return Decision::allow('capability-allowlist');
    }
}
