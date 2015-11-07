<?php
namespace Corley\OAuth2\Server\Storage\Redis;

use League\OAuth2\Server\Storage\SessionInterface;
use League\OAuth2\Server\Entity\AccessTokenEntity;
use League\OAuth2\Server\Entity\AuthCodeEntity;
use League\OAuth2\Server\Entity\SessionEntity;
use League\OAuth2\Server\Entity\ScopeEntity;

use Predis\Client as RedisClient;

class SessionStorage extends RedisAbstractStorage implements SessionInterface
{
    private $idGenerator;

    public function __construct(RedisClient $redis, IdGenerator $generator = null)
    {
        $this->idGenerator = ($generator !== null) ? $generator : new IdGenerator($redis);
        parent::__construct($redis);
    }

    public function getByAccessToken(AccessTokenEntity $accessToken)
    {
        $rawData = $this->redis->get("access_token:{$accessToken->getId()}");

        if (!$rawData) {
            return null;
        }
        $data = json_decode($rawData, true);

        $sessionId = $data['session_id'];

        $rawData = $this->redis->get("session:{$data["session_id"]}");

        if (!$rawData) {
            return null;
        }
        $data = json_decode($rawData, true);

        $session = new SessionEntity($this->server);
        $session->setId($sessionId);
        $session->setOwner($data['owner_type'], $data['owner_id']);

        return $session;
    }

    public function getByAuthCode(AuthCodeEntity $authCode)
    {
        $rawData = $this->redis->get("auth_code:{$authCode->getId()}");

        if (!$rawData) {
            return null;
        }
        $data = json_decode($rawData, true);

        $sessionId = $data['session_id'];

        $rawData = $this->redis->get("session:{$data["session_id"]}");

        if (!$rawData) {
            return null;
        }
        $data = json_decode($rawData, true);

        $session = new SessionEntity($this->server);
        $session->setId($sessionId);
        $session->setOwner($data['owner_type'], $data['owner_id']);

        return $session;
    }

    public function getScopes(SessionEntity $session)
    {
        $data = $this->redis->lrange("session:scopes:{$session->getId()}", 0, -1);

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

    public function create($ownerType, $ownerId, $clientId, $clientRedirectUri = null)
    {
        $sessionId = $this->idGenerator->createId();

        $this->redis->set("session:{$sessionId}", json_encode([
            "owner_type" => $ownerType,
            "owner_id" => $ownerId,
            "client_id" => $clientId,
            "client_redirect_uri" => $clientRedirectUri,
        ]));

        return $sessionId;
    }

    public function associateScope(SessionEntity $session, ScopeEntity $scope)
    {
        $this->redis->lpush("session:scopes:{$session->getId()}", "{$scope->getId()}:{$scope->getDescription()}");
    }
}
