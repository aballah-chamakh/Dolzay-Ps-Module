<?php


namespace Dolzay\Apps\Notifications\Entities ;

use Dolzay\ModuleConfig ;
use Dolzay\CustomClasses\Db\DzDb ;  

use PDO ;

class Notification {

    private $db ;

    private const TABLE_NAME = "notification" ;
    private const NOTIFICATION_TYPES = ["all","process", "config_error", "dormant_or_not_found_order"]  ;


    public const CREATE_TABLE_SQL = 'CREATE TABLE IF NOT EXISTS `'.ModuleConfig::MODULE_PREFIX.self::TABLE_NAME.'` (
        `id` INT(10) UNSIGNED AUTO_INCREMENT NOT NULL,
        `type` ENUM("all","process","config_error","dormant_or_not_found_order") NOT NULL,
        `pathname` VARCHAR(255) NOT NULL,
        `logo` VARCHAR(255) NOT NULL,
        `color` VARCHAR(50) NOT NULL,
        `message` LONGTEXT NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `deletable_once_viewed_by_the_employee_with_the_id` INT(10) UNSIGNED NULL,
        PRIMARY KEY(`id`),
        FOREIGN KEY (`deletable_once_viewed_by_the_employee_with_the_id`) REFERENCES `'._DB_PREFIX_.'employee`(`id_employee`) ON DELETE CASCADE
    ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ;' ;
    
    public const DROP_TABLE_SQL = 'DROP TABLE IF EXISTS `' . ModuleConfig::MODULE_PREFIX . self::TABLE_NAME . '`;';
    

    public function __construct(\PDO $db) {
        $this->db = $db  ;
    }

    public function paginate($list,$list_count,$page_nb, $batch_size) {

        $list_paginated = array_slice($list, ($page_nb - 1) * $batch_size, $batch_size);
        // if the page of the request page id is empty, return the last page with at least one record
        if(count($list_paginated) == 0){
            $last_page_nb_with_records =  (int)ceil($list_count / $batch_size) ;
            $list_paginated = array_slice($list, ($last_page_nb_with_records - 1) * $batch_size, $batch_size);
        }
        return $list_paginated ;
    }

    public function get_all_notifications_count($employee_id){

        $query = "
                SELECT COUNT(*)
                FROM `".ModuleConfig::MODULE_PREFIX."notification` n
                LEFT JOIN `".ModuleConfig::MODULE_PREFIX."notification_viewed_by` nv 
                ON n.id = nv.notif_id AND nv.employee_id = :employee_id
                WHERE  NOT (nv.employee_id IS NOT NULL AND n.deletable_once_viewed_by_the_employee_with_the_id IS NOT NULL)" ;
        $stmt = $this->db->prepare($query);
                
        $stmt->execute(['employee_id' => $employee_id]);
        $notifications_count = (int) $stmt->fetchColumn();
        return $notifications_count  ;
    }


    public function get_the_unpopped_up_notifications_by_the_empolyee(int $employee_id, int $page_nb, int $batch_size) {
        
        
        $query = "SELECT id,type,pathname,logo,message,DATE_FORMAT(created_at, '%H:%i:%S %d-%m-%Y') as created_at,color FROM `".ModuleConfig::MODULE_PREFIX."notification` n
        LEFT JOIN `".ModuleConfig::MODULE_PREFIX."notification_viewed_by` nv 
        ON n.id = nv.notif_id AND nv.employee_id = :employee_id
        LEFT JOIN `".ModuleConfig::MODULE_PREFIX."notification_popped_up_by` np 
        ON n.id = np.notif_id AND np.employee_id = :employee_id_x
        WHERE nv.employee_id IS NULL AND np.employee_id IS NULL " ;
        
        $stmt = $this->db->prepare($query);
        $stmt->execute(['employee_id' => $employee_id,'employee_id_x' => $employee_id]);
        $unpopped_up_notifications_by_the_empolyee = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $unpopped_up_notifications_by_the_empolyee_count = count($unpopped_up_notifications_by_the_empolyee);
        if ($unpopped_up_notifications_by_the_empolyee_count == 0) {
            return [];
        }

        return [$unpopped_up_notifications_by_the_empolyee_count, $this->paginate($unpopped_up_notifications_by_the_empolyee,$unpopped_up_notifications_by_the_empolyee_count,$page_nb, $batch_size)];

    }

