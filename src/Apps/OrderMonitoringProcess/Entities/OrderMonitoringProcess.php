<?php

namespace Dolzay\Apps\OrderMonitoringProcess\Entities;

use Dolzay\ModuleConfig;
use Dolzay\CustomClasses\Db\DzDb;
use Dolzay\Apps\Settings\Entities\Carrier ;

class OrderMonitoringProcess {

    public const TABLE_NAME = ModuleConfig::MODULE_PREFIX."order_monitoring_process";
    public const STATUS_TYPES = [
                                  "Actif", // submitting orders 
                                   "Interrompu", // interrupted by the user
                                   "Terminé"];

    public const STATUS_COLORS = [
        "Actif" => "green",   // Lime Green - Ongoing activity (Active).
        "Interrompu" => "red",  // Tomato Red - Interrupted.
        "Terminé" => "gray"   // Indigo - Final completion.
    ];    

    private static $db;

    public static function init($db){
        self::$db = $db;
    }

    // START DEFINING get_create_table_sql
    public static function get_create_table_sql() {

        $status_types_str = '"'.implode('","', self::STATUS_TYPES).'"';

        return 'CREATE TABLE IF NOT EXISTS `'.self::TABLE_NAME.'` (
            `id` INT(10) UNSIGNED AUTO_INCREMENT NOT NULL,
            `started_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `ended_at` DATETIME NULL,
            `processed_items_cnt` SMALLINT UNSIGNED DEFAULT 0,
            `items_to_process_cnt` SMALLINT UNSIGNED  NULL,
            `status` ENUM('.$status_types_str.') DEFAULT "Actif",
            `error` JSON NULL,
             PRIMARY KEY(`id`)
        );';
    }
    // END DEFINING get_create_table_sql
    public const DROP_TABLE_SQL = 'DROP TABLE IF EXISTS `'.self::TABLE_NAME.'`;';

    public static function insert($items_to_process_cnt): int {
        $query = "INSERT INTO ".self::TABLE_NAME." (items_to_process_cnt) VALUES($items_to_process_cnt);";
        self::$db->query($query);
        return (int)self::$db->lastInsertId();
    }

    public static function get_process_status(int $process_id){
        $query = "SELECT processed_items_cnt,items_to_process_cnt,status,error FROM ".self::TABLE_NAME." WHERE id=".$process_id ;
        $stmt = self::$db->query($query) ;
        $process = $stmt->fetch();
        if ($process['error']){
            $process['error'] = json_decode($process['error'],true);
        }
        return $process ;
    }

}