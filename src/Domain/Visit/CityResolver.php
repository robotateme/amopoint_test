<?php

namespace Domain\Visit;

interface CityResolver
{
    public function resolve(string $ip): string;
}