    public function get_notifications($notif_type, $page_nb, $batch_size, $employee_id) {

        $notifications = [];
        
        // GET THE COUNT OF ALL NOTIFICATIONS 
        //$notifications['all_notifs_cnt'] = $this->get_all_notifications_count($employee_id);

        // GET THE COUNT OF NOTIFICATIONS OF EACH TYPE
        foreach ($this::NOTIFICATION_TYPES as $type) {
            // skip the requested type 
            if ($type == $notif_type) {
                continue;
            }else{

                $count_query = "
                SELECT COUNT(*) as count
                FROM `".ModuleConfig::MODULE_PREFIX."notification` n
                LEFT JOIN `".ModuleConfig::MODULE_PREFIX."notification_viewed_by` nv 
                ON n.id = nv.notif_id AND nv.employee_id = :employee_id
                WHERE NOT (nv.employee_id IS NOT NULL AND n.deletable_once_viewed_by_the_employee_with_the_id IS NOT NULL)" ;
                
                if ($type != "all") {
                    $count_query .= " AND n.type = :notif_type";
                    $stmt = $this->db->prepare($count_query);
                    $stmt->execute(["employee_id"=>$employee_id,"notif_type"=>$type]);
                    
                }else{
                    $stmt = $this->db->prepare($count_query);
                    $stmt->execute(["employee_id"=>$employee_id]);
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
            FROM `".ModuleConfig::MODULE_PREFIX."notification` n
            LEFT JOIN `".ModuleConfig::MODULE_PREFIX."notification_viewed_by` nv 
            ON n.id = nv.notif_id AND nv.employee_id = :employee_id
            WHERE NOT (nv.employee_id IS NOT NULL AND n.deletable_once_viewed_by_the_employee_with_the_id IS NOT NULL)" ;
        
        if ($notif_type != "all") {
            $query .= " AND n.type = :notif_type";
            $stmt = $this->db->prepare($query);
            $stmt->execute(["employee_id"=>$employee_id,"notif_type"=>$notif_type]);
        }else{
            $stmt = $this->db->prepare($query);
            $stmt->execute(["employee_id"=>$employee_id]);
        }

        $requested_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);


        $notifications[$notif_type."_notifs_cnt"] = count($requested_notifications);
        
        if ($notifications[$notif_type."_notifs_cnt"] == 0) {
            $notifications["notifications"] = [];
            $notifications["read_notifs_cnt"] = $notifications["unread_notifs_cnt"] = 0 ;
            return $notifications;
        }

        // get the unread and read notifications count
        [$notifications["read_notifs_cnt"], $notifications["unread_notifs_cnt"]] = $this->get_unread_and_read_notifications_count($requested_notifications);
        
        // paginate the notifications
        $requested_notifications_paginated = $this->paginate($requested_notifications, $notifications[$notif_type."_notifs_cnt"], $page_nb, $batch_size);
        
        $notifications["notifications"] = $requested_notifications_paginated  ;
        return $notifications; 

    }

    public function mark_notification_as_read($notif_id, $employee_id) {

        // select the employee for update to lock the employee so that no other request can't delete the employee while the transaction of this request is running
        $stmt = $this->db->prepare("SELECT * FROM `"._DB_PREFIX_."employee` WHERE id_employee = :employee_id FOR UPDATE");
        $stmt->execute(['employee_id' => (int)$employee_id]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        // if the requesting employee doesn't exist anymore the route will redirect the user to the login page
        if (!$employee) {
            return "REDIRECT_TO_LOGIN_PAGE" ;
        }

        // select the notfication for update to notfication the employee so that no other request can't delete the notfication while the transaction of this request is running
        $stmt = $this->db->prepare("SELECT * FROM `".ModuleConfig::MODULE_PREFIX."notification` WHERE id = :notif_id FOR UPDATE");
        $stmt->execute(['notif_id' => (int)$notif_id]);
        $notification = $stmt->fetch(PDO::FETCH_ASSOC);

        // if the requesting notfication doesn't exist anymore, the function will not throw an error ,it will act like it marked the notfication as read
        // and it will leave the job of informing the user that the notfication doesn't exist anymore to the periodic refresh of the notifications list 
        if (!$notification) {
            return  ;
        }

        // if the attribute  deletable_once_viewed_by_the_employee_with_the_id of the notfication equal the $emplyee id, delete the notfication
        if ($notification["deletable_once_viewed_by_the_employee_with_the_id"] == $employee_id){
            $stmt = $this->db->prepare("DELETE FROM `".ModuleConfig::MODULE_PREFIX."notification` WHERE id = :notif_id");
            $stmt->execute(['notif_id' => (int)$notif_id]);
        }else{ // otherwise mark the notfication as read by the employee
            $stmt = $this->db->prepare("INSERT INTO `".ModuleConfig::MODULE_PREFIX."notification_viewed_by` (employee_id, notif_id) VALUES (:employee_id, :notif_id)");
            $stmt->execute([
                'employee_id' => (int)$employee_id,
                'notif_id' => (int)$notif_id
            ]);
        }
    }

    public function mark_all_notifications_as_read($employee_id) {

        // select the employee for update to lock the employee so that no other request can't delete the employee while the transaction of this request is running
        $stmt = $this->db->prepare("SELECT * FROM `"._DB_PREFIX_."employee` WHERE id_employee = :employee_id FOR UPDATE");
        $stmt->execute(['employee_id' => (int)$employee_id]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        // if the requesting employee doesn't exist anymore the route will redirect the user to the login page
        if (!$employee) {
            return "REDIRECT_TO_LOGIN_PAGE" ;
        }


        // get all notifications for update to lock the notifications so that no other request can't delete the notifications while the transaction of this request is running
        $query = "SELECT id,deletable_once_viewed_by_the_employee_with_the_id FROM `".ModuleConfig::MODULE_PREFIX."notification` for update";  
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);




        // loop through all notifications and mark them as read
        foreach ($notifications as $notification) {

            // if the attribute  deletable_once_viewed_by_the_employee_with_the_id of the notfication equal the $employee_ id, delete the notfication
            if ($notification["deletable_once_viewed_by_the_employee_with_the_id"] == $employee_id){
                $stmt = $this->db->prepare("DELETE FROM `".ModuleConfig::MODULE_PREFIX."notification` WHERE id = :notif_id");
                $stmt->execute(['notif_id' => $notification['id']]);
            }else{
                $stmt = $this->db->prepare("INSERT INTO `".ModuleConfig::MODULE_PREFIX."notification_viewed_by` (employee_id, notif_id) VALUES (:employee_id, :notif_id)");
                $stmt->execute([
                    'employee_id' => $employee_id,
                    'notif_id' => $notification['id']
                ]);    
            }

        }
    }


    // todo : 
    // 1. understand how range locking works exaclty
    // 2. how the function behave with duplicate  viewed by or popped composite 

    public function mark_notifications_as_popped_up($notif_ids, $employee_id) {

        // select the employee for update to lock the employee so that no other request can't delete the employee while the transaction of this request is running
        $stmt = $this->db->prepare("SELECT * FROM `"._DB_PREFIX_."employee` WHERE id_employee = :employee_id FOR UPDATE");
        $stmt->execute(['employee_id' => (int)$employee_id]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        // if the requesting employee doesn't exist anymore the route will redirect the user to the login page
        if (!$employee) {
            return "REDIRECT_TO_LOGIN_PAGE" ;
        }

        // Create placeholders for each ID
        $placeholders = implode(',', array_fill(0, count($notif_ids), '?'));

        // Prepare the query with placeholders
        $query = "SELECT id FROM `".ModuleConfig::MODULE_PREFIX."notification` WHERE id IN ($placeholders) for update";
        $stmt = $this->db->prepare($query);

        // Execute the statement with the array of IDs
        $stmt->execute($notif_ids);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);


        foreach ($notifications as $notification) {
            $stmt = $this->db->prepare("INSERT INTO `".ModuleConfig::MODULE_PREFIX."notification_popped_up_by` (employee_id, notif_id) VALUES (:employee_id, :notif_id)");
            $stmt->execute([
                'employee_id' => (int)$employee_id,
                'notif_id' => (int)$notification['id']
            ]);
        }

    }
    
    private function get_unread_and_read_notifications_count($notifications) {
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

            "SELECT * FROM `".ModuleConfig::MODULE_PREFIX."notification` n
            
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

            "SELECT * FROM `".ModuleConfig::MODULE_PREFIX."notification` n
             LEFT JOIN `".ModuleConfig::MODULE_PREFIX."notification_viewed_by` nv 
             ON n.id = nv.notif_id AND nv.employee_id = " . (int)$employee_id . "
             LEFT JOIN `".ModuleConfig::MODULE_PREFIX."notification_popped_up_by` np 
             ON n.id = np.notif_id AND np.employee_id = " . (int)$employee_id . "
             WHERE nv.employee_id IS NULL AND np.employee_id IS NULL " ;



*/



