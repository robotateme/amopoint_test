<?php

namespace Infrastructure\Redis;

interface RedisConnectionPort
{
    public function command(string $command, string|int ...$arguments): mixed;
}
