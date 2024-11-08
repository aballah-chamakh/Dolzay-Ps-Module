<?php


namespace Dolzay\Apps\Notifications\Entities ;

use Dolzay\ModuleConfig ;
use Dolzay\CustomClasses\Db\DzDb ;  
use Dolzay\Apps\Settings\Entities\Permission;
use Dolzay\Apps\Settings\Entities\Employee;
use PDO ;

class Notification {


    public const TABLE_NAME = ModuleConfig::MODULE_PREFIX."notification" ;
    private const NOTIFICATION_TYPES = ["all","process", "config_error", "dormant_or_not_found_order"]  ;
   
    private static $db ;
    private static $employee_id ;
    private static $employee_permission_ids_str ;
    private static $employee_permission_ids_arr ;
    
    public static function get_create_table_sql() {
        return 'CREATE TABLE IF NOT EXISTS `'.self::TABLE_NAME.'` (
            `id` INT(10) UNSIGNED AUTO_INCREMENT NOT NULL,
            `type` ENUM("process","config_error","dormant_or_not_found_order") NOT NULL,
            `pathname` VARCHAR(255) NOT NULL,
            `logo` VARCHAR(255) NOT NULL,
            `color` VARCHAR(50) NOT NULL,
            `message` LONGTEXT NOT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `deletable_once_viewed_by_the_employee_with_the_id` INT(10) UNSIGNED NULL,
            `permission_id` INT(10) UNSIGNED,
            PRIMARY KEY(`id`),
            FOREIGN KEY (`permission_id`) REFERENCES `'.Permission::TABLE_NAME.'` (`id`) ON DELETE CASCADE,
            FOREIGN KEY (`deletable_once_viewed_by_the_employee_with_the_id`) REFERENCES `'._DB_PREFIX_. \EmployeeCore::$definition['table'] . '` (`id_employee`) ON DELETE CASCADE
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ;' ;
    }
    public const DROP_TABLE_SQL = 'DROP TABLE IF EXISTS `'.self::TABLE_NAME . '`;';
    

    public static function init($db, $employee_id, $employee_permission_ids)
    {
        self::$db = $db;
        self::$employee_id = $employee_id;
        self::$employee_permission_ids_str = implode(',', $employee_permission_ids) ;
        self::$employee_permission_ids_arr = $employee_permission_ids ;
    }


    public static function paginate($list,$list_count,$page_nb, $batch_size) {

        $list_paginated = array_slice($list, ($page_nb - 1) * $batch_size, $batch_size);
        // if the page of the request page id is empty, return the last page with at least one record
        if(count($list_paginated) == 0){
            $last_page_nb_with_records =  (int)ceil($list_count / $batch_size) ;
            $list_paginated = array_slice($list, ($last_page_nb_with_records - 1) * $batch_size, $batch_size);
        }
        return $list_paginated ;
    }

    public function get_all_notifications_count(){
        $query = "
                SELECT COUNT(*)
                FROM `".self::TABLE_NAME."` n
                LEFT JOIN `".NotificationViewedBy::TABLE_NAME."` nv 
                ON n.id = nv.notif_id AND nv.employee_id = ?
                WHERE n.permission_id IN (". self::$employee_permission_ids_str . ") AND NOT (nv.employee_id IS NOT NULL AND n.deletable_once_viewed_by_the_employee_with_the_id IS NOT NULL)" ;
        $stmt = self::$db->prepare($query);
        $stmt->execute([self::$employee_id]);

        $notifications_count = $stmt->fetchColumn();
        
        return $notifications_count  ;
    }

    public static function delete($notif_id) {
        // Prepare the delete statement
        $stmt = self::$db->prepare("DELETE FROM `".self::TABLE_NAME."` WHERE id = :notif_id");
        // Execute the statement with the notification ID
        $stmt->execute(['notif_id' => (int)$notif_id]);
    }

    private function get_notification_perm_id($notif_id) {
        $query = "SELECT permission_id FROM `".self::TABLE_NAME."` WHERE id = :notif_id";
        $stmt = self::$db->prepare($query);
        $stmt->execute(['notif_id' => $notif_id]);
        return $stmt->fetchColumn();
    }


