<?php

namespace Dolzay\Apps\Notifications\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Dolzay\Apps\Notifications\Entities\Notification;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;
use Dolzay\CustomClasses\Constraints\IsIntegerAndGreaterThanZero;
use Dolzay\CustomClasses\Db\DzDb ;  
use Dolzay\Apps\Settings\Entities\EmployeePermission;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class NotificationController extends FrameworkBundleAdminController
{

    public function get_the_requesting_employee_id(){

        // get the employee id of the requesting user
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse([
                "status" => "error",
                "data" => ["message" => "this employee doesn't exist anymore"]
            ], 401);
        }

        return $user->getId() ;
                
    }

    public function check_if_the_employee_exists($db,$employee_id){
        
        
        // check if the employee with the id $employee_id exists
        $checkEmployeeQuery = "SELECT COUNT(*) FROM ". _DB_PREFIX_ ."employee WHERE id_employee = :employee_id";
        $stmt = $db->prepare($checkEmployeeQuery);
        $stmt->bindParam(':employee_id', $employee_id, \PDO::PARAM_INT);
        $stmt->execute();
        $employeeExists = $stmt->fetchColumn();

        if (!$employeeExists) {
            $db->commit();
            return new JsonResponse([
                "status" => "error",
                "data" => ["message" => "this employee doesn't exist anymore"]
            ], 401);
        }

        return true ;
    }

    public function validateData($data, $constraints)
    {
        $validator = Validation::createValidator();
        $violations = $validator->validate($data, $constraints);

        // collect the validation errors if there are any
        $ValidationErrors = [];

        foreach($violations as $violation){
            $field = str_replace(['[', ']'], '', $violation->getPropertyPath());
            $ValidationErrors[$field] = $violation->getMessage();
        }
        
        // return the validation errors if there are any
        if (count($ValidationErrors) > 0){
            return new JsonResponse([
                "status" => "error",
                "data" => ["validation_errors" => $ValidationErrors]]              
                , 400);
        }
    }

    public function getNotificationsOverview(Request $request)
    {   


        // get the employee id of the requesting user or return an error response if the user doesn't exist
        $employee_id = $this->get_the_requesting_employee_id() ;
        if($employee_id instanceof JsonResponse){
            return $employee_id ;
        }
        
        //   -- validate the query parameters -- 

        // get the query parameters
        $query_parameter  = [
            "page_nb" =>  $request->query->get('page_nb'),
            "batch_size" => $request->query->get('batch_size')
        ] ;

        // define the constraints of each query parameter
        $constraints =  new Assert\Collection([
            'page_nb' => [
                new Assert\NotBlank(),
                new IsIntegerAndGreaterThanZero()
            ],
            'batch_size' => [
                new Assert\NotBlank(),
                new IsIntegerAndGreaterThanZero()
            ]]
        );

        // validate the query parameters
        $validationErrorRes = $this->validateData($query_parameter,$constraints) ;
        if($validationErrorRes){
            return $validationErrorRes ;
        }

        //  -- return the notifications overview data --
    
        // initiate the db connection and start a transaction
        $db =  DzDb::getInstance();
        $db->beginTransaction();

        // check if the employee with the id $employeeId still exists within this transaction
        $res = $this->check_if_the_employee_exists($db,$employee_id);
        if ($res instanceof JsonResponse){
            return $res ;
        }

        // get the permission ids of the employee
        EmployeePermission::init($db,$employee_id);
        $employee_permission_ids = EmployeePermission::get_permissions();


        // get the notifications overview data whitin the transaction
        Notification::init($db,$employee_id,$employee_permission_ids);
        $all_notifs_count = Notification::get_all_notifications_count();
        [$unpopped_up_notifs_count,$unpopped_up_notifications] = Notification::get_the_unpopped_up_notifications_by_the_empolyee($query_parameter["page_nb"],$query_parameter["batch_size"]);  
        
        // end the transaction
        $db->commit();

        return new JsonResponse([
            "status" => "success",
            "data" => [
                "all_notifs_count" => $all_notifs_count,
                "unpopped_up_notifs_count" => $unpopped_up_notifs_count,
                "unpopped_up_notifications" => $unpopped_up_notifications
            ]
  
        ]);
    }


    public function getNotificationsList(Request $request)
    {
        // get the employee id of the requesting user or return an error response if the user doesn't exist
        $employee_id = $this->get_the_requesting_employee_id() ;
        if($employee_id instanceof JsonResponse){
            return $employee_id ;
        }

        // get the query parameters
        $query_parameter = [
            'notif_type' => $request->query->get('notif_type'),
            'page_nb' => $request->query->get('page_nb'),
            'batch_size' => $request->query->get('batch_size')
        ];

        // define the constraints of each query parameter
        $constraints =  new Assert\Collection([
            'notif_type' => [
                new Assert\NotBlank(),           
                new Assert\Choice(['choices' => ["all","process","config_error","dormant_or_not_found_order"],
                                    'message' => 'the notification type must be one of the following values: all, process, config_error, dormant_or_not_found_order.'])
            ],
            'page_nb' => [
                new Assert\NotBlank(),
                new IsIntegerAndGreaterThanZero()
            ],
            'batch_size' => [
                new Assert\NotBlank(),
                new IsIntegerAndGreaterThanZero()
            ]]
        );

        // validate the query parameters
        $validationErrorRes = $this->validateData($query_parameter,$constraints) ;
        if($validationErrorRes){
            return $validationErrorRes ;
        }

        // initiate the db connection and start a transaction
        $db = DzDb::getInstance();
        $db->beginTransaction();

        // check if the employee with the id $employeeId still exists within this transaction
        $res = $this->check_if_the_employee_exists($db,$employeeId);
        if ($res instanceof JsonResponse){
            return $res ;
        }

        // get the permission ids of the employee
        EmployeePermission::init($db,$employee_id);
        $employee_permission_ids = EmployeePermission::get_permissions();

        // get the notification list
        Notification::init($db,$employee_id,$employee_permission_ids);
        $notifications = Notification::get_notifications($query_parameter["notif_type"], $query_parameter["page_nb"], $query_parameter["batch_size"]);

        $db->commit();

        return new JsonResponse([
            "status" => "success",
            "data" => ["notifications" =>$notifications]
        ]);

    }


    public function markNotificationAsRead($notif_id, Request $request)
    {

        // get the employee id of the requesting user or return an error response if the user doesn't exist
        $employee_id = $this->get_the_requesting_employee_id() ;
        if($employee_id instanceof JsonResponse){
            return $employee_id ;
        }
        
        // initiate the db connection and start a transaction
        $db = DzDb::getInstance();

        // get the permission ids of the employee
        EmployeePermission::init($db,$employee_id);
        $employee_permission_ids = EmployeePermission::get_permissions();

        // mark the notificaiton as read
        Notification::init($db,$employee_id,$employee_permission_ids);
        $res = $notification::mark_notification_as_read($notif_id);

        // handle employee not found or not permitted error
        if($res){
            return new JsonResponse([
                "status" => "error",
                "msg" => $res
            ], 401);
        }

        return new JsonResponse(['status' => 'success']);
    }


    public function markAllNotificationsAsRead(Request $request)
    {
        // get the notification ids list from the request body 

        // get the employee id of the requesting user or return an error response if the user doesn't exist
        $employee_id = $this->get_the_requesting_employee_id() ;
        if($employee_id instanceof JsonResponse){
            return $employee_id ;
        }

       // initiate the db connection and start a transaction
       $db = DzDb::getInstance();
        
        Notification::init($db,$employee_id,$permission_ids);
        Notification::mark_all_notifications_as_read();

        return new JsonResponse(['status' => 'success']);
    }


    public function markNotificationdAsPoppedUp(Request $request)
    {
        // get the employee id of the requesting user or return an error response if the user doesn't exist
        $employee_id = $this->get_the_requesting_employee_id() ;
        if($employee_id instanceof JsonResponse){
            return $employee_id ;
        }
        
        // initiate the db connection and start a transaction
       $db = DzDb::getInstance();
       $db->beginTransaction();
        
        // check if the employee exists within this transaction
        $res = $this->check_if_the_employee_exists($db,$employee_id);
        if ($res instanceof JsonResponse){
            return $res ;
        }

        Notification::init($db,$employee_id,$permission_ids);
        $notification->mark_notification_as_popped_up($notif_id);

        return new JsonResponse(['status' => 'success']);
    }
}



