<?php

namespace Dolzay\Apps\Notifications\Entities ;

use Dolzay\ModuleConfig ;

class ProfilePermission {

    const TABLE_NAME = "profile_permission" ;


    public const CREATE_TABLE_SQL = 'CREATE TABLE IF NOT EXISTS `' . ModuleConfig::MODULE_PREFIX . self::TABLE_NAME . '` (
        `employee_id` INT(10) UNSIGNED NOT NULL,
        `permission_id` INT(10) UNSIGNED NOT NULL,
        PRIMARY KEY(`employee_id`, `permission_id`),
        FOREIGN KEY (`employee_id`) REFERENCES `' . _DB_PREFIX_ . 'employee`(`id_employee`) ON DELETE CASCADE,
        FOREIGN KEY (`permission_id`) REFERENCES `' . ModuleConfig::MODULE_PREFIX . 'permission`(`id`) ON DELETE CASCADE
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;';


    public const DROP_TABLE_SQL = 'DROP TABLE IF EXISTS `' . ModuleConfig::MODULE_PREFIX . self::TABLE_NAME . '`;';


}