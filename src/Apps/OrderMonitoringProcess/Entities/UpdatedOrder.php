<?php

namespace Dolzay\Apps\OrderMonitoringProcess\Entities;

use Dolzay\ModuleConfig;
use Dolzay\CustomClasses\Db\DzDb;


class UpdatedOrder {

    public const TABLE_NAME = ModuleConfig::MODULE_PREFIX."updated_order";

    private static $db;

    public static function init($db){
        self::$db = $db;
    }
    
    // START DEFINING get_create_table_sql
    public static function get_create_table_sql() {
        
        return 'CREATE TABLE IF NOT EXISTS `'.self::TABLE_NAME.'` (
            `omp_id` INT(10) UNSIGNED  NOT NULL ,
            `order_id` INT(10) UNSIGNED  NOT NULL ,
            `old_status` INT(10) UNSIGNED  NOT NULL,
            `new_status` INT(10) UNSIGNED  NOT NULL,
            `error_type` VARCHAR(60) NULL,
            `error_detail` JSON NULL,
             PRIMARY KEY (omp_id, order_id)
        );';

    }
    // END DEFINING get_create_table_sql
    
    public const DROP_TABLE_SQL = 'DROP TABLE IF EXISTS `'.self::TABLE_NAME.'`;';

    public static function insert($omp_id, $order_id, $old_status, $new_status): int {
        $query = "INSERT INTO ".self::TABLE_NAME." (omp_id, order_id, old_status, new_status) VALUES($omp_id, $order_id, $old_status, $new_status);";
        self::$db->query($query);
        return (int)self::$db->lastInsertId();
    }
}