    public function get_the_unpopped_up_notifications_by_the_empolyee(int $page_nb, int $batch_size) {
        
    
        $query = "SELECT id,type,pathname,logo,message,DATE_FORMAT(created_at, '%H:%i:%S %d-%m-%Y') as created_at,color FROM `".self::TABLE_NAME."` n
        LEFT JOIN `" . NotificationViewedBy::TABLE_NAME . "` nv 
        ON n.id = nv.notif_id AND nv.employee_id = ?
        LEFT JOIN `" . NotificationPoppedUpBy::TABLE_NAME . "` np 
        ON n.id = np.notif_id AND np.employee_id = ?
        WHERE n.permission_id IN (" . self::$employee_permission_ids_str . ") AND (nv.employee_id IS NULL AND np.employee_id IS NULL) " ;
        
        $stmt = self::$db->prepare($query);
        $stmt->execute([self::$employee_id,self::$employee_id]);

        $unpopped_up_notifications_by_the_empolyee = $stmt->fetchAll();

        $unpopped_up_notifications_by_the_empolyee_count = count($unpopped_up_notifications_by_the_empolyee);
        if ($unpopped_up_notifications_by_the_empolyee_count == 0) {
            return [];
        }

        return [$unpopped_up_notifications_by_the_empolyee_count, self::paginate($unpopped_up_notifications_by_the_empolyee,$unpopped_up_notifications_by_the_empolyee_count,$page_nb, $batch_size)];

    }

    public static function get_notifications($notif_type, $page_nb, $batch_size) {

        $notifications = [];
        
        // GET THE COUNT OF ALL NOTIFICATIONS 
        //$notifications['all_notifs_cnt'] = $this->get_all_notifications_count($employee_id);

        // GET THE COUNT OF NOTIFICATIONS OF EACH TYPE
        foreach (self::NOTIFICATION_TYPES as $type) {
            // skip the requested type 
            if ($type == $notif_type) {
                continue;
            }else{

                $count_query = "
                SELECT COUNT(*) as count
                FROM `".self::TABLE_NAME."` n
                LEFT JOIN `".NotificationViewedBy::TABLE_NAME."` nv 
                ON n.id = nv.notif_id AND nv.employee_id = ?
                WHERE n.permission_id IN (" . self::$employee_permission_ids_str . ") AND NOT (nv.employee_id IS NOT NULL AND n.deletable_once_viewed_by_the_employee_with_the_id IS NOT NULL)" ;

                if ($type != "all") {
                    $count_query .= " AND n.type = ?";
                    $stmt = self::$db->prepare($count_query);
                    $stmt->execute([self::$employee_id,$type]);
                    
                }else{
                    $stmt = self::$db->prepare($count_query);
                    $stmt->execute([self::$employee_id]);
                }
                //echo $type."_notifs_cnt : " . $stmt->fetchColumn() . "|| <br/>";
                $notifications_count = (int) $stmt->fetchColumn();
                $notifications[$type."_notifs_cnt"] = $notifications_count;
            }
        }
        
        // GET THE NOTIFICATIONS OF THE REQUESTED TYPE WITH UNREAD AND READ NOTIFICATIONS NUMBERS 
        
        $query = "
            SELECT id, type, pathname, logo, message, DATE_FORMAT(created_at, '%H:%i:%S %d-%m-%Y') as created_at, color,
                   (CASE WHEN nv.employee_id IS NOT NULL THEN TRUE ELSE FALSE END) AS viewed
            FROM `".self::TABLE_NAME."` n
            LEFT JOIN `".NotificationViewedBy::TABLE_NAME."` nv 
            ON n.id = nv.notif_id AND nv.employee_id = :employee_id
            WHERE n.permission_id IN (" . self::$employee_permission_ids_str . ") AND NOT (nv.employee_id IS NOT NULL AND n.deletable_once_viewed_by_the_employee_with_the_id IS NOT NULL)" ;
        
        if ($notif_type != "all") {
            $query .= " AND n.type = :notif_type";
            $stmt = self::$db->prepare($query);
            $stmt->execute(["employee_id"=>self::$employee_id,"notif_type"=>$notif_type]);
        }else{
            $stmt = self::$db->prepare($query);
            $stmt->execute(["employee_id"=>self::$employee_id]);
        }

        $requested_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);


        $notifications[$notif_type."_notifs_cnt"] = count($requested_notifications);
        
