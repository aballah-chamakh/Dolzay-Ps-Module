<?php

namespace Dolzay\Apps\Settings\Entities ;




class Employee {

    private static $db;
    private static $id;

    public static function init($db,$id) {
        self::$db = $db;
        self::$id = $id ;

    }

    public static function delete() {
        $query = "DELETE FROM `" . _DB_PREFIX_.\EmployeeCore::$definition['table'] . "` WHERE id_employee = :employee_id";
        $stmt = self::$db->prepare($query);
        $stmt->bindParam(':employee_id', self::$id);
        $stmt->execute();
    }

    public static function does_it_exist(){
        $query = "SELECT COUNT(*) FROM `" . _DB_PREFIX_.\EmployeeCore::$definition['table'] . "` WHERE id_employee = :employee_id";
        $stmt = self::$db->prepare($query);
        $stmt->bindParam(':employee_id', self::$id);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        return $count > 0;
    }

    public static function get_permissions(){

        $query = "SELECT permission_id FROM `" . EmployeePermission::TABLE_NAME . "` WHERE employee_id = :employee_id";
        $stmt = self::$db->prepare($query);
        $stmt->bindParam(':employee_id', self::$id);
        $stmt->execute();
        $permission_ids = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        return $permission_ids;
    }

    public static function has_permission(string $permission_name): bool {
        $query = "SELECT COUNT(*) FROM `" . EmployeePermission::TABLE_NAME . "` ep
                  INNER JOIN permissions p ON ep.permission_id = p.id 
                  WHERE ep.employee_id = :employee_id 
                  AND p.name = :permission_name";
                  
        $stmt = self::$db->prepare($query);
        $stmt->bindParam(':employee_id', self::$id);
        $stmt->bindParam(':permission_name', $permission_name);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }




}