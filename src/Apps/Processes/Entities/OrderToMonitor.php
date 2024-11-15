<?php

namespace Dolzay\Apps\Processes\Entities;

use Dolzay\ModuleConfig;
use Dolzay\CustomClasses\Db\DzDb;


class OrderToMonitor {
    public const TABLE_NAME = ModuleConfig::MODULE_PREFIX."order_to_monitor";
    private const STATES = ["Active", "Dormante", "Non trouvée"];
    
    private static $db;
    
    public static function get_create_table_sql() {
        
        $states_str = '"'.implode('","', self::STATES).'"';

        return 'CREATE TABLE IF NOT EXISTS `'.self::TABLE_NAME.'` (
            `order_id` INT(10) UNSIGNED NOT NULL,
            `process_id` INT(10) UNSIGNED NOT NULL,
            `carrier_name` VARCHAR(255) NOT NULL,
            `old_status` VARCHAR(50) NULL,
            `new_status` VARCHAR(50) NULL,
            `state` ENUM('. $states_str. ') NOT NULL,
            `updated` BOOLEAN DEFAULT FALSE,
            PRIMARY KEY(`order_id`, `process_id`),
            FOREIGN KEY (`process_id`) REFERENCES `'.Process::TABLE_NAME.'` (`id`) ON DELETE CASCADE
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;';
    }
    
    
    public const DROP_TABLE_SQL = 'DROP TABLE IF EXISTS `'.self::TABLE_NAME.'`;';
}