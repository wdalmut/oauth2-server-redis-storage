<?php
namespace Corley\OAuth2\Server\Storage\Redis;

use League\OAuth2\Server\Entity\AuthCodeEntity;
use League\OAuth2\Server\Entity\ScopeEntity;

class AuthCodeStorageTest extends \PHPUnit_Framework_TestCase
{
    public function testGetExistingAuthCode()
    {
        $future = time() + 300;

        $redis = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\RedisMock");
        $redis->get("auth_code:auth_code_id")->shouldBeCalledTimes(1)->willReturn(<<<EOF
{
    "auth_code_id": "auth_code_id",
    "expire_time": {$future},
    "redirect_uri": "http://localhost:8080/",
    "session_id": "session_id"
}
EOF
);
        $server = $this->prophesize("League\OAuth2\Server\AbstractServer");

        $authCodeStorage = new AuthCodeStorage($redis->reveal());
        $authCodeStorage->setServer($server->reveal());

        $authCode = $authCodeStorage->get("auth_code_id");

        $this->assertInstanceOf("League\OAuth2\Server\Entity\AuthCodeEntity", $authCode);
    }

    public function testGetAMissingAuthCode()
    {
        $future = time() + 60;

        $redis = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\RedisMock");
        $redis->get("auth_code:auth_code_id")->shouldBeCalledTimes(1)->willReturn(null);
        $server = $this->prophesize("League\OAuth2\Server\AbstractServer");

        $authCodeStorage = new AuthCodeStorage($redis->reveal());
        $authCodeStorage->setServer($server->reveal());

        $authCode = $authCodeStorage->get("auth_code_id");

        $this->assertNull($authCode);
    }

    public function testGetAnExpiredAuthToken()
    {
        $future = time()-1;

        $redis = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\RedisMock");
        $redis->get("auth_code:auth_code_id")->shouldBeCalledTimes(1)->willReturn(<<<EOF
{
    "auth_code_id": "auth_code_id",
    "expire_time": {$future},
    "client_redirect_uri": "http://localhost:8080/",
    "session_id": "session_id"
}
EOF
);
        $server = $this->prophesize("League\OAuth2\Server\AbstractServer");

        $authCodeStorage = new AuthCodeStorage($redis->reveal());
        $authCodeStorage->setServer($server->reveal());

        $authCode = $authCodeStorage->get("auth_code_id");

        $this->assertNull($authCode);
    }

    public function testCreateNewAuthToken()
    {
        $redis = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\RedisMock");
        $redis->set("auth_code:auth_code_id", '{"auth_code_id":"auth_code_id","expire_time":11111,"redirect_uri":"http:\/\/localhost:8082\/","session_id":"session_id"}')->shouldBeCalledTimes(1);

        $server = $this->prophesize("League\OAuth2\Server\AbstractServer");

        $authCodeStorage = new AuthCodeStorage($redis->reveal());
        $authCodeStorage->setServer($server->reveal());

        $authCode = $authCodeStorage->create("auth_code_id", 11111, "session_id", "http://localhost:8082/");
    }

    public function testGetScopes()
    {
        $redis = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\RedisMock");
        $redis->lrange("auth_code:scopes:auth_code_id", 0, -1)->willReturn(["scope:desc"]);

        $server = $this->prophesize("League\OAuth2\Server\AbstractServer");

        $authCodeStorage = new AuthCodeStorage($redis->reveal());
        $authCodeStorage->setServer($server->reveal());

        $token = new AuthCodeEntity($server->reveal());
        $token->setId("auth_code_id");
        $token->setRedirectUri("http://localhost:8080/");
        $token->setExpireTime(11111);

        $scopes = $authCodeStorage->getScopes($token);

        $this->assertInternalType("array", $scopes);
        $this->assertCount(1, $scopes);

        $this->assertEquals("scope", $scopes[0]->getId());
        $this->assertEquals("desc", $scopes[0]->getDescription());
    }

    public function testAssociateScopes()
    {
        $redis = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\RedisMock");
        $redis->lpush("auth_code:scopes:auth_code_id", "scope_id:desc")->shouldBeCalledTimes(1);

        $server = $this->prophesize("League\OAuth2\Server\AbstractServer");

        $authCodeStorage = new AuthCodeStorage($redis->reveal());
        $authCodeStorage->setServer($server->reveal());

        $token = new AuthCodeEntity($server->reveal());
        $token->setId("auth_code_id");
        $token->setRedirectUri("http://localhost:8080/");
        $token->setExpireTime(11111);

        $scope = new ScopeEntity($server->reveal());
        $scope->hydrate([
            "id" => "scope_id",
            "description" => "desc",
        ]);

        $authCodeStorage->associateScope($token, $scope);
    }

    public function testDeleteAuthCode()
    {
        $redis = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\RedisMock");
        $redis->del("auth_code:auth_code_id")->shouldBeCalledTimes(1);

        $server = $this->prophesize("League\OAuth2\Server\AbstractServer");

        $authCodeStorage = new AuthCodeStorage($redis->reveal());
        $authCodeStorage->setServer($server->reveal());

        $token = new AuthCodeEntity($server->reveal());
        $token->setId("auth_code_id");
        $token->setRedirectUri("http://localhost:8080/");
        $token->setExpireTime(11111);

        $authCodeStorage->delete($token);
    }
}
