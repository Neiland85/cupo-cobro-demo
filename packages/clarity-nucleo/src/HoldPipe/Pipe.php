<?php

declare(strict_types=1);

namespace Clarity\Nucleo\HoldPipe;

use Clarity\Nucleo\Capability\Gate;
use Clarity\Nucleo\Content\Boundary;
use Clarity\Nucleo\Evidence\Hash;
use Clarity\Nucleo\Evidence\Record;

final class Pipe
{
    /** @var array<string, Reservation> */
    private array $keys = [];

    private int $seq = 1;

    public function __construct(
        private readonly Gate $gate = new Gate(),
        private readonly Boundary $content = new Boundary(),
    ) {
    }

    public function reset(): void
    {
        $this->keys = [];
        $this->seq = 1;
    }

    /**
     * @return array{
     *   reservation: ?Reservation,
     *   steps: list<array{id:string,label:string,verdict:string,detail:string}>,
     *   evidence: Record,
     *   blocked: bool,
     *   message: string
     * }
     */
    public function run(
        string $kind,
        string $agent,
        string $capability,
        string $key,
        string $date,
        PaymentPort $payment,
        ?string $parentCapability = null,
        ?string $provenance = null,
        string $trust = 'trusted',
    ): array {
        $at = time();
        $steps = [];

        $steps[] = [
            'id' => 'intent',
            'label' => 'Intención',
            'verdict' => 'pass',
            'detail' => $provenance === null
                ? $agent.' propone '.$capability
                : $agent.' analiza '.$provenance.' · el modelo acaba proponiendo '.$capability,
        ];

        if ($provenance !== null) {
            $steps[] = [
                'id' => 'content',
                'label' => 'Contenido',
                'verdict' => 'warn',
                'detail' => $provenance.' · instrucción oculta «'.$capability.'». El usuario no atacó al modelo.',
            ];
            $admitted = $this->content->admit($provenance, $trust);
            if (!$admitted->allowed) {
                $steps[] = [
                    'id' => 'boundary',
                    'label' => 'Content boundary',
                    'verdict' => 'block',
                    'detail' => 'provenance='.$provenance.' · trust='.$trust.' · retrieved ≠ trusted',
                ];

                return $this->blocked(
                    $steps,
                    $agent,
                    $capability,
                    $key,
                    $at,
                    $admitted->policy,
                    'Contenido no confiable no otorga capacidad. El LLM no es la frontera. El dominio no se enteró.',
                );
            }
        }

        $decision = $this->gate->decide($agent, $capability, $parentCapability);

        if ($parentCapability !== null && !$decision->allowed && $decision->policy === 'child-subseteq-parent') {
            $steps[] = [
                'id' => 'delegation',
                'label' => 'Delegación',
                'verdict' => 'block',
                'detail' => $capability.' ⊈ '.$parentCapability,
            ];

            return $this->blocked($steps, $agent, $capability, $key, $at, $decision->policy, 'ChildCapability no puede superar ParentAuthority. El dominio no se enteró.');
        }

        $steps[] = [
            'id' => 'delegation',
            'label' => 'Delegación',
            'verdict' => 'pass',
            'detail' => 'child ⊆ parent',
        ];

        if (!$decision->allowed) {
            $steps[] = [
                'id' => 'capability',
                'label' => 'Capacidad',
                'verdict' => 'block',
                'detail' => $capability.' ∉ CapabilitySet('.$agent.')',
            ];

            return $this->blocked($steps, $agent, $capability, $key, $at, $decision->policy, 'Compromiso del agente ≠ compromiso del dominio.');
        }

        $steps[] = [
            'id' => 'capability',
            'label' => 'Capacidad',
            'verdict' => 'pass',
            'detail' => $capability.' ∈ CapabilitySet('.$agent.')',
        ];

        if (isset($this->keys[$key]) && $kind === 'replay') {
            $existing = $this->keys[$key];
            $existing->replay = true;
            $steps[] = [
                'id' => 'exec',
                'label' => 'Ejecución',
                'verdict' => 'replay',
                'detail' => 'Idempotency-Key '.$key.' ya existía. Cero segunda fila.',
            ];

            return [
                'reservation' => $existing,
                'steps' => $steps,
                'evidence' => new Record(
                    'ev-'.$key.'-replay',
                    $at,
                    $agent,
                    $capability,
                    $capability,
                    'idempotency',
                    'ALLOW',
                    'replay '.$existing->id,
                    Hash::of($key.'|replay|'.$existing->id),
                ),
                'blocked' => false,
                'message' => 'Misma clave, misma reserva.',
            ];
        }

        $id = 'rsv-'.str_pad((string) $this->seq++, 3, '0', STR_PAD_LEFT);
        $mode = $payment->authorize($key, $id);

        if ($mode === 'ok') {
            $reservation = new Reservation($id, $date, 'confirmed', $key);
            $this->keys[$key] = $reservation;
            $steps[] = [
                'id' => 'exec',
                'label' => 'Ejecución',
                'verdict' => 'pass',
                'detail' => 'Hold '.$id.' · PaymentAuthorized · BookingConfirmed',
            ];

            return [
                'reservation' => $reservation,
                'steps' => $steps,
                'evidence' => new Record(
                    'ev-'.$key,
                    $at,
                    $agent,
                    $capability,
                    $capability,
                    'hold-and-pay',
                    'ALLOW',
                    'confirmed '.$id,
                    Hash::of($key.'|'.$id.'|confirmed'),
                ),
                'blocked' => false,
                'message' => 'Reserva confirmada. El dominio recibió PaymentAuthorized, no un JSON de pasarela.',
            ];
        }

        if ($mode === 'reject') {
            $reservation = new Reservation($id, $date, 'failed', $key);
            $this->keys[$key] = $reservation;
            $steps[] = [
                'id' => 'exec',
                'label' => 'Ejecución',
                'verdict' => 'block',
                'detail' => 'PaymentFailed. Hold liberado. BookingRejected.',
            ];

            return [
                'reservation' => $reservation,
                'steps' => $steps,
                'evidence' => new Record(
                    'ev-'.$key,
                    $at,
                    $agent,
                    $capability,
                    $capability,
                    'no-confirm-without-payment',
                    'ALLOW',
                    'failed '.$id,
                    Hash::of($key.'|failed'),
                ),
                'blocked' => false,
                'message' => 'Fallo controlado. Nada se confirma «por si acaso».',
            ];
        }

        $reservation = new Reservation($id, $date, 'unknown', $key);
        $this->keys[$key] = $reservation;
        $steps[] = [
            'id' => 'exec',
            'label' => 'Ejecución',
            'verdict' => 'warn',
            'detail' => 'Proveedor timeout. Estado unknown. No retry ciego.',
        ];

        return [
            'reservation' => $reservation,
            'steps' => $steps,
            'evidence' => new Record(
                'ev-'.$key,
                $at,
                $agent,
                $capability,
                $capability,
                'timeout-reconcile',
                'ALLOW',
                'unknown '.$id,
                Hash::of($key.'|unknown'),
            ),
            'blocked' => false,
            'message' => 'Unknown no es confirmed.',
        ];
    }

    /**
     * @param list<array{id:string,label:string,verdict:string,detail:string}> $steps
     * @return array{reservation:null,steps:list<array{id:string,label:string,verdict:string,detail:string}>,evidence:Record,blocked:true,message:string}
     */
    private function blocked(array $steps, string $agent, string $capability, string $key, int $at, string $policy, string $message): array
    {
        return [
            'reservation' => null,
            'steps' => $steps,
            'evidence' => new Record(
                'ev-'.$key.'-block',
                $at,
                $agent,
                $capability,
                $capability,
                $policy,
                'DENIED',
                'blocked',
                Hash::of($agent.'|'.$capability.'|deny|'.$key),
            ),
            'blocked' => true,
            'message' => $message,
        ];
    }
}
