<?php
namespace Corley\OAuth2\Server\Storage\Redis;

use League\OAuth2\Server\Storage\AuthCodeInterface;
use League\OAuth2\Server\Entity\AuthCodeEntity;
use League\OAuth2\Server\Entity\ScopeEntity;

class AuthCodeStorage extends RedisAbstractStorage implements AuthCodeInterface
{
    public function get($code)
    {
        $rawData = $this->redis->get("auth_code:{$code}");

        if (!$rawData) {
            return null;
        }

        $data = json_decode($rawData, true);

        if (!$data) {
            return null;
        }

        $expireTime = $data["expire_time"];
        if ($expireTime < time()) {
            return null;
        }

        $token = new AuthCodeEntity($this->server);
        $token->setId($code);
        $token->setRedirectUri($data['client_redirect_uri']);
        $token->setExpireTime($data['expire_time']);

        return $token;
    }

    public function create($token, $expireTime, $sessionId, $redirectUri)
    {
        $this->redis->set("auth_code:{$token}", json_encode([
            "auth_code_id" => $token,
            "expire_time" => $expireTime,
            "redirect_uri" => $redirectUri,
            "session_id" => $sessionId,
        ]));
    }

    public function getScopes(AuthCodeEntity $token)
    {
        $data = $this->redis->lrange("auth_code:scopes:{$token}", 0, -1);

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

    public function associateScope(AuthCodeEntity $token, ScopeEntity $scope)
    {
        $this->redis->lpush("auth_code:scopes:{$token}", "{$scope->getId()}:{$scope->getDescription()}");
    }

    public function delete(AuthCodeEntity $token)
    {
        $this->redis->del("auth_code:{$token}");
    }
}
