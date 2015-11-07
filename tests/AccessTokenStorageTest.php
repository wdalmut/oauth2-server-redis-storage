<?php
namespace Corley\OAuth2\Server\Storage\Redis;

use League\Event\Emitter;
use League\OAuth2\Server\Entity\AccessTokenEntity;
use League\OAuth2\Server\Entity\ScopeEntity;

class AccessTokenStorageTest extends \PHPUnit_Framework_TestCase
{
    public function testGetAccessToken()
    {
        $redis = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\RedisMock");
        $redis->get("access_token:access_token_id")->willReturn(<<<EOF
{
    "access_token_id": "access_token_id",
    "expire_time": 11587174
}
EOF
);

        $server = $this->prophesize("League\OAuth2\Server\AbstractServer");
        $server->getEventEmitter()->willReturn(new Emitter());

        $accessTokenStorage = new AccessTokenStorage($redis->reveal());
        $accessTokenStorage->setServer($server->reveal());

        $accessToken = $accessTokenStorage->get("access_token_id");

        $this->assertInstanceOf("League\OAuth2\Server\Entity\AccessTokenEntity", $accessToken);
        $this->assertEquals("access_token_id", $accessToken->getId());
        $this->assertEquals("11587174", $accessToken->getExpireTime());
    }

    public function testGetMissingAccessToken()
    {
        $redis = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\RedisMock");
        $redis->get("access_token:access_token_id")->willReturn(null);

        $server = $this->prophesize("League\OAuth2\Server\AbstractServer");
        $server->getEventEmitter()->willReturn(new Emitter());

        $accessTokenStorage = new AccessTokenStorage($redis->reveal());
        $accessTokenStorage->setServer($server->reveal());

        $accessToken = $accessTokenStorage->get("access_token_id");

        $this->assertNull($accessToken);
    }

    public function testCreateNewAccessToken()
    {
        $redis = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\RedisMock");
        $redis->set(
            "access_token:access_token_id",
            '{"access_token_id":"access_token_id","expire_time":"111111111","session_id":"session_id"}'
        )->shouldBeCalledTimes(1);

        $server = $this->prophesize("League\OAuth2\Server\AbstractServer");
        $server->getEventEmitter()->willReturn(new Emitter());

        $accessTokenStorage = new AccessTokenStorage($redis->reveal());
        $accessTokenStorage->setServer($server->reveal());

        $accessTokenStorage->create("access_token_id", "111111111", "session_id");
    }

    public function testDeleteExistingAccessToken()
    {
        $redis = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\RedisMock");
        $redis->del("access_token:access_token_id")->shouldBeCalledTimes(1);

        $server = $this->prophesize("League\OAuth2\Server\AbstractServer");
        $server->getEventEmitter()->willReturn(new Emitter());

        $accessToken = new AccessTokenEntity($server->reveal());
        $accessToken->setId("access_token_id");

        $accessTokenStorage = new AccessTokenStorage($redis->reveal());
        $accessTokenStorage->setServer($server->reveal());

        $accessTokenStorage->delete($accessToken);
    }

    public function testGetScopes()
    {
        $redis = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\RedisMock");
        $redis->lrange("access_token:scopes:access_token_id", 0, -1)->shouldBeCalledTimes(1)->willReturn(["scope_id:desc"]);

        $server = $this->prophesize("League\OAuth2\Server\AbstractServer");
        $server->getEventEmitter()->willReturn(new Emitter());

        $accessTokenStorage = new AccessTokenStorage($redis->reveal());
        $accessTokenStorage->setServer($server->reveal());

        $accessToken = new AccessTokenEntity($server->reveal());
        $accessToken->setId("access_token_id");

        $scopes = $accessTokenStorage->getScopes($accessToken);

        $this->assertInternalType("array", $scopes);
        $this->assertCount(1, $scopes);
    }

    public function testAssociateScope()
    {
        $redis = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\RedisMock");
        $redis->lpush("access_token:scopes:access_token_id", "scope_id:desc")->shouldBeCalledTimes(1)->willReturn(null);

        $server = $this->prophesize("League\OAuth2\Server\AbstractServer");
        $server->getEventEmitter()->willReturn(new Emitter());

        $accessTokenStorage = new AccessTokenStorage($redis->reveal());
        $accessTokenStorage->setServer($server->reveal());

        $accessToken = new AccessTokenEntity($server->reveal());
        $accessToken->setId("access_token_id");

        $scope = new ScopeEntity($server->reveal());
        $scope->hydrate([
            "id" => "scope_id",
            "description" => "desc",
        ]);

        $accessTokenStorage->associateScope($accessToken, $scope);
    }
}
