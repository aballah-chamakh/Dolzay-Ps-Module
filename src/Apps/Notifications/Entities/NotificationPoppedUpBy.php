<?php

namespace Dolzay\Apps\Notifications\Entities ;

use Dolzay\ModuleConfig ;


class NotificationPoppedUpBy {

    private const TABLE_NAME = "notification_popped_up_by" ;



    public const CREATE_TABLE_SQL = 'CREATE TABLE IF NOT EXISTS `' . ModuleConfig::MODULE_PREFIX . self::TABLE_NAME . '` (
        `employee_id` INT(10) UNSIGNED NOT NULL,
        `notif_id` INT(10) UNSIGNED NOT NULL,
        PRIMARY KEY(`employee_id`, `notif_id`),
        FOREIGN KEY (`notif_id`) REFERENCES `' . ModuleConfig::MODULE_PREFIX . 'notification`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`employee_id`) REFERENCES `' ._DB_PREFIX_. 'employee`(`id_employee`) ON DELETE CASCADE
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;' ;


    public const DROP_TABLE_SQL = 'DROP TABLE IF EXISTS `' . ModuleConfig::MODULE_PREFIX . self::TABLE_NAME . '`;';



}