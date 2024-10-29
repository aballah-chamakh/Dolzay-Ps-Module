<?php

namespace Dolzay\Apps\Settings\Entities ;




class Employee {

    private static $db;

    public static function init($db) {
        self::$db = $db;
    }

    public static function delete($employee_id) {
        $query = "DELETE FROM `" . _DB_PREFIX_.\EmployeeCore::$definition['table'] . "` WHERE id_employee = :employee_id";
        $stmt = self::$db->prepare($query);
        $stmt->bindParam(':employee_id', $employee_id);
        $stmt->execute();
    }


}