        if ($notifications[$notif_type."_notifs_cnt"] == 0) {
            $notifications["notifications"] = [];
            $notifications["read_notifs_cnt"] = $notifications["unread_notifs_cnt"] = 0 ;
            return $notifications;
        }

        // get the unread and read notifications count
        [$notifications["read_notifs_cnt"], $notifications["unread_notifs_cnt"]] = self::get_unread_and_read_notifications_count($requested_notifications);
        
        // paginate the notifications
        $requested_notifications_paginated = self::paginate($requested_notifications, $notifications[$notif_type."_notifs_cnt"], $page_nb, $batch_size);
        
        $notifications["notifications"] = $requested_notifications_paginated  ;
        return $notifications; 

    }

    public static function mark_notification_as_read($notif_id,$test_parameters) {

        // get the notfication 
        $stmt = self::$db->prepare("SELECT * FROM `".self::TABLE_NAME."` WHERE id = :notif_id AND permission_id IN (" . self::$employee_permission_ids_str . ")") ;
        $stmt->execute(['notif_id' => $notif_id]);
        $notification = $stmt->fetch();

        // check if the notification does exist anymore 
        if (!$notification) {
            return [[
                "status" => "not_found",
                "message" => "NOTIFICATION_NOT_FOUND"
            ],404] ;
        }

        // if the attribute  deletable_once_viewed_by_the_employee_with_the_id of the notfication equal to the id of the employee, delete the notfication, 
        if ($notification["deletable_once_viewed_by_the_employee_with_the_id"] == self::$employee_id){
            $stmt = self::$db->prepare("DELETE FROM `".self::TABLE_NAME."` WHERE id = :notif_id");
            $stmt->execute(['notif_id' => $notif_id]);
            return [[
                "status" => "success",
                "message" => "DELETABLE_ONCE_VIEWED_BY_THE_EMPLOYEE"
            ],200] ;

        }else{ 

            // delete the employee or the notification before marking the notification as read if the tester wants
            if ($test_parameters['delete_employee_before_marking_as_read']){
                Employee::delete(self::$employee_id); ;
            }else if ($test_parameters['delete_notification_before_marking_as_read']){
                self::delete($notif_id);
            }

            //  mark the notfication as read by the employee
            $stmt = self::$db->prepare("INSERT INTO `".NotificationViewedBy::TABLE_NAME."` (employee_id, notif_id) VALUES (:employee_id, :notif_id)");
            try {
                $stmt->execute([
                    'employee_id' => self::$employee_id,
                    'notif_id' => $notif_id
                ]);
            } catch (\PDOException $e) {
                // 23000 : SQLSTATE code for Integrity constraint violation (SQLSTATE is a five-character code that conforms to the SQL standard's conventions for "SQLSTATE" codes)
                // 1452 : mysql error code for adding foreign key of a record that doesn't exist
                // 1062 : mysql error code for adding a duplicate record for a unique key

                // if we have a Integrity constraint violation error 
                if ($e->getCode() == 23000) {
                    $error_msg = $e->getMessage();
                    // if one of the foreign keys doesn't exist
                    if (strpos($error_msg, '1452')){
                        // check if the employee stills exists 
                        if (strpos($error_msg, _DB_PREFIX_.\EmployeeCore::$definition['table'])){
                            return [[
                                "status" => "unauthorized",
                                "message" => "THIS_EMPLOYEE_WAS_NOT_FOUND_RIGHT_BEFORE_MARKING_IT_AS_VIEWED"
                            ],401] ;
                        }
                        // otherwise the notification doesn't exist anymore
                        return [[
                            "status" => "not_found",
                            "message" => "THIS_NOTIFICATION_WAS_NOT_FOUND_RIGHT_BEFORE_MARKING_IT_AS_VIEWED"
                        ],404] ;
                    // check if the notification is already marked as viewed
                    }elseif (strpos($error_msg, '1062')) {
                        return [[
                            "status" => "success",
                            "message" => "NOTIFICATION_ALREADY_MARKED_AS_VIEWED"
                        ],200] ;
                    }
                }
                // otherwise re-throw the exception if it's not a constraint violation error
                throw $e;
            }
            return [[
                "status" => "success",
                "message" => "NOTIFICATION_WAS_MARKED_AS_VIEWED_SUCCESSFULLY"
            ],200] ;
        }
    }

    public function mark_all_notifications_as_read($testing) {

        // get all the valid permitted notifications
        $query = "SELECT id,deletable_once_viewed_by_the_employee_with_the_id,message FROM `".self::TABLE_NAME."`n
                  LEFT JOIN `".NotificationViewedBy::TABLE_NAME."` nv ON n.id = nv.notif_id AND nv.employee_id = :employee_id
                  WHERE n.permission_id IN (".self::$employee_permission_ids_str.") AND NOT (nv.employee_id IS NOT NULL AND n.deletable_once_viewed_by_the_employee_with_the_id IS NOT NULL)" ;  
        
        $stmt = self::$db->prepare($query);
        $stmt->execute(["employee_id"=>self::$employee_id]);
        $notifications = $stmt->fetchAll();
        
        // if the testing flag is true, create a placeholder for the testing data
        if ($testing){
            $testing_data = [];
            foreach ($notifications as $notification) {
                $testing_data[$notification["message"]] = [] ;
            }
        }

        // loop through all notifications and mark them as read
        foreach ($notifications as $notification) {

            // if the attribute  deletable_once_viewed_by_the_employee_with_the_id of the notfication equal to the $employee_id, delete the notfication 
            if ($notification["deletable_once_viewed_by_the_employee_with_the_id"] == self::$employee_id){
                $stmt = self::delete($notification['id']);
                if ($testing){
                    $testing_data[$notification["message"]][] = $notification['id'] ;
                }
            }else{
                //  mark the notfication as read by the employee  

                if ($testing){
                    if($notification["message"] == "delete_employee"){
                        Employee::init(self::$db,self::$employee_id);
                        Employee::delete(self::$employee_id); 
                    }else if($notification["message"] == "delete_notification"){
                        self::delete($notification['id']);
                    }
                }

                $stmt = self::$db->prepare("INSERT INTO `".NotificationViewedBy::TABLE_NAME."` (employee_id, notif_id) VALUES (:employee_id, :notif_id)");
                try {
                    $stmt->execute([
                        'employee_id' => self::$employee_id,
                        'notif_id' => $notification['id']
                    ]);  
                    if ($testing){
                        $testing_data[$notification["message"]][] = $notification['id'] ;
                    }  

                } catch (\PDOException $e) {
                    $error_msg = $e->getMessage();
                    // if we have a constraint violation error 
                    if ($e->getCode() == 23000) {
                        // if one of the foreign keys doesn't exist
                        if (strpos($error_msg, '1452')){
                            // if the employee doesn't exist anymore, return "REDIRECT_TO_LOGIN_PAGE"
                            if (strpos($error_msg, _DB_PREFIX_.\EmployeeCore::$definition['table'])){
                            
                                $response = [[
                                    "status" => "unauthorized",
                                    "message" => "THIS_EMPLOYEE_WAS_NOT_FOUND_RIGHT_BEFORE_MARKING_IT_AS_VIEWED",
                                ],401] ;

                                if ($testing){
                                    $testing_data[$notification["message"]][] = $notification['id'] ;
                                    $response[0]["testing_data"] = $testing_data ;
                                }
                                
                                return $response ;

                            }
                            // if the notification doesn't exist anymore, by pass it 
                            if ($testing){
                                $testing_data[$notification["message"]][] = $notification['id'] ;
                            }  
                            continue ;
                        }elseif (strpos($error_msg, '1062')) {
                            // if the notification is already marked as viewed, by pass it 
                            if ($testing){
                                $testing_data[$notification["message"]][] = $notification['id'] ;
                            }  
                            continue;
                        }
                    }
                    // otherwise re-throw the exception if it's not a constraint violation error
                    throw $e;
                }
            }
        }

        $response =  [[
            "status" => "success",
            "message" => "ALL_NOTIFICATIONS_WERE_MARKED_AS_VIEWED_SUCCESSFULLY"
        ],200] ;

        if ($testing){
            $response[0]["testing_data"] = $testing_data ;
        }
        return $response ;
    }


    // todo : 
    // 1. understand how range locking works exaclty
    // 2. how the function behave with duplicate  viewed by or popped composite 

    public function mark_notifications_as_popped_up($notif_ids) {


        // create a placeholder for the notification ids
        $notif_ids_placehoder = implode(',', array_fill(0, count($notif_ids), '?'));

        // get the notfications to pop up
        $stmt = self::$db->prepare("SELECT id FROM `".self::TABLE_NAME."` WHERE id IN ($notif_ids_placehoder)
                                    WHERE permission id IN " . self::$employee_permission_ids_str . " ;");
        $stmt->execute($notif_ids);
        $notifications = $stmt->fetchAll();

        // mark the notifications as popped up
        foreach ($notifications as $notification) {
            $stmt = self::$db->prepare("INSERT INTO `".NotificationPoppedBy::TABLE_NAME."` (employee_id, notif_id) VALUES (:employee_id, :notif_id)");
            try{
                $stmt->execute([
                    'employee_id' => self::$employee_id,
                    'notif_id' => $notification['id']
                ]);
            } catch (\PDOException $e) {
                $error_msg = $e->getMessage();
                // if we have a constraint violation error 
                if ($e->getCode() == 23000) {
                    // if one of the foreign keys doesn't exist
                    if (strpos($error_msg, '1452')){
                        // if the employee doesn't exist anymore, return "REDIRECT_TO_LOGIN_PAGE"
                        if (strpos($error_msg, _DB_PREFIX_.\EmployeeCore::$definition['table'])){
                            return "REDIRECT_TO_LOGIN_PAGE" ;
                        }
                        // if the notification doesn't exist anymore,by pass it (let the periodic refresh let the employee knows that it was deleted)
                        continue ;
                    }elseif (strpos($error_msg, '1062')) {
                        // if the notification is already marked as popped up, by pass it 
                        continue;
                    }
                }
                // otherwise re-throw the exception if it's not a constraint violation error
                throw $e;
            }
        }
    }
    
    private static function get_unread_and_read_notifications_count($notifications) {
        $read_count = 0;
        $unread_count = 0;

        foreach ($notifications as $notification) {
            if ($notification['viewed']) {
                $read_count++;
            } else {
                $unread_count++;
            }
        }

        return [$read_count, $unread_count];
    }
    

}

