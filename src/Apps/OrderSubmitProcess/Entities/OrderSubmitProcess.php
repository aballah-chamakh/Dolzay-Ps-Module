<?php

namespace Dolzay\Apps\OrderSubmitProcess\Entities;

use Dolzay\ModuleConfig;
use Dolzay\CustomClasses\Db\DzDb;
use Dolzay\Apps\Settings\Entities\Carrier ;

class OrderSubmitProcess {

    public const TABLE_NAME = ModuleConfig::MODULE_PREFIX."order_submit_process";
    private const PROCESS_TYPES = ["Soumission", "Changement du zone", "Mise à jour"];
    public const STATUS_TYPES = ["Initié", // has just been created 
                                  "Actif", // submitting orders 
                                  "Pre-terminé par l'utilisateur", // the user requested to submit the orders
                                  "Terminé par l'utilisateur", // the osp accepted the terminate request of the user 
                                   "Interrompu", // interrupted by the user
                                   "Annulé par l'utilisateur", // canceled by the user  
                                   "Annulé automatiquement", // canceled automatically because there is valid order to submit after v
                                   "Terminé"];
    public const ACTIVE_STATUSES = [
        "Initié", 
        "Actif"
    ];
    public const STATUS_COLORS = [
        "Initié" => "#FFD700",  // Gold - Start of something important (Initiated).
        "Actif" => "green",   // Lime Green - Ongoing activity (Active).
        "Pre-terminé par l'utilisateur" => "orange",  // Orange - Near completion by user (Pre-completed).
        "Terminé par l'utilisateur" => "gray",     // Dodger Blue - Completed by user.
        "Interrompu" => "red",  // Tomato Red - Interrupted.
        "Annulé par l'utilisateur" => "gray",  // Orange-Red - Canceled by user.
        "Annulé automatiquement" => "gray", // Crimson - Automatically canceled.
        "Terminé" => "gray"   // Indigo - Final completion.
    ];    

    private const CITIES = [
        "Ariana",
        "Beja",
        "Ben Arous",
        "Bizerte",
        "Gabes",
        "Gafsa",
        "Jendouba",
        "Kairouan",
        "Kasserine",
        "Kebili",
        "La Manouba",
        "Le Kef",
        "Mahdia",
        "Medenine",
        "Monastir",
        "Nabeul",
        "Sfax",
        "Sidi Bouzid",
        "Siliana",
        "Sousse",
        "Tataouine",
        "Tozeur",
        "Tunis",
        "Zaghouan"
    ];

    // Why I added the status: "Pre-terminé par l'utilisateur"
    // Problematic case caused by directly setting the status of the obs 
    // to "Terminé par l'utilisateur" when the user requests to terminate the obs:
    // The client-side monitor tracking the progress of the obs gets the status "Terminé par l'utilisateur," 
    // which triggers a finish popup to open. 
    // However, the current order being processed by the obs may encounter an exception 
    // or might be the last one processed, which would override the status of the obs to 
    // "Interrompu" or "Terminé."
    // By adding the "Pre-terminé par l'utilisateur" status, the obs will check after submitting each 
    // order whether the user has requested to terminate the obs by evaluating if its status == "Pre-terminé par l'utilisateur."
    // If so, it will then set its status to "Terminé par l'utilisateur."

    private static $db;

    public static function init($db) {
        self::$db = $db;
    }
    
