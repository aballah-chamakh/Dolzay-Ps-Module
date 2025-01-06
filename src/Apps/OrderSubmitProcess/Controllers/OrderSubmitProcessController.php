<?php

namespace Dolzay\Apps\OrderSubmitProcess\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Dolzay\Apps\Processes\Entities\Process;
use Dolzay\Apps\Processes\Entities\OrderToMonitor;
use Dolzay\CustomClasses\Db\DzDb;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;
use Dolzay\CustomClasses\Constraints\IsIntegerAndGreaterThanZero;
use Dolzay\CustomClasses\Constraints\All;
use Dolzay\Apps\OrderSubmitProcess\Entities\OrderSubmitProcess ;
use Dolzay\Apps\Settings\Entities\Carrier ;

class OrderSubmitProcessController extends FrameworkBundleAdminController
{   

    public function launchObsScript($order_submit_process_id,$carrier,$employee_id){
        // Path to the PHP script
        $script_path = dirname(__DIR__,1) .'/order_submit_process.php';
        $logFilePath = _PS_MODULE_DIR_."dolzay/uploads/log/log.txt" ;
        // Run the script in the background on Windows
        //$output = [];
        $returnVar = 0;
        $command = "start /B php $script_path  $order_submit_process_id $carrier $employee_id > $logFilePath 2>&1";
        exec($command);
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
                , Response::HTTP_BAD_REQUEST);
        }
    }


    public function OrderSubmitProcessList(Request $request){
        $batch_sizes = [20,50,100];
        $query_parameter = [
            "status" => $request->query->get('status'),
            "carrier" => $request->query->get('carrier'),
            "page_nb" =>  $request->query->get('page_nb') ?? 1,
            "batch_size" => $request->query->get('batch_size') ?? $batch_sizes[0],
            "start_date" => ($request->query->get('start_date') == "null") ? null : $request->query->get('start_date'),
            "end_date" => ($request->query->get('end_date') == "null") ? null : $request->query->get('end_date'),
            "is_json" => $request->query->get('is_json'),
        ];

        $db = DzDb::getInstance();
        OrderSubmitProcess::init($db);
        $order_submit_processes = OrderSubmitProcess::get_order_submit_process_list($query_parameter);
        
        if($query_parameter['is_json']){
            return new JsonResponse(['status'=>'success','order_submit_processes'=>$order_submit_processes]);
        }

        Carrier::init($db);
        $carriers = Carrier::get_all();

        $total_pages = 1 ;
        $total_count = 0 ;
        $first_end = 0 ;
        $last_end = 0 ;

        if(count($order_submit_processes)){
            $total_count = $order_submit_processes[0]['total_count'] ;
            $total_pages = ceil($total_count / $batch_sizes[0]) ;
            $first_end = 1 ;
            $last_end = $total_count >= $batch_sizes[0] ? $batch_sizes[0] : $total_count ;
        }
        

        return $this->render('@Modules/dolzay/views/templates/admin/process/process_list.html.twig',[
            'order_submit_processes'=>$order_submit_processes,
            'status_types'=> OrderSubmitProcess::STATUS_TYPES,
            'carriers'=>$carriers,
            'batch_sizes'=>$batch_sizes,
            'total_pages'=>$total_pages,
            'first_end'=>$first_end,
            'last_end'=>$last_end,
            'total_count'=>$total_count,
            'status_colors'=>OrderSubmitProcess::STATUS_COLORS
        ]);
    }

    public function OrderSubmitProcessDetail($process_id){
        $db = DzDb::getInstance();
        OrderSubmitProcess::init($db);
        $order_submit_process_detail = OrderSubmitProcess::get_order_submit_process_detail($process_id);
        
        if(!$order_submit_process_detail){
            return new JsonResponse(['status'=>'not_found'],JsonResponse::HTTP_NOT_FOUND) ;
        }
        
        return $this->render("@Modules/dolzay/views/templates/admin/process/process_detail.html.twig",
                             ["process"=>$order_submit_process_detail]) ;

    }



    public function launchOrderSubmitProcess(Request $request) {
        $employee_id = $this->getUser()->getId();
 
        // validate the order ids 

        // get the request body
        $request_body = json_decode($request->getContent(), true) ;
        $request_body = is_array($request_body) ? $request_body : [] ;
        
        // define the constraints for the request body
        $constraints = new Assert\Collection([
            'order_ids' => [
            new Assert\NotBlank(),
            new Assert\Type('array'),
            new All([
                new Assert\NotBlank(),
                new IsIntegerAndGreaterThanZero()
            ])],
            'carrier' =>[
                new Assert\NotBlank(),
                new Assert\Type('string')
             ]
        ]);

        // validate the request body
        $validationErrorRes = $this->validateData($request_body, $constraints);
        if ($validationErrorRes) {
            return $validationErrorRes;
        }

        $order_ids = $request_body['order_ids'] ;
        $carrier = $request_body['carrier'];

        // create an order submit process
        $db = DzDb::getInstance();
        $db->query("LOCK TABLES ".OrderSubmitProcess::TABLE_NAME." WRITE");
        OrderSubmitProcess::init($db);
        if($process = OrderSubmitProcess::get_running_process())
        {
            $db->query("UNLOCK TABLES");
            return new JsonResponse(['status'=>'conflict','process'=>$process],JsonResponse::HTTP_CONFLICT);
        } 
        $order_submit_process_id = OrderSubmitProcess::insert($carrier); 
        $db->query("UNLOCK TABLES");

        // get already submitted orders and orders with invalid field if they exist
        // then set them in the metadata of the order submit process
        $order_submit_process_metadata = OrderSubmitProcess::set_and_get_the_metadata_of_the_order_submit_process($order_submit_process_id,$order_ids) ;        
        

        $response = ["status"=>"success","process"=>["id"=>$order_submit_process_id]] ; 
        
        if($order_submit_process_metadata){
            $response['process']['meta_data'] = $order_submit_process_metadata ;
            return new JsonResponse($response) ;
        }

        // launch the order submit process 
        $this->launchObsScript($order_submit_process_id,$carrier,$employee_id) ;
        return new JsonResponse($response) ;
    }

    // this route can be called after the user selected the order to re-submit 
    // and the fixed the invalid values 
    public function continueOrderSubmitProcess($process_id,Request $request){
        $employee_id = $this->getUser()->getId();

        // validate the the orders to re-submit ids

        // get the request body
        $request_body = json_decode($request->getContent(), true) ;
        $request_body = is_array($request_body) ? $request_body : [] ;
        
        // define the constraints for the request body
        $constraints = new Assert\Collection([
            'order_ids' => [
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

        $order_to_resubmit_ids = $request_body['order_ids'];

        // check if the process exists otherwise return 404 
        $db = DzDb::getInstance() ;
        $db->beginTransaction() ;

        // get the order submit process and lock it
        OrderSubmitProcess::init($db);
        $process = OrderSubmitProcess::get_process($process_id,$lock_it=true);
        
        // handle the process was not fount
        if (!$process){
            $db->commit();
            return new JsonResponse(['status'=>'not_found'],JsonResponse::HTTP_NOT_FOUND) ;
        }

        // handle the process isn't not still initiated
        if ($process['status'] != "Initié"){
            $db->commit();
            return new JsonResponse(['status'=>"conflict",'process_status'=>$process['status']],JsonResponse::HTTP_CONFLICT);
        }

        // the orders to resubmit, check if the invalid orders were fixed and activate the process
        $items_to_process_cnt = OrderSubmitProcess::add_orders_to_resubmit_and_activate_the_process($process,$order_to_resubmit_ids);
        if ($items_to_process_cnt == 0){
            OrderSubmitProcess::cancel($process['id'],"Annulé automatiquement");
            $db->commit() ;
        }else{
            $db->commit() ;
            $this->launchObsScript($process['id'],$process['carrier'],$employee_id) ;
        }
        // launch the order submit process 
        return new JsonResponse(['status'=>'success','items_to_process_cnt' => $items_to_process_cnt], 200);                
    }

    // this route can be called if the user wants to cancel an initiated process 
    public function cancelOrderSubmitProcess($process_id){
        
        // check if the process exists otherwise return 404 
        $db = DzDb::getInstance() ;
        $db->beginTransaction() ;

        // get the order submit process and lock it
        OrderSubmitProcess::init($db);
        $process = OrderSubmitProcess::get_process($process_id,$lock_it=true);
        
        // handle the process was not found
        if (!$process){
            $db->commit();
            return new JsonResponse(['status'=>'not_found'],JsonResponse::HTTP_NOT_FOUND) ;
        }

        // handle the process isn't still initiated
        if ($process['status'] != "Initié"){
            $db->commit();
            return new JsonResponse(['status'=>"conflict",'process_status'=>$process['status']],JsonResponse::HTTP_CONFLICT);
        }

        OrderSubmitProcess::cancel($process['id'],"Annulé par l'utilisateur");
        $db->commit() ;
        return new JsonResponse(['status'=>"success"]) ;

    }

    public function monitorOrderSubmitProcess($process_id){
        $db = DzDb::getInstance() ;
        OrderSubmitProcess::init($db);
        $process_status = OrderSubmitProcess::get_process_status($process_id);

        // handle the process was not found
        if (!$process_status){
            return new JsonResponse(['status'=>'not_found'],JsonResponse::HTTP_NOT_FOUND) ;
        }

        return new JsonResponse(['status'=>'success',"process"=>$process_status]);

    }

    public function terminateOrderSubmitProcess($process_id){
        // check if the process exists otherwise return 404 
        $db = DzDb::getInstance() ;
        $db->beginTransaction() ;

        // get the order submit process and lock it
        OrderSubmitProcess::init($db);
        $process = OrderSubmitProcess::get_process($process_id,$lock_it=true);
        
        // handle the process was not found
        if (!$process){
            $db->commit();
            return new JsonResponse(['status'=>'not_found'],JsonResponse::HTTP_NOT_FOUND) ;
        }

        // terminate the process only if it's active to no override other end status
        if ($process['status'] != "Actif"){
            return new JsonResponse(['status'=>"conflict",'process_status'=>$process['status']]) ;
        }

        // interrupt the order submit process
        OrderSubmitProcess::terminate($process_id);
        $db->commit();  
        return new JsonResponse(['status'=>"success"]) ;
      
    }






}