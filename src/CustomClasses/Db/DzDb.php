<?php

namespace Dolzay\CustomClasses\Db ;  

use PDO;

class DzDb {




    public static function getInstance() {
            
               /* 
                $host = "127.0.0.1:3306";
                $dbname = "prestashop";
                $username = "root";
                $password = "";
                */

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
/*
$db = DzDb::getInstance() ;

$db->beginTransaction();

$stmt = $db->prepare("INSERT INTO `dz_notification_viewed_by` (`employee_id`,`notif_id`) VALUES (:emp_id,:notif_id)") ;

try{
    $stmt->execute(['emp_id' => 100, 'notif_id' => 100]);

}catch(\PDOException $e){
    if ($e->getCode() == 23000) {
        if (strpos($e->getMessage(), '1452') !== false) {
            // Handle the foreign key constraint violation
            echo "Foreign key constraint violation: Cannot insert a record with a non-existent foreign key.";
        } else if (strpos($e->getMessage(), '1062') !== false) {
            // Handle the unique constraint violation
            echo "Unique constraint violation: Cannot insert a duplicate record.";
        } 

    } else {
        // Re-throw the exception if it's not a constraint violation
        throw $e;
    }
}

$db->commit();
*/