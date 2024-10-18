<?php

namespace Dolzay\Apps\Settings\Entities ;

use Dolzay\ModuleConfig ;


class Permission {

    private const TABLE_NAME = "permission" ;



    public const CREATE_TABLE_SQL = 'CREATE TABLE IF NOT EXISTS `' . ModuleConfig::MODULE_PREFIX . self::TABLE_NAME . '` (
        `id` INT(10) UNSIGNED AUTO_INCREMENT NOT NULL,
        `name` VARCHAR(255) NOT NULL UNIQUE,
        PRIMARY KEY(`id`) 
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;' ;


    public const DROP_TABLE_SQL = 'DROP TABLE IF EXISTS `' . ModuleConfig::MODULE_PREFIX . self::TABLE_NAME . '`;';



}