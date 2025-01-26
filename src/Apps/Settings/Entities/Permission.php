<?php

namespace Dolzay\Apps\Settings\Entities ;

use Dolzay\ModuleConfig ;


class Permission {

    public const TABLE_NAME = ModuleConfig::MODULE_PREFIX."permission" ;
    // START DEFINING get_create_table_sql
    public static function get_create_table_sql() {
        return 'CREATE TABLE IF NOT EXISTS `' . self::TABLE_NAME . '` (
        `id` INT(10) UNSIGNED AUTO_INCREMENT NOT NULL,
        `name` VARCHAR(255) NOT NULL UNIQUE,
        PRIMARY KEY(`id`) 
        );' ;
    }
    // END DEFINING get_create_table_sql

    public const DROP_TABLE_SQL = 'DROP TABLE IF EXISTS `' . self::TABLE_NAME . '`;';



}