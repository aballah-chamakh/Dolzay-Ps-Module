<?php

namespace Dolzay\Apps\OrderMonitoringProcess\Entities;

use Dolzay\ModuleConfig;
use Dolzay\CustomClasses\Db\DzDb;
use Dolzay\Apps\Settings\Entities\Carrier ;

class OrderMonitoringProcess {

    public const TABLE_NAME = ModuleConfig::MODULE_PREFIX."order_monitoring_process";
    public const STATUS_TYPES = [
                                  "Actif", // monitoring orders 
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

    public static function get_order_monitoring_process_list($query_parameter){
        
        $values = ['limit'=>$query_parameter['batch_size'],'offset'=>($query_parameter['page_nb'] - 1) * $query_parameter['batch_size']] ;
        
        // note : i did add 1=1 for the case of there is no query parameters to filter by 
        $query = "SELECT id,DATE_FORMAT(started_at, '%H:%i:%s - %d/%m/%Y') AS started_at,processed_items_cnt,items_to_process_cnt,status,COUNT(*) OVER() as total_count FROM ".self::TABLE_NAME." WHERE 1=1 " ;
        


        if ( $query_parameter['status']){
            $values['status'] = $query_parameter['status'] ;
            $query .= "AND status= :status " ;
        }

        if ($query_parameter['start_date'] && $query_parameter['end_date']){
            $values['start_date'] = $query_parameter['start_date']." 00:00:00" ;
            $values['end_date'] = $query_parameter['end_date']." 23:59:59" ;
            $query .= "AND started_at BETWEEN :start_date AND :end_date " ;
        }

        $query .= " ORDER BY id DESC LIMIT :limit OFFSET :offset ;" ;

        $stmt = self::$db->prepare($query);
        $stmt->execute($values);
        $processes = $stmt->fetchAll();
        if(count($processes) == 0){
            $values['offset'] = 0 ;
            $values['limit'] = $query_parameter['batch_size'] ;
            $stmt = self::$db->prepare($query);
            $stmt->execute($values);
            $processes = $stmt->fetchAll();
            return $processes ;
        }
        return $processes ;
    }

    public static function get_order_monitoring_process_detail($process_id,$query_parameter,$id_lang){
        
        // fech the order_monitoring_process_detail by id
        $query = "SELECT id,status,DATE_FORMAT(started_at, '%H:%i:%s - %d/%m/%Y') AS started_at,DATE_FORMAT(ended_at, '%H:%i:%s - %d/%m/%Y') AS ended_at,processed_items_cnt,items_to_process_cnt,error" ;
        $query .= " FROM ".self::TABLE_NAME." WHERE id=".$process_id ;

        $order_monitoring_process_detail = self::$db->query($query)->fetch() ;
        if(!$order_monitoring_process_detail){
            return false ;
        }
        $order_monitoring_process_detail['status_color'] = self::STATUS_COLORS[$order_monitoring_process_detail['status']] ;

        // fetch the kpis of order_monitoring_process and add them to it
        $query  = "SELECT NewStatusLang.name AS new_status,NewStatus.color AS new_status_color,COUNT(*) AS count FROM `dz_updated_order` As Uord ";
        $query .= "LEFT JOIN "._DB_PREFIX_."order_state AS NewStatus ON Uord.new_status = NewStatus.id_order_state ";
        $query .= "LEFT JOIN "._DB_PREFIX_."order_state_lang AS NewStatusLang ON NewStatus.id_order_state = NewStatusLang.id_order_state AND NewStatusLang.id_lang = ".(int)$id_lang." ";
        $query .= "GROUP BY Uord.new_status";
        $stmt = self::$db->prepare($query);
        $stmt->execute();
        $order_monitoring_process_detail['kpis'] = $stmt->fetchAll();

        if(count($order_monitoring_process_detail['kpis']) == 0){
            $order_monitoring_process_detail['updated_orders'] = [] ;
            return $order_monitoring_process_detail ;
        }

        
        // fetch the updated_orders of order_monitoring_process and add them to it
        $updated_orders = [];
        $values = ['limit'=>$query_parameter['batch_size'],'offset'=>($query_parameter['page_nb'] - 1) * $query_parameter['batch_size']] ;
        $query  = "SELECT Uord.order_id,Addr.firstname,Addr.lastname,OldStatusLang.name AS old_status,OldStatus.color AS old_status_color,NewStatusLang.name AS new_status,NewStatus.color AS new_status_color,COUNT(*) OVER() as total_count FROM ".UpdatedOrder::TABLE_NAME." AS Uord " ;
        $query .= "INNER JOIN "._DB_PREFIX_.\OrderCore::$definition['table']. " AS Ord ON Ord.id_order=Uord.order_id " ;
        $query .= "LEFT JOIN "._DB_PREFIX_.\AddressCore::$definition['table']." As Addr ON Ord.id_address_delivery=Addr.id_address ";
        $query .= "LEFT JOIN "._DB_PREFIX_."order_state AS OldStatus ON Uord.old_status = OldStatus.id_order_state ";
        $query .= "LEFT JOIN "._DB_PREFIX_."order_state_lang AS OldStatusLang ON OldStatus.id_order_state = OldStatusLang.id_order_state AND OldStatusLang.id_lang = ".(int)$id_lang." ";
        $query .= "LEFT JOIN "._DB_PREFIX_."order_state AS NewStatus ON Uord.new_status = NewStatus.id_order_state ";
        $query .= "LEFT JOIN "._DB_PREFIX_."order_state_lang AS NewStatusLang ON NewStatus.id_order_state = NewStatusLang.id_order_state AND NewStatusLang.id_lang = ".(int)$id_lang." ";
        $query .= "WHERE Uord.omp_id=$process_id" ;

        if($query_parameter['order_id']){
            $query .= " AND Ord.id_order=:order_id" ;
            $values['order_id'] = $query_parameter['order_id'] ;
        }

        if($query_parameter['client']){
            $query .= " AND CONCAT(firstname, ' ', lastname) LIKE :client" ; 
            $values['client'] = "%".$query_parameter['client']."%";
        }

        if($query_parameter['new_status']){
            $query .= " AND Uord.new_status=:new_status" ; 
            $values['new_status'] = $query_parameter['new_status'] ;
        }

        if($query_parameter['old_status']){
            $query .= " AND Uord.old_status=:old_status" ; 
            $values['old_status'] = $query_parameter['old_status'] ;
        }

        $query .= " LIMIT :limit OFFSET :offset ;" ;
        $stmt = self::$db->prepare($query);
        $stmt->execute($values);
        $updated_orders = $stmt->fetchAll();
        if(count($updated_orders) == 0){
            $values['offset'] = 0 ;
            $values['limit'] = $query_parameter['batch_size'] ;
            $stmt = self::$db->prepare($query);
            $stmt->execute($values);
            $updated_orders = $stmt->fetchAll();
        }

        $order_monitoring_process_detail['updated_orders'] = $updated_orders ;
        return $order_monitoring_process_detail ;
    }

}