/*

MAIN QUERY EXPLAINED : 
    THE GOAL IS TO GET THE UNPOPPED UP notifications FOR THE EMPLOYEE THAT : 
        1. THE EMPLOYEE DIDN'T VIEW THEM BEFORE 
        2. THE EMPLOYEE DIDN'T POPPED THEM BEFOR
        3. NOT (VIEWED BY THE EMPLOYEE AND DELETABLE ONCE VIEWED BY AN EMPLOYEE )

            "SELECT * FROM `".self::TABLE_NAME."` n
            
            // attach the viewed notifications by this employee
             LEFT JOIN `".ModuleConfig::MODULE_PREFIX."notification_viewed_by` nv 
             ON n.id = nv.notif_id AND nv.employee_id = " . (int)$employee_id . "

            // attach the popped up  notifications by this employee
             LEFT JOIN `".ModuleConfig::MODULE_PREFIX."notification_popped_up_by` np 
             ON n.id = np.notif_id AND np.employee_id = " . (int)$employee_id . "

             // keep only the notifications that this employee didn't view them neither popped them
                    (not viewed)           (not popped up)
             WHERE nv.employee_id IS NULL AND np.employee_id IS NULL 
             
             // don't return the notifications that the user viewed them and they are deletable once viewed by an employee 
             AND NOT (nv.employee_id IS NOT NULL AND n.deletable_once_viewed_by_the_employee_with_the_id NOT NULL)
             /* we can remove this last condition because it will be tested only when nv.employee_id IS NULL 
                and it's null this condition we always return true  $/

            "SELECT * FROM `".self::TABLE_NAME."` n
             LEFT JOIN `".ModuleConfig::MODULE_PREFIX."notification_viewed_by` nv 
             ON n.id = nv.notif_id AND nv.employee_id = " . (int)$employee_id . "
             LEFT JOIN `".ModuleConfig::MODULE_PREFIX."notification_popped_up_by` np 
             ON n.id = np.notif_id AND np.employee_id = " . (int)$employee_id . "
             WHERE nv.employee_id IS NULL AND np.employee_id IS NULL " ;



*/



