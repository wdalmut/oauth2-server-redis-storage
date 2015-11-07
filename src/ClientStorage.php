<?php
namespace Corley\OAuth2\Server\Storage\Redis;

use League\OAuth2\Server\Storage\ClientInterface;
use League\OAuth2\Server\Entity\SessionEntity;
use League\OAuth2\Server\Entity\ClientEntity;

class ClientStorage extends RedisAbstractStorage implements ClientInterface
{
    public function get($clientId, $clientSecret = null, $redirectUri = null, $grantType = null)
    {
        $rawData = $this->redis->get("client:{$clientId}");

        if ($rawData === null) {
            return null;
        }

        $data = json_decode($rawData, true);

        if ($clientSecret !== null && is_string($clientSecret)) {
            if (strcmp($clientSecret, $data["secret"]) !== 0) {
                return null;
            }
        }

        if ($redirectUri !== null && is_string($redirectUri)) {
            if (!in_array($redirectUri, $data["redirect_uris"])) {
                return null;
            }
        }

        $entity = new ClientEntity($this->server);
        $entity->hydrate([
            "id" => $data["client_id"],
            "name" =>  $data["name"],
        ]);

        return $entity;
    }

    public function getBySession(SessionEntity $session)
    {
        $rawData = $this->redis->get("session:{$session->getId()}");

        if ($rawData === null) {
            return null;
        }

        $data = json_decode($rawData, true);

        $rawData = $this->redis->get("client:{$data["client_id"]}");

        if ($rawData === null) {
            return null;
        }

        $data = json_decode($rawData, true);

        $entity = new ClientEntity($this->server);
        $entity->hydrate([
            "id" => $data["client_id"],
            "name" =>  $data["name"],
        ]);

        return $entity;
    }
}
