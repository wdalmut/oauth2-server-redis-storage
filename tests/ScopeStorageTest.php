<?php
namespace Corley\OAuth2\Server\Storage\Redis;

class ScopeStorageTest extends \PHPUnit_Framework_TestCase
{
    public function testGetScopes()
    {
        $redis = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\RedisMock");
        $redis->get("scope:scope_id")->shouldBeCalledTimes(1)->willReturn(<<<EOF
{
    "scope_id": "scope_id",
    "description": "desc"
}
EOF
);
        $server = $this->prophesize("League\OAuth2\Server\AbstractServer");

        $scope = new ScopeStorage($redis->reveal());
        $scope->setServer($server->reveal());

        $scope = $scope->get("scope_id");

        $this->assertInstanceOf("League\OAuth2\Server\Entity\ScopeEntity", $scope);
        $this->assertEquals("scope_id", $scope->getId());
        $this->assertEquals("desc", $scope->getDescription());
    }

    public function testGetNotExistingScope()
    {
        $redis = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\RedisMock");
        $redis->get("scope:scope_id")->shouldBeCalledTimes(1)->willReturn(null);
        $server = $this->prophesize("League\OAuth2\Server\AbstractServer");

        $scope = new ScopeStorage($redis->reveal());
        $scope->setServer($server->reveal());

        $scope = $scope->get("scope_id");

        $this->assertNull($scope);

    }
}
