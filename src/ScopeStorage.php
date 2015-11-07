<?php
namespace Corley\OAuth2\Server\Storage\Redis;

use League\OAuth2\Server\Storage\ScopeInterface;
use League\OAuth2\Server\Entity\ScopeEntity;

class ScopeStorage extends RedisAbstractStorage implements ScopeInterface
{
    public function get($scope, $grantType = null, $clientId = null)
    {
        $rawData = $this->redis->get("scope:{$scope}");

        if (!$rawData) {
            return null;
        }

        $data = json_decode($rawData, true);

        if (!$data) {
            return null;
        }

        $scope = (new ScopeEntity($this->server))->hydrate([
            'id'            =>  $scope,
            'description'   =>  $data['description'],
        ]);

        return $scope;
    }
}
