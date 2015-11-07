<?php
namespace Corley\OAuth2\Server\Storage\Redis;

use League\OAuth2\Server\Storage\RefreshTokenInterface;
use League\OAuth2\Server\Entity\RefreshTokenEntity;

class RefreshTokenStorage extends RedisAbstractStorage implements RefreshTokenInterface
{
    public function get($token)
    {
        $rawData = $this->redis->get("refresh_token:{$token}");

        if (!$rawData) {
            return null;
        }

        $data = json_decode($rawData, true);

        if (!$data) {
            return null;
        }

        $token = (new RefreshTokenEntity($this->server))
            ->setId($data['refresh_token_id'])
            ->setExpireTime($data['expire_time'])
            ->setAccessTokenId($data['access_token_id']);

        return $token;
    }

    public function create($token, $expireTime, $accessToken)
    {
        $this->redis->set("refresh_token:{$token}", json_encode([
            "refresh_token_id" => $token,
            "expire_time" => $expireTime,
            "access_token_id" => $accessToken,
        ]));
    }

    public function delete(RefreshTokenEntity $token)
    {
        $this->redis->del("refresh_token:{$token}");
    }
}
