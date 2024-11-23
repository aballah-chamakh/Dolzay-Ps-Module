<?php

namespace Dolzay\Apps\Zones\Entities ;  


use Dolzay\ModuleConfig;
use Dolzay\CustomClasses\Db\DzDb;

class Delegation {
    public const TABLE_NAME = ModuleConfig::MODULE_PREFIX . "delegation";

    private static $db;

    public static function init(DzDb $db) {
        self::$db = $db;
    }

    public static function get_create_table_sql() {
        return "CREATE TABLE IF NOT EXISTS `" . self::TABLE_NAME . "` (
            `id` INT(10) UNSIGNED AUTO_INCREMENT NOT NULL,
            `city_id` INT(10) UNSIGNED NOT NULL,
            `zone_id` INT(10) UNSIGNED  NULL,
            `name` VARCHAR(255) NOT NULL,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`city_id`) REFERENCES `" . City::TABLE_NAME . "` (`id`) ON DELETE CASCADE,
            FOREIGN KEY (`zone_id`) REFERENCES `" . Zone::TABLE_NAME . "` (`id`) ON DELETE SET NULL
        );";
    }

    public const DROP_TABLE_SQL = 'DROP TABLE IF EXISTS `' . self::TABLE_NAME . '`;';
}