    public static function get_create_table_sql() {

        $process_types_str = '"'.implode('","', self::PROCESS_TYPES).'"';
        $status_types_str = '"'.implode('","', self::STATUS_TYPES).'"';

        return 'CREATE TABLE IF NOT EXISTS `'.self::TABLE_NAME.'` (
            `id` INT(10) UNSIGNED AUTO_INCREMENT NOT NULL,
            `carrier` VARCHAR(255) NOT NULL,
            `started_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `ended_at` DATETIME NULL,
            `processed_items_cnt` SMALLINT UNSIGNED DEFAULT 0,
            `items_to_process_cnt` SMALLINT UNSIGNED  NULL,
            `status` ENUM('.$status_types_str.') DEFAULT "Initié",
            `error` JSON NULL,
            `meta_data` JSON NULL,
             PRIMARY KEY(`id`),
             FOREIGN KEY (`carrier`) REFERENCES `'.Carrier::TABLE_NAME.'` (`name`) ON DELETE CASCADE
        );';
    }

    // INSERT INTO `ps_order_submit_process` (`items_to_process_cnt`, `status`) VALUES (100, 'Initié');
    
    public const DROP_TABLE_SQL = 'DROP TABLE IF EXISTS `'.self::TABLE_NAME.'`;';

    
    public static function get_process(int $process_id, bool $lock_it=false){
        $query = "SELECT id,carrier,status,meta_data FROM ".self::TABLE_NAME." WHERE id=".$process_id ;
        if($lock_it){
            $query .= " FOR UPDATE" ;
        }
        $stmt = self::$db->query($query) ;
        $process = $stmt->fetch();
        return $process ;
    }

    public static function get_process_status(int $process_id){
        $query = "SELECT processed_items_cnt,items_to_process_cnt,status,error,carrier FROM ".self::TABLE_NAME." WHERE id=".$process_id ;
        $stmt = self::$db->query($query) ;
        $process = $stmt->fetch();
        if ($process['error']){
            $process['error'] = json_decode($process['error'],true);
        }
        return $process ;
    }

    public static function get_running_process(){
        $query = "SELECT id FROM ".self::TABLE_NAME." WHERE status IN ('Initié','Actif')" ;
        $stmt = self::$db->query($query) ;
        $process = $stmt->fetch();
        return $process === false ? null : $process;
    }

    public static function insert(string $carrier): int {
        $query = "INSERT INTO ".self::TABLE_NAME." (carrier) VALUES ('".$carrier."');";
        self::$db->query($query);
        return (int)self::$db->lastInsertId();
    }

    public static function is_there_a_running_process(){
        $query = "SELECT id FROM ".self::TABLE_NAME." WHERE status IN ('Initié','Actif');" ;
        $stmt = self::$db->query($query);
        $order_submit_process = $stmt->fetch();
        return ($order_submit_process) ? $order_submit_process['id'] : false ;
    }
    
    public static function set_and_get_the_metadata_of_the_order_submit_process($order_submit_process_id,$order_ids){
        
        // get the selected orders
        $query = "SELECT id_order,submitted,city,delegation,phone,firstname,lastname FROM ". _DB_PREFIX_.\OrderCore::$definition['table']." AS Ord INNER JOIN 
                 ". _DB_PREFIX_.\AddressCore::$definition['table']. " AS addr ON Ord.id_address_delivery=Addr.id_address WHERE id_order IN  (".implode(',',$order_ids).")" ;
        $stmt = self::$db->query($query);
        $orders = $stmt->fetchAll() ;

        // collect already submitted orders and orders with invalid fields if they exists
        $already_submitted_orders = [] ;
        $orders_with_invalid_fields = [] ;
        $valid_order_ids = [] ;

        foreach ($orders as $order) {
            
            if ($order['submitted']){
                $already_submitted_orders[] = ['order_id'=>$order['id_order'],'fullname'=>$order['firstname']." ".$order['lastname']] ;
            }else{
                $invalid_fields = [] ;
                
                if(!in_array($order['city'], self::CITIES)){
                    $invalid_fields[] = "city" ;
                }
                if(!$order['delegation']){
                    $invalid_fields[] = "delegation" ;
                }
                if (!preg_match('/^\d{8}$/', $order['phone'])) {
                    $invalid_fields[] = "phone" ;
                }

                if ($invalid_fields){
                    $orders_with_invalid_fields[] = ['order_id'=>$order['id_order'],'invalid_fields'=>$invalid_fields] ;
                }else{
                    $valid_order_ids[] = $order['id_order'] ;
                }
            }

        }

        // construct the meta_data
        $order_submit_process_metadata = [] ;
           
        $order_submit_process_metadata['valid_order_ids'] = $valid_order_ids ;
        
        if ($orders_with_invalid_fields){
            $order_submit_process_metadata['orders_with_invalid_fields'] = $orders_with_invalid_fields ;
        }

        if($already_submitted_orders){
            $order_submit_process_metadata['already_submitted_orders'] = $already_submitted_orders ;
        }

        // set the meta data of the order submit process 
        $query = "UPDATE ".OrderSubmitProcess::TABLE_NAME." SET meta_data='".json_encode($order_submit_process_metadata,JSON_UNESCAPED_UNICODE)."'" ;
        if($valid_order_ids){
            $query .=  ",items_to_process_cnt=".count($order_submit_process_metadata['valid_order_ids']) ;
        }

        $query .= " WHERE id=".$order_submit_process_id ;
        self::$db->query($query) ;

        // return the meta_data without the order ids
        unset($order_submit_process_metadata["valid_order_ids"]) ;
        return $order_submit_process_metadata ;
    }

    public static function add_orders_to_resubmit_and_activate_the_process($process,$order_to_resubmit_ids){
        
        $meta_data = json_decode($process['meta_data'],true) ;

        // add the orders to resubmit
        if($order_to_resubmit_ids){
            $meta_data['valid_order_ids'] = array_merge($meta_data['valid_order_ids'],$order_to_resubmit_ids) ;
        }

        // check if there is an old invalid order that got fixed then add them to the valid_order_ids
        if (isset($meta_data['orders_with_invalid_fields'])){
            $orders_with_invalid_fields_ids = array_map(fn($order) => $order['order_id'],$meta_data['orders_with_invalid_fields']); 
            
            $query = "SELECT id_order,submitted,city,delegation,phone FROM ". _DB_PREFIX_.\OrderCore::$definition['table']." AS Ord INNER JOIN 
                    ". _DB_PREFIX_.\AddressCore::$definition['table']. " AS addr ON Ord.id_address_delivery=Addr.id_address WHERE id_order IN  (".implode(',',$orders_with_invalid_fields_ids).")" ;

            $orders_with_invalid_fields = self::$db->query($query)->fetchAll();

            foreach ($orders_with_invalid_fields as $order) {
                $invalid_fields = [] ;
                
                if(!in_array($order['city'], self::CITIES)){
                    $invalid_fields[] = "city" ;
                }
                if(!$order['delegation']){
                    $invalid_fields[] = "delegation" ;
                }
                if (!preg_match('/^\d{8}$/', $order['phone'])) {
                    $invalid_fields[] = "phone" ;
                }

                if (!$invalid_fields){
                    $meta_data['valid_order_ids'][] = $order['id_order'] ;
                }
            }
        }
    
        // unset other meta data other than valid_order_ids
        $new_meta_data = ['valid_order_ids'=>$meta_data['valid_order_ids']];
        $items_to_process_cnt = count($new_meta_data['valid_order_ids']);
        // persist the new meta data and activate the process the process 
        self::$db->query("UPDATE ".self::TABLE_NAME." SET status='Actif',meta_data='".json_encode($meta_data,JSON_UNESCAPED_UNICODE)."',items_to_process_cnt=$items_to_process_cnt WHERE id=".$process['id']) ;
        return $items_to_process_cnt ;
    }


    public static function cancel($process_id,$cancel_status){
        $cancel_status = str_replace("'", "\'", $cancel_status);
        self::$db->query("UPDATE ".self::TABLE_NAME." SET status='$cancel_status' WHERE id=".$process_id) ;
    }

    public static function terminate($process_id){
        self::$db->query("UPDATE ".self::TABLE_NAME." SET status='Pre-terminé par l\'utilisateur' WHERE id=".$process_id) ;
    }

    public static function get_order_submit_process_list($query_parameter){
        
        $values = ['limit'=>$query_parameter['batch_size'],'offset'=>($query_parameter['page_nb'] - 1) * $query_parameter['batch_size']] ;
        
        // note : i did add 1=1 for the case of there is no query parameters to filter by 
        $query = "SELECT id,carrier,started_at,processed_items_cnt,items_to_process_cnt,status,COUNT(*) OVER() as total_count FROM ".self::TABLE_NAME." WHERE 1=1 " ;
        
        if ($query_parameter['carrier']){
            $values['carrier'] = $query_parameter['carrier'] ;
            $query .= "AND carrier= :carrier " ; 
        }

        if ( $query_parameter['status']){
            $values['status'] = $query_parameter['status'] ;
            $query .= "AND status= :status " ;
        }

        if ($query_parameter['start_date'] && $query_parameter['end_date']){
            $values['start_date'] = $query_parameter['start_date']." 00:00:00" ;
            $values['end_date'] = $query_parameter['end_date']." 23:59:59" ;
            $query .= "AND started_at BETWEEN :start_date AND :end_date " ;
        }

        $query .= "LIMIT :limit OFFSET :offset ;" ;

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

    public static function get_order_submit_process_detail($process_id,$query_parameter){
        $query = "SELECT carrier,status,started_at,ended_at,processed_items_cnt,items_to_process_cnt,error,meta_data" ;
        $query .= " FROM ".self::TABLE_NAME." WHERE id=".$process_id ;

        $order_submit_process_detail = self::$db->query($query)->fetch() ;
        if(!$order_submit_process_detail){
            return false ;
        }
        // add the orders_to_submit to order_submit_process_detail
        $order_submit_process_detail["error"] = json_decode($order_submit_process_detail["error"],true);
        $order_submit_process_detail['meta_data'] = json_decode($order_submit_process_detail['meta_data'],true);
        $order_ids = $order_submit_process_detail["meta_data"]['valid_order_ids'] ;
        $orders_to_submit = [];
        if(count($order_ids)){
            $values = ['limit'=>$query_parameter['batch_size'],'offset'=>($query_parameter['page_nb'] - 1) * $query_parameter['batch_size']] ;
            $query = "SELECT id_order,firstname,lastname,submitted,COUNT(*) OVER() as total_count FROM ". _DB_PREFIX_.\OrderCore::$definition['table']." AS Ord INNER JOIN " ;
            $query .= _DB_PREFIX_.\AddressCore::$definition['table']. " AS addr ON Ord.id_address_delivery=Addr.id_address WHERE id_order IN  (".implode(',',$order_ids).")" ;
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

        $order_submit_process_detail['orders_to_submit'] = $orders_to_submit ;
        $order_submit_process_detail['status_color'] = self::STATUS_COLORS[$order_submit_process_detail['status']] ;
        return $order_submit_process_detail ;
    }
    

}