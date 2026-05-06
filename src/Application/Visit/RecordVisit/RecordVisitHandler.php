<?php

namespace Application\Visit\RecordVisit;

use Domain\Visit\CityResolver;
use Domain\Visit\Visit;
use Domain\Visit\VisitRepository;
use Domain\Visit\VisitStatisticsCache;

final readonly class RecordVisitHandler
{
    public function __construct(
        private CityResolver $cityResolver,
        private VisitRepository $visits,
        private VisitStatisticsCache $cache,
    ) {
    }

    public function handle(RecordVisitCommand $command): void
    {
        $fingerprint = trim((string) $command->fingerprint);

        if ($fingerprint === '') {
            $fingerprint = hash('sha256', implode('|', [
                $command->ip,
                $command->userAgent ?? '',
                $command->pageUrl ?? '',
            ]));
        }

        $this->visits->save(new Visit(
            fingerprint: $fingerprint,
            ip: $command->ip,
            city: $this->cityResolver->resolve($command->ip),
            device: $command->device ?: 'unknown',
            userAgent: $command->userAgent,
            pageUrl: $command->pageUrl,
            referrer: $command->referrer,
        ));

        $this->cache->flush();
    }
}
