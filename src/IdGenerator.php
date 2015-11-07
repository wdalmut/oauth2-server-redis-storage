<?php
namespace Corley\OAuth2\Server\Storage\Redis;

use Predis\Client as RedisClient;

class IdGenerator
{
    private $redis;

    public function __construct(RedisClient $redis)
    {
        $this->redis = $redis;
    }

    public function createId()
    {
        $data = $this->redis->transaction()->incr("oauth:ids")->execute();

        return array_shift($data);
    }
}
