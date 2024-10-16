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


class NotificationController extends FrameworkBundleAdminController
{

    public function get_the_requesting_employee_id(){
        // get the employee id of the requesting user
        $employeeId = $this->getUser()->getId();

        // check if the requesting user still exists
        if(!$employeeId){
            return new JsonResponse([
                "status" => "error",
                "data" => ["message" => "this employee doesn't exist anymore"]
            ], 401);
        }

        return $employeeId ;
                
    }

    public function check_if_the_employee_exists($db,$employeeId){

        // check if the employee with the id $employeeId exists
        $checkEmployeeQuery = "SELECT COUNT(*) FROM ". _DB_PREFIX_ ."employee WHERE id_employee = :employeeId";
        $stmt = $db->prepare($checkEmployeeQuery);
        $stmt->bindParam(':employeeId', $employeeId, \PDO::PARAM_INT);
        $stmt->execute();
        $employeeExists = $stmt->fetchColumn();

        if (!$employeeExists) {
            $db->commit();
            return new JsonResponse([
                "status" => "error",
                "data" => ["message" => "this employee doesn't exist anymore"]
            ], 401);
        }

        return $employeeExists ;
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
        $employee_id = get_the_requesting_employee_id() ;
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

        // check if the employee with the id $employeeId exists
        // note : I need to check if the employee exists within the same transaction that retrieves the notifications overview data.
        //        This ensures that I don't get invalid notifications overview data due to the employee being deleted, as their viewed_by and popped_up_by rows 
        //        would also be deleted along with them.
        $res = $this->check_if_the_employee_exists($db,$employeeId);
        if ($res instanceof JsonResponse){
            return $res ;
        }

        // get the notifications overview data whitin the transaction
        $notification = new Notification($db);
        $all_notifs_count = $notification->get_all_notifications_count($employeeId);
        [$unpopped_up_notifs_count,$unpopped_up_notifications] = $notification->get_the_unpopped_up_notifications_by_the_empolyee($employeeId,$query_parameter["page_nb"],$query_parameter["batch_size"]);  
        
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
        $employee_id = get_the_requesting_employee_id() ;
        if($employee_id instanceof JsonResponse){
            return $employee_id ;
        }

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

        // check if the employee with the id $employeeId exists
        // note : I need to check if the employee exists within the same transaction that retrieves the notifications list.
        //        This ensures that I don't get an invalid notifications list due to the employee being deleted, as their viewed_by and popped_up_by rows 
        //        would also be deleted along with them.
        $res = $this->check_if_the_employee_exists($db,$employeeId);
        if ($res instanceof JsonResponse){
            return $res ;
        }


        $notification = new Notification($db);
        
        // $notif_type, $page_nb, $batch_size, $employee_id
        $notifications = $notification->get_notifications($query_parameter["notif_type"], $query_parameter["page_nb"], $query_parameter["batch_size"], $employeeId);

        $db->commit();
        return new JsonResponse([
            "status" => "success",
            "data" => ["notifications" =>$notifications]
        ]);
    }


    public function markNotificationAsRead($notif_id, Request $request)
    {
        $employee_id = get_the_requesting_employee_id();
        
        // initiate the db connection and start a transaction
        $db = DzDb::getInstance();
        $db->beginTransaction();

        $notification = new Notification($this->getParameter('module_table_prefix'));
        $res = $notification->mark_notification_as_read($notif_id, $employeeId);
        $db->commit();

        if($res == "REDIRECT_TO_LOGIN_PAGE"){
            $db->commit();
            return new JsonResponse([
                "status" => "error",
                "data" => ["message" => "this employee doesn't exist anymore"]
            ], 401);
        }


        return new JsonResponse(['status' => 'success']);
    }


    public function markAllNotificationsAsRead(Request $request)
    {
        $employeeId = $this->getUser()->getId();

        $db->beginTransaction();
        $notification = new Notification($this->getParameter('module_table_prefix'));
        $notification->mark_all_notifications_as_read($employeeId);
        $db->commit();

        return new JsonResponse(['status' => 'success']);
    }


    public function markNotificationAsPoppedUp($notif_id, Request $request)
    {
        $employeeId = $this->getUser()->getId();
        $notification = new Notification($this->getParameter('module_table_prefix'));
        $notification->mark_notification_as_popped_up($notif_id, $employeeId);

        return new JsonResponse(['status' => 'success']);
    }
}



