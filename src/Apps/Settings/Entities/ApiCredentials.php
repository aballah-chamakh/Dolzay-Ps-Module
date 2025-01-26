<?php

namespace Dolzay\Apps\Settings\Entities ;


use Dolzay\ModuleConfig ;

class ApiCredentials {

    const TABLE_NAME = ModuleConfig::MODULE_PREFIX."api_credentials" ;
    // START DEFINING get_create_table_sql
    public static function get_create_table_sql() {
        return 'CREATE TABLE IF NOT EXISTS `' . self::TABLE_NAME . '` (
                `id` INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `user_id` VARCHAR(255)  NULL,
                `token` VARCHAR(255)  NULL,
                `is_user_id_required` BOOLEAN DEFAULT FALSE
            ); ';
    }
    // END DEFINING get_create_table_sql

    public const DROP_TABLE_SQL = 'DROP TABLE IF EXISTS `' . self::TABLE_NAME . '`;';

}