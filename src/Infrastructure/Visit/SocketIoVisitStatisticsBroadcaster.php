<?php

namespace Infrastructure\Visit;

use Domain\Visit\VisitStatisticsBroadcaster;
use Illuminate\Support\Facades\Http;
use Throwable;

final readonly class SocketIoVisitStatisticsBroadcaster implements VisitStatisticsBroadcaster
{
    public function __construct(
        private ?string $endpoint,
        private ?string $token,
    ) {}

    public function changed(): void
    {
        if ($this->endpoint === null || $this->endpoint === '') {
            return;
        }

        try {
            $request = Http::timeout(1)->acceptJson();

            if ($this->token !== null && $this->token !== '') {
                $request = $request->withHeader('X-Internal-Token', $this->token);
            }

            $request->post(rtrim($this->endpoint, '/').'/emit', [
                'event' => 'visit-statistics:changed',
                'data' => [
                    'changedAt' => now()->toIso8601String(),
                ],
            ]);
        } catch (Throwable) {
            report('Failed to broadcast visit statistics update.');
        }
    }
}
