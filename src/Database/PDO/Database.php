<?php

namespace HE\Database\PDO;

use PDO;
use PDOException;

class Database{
	private PDO $connection;

    /**
     * @param string $username
     * @param string $password
     * @param string $host
     * @param string $dbName
     * @param string $connectionType
    */
	public function __construct($username = 'root', $password = '', $host = 'localhost', $dbName = 'dbname', $connectionType = 'mysql'){
		try{
			$this->connection = new PDO("$connectionType:host=$host;port=3306;dbname=$dbName;charset=utf8mb4",$username,$password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_OBJ);
            $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		}catch(PDOException $e){
			echo $e->getMessage();
		}
	}

    /**
     * @param string $query
     * @param array<mixed> $params
    */
	public function insert($query ,$params = []): string|bool{
		$this->executeStatement($query,$params);
		return $this->connection->lastInsertId();
	}

    /**
     * @param string $query
     * @param array<mixed> $params
    */
	public function select($query, $params = []){
		$statement = $this->executeStatement($query,$params);
		return $statement->fetchAll();
	}

    /**
     * @param string $query
     * @param array<mixed> $params
    */
	public function update($query, $params = []){
		$this->executeStatement($query,$params);
	}

    /**
     * @param string $query
     * @param array<mixed> $params
    */
	public function remove($query, $params = []){
		$this->executeStatement($query,$params);
	}

    /**
     * @param array<mixed> $data
    */
    public function createJoinedParameters($data){
        $newData = [];

        foreach($data as $key => $value){
            $parts = explode('.',$key);

            $table = $parts[0];
            $column = $parts[1];

            $newData[$column] = $value;
        }

        return $newData;
    }
    
    /**
     * @param array<mixed> $data
     * @param array<mixed> $excludedParameters
    */
    public function createParameterString($data,$excludedParameters = []){
        $parameterString = "";
        
        foreach($data as $key => $value){
            if(!in_array($key,$excludedParameters)){
                if(strpos($key,'.') !== false){
                    $parts = explode('.',$key);

                    $table = $parts[0];
                    $column = $parts[1];

                    $parameterString .= "$key=:$column,";
                }else{
                    $parameterString .= "$key=:$key,";
                }
            }
        }
        
        return substr($parameterString,0,-1);
    }

    /**
     * @param string $query
     * @param array<mixed> $params
    */
	private function executeStatement($query,$params = []){
		$statement = $this->connection->prepare($query);
        
        try{
            $statement->execute($params);
        } catch (PDOException $e){
            error_log($e);
            error_log(print_r($params));
            error_log($query);
            return;
        }

        return $statement;
	}
}

?>
