<?php

namespace HE\Database\Redis;

use Predis;
use Predis\Client;

class RedisManager {

    public Client $redisClient;

    const DB = [
        'localhost' => 'localhost'
    ];

    public function __construct($db){
        $this->redisClient = new Client([
            'scheme'    => 'tcp',
            'host'      => self::DB[$db],
            'port'      => 6379,
            'password' => 'supersecurepass'
        ]);
    }

}