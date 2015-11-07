<?php
namespace Corley\OAuth2\Server\Storage\Redis;

use Predis\Client as RedisClient;
use League\OAuth2\Server\Storage\AbstractStorage;

abstract class RedisAbstractStorage extends AbstractStorage
{
    protected $redis;

    public function __construct(RedisClient $redis)
    {
        $this->redis = $redis;
    }
}
