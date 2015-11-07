<?php
namespace Corley\OAuth2\Server\Storage\Redis;

use League\OAuth2\Server\Entity\RefreshTokenEntity;

class RefreshTokenStorageTest extends \PHPUnit_Framework_TestCase
{
    public function testGetRefreshToken()
    {
        $redis = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\RedisMock");
        $redis->get("refresh_token:refresh_token_id")->shouldBeCalledTimes(1)->willReturn(<<<EOF
{
    "refresh_token_id": "refresh_token_id",
    "expire_time": 11111,
    "access_token_id": "access_token_id"
}
EOF
        );

        $server = $this->prophesize("League\OAuth2\Server\AbstractServer");

        $storage = new RefreshTokenStorage($redis->reveal());
        $storage->setServer($server->reveal());

        $refreshToken = $storage->get("refresh_token_id");

        $this->assertInstanceOf("League\OAuth2\Server\Entity\RefreshTokenEntity", $refreshToken);
    }

    public function testGetMissingRefreshToken()
    {
        $redis = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\RedisMock");
        $redis->get("refresh_token:refresh_token_id")->shouldBeCalledTimes(1)->willReturn(null);

        $server = $this->prophesize("League\OAuth2\Server\AbstractServer");

        $storage = new RefreshTokenStorage($redis->reveal());
        $storage->setServer($server->reveal());

        $refreshToken = $storage->get("refresh_token_id");

        $this->assertNull($refreshToken);
    }

    public function testCreateRefreshToken()
    {
        $redis = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\RedisMock");
        $redis->set(
            "refresh_token:refresh_token_id",
            '{"refresh_token_id":"refresh_token_id","expire_time":11111,"access_token_id":"access_token_id"}')
            ->shouldBeCalledTimes(1)->willReturn(null);

        $server = $this->prophesize("League\OAuth2\Server\AbstractServer");

        $storage = new RefreshTokenStorage($redis->reveal());
        $storage->setServer($server->reveal());

        $storage->create("refresh_token_id", 11111, "access_token_id");
    }

    public function testDeleteRefreshToken()
    {
        $redis = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\RedisMock");
        $redis->del("refresh_token:refresh_token_id")->shouldBeCalledTimes(1)->willReturn(null);

        $server = $this->prophesize("League\OAuth2\Server\AbstractServer");

        $storage = new RefreshTokenStorage($redis->reveal());
        $storage->setServer($server->reveal());

        $token = new RefreshTokenEntity($server->reveal());
        $token->setId("refresh_token_id");
        $storage->delete($token);
    }
}

