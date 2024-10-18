<?php

namespace Dolzay\CustomClasses\Db ;  

use PDO;

class DzDb {




    public static function getInstance() {
            
                $host = _DB_SERVER_;
                $dbname = _DB_NAME_;
                $username = _DB_USER_;
                $password = _DB_PASSWD_;
                $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

                $conn = new PDO($dsn, $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                $conn->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
                $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                $conn->exec("SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ");
                return $conn;
                
    }


    
}
