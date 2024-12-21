<?php

namespace Dolzay\Apps\Settings\Entities ;


use Dolzay\ModuleConfig ;

class Settings {

    const TABLE_NAME = ModuleConfig::MODULE_PREFIX."settings" ;
    public static $db ;
    public static $employee_id ;

    public static function get_create_table_sql() {
        return 'CREATE TABLE IF NOT EXISTS `' . self::TABLE_NAME . '` (
                `id` INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `license_key` VARCHAR(255) NOT NULL,
                `license_type` VARCHAR(255) NOT NULL,
                `subscription_started` DATETIME NOT NULL,
                `subscription_ended` DATETIME NOT NULL,
                `post_submit_state_id` INT(10) UNSIGNED NULL,
                 FOREIGN KEY (`post_submit_state_id`) REFERENCES `' . _DB_PREFIX_.\OrderStateCore::$definition['table'] .'` (`id_order_state`) ON DELETE SET NULL
            ) ;';
    }


    public const DROP_TABLE_SQL = 'DROP TABLE IF EXISTS `' . self::TABLE_NAME . '`;';

}