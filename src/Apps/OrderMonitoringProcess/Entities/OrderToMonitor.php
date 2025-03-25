<?php

namespace Dolzay\Apps\OrderMonitoringProcess\Entities;

use Dolzay\ModuleConfig;
use Dolzay\CustomClasses\Db\DzDb;
use Dolzay\Apps\Settings\Entities\Carrier ;


// TODO :  recheck it later 

class OrderToMonitor {

    public const TABLE_NAME = ModuleConfig::MODULE_PREFIX."order_to_monitor";

    private static $db;

    public static function init($db){
        self::$db = $db;
    }
    
    // START DEFINING get_create_table_sql
    public static function get_create_table_sql() {
        
        return 'CREATE TABLE IF NOT EXISTS `'.self::TABLE_NAME.'` (
            `carrier` VARCHAR(50) NOT NULL,
            `order_id` INT(10) UNSIGNED UNIQUE NOT NULL ,
            `carrier_order_ref` VARCHAR(50) NOT NULL,
             FOREIGN KEY (`carrier`) REFERENCES `'.Carrier::TABLE_NAME.'` (`name`) ON DELETE CASCADE
            ,FOREIGN KEY (`order_id`) REFERENCES `' . _DB_PREFIX_.\OrderCore::$definition['table'] . '` (`id_order`) ON DELETE CASCADE
        );';

    }
    // END DEFINING get_create_table_sql
    
    public const DROP_TABLE_SQL = 'DROP TABLE IF EXISTS `'.self::TABLE_NAME.'`;';


    public static function afexToPrestaStateConverter($afexOrderState) {

        $afexToPrestaState = [
            'pre_manifest' => 3, // 'En cours de préparation'
            'awaiting_removal' => 3, // 'En cours de préparation'
            'delivered' => 5, // 'Livré'
            'returned' => 7, // 'Retour'
            'canceled' => 6, // 'Annulé'
            'pre_shipping_canceling' => 14, //'Annulé'
        ];
    
        return $afexToPrestaState[$afexOrderState] ?? 4 ; // Expidié 
    }

    public static function getOrdersToMonitorCount(){
        $query = "SELECT COUNT(*) FROM ".self::TABLE_NAME ;
        return self::$db->query($query)->fetchColumn();
    }


    
}