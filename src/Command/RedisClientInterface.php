<?php

namespace App\Command;

interface RedisClientInterface
{
    public function set(string $key, $value): void;

    public function get(string $key);
}
