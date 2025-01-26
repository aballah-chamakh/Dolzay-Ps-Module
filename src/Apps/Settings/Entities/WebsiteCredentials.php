<?php

namespace Dolzay\Apps\Settings\Entities ;


use Dolzay\ModuleConfig ;

class WebsiteCredentials {

    const TABLE_NAME = ModuleConfig::MODULE_PREFIX."website_credentials" ;
    public static $db ;
    public static $employee_id ;
    // START DEFINING get_create_table_sql
    public static function get_create_table_sql() {
        return 'CREATE TABLE IF NOT EXISTS `' . self::TABLE_NAME . '` (
                `id` INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `email` VARCHAR(255)  NULL UNIQUE,
                `password` VARCHAR(255)  NULL
            ) ;';
    }
    // END DEFINING get_create_table_sql

    public const DROP_TABLE_SQL = 'DROP TABLE IF EXISTS `' . self::TABLE_NAME . '`;';

}