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

    public static function get_order_monitoring_process_detail($process_id,$query_parameter){
        
        $query = "SELECT status,DATE_FORMAT(started_at, '%H:%i:%s - %d/%m/%Y') AS started_at,DATE_FORMAT(ended_at, '%H:%i:%s - %d/%m/%Y') AS ended_at,processed_items_cnt,items_to_process_cnt,error" ;
        $query .= " FROM ".self::TABLE_NAME." WHERE id=".$process_id ;

        $order_monitoring_process_detail = self::$db->query($query)->fetch() ;
        if(!$order_monitoring_process_detail){
            return false ;
        }
        // add the orders_to_submit to order_monitoring_process_detail
        $order_monitoring_process_detail["error"] = json_decode($order_monitoring_process_detail["error"],true);
        $order_ids = ($order_monitoring_process_detail["updated_orders"]) ? $order_monitoring_process_detail["meta_data"]['valid_order_ids'] : [];
        $orders_to_submit = [];
        if(count($order_ids)){
            $values = ['limit'=>$query_parameter['batch_size'],'offset'=>($query_parameter['page_nb'] - 1) * $query_parameter['batch_size']] ;
            $query = "SELECT id_order,firstname,lastname,submitted,COUNT(*) OVER() as total_count FROM ".UpdatedOrder::TABLE_NAME." AS Uord " ;
            $query .= "INNER JOIN "._DB_PREFIX_.\OrderCore::$definition['table']. " AS Ord ON Ord.id_address_delivery=Addr.id_address" ;
            $query .= "INNER JOIN  WHERE id_order IN  (".implode(',',$order_ids).")" ;

            if($query_parameter['order_id']){
                $query .= " AND Ord.id_order=:order_id" ;
                $values['order_id'] = $query_parameter['order_id'] ;
            }
            if($query_parameter['submitted']){
                $query .= " AND Ord.submitted=:submitted" ;
                $values['submitted'] = ($query_parameter['submitted'] == "Oui") ? true : false ;
            }
            if($query_parameter['client']){
                $query .= " AND CONCAT(firstname, ' ', lastname) LIKE :client" ; 
                $values['client'] = "%".$query_parameter['client']."%";
            }
            $query .= " LIMIT :limit OFFSET :offset ;" ;
            $stmt = self::$db->prepare($query);
            $stmt->execute($values);
            $orders_to_submit = $stmt->fetchAll();
            if(count($orders_to_submit) == 0){
                $values['offset'] = 0 ;
                $values['limit'] = $query_parameter['batch_size'] ;
                $stmt = self::$db->prepare($query);
                $stmt->execute($values);
                $orders_to_submit = $stmt->fetchAll();
            }
        }

        $order_monitoring_process_detail['orders_to_submit'] = $orders_to_submit ;
        $order_monitoring_process_detail['status_color'] = self::STATUS_COLORS[$order_monitoring_process_detail['status']] ;
        return $order_monitoring_process_detail ;
    }

}