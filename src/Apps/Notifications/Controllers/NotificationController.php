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
use Dolzay\CustomClasses\Constraints\All;
use Dolzay\CustomClasses\Db\DzDb ;  
use Dolzay\Apps\Settings\Entities\EmployeePermission;
use Dolzay\Apps\Settings\Entities\Employee ;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class NotificationController extends FrameworkBundleAdminController
{

    private const EMPLOYEE_DOES_NOT_EXIST_ANYMORE_RESPONSE = [
        "status" => "unauthorized",
        "msg" => "THE_EMPLOYEE_DOES_NOT_EXIST_ANYMORE"
    ] ;



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


        //   -- validate the query parameters -- 

        // get the test query paramerters
        $test_parameters = [
            "remove_employee_right_before_starting_the_transaction" => (int)$request->query->get('remove_employee_right_before_starting_the_transaction')
        ];

        // get the query parameters
        $query_parameter  = [
            "page_nb" =>  $request->query->get('page_nb'),
            "batch_size" => $request->query->get('batch_size')
        ];
    

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
        
        // initiate the db connection and get the employee id
        $db = DzDb::getInstance();
        $employee_id = $this->getUser()->getId();

        Employee::init($db,$employee_id) ;

        // delete the requesting employee if the tester want 
        if($test_parameters["remove_employee_right_before_starting_the_transaction"]){
            Employee::delete();
        }

        // initiate the db connection and start a transaction
        $db->beginTransaction();

        // check if the employee still exist within this transaction 
        if (!Employee::does_it_exist()){
            // end the transaction
            $db->commit();

            return new JsonResponse([
                "status" => "unauthorized",
                "msg" => "THIS_EMPLOYEE_DOES_NOT_EXIST_ANY_MORE"
            ],401
            ) ;
        }

        // get the permission ids of the employee
        $employee_permission_ids = Employee::get_permissions() ;
        
        // check if the employee has any permissions
        if(empty($employee_permission_ids)){
            // end the transaction
            $db->commit();

            return new JsonResponse([
                "status" => "unauthorized",
                "msg" => "THIS_EMPLOYEE_DOES_NOT_HAVE_ANY_PERMISSIONS"
            ], 401);
        }


        // get the notifications overview data whithin the transaction
        Notification::init($db,$employee_id,$employee_permission_ids);
        $all_notifs_count = Notification::get_all_notifications_count();
        [$unpopped_up_notifications_count,$unpopped_up_notifications] = Notification::get_the_unpopped_up_notifications_by_the_empolyee($query_parameter["page_nb"],$query_parameter["batch_size"]);  
        
        // end the transaction
        $db->commit();

        return new JsonResponse([
            "status" => "success",
            "data" => [
                "all_notifs_count" => $all_notifs_count,
                "unpopped_up_notifications_count" => $unpopped_up_notifications_count,
                "unpopped_up_notifications" => $unpopped_up_notifications
            ]
  
        ]);
    }


    public function getNotificationsList(Request $request)
    {
        // get the test query paramerters
        $test_parameters = [
            "remove_employee_right_before_starting_the_transaction" => (int)$request->query->get('remove_employee_right_before_starting_the_transaction')
        ] ;

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

        // initiate the db connection and get the employee id
        $db = DzDb::getInstance();
        $employee_id = $this->getUser()->getId();

        Employee::init($db,$employee_id) ;

        // delete the requesting employee if the tester want 
        if($test_parameters["remove_employee_right_before_starting_the_transaction"]){
            Employee::delete($employee_id);
        }

        $db->beginTransaction();

        // check if the employee still exist within this transaction 
        if (!Employee::does_it_exist()){
            // end the transaction
            $db->commit();

            return new JsonResponse([
                "status" => "unauthorized",
                "msg" => "THIS_EMPLOYEE_DOES_NOT_EXIST_ANY_MORE"
            ],401) ;
        }

        // get the permission ids of the employee
        $employee_permission_ids = Employee::get_permissions() ;
        
        // check if the employee has any permissions
        if(empty($employee_permission_ids)){
            // end the transaction
            $db->commit();

            return new JsonResponse([
                "status" => "unauthorized",
                "msg" => "THIS_EMPLOYEE_DOES_NOT_HAVE_ANY_PERMISSIONS"
            ], 401);
        }

        // get the notification list
        Notification::init($db,$employee_id,$employee_permission_ids);
        $notifications = Notification::get_notifications($query_parameter["notif_type"], $query_parameter["page_nb"], $query_parameter["batch_size"]);

        $db->commit();

        return new JsonResponse([
            "status" => "success",
            "data" => ["notifications" => $notifications]
        ]);

    }

    public function markNotificationAsRead($notif_id,Request $request)
    {


        // initialize the db connection and get the employee id
        $db =  DzDb::getInstance();
        $employee_id = $this->getUser()->getId();

        // get the test query paramerters
        $test_parameters = [
            "delete_employee_before_marking_as_read" => (int)$request->query->get('delete_employee_before_marking_as_read') ,
            "delete_notification_before_marking_as_read" => (int)$request->query->get('delete_notification_before_marking_as_read'),
            "throw_exception" => (bool)$request->query->get('throw_exception')
        ] ;

        // get the permission ids of the employee
        Employee::init($db,$employee_id);
        $employee_permission_ids = Employee::get_permissions();

        // check if the employee has any permissions
        if (count($employee_permission_ids) == 0){
            return new JsonResponse([
                "status" => "unauthorized",
                "message" => "THIS_EMPLOYEE_DOES_NOT_HAVE_ANY_PERMISSIONS"
            ], 401);
        }

        // mark the notificaiton as read
        Notification::init($db,$employee_id,$employee_permission_ids);
        [$response,$status_code]  = Notification::mark_notification_as_read($notif_id,$test_parameters);

        // return the response
        return new JsonResponse($response, $status_code) ;


 
    }

    public function markAllNotificationsAsRead(Request $request)
    {
        // get the test query paramerters
        $test_parameters = [
            "testing" => (bool)$request->query->get('testing'),
            "throw_exception" => (bool)$request->query->get('throw_exception')
        ] ;

        // initialize the db connection and get the employee id
        $db =  DzDb::getInstance();
        $employee_id = $this->getUser()->getId();

        // get the permission ids of the employee
        EmployeePermission::init($db,$employee_id);
        $employee_permission_ids = EmployeePermission::get_permissions();

        // check if the employee has any permissions
        if (count($employee_permission_ids) == 0){
            return new JsonResponse([
                "status" => "unauthorized",
                "message" => "THIS_EMPLOYEE_DOES_NOT_HAVE_ANY_PERMISSIONS"
            ], 401);
        }
        
        // mark all the notifications as read
        Notification::init($db,$employee_id,$employee_permission_ids);
        [$response,$status_code] = Notification::mark_all_notifications_as_read($test_parameters['testing'],$test_parameters['throw_exception']);
        return new JsonResponse($response, $status_code) ;
        
    }


    public function markNotificationsAsPoppedUp(Request $request)
    {

        // get the test query paramerters
        $test_parameters = [
            "testing" => (bool)$request->query->get('testing'),
            "throw_exception" => (bool)$request->query->get('throw_exception')
        ] ;

        // get the request body
        $request_body = json_decode($request->getContent(), true) ;
        $request_body = is_array($request_body) ? $request_body : [] ;
        // define the constraints for the request body
        $constraints = new Assert\Collection([
            'notif_ids' => [
            new Assert\NotBlank(),
            new Assert\Type('array'),
            new All([
                new Assert\NotBlank(),
                new IsIntegerAndGreaterThanZero()
            ]),
            
            
            ]
        ]);

        // validate the request body
        $validationErrorRes = $this->validateData($request_body, $constraints);
        if ($validationErrorRes) {
            return $validationErrorRes;
        }

        // initialize the db connection and get the employee id
        $db =  DzDb::getInstance();
        $employee_id = $this->getUser()->getId();
        
        // get the permission ids of the employee
        EmployeePermission::init($db,$employee_id);
        $employee_permission_ids = EmployeePermission::get_permissions();

        // check if the employee has any permissions
        if (count($employee_permission_ids) == 0){
            return new JsonResponse([
                "status" => "unauthorized",
                "message" => "THIS_EMPLOYEE_DOES_NOT_HAVE_ANY_PERMISSIONS"
            ], 401);
        }
    
        // mark notifications as popped up
        Notification::init($db,$employee_id,$employee_permission_ids);
        [$response,$status_code] = Notification::mark_notifications_as_popped_up($request_body['notif_ids'],$test_parameters['testing'],$test_parameters['throw_exception']);
        return new JsonResponse($response, $status_code);

    }
}



