<?php
namespace Corley\OAuth2\Server\Storage\Redis;

/** Mock */
class RedisMock extends \Predis\Client {
    public function get() {}
    public function set() {}
    public function incr() {}
    public function transaction() {}
    public function execute() {}
    public function lrange() {}
    public function lpush() {}
    public function del() {}
}
