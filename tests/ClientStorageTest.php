<?php
namespace Corley\OAuth2\Server\Storage\Redis;

use League\OAuth2\Server\Entity\SessionEntity;

/** Mock */
class RedisMock extends \Predis\Client {
    public function get() {}
}

class ClientStorageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getRightCredentials
     */
    public function testGetExistingClientStorage($data)
    {
        $redis = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\RedisMock");
        $redis->get("client:client_id")->shouldBeCalledTimes(1)->willReturn(<<<EOF
{
    "name": "app",
    "client_id": "client_id",
    "secret": "secret",
    "redirect_uris": [
        "http://localhost:8080/"
    ]
}
EOF
);
        $server = $this->prophesize("League\OAuth2\Server\AbstractServer");

        $client = new ClientStorage($redis->reveal());
        $client->setServer($server->reveal());

        $clientEntity = call_user_func_array([$client, "get"], $data);

        $this->assertInstanceOf("League\OAuth2\Server\Entity\ClientEntity", $clientEntity);
        $this->assertEquals("client_id", $clientEntity->getId());
        $this->assertEquals("app", $clientEntity->getName());
    }

    public function getRightCredentials()
    {
        return [
            [["client_id"]],
            [["client_id", "secret"]],
            [["client_id", "secret", "http://localhost:8080/"]],
        ];
    }

    public function testGetExistingClientWithWrongSecret()
    {
        $redis = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\RedisMock");
        $redis->get("client:client_id")->willReturn(<<<EOF
{
    "name": "app",
    "client_id": "client_id",
    "secret": "secret",
    "redirect_uris": [
        "http://localhost:8080/"
    ]
}
EOF
);
        $server = $this->prophesize("League\OAuth2\Server\AbstractServer");

        $client = new ClientStorage($redis->reveal());
        $client->setServer($server->reveal());

        $clientEntity = $client->get("client_id", "wrong_secret", "http://localhost:8080");

        $this->assertNull($clientEntity);
    }

    public function testGetExistingClientWithWrongRedirectUrl()
    {
        $redis = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\RedisMock");
        $redis->get("client:client_id")->willReturn(<<<EOF
{
    "name": "app",
    "client_id": "client_id",
    "secret": "secret",
    "redirect_uris": [
        "http://localhost:8080/"
    ]
}
EOF
);
        $server = $this->prophesize("League\OAuth2\Server\AbstractServer");

        $client = new ClientStorage($redis->reveal());
        $client->setServer($server->reveal());

        $clientEntity = $client->get("client_id", "secret", "http://localhost:8081/callback");

        $this->assertNull($clientEntity);
    }

    public function testGetSessions()
    {
        $redis = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\RedisMock");
        $redis->get("client:client_id")->willReturn(<<<EOF
{
    "name": "app",
    "client_id": "client_id",
    "secret": "secret",
    "redirect_uris": [
        "http://localhost:8080/"
    ]
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

        $client = new ClientStorage($redis->reveal());
        $client->setServer($server->reveal());

        $session = new SessionEntity($server->reveal());
        $session->setId("session_id");
        $clientEntity = $client->getBySession($session);

        $this->assertInstanceOf("League\OAuth2\Server\Entity\ClientEntity", $clientEntity);
        $this->assertEquals("client_id", $clientEntity->getId());
        $this->assertEquals("app", $clientEntity->getName());
    }

    public function testGetMissingSessions()
    {
        $redis = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\RedisMock");
        $redis->get("session:missing_session_id")->willReturn(null);
        $server = $this->prophesize("League\OAuth2\Server\AbstractServer");

        $client = new ClientStorage($redis->reveal());
        $client->setServer($server->reveal());

        $session = new SessionEntity($server->reveal());
        $session->setId("missing_session_id");
        $clientEntity = $client->getBySession($session);

        $this->assertNull($clientEntity);
    }
}
