<?php

namespace Domain\Visit;

interface VisitStatisticsBroadcaster
{
    public function changed(): void;
}
