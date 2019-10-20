<?php

class PDO_DB {

    public $dbh; 
    private static $dsn1  = 'mysql:host=REDACTED';
    private static $dsn2  = ';port=3306;dbname=REDACTED';
    private static $user = 'REDACTED'; 
    private static $pass = 'REDACTED'; 
    private static $dbOptions = array(
        //PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_CASE => PDO::CASE_LOWER,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION //TODO: remove this line on production (maybe not, just hide php errors, so I can see logs)
    );

    public static function factory() { 
        
        //$sock = '/var/run/mysql/mysql.sock'; //localhost
        // $sock = '/var/lib/mysql/mysql.sock';
            
        if(!isset(self::$dbh)){
            $dbh = new PDO(self::$dsn1.self::$dsn2,self::$user,self::$pass, self::$dbOptions); 
        }
        return $dbh;
    }
    
}

?>
