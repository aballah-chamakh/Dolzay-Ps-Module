<?php

namespace Dolzay\Apps\Notifications\Entities ;

use Dolzay\ModuleConfig ;


class NotificationPoppedUpBy {

    public const TABLE_NAME = ModuleConfig::MODULE_PREFIX ."notification_popped_up_by" ;


    public static function get_create_table_sql() {
        return 'CREATE TABLE IF NOT EXISTS `' . self::TABLE_NAME . '` (
        `employee_id` INT(10) UNSIGNED NOT NULL,
        `notif_id` INT(10) UNSIGNED NOT NULL,
        PRIMARY KEY(`employee_id`, `notif_id`),
        FOREIGN KEY (`notif_id`) REFERENCES `' . Notification::TABLE_NAME . '` (`id`) ON DELETE CASCADE,
        FOREIGN KEY (`employee_id`) REFERENCES `' . _DB_PREFIX_ . \EmployeeCore::$definition['table'] . '` (`id_employee`) ON DELETE CASCADE
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;' ;
    }

    public const DROP_TABLE_SQL = 'DROP TABLE IF EXISTS `' . self::TABLE_NAME . '`;';



}