<?php

namespace App\Command;

use Predis\Client;

class RedisClient implements RedisClientInterface
{
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function set(string $key, $value): void
    {
        $this->client->set($key, $value);
    }

    public function get(string $key)
    {
        return $this->client->get($key);
    }
}
