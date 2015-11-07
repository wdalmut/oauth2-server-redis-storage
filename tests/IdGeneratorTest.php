<?php
namespace Corley\OAuth2\Server\Storage\Redis;

class IdGeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testIdGeneration()
    {
        $redis = $this->prophesize("Corley\\OAuth2\\Server\\Storage\\Redis\\RedisMock");
        $redis->transaction()->willReturn($redis->reveal());
        $redis->incr("oauth:ids")->willReturn($redis->reveal());
        $redis->execute()->willReturn([1]);

        $idGen = new IdGenerator($redis->reveal());
        $id = $idGen->createId();

        $this->assertInternalType("int", $id);
        $this->assertSame(1, $id);
    }
}
