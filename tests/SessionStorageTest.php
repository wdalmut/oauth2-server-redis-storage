<?php
namespace Corley\OAuth2\Server\Storage\Redis;

use League\OAuth2\Server\Entity\AccessTokenEntity;
use League\Event\Emitter;
use League\OAuth2\Server\Entity\AuthCodeEntity;
use League\OAuth2\Server\Entity\SessionEntity;
use League\OAuth2\Server\Entity\ScopeEntity;

class SessionStorageTest extends \PHPUnit_Framework_TestCase
{
    public function testGetByAccessToken()
    {
        $redis = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\RedisMock");
        $redis->get("access_token:access_token_id")->willReturn(<<<EOF
{
    "session_id": "session_id"
}
EOF
);
        $redis->get("session:session_id")->willReturn(<<<EOF
{
    "owner_type": "user",
    "owner_id": "owner",
    "client_id": "client_id",
    "client_redirect_uri": "http://localhost:8080/"
}
EOF
);

        $server = $this->prophesize("League\OAuth2\Server\AbstractServer");
        $server->getEventEmitter()->willReturn(new Emitter());

        $sessionStorage = new SessionStorage($redis->reveal());
        $sessionStorage->setServer($server->reveal());

        $accessToken = new AccessTokenEntity($server->reveal());
        $accessToken->setId("access_token_id");
        $session = $sessionStorage->getByAccessToken($accessToken);

        $this->assertInstanceOf("League\OAuth2\Server\Entity\SessionEntity", $session);
        $this->assertEquals("session_id", $session->getId());
    }

    public function testGetMissingAccessToken()
    {
        $redis = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\RedisMock");
        $redis->get("access_token:access_token_id")->willReturn(null);

        $server = $this->prophesize("League\OAuth2\Server\AbstractServer");
        $server->getEventEmitter()->willReturn(new Emitter());

        $sessionStorage = new SessionStorage($redis->reveal());
        $sessionStorage->setServer($server->reveal());

        $accessToken = new AccessTokenEntity($server->reveal());
        $accessToken->setId("access_token_id");
        $session = $sessionStorage->getByAccessToken($accessToken);

        $this->assertNull($session);
    }

    public function testGetMissingSessionIdWithAccessToken()
    {
        $redis = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\RedisMock");
        $redis->get("access_token:access_token_id")->willReturn(<<<EOF
{
    "session_id": "session_id"
}
EOF
);
        $redis->get("session:session_id")->willReturn(null);

        $server = $this->prophesize("League\OAuth2\Server\AbstractServer");
        $server->getEventEmitter()->willReturn(new Emitter());

        $sessionStorage = new SessionStorage($redis->reveal());
        $sessionStorage->setServer($server->reveal());

        $accessToken = new AccessTokenEntity($server->reveal());
        $accessToken->setId("access_token_id");
        $session = $sessionStorage->getByAccessToken($accessToken);

        $this->assertNull($session);
    }

    public function testGetByAuthCode()
    {
        $redis = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\RedisMock");
        $redis->get("auth_code:auth_code_id")->willReturn(<<<EOF
{
    "session_id": "session_id"
}
EOF
);
        $redis->get("session:session_id")->willReturn(<<<EOF
{
    "owner_type": "user",
    "owner_id": "owner",
    "client_id": "client_id",
    "client_redirect_uri": "http://localhost:8080/"
}
EOF
);

        $server = $this->prophesize("League\OAuth2\Server\AbstractServer");
        $server->getEventEmitter()->willReturn(new Emitter());

        $sessionStorage = new SessionStorage($redis->reveal());
        $sessionStorage->setServer($server->reveal());

        $authCode = new AuthCodeEntity($server->reveal());
        $authCode->setId("auth_code_id");
        $session = $sessionStorage->getByAuthCode($authCode);

        $this->assertInstanceOf("League\OAuth2\Server\Entity\SessionEntity", $session);
        $this->assertEquals("session_id", $session->getId());
    }

    public function testGetMissingAuthCode()
    {
        $redis = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\RedisMock");
        $redis->get("auth_code:auth_code_id")->willReturn(null);

        $server = $this->prophesize("League\OAuth2\Server\AbstractServer");
        $server->getEventEmitter()->willReturn(new Emitter());

        $sessionStorage = new SessionStorage($redis->reveal());
        $sessionStorage->setServer($server->reveal());

        $accessToken = new AuthCodeEntity($server->reveal());
        $accessToken->setId("auth_code_id");
        $session = $sessionStorage->getByAuthCode($accessToken);

        $this->assertNull($session);
    }

    public function testGetMissingSessionIdWithAuthCode()
    {
        $redis = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\RedisMock");
        $redis->get("auth_code:auth_code_id")->willReturn(<<<EOF
{
    "session_id": "session_id"
}
EOF
);
        $redis->get("session:session_id")->willReturn(null);

        $server = $this->prophesize("League\OAuth2\Server\AbstractServer");
        $server->getEventEmitter()->willReturn(new Emitter());

        $sessionStorage = new SessionStorage($redis->reveal());
        $sessionStorage->setServer($server->reveal());

        $accessToken = new AuthCodeEntity($server->reveal());
        $accessToken->setId("auth_code_id");
        $session = $sessionStorage->getByAuthCode($accessToken);

        $this->assertNull($session);
    }

    public function testCreateNewSession()
    {
        $redis = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\RedisMock");
        $redis->set(
            "session:session_id",
            '{"owner_type":"user","owner_id":"owner","client_id":"client_id","client_redirect_uri":"http:\/\/localhost:8080\/"}'
        )->shouldBeCalledTimes(1);

        $server = $this->prophesize("League\OAuth2\Server\AbstractServer");
        $server->getEventEmitter()->willReturn(new Emitter());

        $idGenerator = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\IdGenerator");
        $idGenerator->createId()->willReturn("session_id");

        $sessionStorage = new SessionStorage($redis->reveal(), $idGenerator->reveal());
        $sessionStorage->setServer($server->reveal());

        $sessionId = $sessionStorage->create("user", "owner", "client_id", "http://localhost:8080/");
        $this->assertEquals("session_id", $sessionId);
    }

    public function testGetScopes()
    {
        $redis = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\RedisMock");
        $redis->lrange("session:scopes:session_id", 0, -1)->willReturn(["scope_id:description"]);

        $server = $this->prophesize("League\OAuth2\Server\AbstractServer");
        $server->getEventEmitter()->willReturn(new Emitter());

        $sessionStorage = new SessionStorage($redis->reveal());
        $sessionStorage->setServer($server->reveal());

        $session = new SessionEntity($server->reveal());
        $session->setId("session_id");

        $scopes = $sessionStorage->getScopes($session);

        $this->assertInternalType("array", $scopes);
        $this->assertCount(1, $scopes);
    }

    public function testAssociateScope()
    {
        $redis = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\RedisMock");
        $redis->lpush("session:scopes:session_id", "scope_id:desc")->shouldBeCalledTimes(1);

        $server = $this->prophesize("League\OAuth2\Server\AbstractServer");
        $server->getEventEmitter()->willReturn(new Emitter());

        $sessionStorage = new SessionStorage($redis->reveal());
        $sessionStorage->setServer($server->reveal());

        $session = new SessionEntity($server->reveal());
        $session->setId("session_id");

        $scope = new ScopeEntity($server->reveal());
        $scope->hydrate([
            "id" => "scope_id",
            "description" => "desc",
        ]);

        $sessionStorage->associateScope($session, $scope);
    }
}
