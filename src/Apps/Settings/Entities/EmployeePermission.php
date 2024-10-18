<?php

namespace Dolzay\Apps\Settings\Entities ;

use Dolzay\ModuleConfig ;

class EmployeePermission {

    const TABLE_NAME = "employee_permission" ;
    public static $name = "default" ;


    public const CREATE_TABLE_SQL = 'CREATE TABLE IF NOT EXISTS `' . ModuleConfig::MODULE_PREFIX . self::TABLE_NAME . '` (
        `employee_id` INT(10) UNSIGNED NOT NULL,
        `permission_id` INT(10) UNSIGNED NOT NULL,
        PRIMARY KEY(`employee_id`, `permission_id`),
        FOREIGN KEY (`employee_id`) REFERENCES `' . _DB_PREFIX_ . 'employee`(`id_employee`) ON DELETE CASCADE,
        FOREIGN KEY (`permission_id`) REFERENCES `' . ModuleConfig::MODULE_PREFIX . 'permission`(`id`) ON DELETE CASCADE
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;';


    public const DROP_TABLE_SQL = 'DROP TABLE IF EXISTS `' . ModuleConfig::MODULE_PREFIX . self::TABLE_NAME . '`;';

    public static function init($db, $employee_id)
    {
        self::$db = $db;
        self::$employee_id = $employee_id;
    }


    public static function get_permissions()
    {
        $query = "SELECT permission_id FROM `" . ModuleConfig::MODULE_PREFIX . "employee_permission` WHERE employee_id = :employee_id";
        $stmt = self::$db->prepare($query);
        $stmt->bindParam(':employee_id', self::$employee_id, \PDO::PARAM_INT);
        $stmt->execute();
        $permission_ids = $stmt->fetchAll();

        return $permission_ids;
    }

}