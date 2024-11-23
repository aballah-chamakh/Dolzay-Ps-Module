<?php

namespace Dolzay\Apps\Processes\Entities;

use Dolzay\ModuleConfig;
use Dolzay\CustomClasses\Db\DzDb;

class Process {

    public const TABLE_NAME = ModuleConfig::MODULE_PREFIX."process";
    private const PROCESS_TYPES = ["Soumission", "Changement du zone", "Mise à jour"];
    private const STATUS_TYPES = ["Actif", "Terminé", "Bloqué"];
    
    private static $db;


    public static function init(DzDb $db) {
        self::$db = $db;
    }
    
    public static function get_create_table_sql() {

        $process_types_str = '"'.implode('","', self::PROCESS_TYPES).'"';
        $status_types_str = '"'.implode('","', self::STATUS_TYPES).'"';

        return 'CREATE TABLE IF NOT EXISTS `'.self::TABLE_NAME.'` (
            `id` INT(10) UNSIGNED AUTO_INCREMENT NOT NULL,
            `type` ENUM('.$process_types_str.') NOT NULL,
            `started_at` DATETIME NOT NULL,
            `ended_at` DATETIME NULL,
            `processed_items_cnt` SMALLINT UNSIGNED DEFAULT 0,
            `items_to_process_cnt` SMALLINT UNSIGNED NOT NULL,
            `status` ENUM('.$status_types_str.') NOT NULL,
            `error_msg` TEXT DEFAULT "",
            `meta_data` JSON,
            PRIMARY KEY(`id`)
        );';
    }
    
    public const DROP_TABLE_SQL = 'DROP TABLE IF EXISTS `'.self::TABLE_NAME.'`;';


    public static function insert(string $type , int $items_to_process_cnt, string $status, string $meta_data): int {
        $sql = "INSERT INTO " . self::TABLE_NAME . " 
            (type, started_at, items_to_process_cnt, status, meta_data) 
            VALUES (:type, :started_at, :items_to_process_cnt, :status, :meta_data)";
            
        $stmt = self::$db->prepare($sql);
        $stmt->execute([
        ':type' => $type,
        ':started_at' => date('H:i:s d-m-Y'),
        ':items_to_process_cnt' => $items_to_process_cnt,
        ':status' => $status,
        ':meta_data' => $meta_data
        ]);
        
        return (int)self::$db->lastInsertId();
    }
    
}