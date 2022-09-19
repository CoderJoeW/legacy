<?php

namespace HE\Database\PDO;

use HE\Database\PDO\DatabaseConstants;
use HE\Database\PDO\Database;

class Model{ 
    public static function getInstance($type){
        $credentials = DatabaseConstants::DB_CREDENTIALS[$type];
        
        return new Database($credentials['username'], $credentials['password'], $credentials['host'], $credentials['db']);
    }
}
?>