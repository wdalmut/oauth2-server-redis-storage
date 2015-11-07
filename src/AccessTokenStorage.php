<?php
namespace Corley\OAuth2\Server\Storage\Redis;

use League\OAuth2\Server\Storage\AccessTokenInterface;
use League\OAuth2\Server\Entity\AccessTokenEntity;
use League\OAuth2\Server\Entity\ScopeEntity;

class AccessTokenStorage extends RedisAbstractStorage implements AccessTokenInterface
{
    public function get($token)
    {
        $rawData = $this->redis->get("access_token:{$token}");

        if (!$rawData) {
            return null;
        }

        $data = json_decode($rawData, true);

        $token = (new AccessTokenEntity($this->server))
            ->setId($token)
            ->setExpireTime($data['expire_time']);

        return $token;
    }

    public function getScopes(AccessTokenEntity $token)
    {
        $data = $this->redis->lrange("access_token:scopes:{$token}", 0, -1);

        $scopes = [];

        foreach ($data as $scope) {
            list($id, $description) = explode(":", $scope);
            $scopes[] = (new ScopeEntity($this->server))->hydrate([
                'id'            =>  $id,
                'description'   =>  $description,
            ]);
        }

        return $scopes;
    }

    public function create($token, $expireTime, $sessionId)
    {
        $this->redis->set("access_token:{$token}", json_encode([
            "access_token_id" => $token,
            "expire_time" => $expireTime,
            "session_id" => $sessionId,
        ]));
    }

    public function associateScope(AccessTokenEntity $token, ScopeEntity $scope)
    {
        $this->redis->lpush("access_token:scopes:{$token}", "{$scope->getId()}:{$scope->getDescription()}");
    }

    public function delete(AccessTokenEntity $token)
    {
        $this->redis->del("access_token:{$token}");
    }
}

