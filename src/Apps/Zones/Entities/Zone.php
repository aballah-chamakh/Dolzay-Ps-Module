<?php

namespace Dolzay\Apps\Zones\Entities ;  

use Dolzay\ModuleConfig;
use Dolzay\CustomClasses\Db\DzDb;

class Zone {
    public const TABLE_NAME = ModuleConfig::MODULE_PREFIX . "zone";

    private static $db;

    public static function init(DzDb $db) {
        self::$db = $db;
    }

    public static function get_create_table_sql() {
        return "CREATE TABLE IF NOT EXISTS `" . self::TABLE_NAME . "` (
            `id` INT(10) UNSIGNED AUTO_INCREMENT NOT NULL,
            `name` VARCHAR(255) NOT NULL,
            `batch_size` INT(10) UNSIGNED NOT NULL,
            PRIMARY KEY (`id`)
        );";
    }

    public const DROP_TABLE_SQL = 'DROP TABLE IF EXISTS `' . self::TABLE_NAME . '`;';
}