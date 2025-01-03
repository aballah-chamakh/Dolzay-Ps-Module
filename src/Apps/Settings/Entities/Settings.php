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
                `license_key` VARCHAR(255)  NULL,
                `license_type` VARCHAR(255)  NULL,
                `subscription_started` DATETIME  NULL,
                `subscription_ended` DATETIME  NULL,
                `post_submit_state_id` INT(10) UNSIGNED NULL 
            );';
    }


    public const DROP_TABLE_SQL = 'DROP TABLE IF EXISTS `' . self::TABLE_NAME . '`;';

}