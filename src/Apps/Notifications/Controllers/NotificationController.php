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
use Dolzay\Apps\Settings\Entities\Employee ;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class NotificationController extends FrameworkBundleAdminController
{

    public function get_the_requesting_employee_id(){

        // get the employee id of the requesting user
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse([
                "status" => "error",
                "msg" => "this employee doesn't exist anymore"
            ], 401);
        }

        return $user->getId() ;
                
    }

    public function check_if_the_employee_exists($db,$employee_id){
        
        $employee_table_name = _DB_PREFIX_.\EmployeeCore::$definition['table'] ;
        // check if the employee with the id $employee_id exists
        $checkEmployeeQuery = "SELECT COUNT(*) FROM ".$employee_table_name." WHERE id_employee = :employee_id";
        $stmt = $db->prepare($checkEmployeeQuery);
        $stmt->bindParam(':employee_id', $employee_id, \PDO::PARAM_INT);
        $stmt->execute();
        $employeeExists = $stmt->fetchColumn();

        if (!$employeeExists) {
            $db->commit();
            return new JsonResponse([
                "status" => "unauthorized",
                "msg" => "this employee doesn't exist anymore"
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
        // get the test query paramerters
        $test_parameteter = [
            "remove_employee_right_before_getting_his_id" =>  $request->query->get('remove_employee_right_before_getting_his_id'),
            "remove_employee_right_before_starting_the_transaction" => $request->query->get('remove_employee_right_before_starting_the_transaction')
        ] ;

        // remove the employee right before getting his id if the test parameter is set to true
        if ($test_parameteter["remove_employee_right_before_getting_his_id"]){
            $employee_id = $this->get_the_requesting_employee_id() ;
            $db =  DzDb::getInstance();
            Employee::init($db);
            Employee::delete($employee_id) ;
        }

        // get the employee id of the requesting user or return an error response if the user doesn't exist
        $res = $this->get_the_requesting_employee_id() ;
        if($res instanceof JsonResponse){
            return $res ;
        }
        $employee_id = $res ;
        
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


        // remove the employee right before starting the transaction if the test parameter is set to true
        if ($test_parameteter["remove_employee_right_before_starting_the_transaction"]){
            $db =  DzDb::getInstance();
            Employee::init($db);
            Employee::delete($employee_id) ;
        }
        
        //  -- return the notifications overview data --

        // initiate the db connection and start a transaction
        $db =  DzDb::getInstance();
        $db->beginTransaction();

        // check if the employee with the id $employee_id still exists within this transaction
        $res = $this->check_if_the_employee_exists($db,$employee_id);
        if ($res instanceof JsonResponse){
            return $res ;
        }

        // get the permission ids of the employee
        EmployeePermission::init($db,$employee_id);
        $employee_permission_ids = EmployeePermission::get_permissions();

        // return a 401 response if the employee doesn't have any permissions
        if(empty($employee_permission_ids)){
            // end the transaction
            $db->commit();

            return new JsonResponse([
                "status" => "unauthorized",
                "msg" => "this employee doesn't have any permissions"
            ], 401);
        }


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
        // get the test query paramerters
        $test_parameteter = [
            "remove_employee_right_before_getting_his_id" =>  (int)$request->query->get('remove_employee_right_before_getting_his_id'),
            "remove_employee_right_before_starting_the_transaction" => (int)$request->query->get('remove_employee_right_before_starting_the_transaction')
        ] ;

        // remove the employee right before getting his id if the test parameter is set to true
        if ($test_parameteter["remove_employee_right_before_getting_his_id"]){
            $employee_id = $this->get_the_requesting_employee_id() ;
            $db =  DzDb::getInstance();
            Employee::init($db);
            Employee::delete($employee_id) ;
        }

        // get the employee id of the requesting user or return an error response if the user doesn't exist
        $res = $this->get_the_requesting_employee_id() ;
        if($res instanceof JsonResponse){
            return $res ;
        }
        $employee_id = $res ;

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

        // remove the employee right before starting the transaction if the test parameter is set to true
        if ($test_parameteter["remove_employee_right_before_starting_the_transaction"]){
            $db = DzDb::getInstance();
            Employee::init($db);
            Employee::delete($employee_id) ;
        }

        // initiate the db connection and start a transaction
        $db = DzDb::getInstance();
        $db->beginTransaction();

        // check if the employee with the id $employee_id still exists within this transaction
        $res = $this->check_if_the_employee_exists($db,$employee_id);
        if ($res instanceof JsonResponse){
            return $res ;
        }

        // get the permission ids of the employee
        EmployeePermission::init($db,$employee_id);
        $employee_permission_ids = EmployeePermission::get_permissions();

        // return a 401 response if the employee doesn't have any permissions
        if(empty($employee_permission_ids)){
            // end the transaction
            $db->commit();
            return new JsonResponse([
                "status" => "unauthorized",
                "msg" => "this employee doesn't have any permissions"
            ], 401);
        }

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

        // note : even if the employee doesn't have any permissions i wan't to proceed to check if this 
        //        notification is deletable by him

        // mark the notificaiton as read
        Notification::init($db,$employee_id,$employee_permission_ids);
        $res = $notification::mark_notification_as_read($notif_id);

        // return a 401 error response if the employee doesn't exist anymore 
        if($res){
            return new JsonResponse([
                "status" => "unauthorized",
                "msg" => $res
            ], 401);
        }

        return new JsonResponse(['status' => 'success']);

        /*
          notes : 
           - if an employee no longer has the permission to access a notification, i will not mark it as read but i will
             return success and i will let the periodic refresh inform the employee that this notification is no longer accessible 
             for him by not showing it to him again 
           - if an employee no longer has the permission to access a notification but this notification is deletable once viewed
             by him we will delete this notification and return success so that this notification does't stay in the db     
        */
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

        // get the permission ids of the employee
        EmployeePermission::init($db,$employee_id);
        $employee_permission_ids = EmployeePermission::get_permissions();
        
        // mark all the notifications as read
        Notification::init($db,$employee_id,$permission_ids);
        $res = Notification::mark_all_notifications_as_read();
        
        // resturn a 401 error response if the employee doesn't exist anymore 
        if ($res){
            return new JsonResponse([
                "status" => "unauthorized",
                "msg" => $res
            ], 401);
        }

        return new JsonResponse(['status' => 'success']);
    }


    public function markNotificationsdAsPoppedUp(Request $request)
    {
        // get notification ids list from the request body
        $request_body = [
            "notif_ids" => $request->request->get('notif_ids')
        ] ;

        // define the constraints of the request body   
        $constraints =  new Assert\Collection([
            'notif_ids' => [
                new Assert\NotBlank(),
                new Assert\Type('array'),
                new Assert\All([
                    new Assert\NotBlank(),
                    new IsIntegerAndGreaterThanZero()
                ])
            ]
        ]);

        // validate the resquest body
        $validationErrorRes = $this->validateData($query_parameter,$constraints) ;
        if($validationErrorRes){
            return $validationErrorRes ;
        }



        // get the employee id of the requesting user or return an error response if the user doesn't exist
        $employee_id = $this->get_the_requesting_employee_id() ;
        if($employee_id instanceof JsonResponse){
            return $employee_id ;
        }
        
        // initiate the db connection and start a transaction               
        $db = DzDb::getInstance();
        
        // get the permission ids of the employee
        EmployeePermission::init($db,$employee_id);
        $permission_ids = EmployeePermission::get_permissions();
        
        // mark notifications as popped up
        Notification::init($db,$employee_id,$permission_ids);
        $res = $notification->mark_notification_as_popped_up($request_body['notif_ids']);

        if ($res){
            return new JsonResponse([
                "status" => "unauthorized",
                "msg" => $res
            ], 401);
        }

        return new JsonResponse(['status' => 'success']);
    }
}



