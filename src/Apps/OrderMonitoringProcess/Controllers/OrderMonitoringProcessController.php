<?php

namespace Dolzay\Apps\OrderMonitoringProcess\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Dolzay\CustomClasses\Db\DzDb;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;
use Dolzay\CustomClasses\Constraints\IsIntegerAndGreaterThanZero;
use Dolzay\CustomClasses\Constraints\All;
use Dolzay\Apps\OrderMonitoringProcess\Entities\OrderMonitoringProcess ;
use Dolzay\Apps\OrderMonitoringProcess\Entities\OrderToMonitor ;
use Dolzay\Apps\Settings\Entities\Carrier ;
use Dolzay\Apps\Settings\Entities\Settings ;
use Dolzay\CarrierApiClients\AfexCarrier ;

class OrderMonitoringProcessController extends FrameworkBundleAdminController
{   
    private const BATCH_SIZES = [20,50,100] ;

    public function launchOmpScript($order_monitoring_process_id, $employee_id,$process_execution_type) {
        
        // Path to the PHP script
        if ($process_execution_type == "async"){
            $script_path = dirname(__DIR__, 1) . '/order_monitoring_process.php';
            $logFilePath = _PS_MODULE_DIR_ . "dolzay/data/process_logs.txt";
        
            // Determine the operating system
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows command
                $command = "start /B php $script_path $order_monitoring_process_id $employee_id >> $logFilePath 2>&1";
                pclose(popen($command, 'r'));
            } else {
                // Linux/Unix command
                $command = "php $script_path $order_monitoring_process_id $employee_id >> $logFilePath 2>&1 &";
                exec($command);
            }
            return null ;
        }else{
            AfexCarrier::init($order_monitoring_process_id,$employee_id) ;
            $result = AfexCarrier::monitor_orders();
            if(!array_key_exists('error_message', $result)){
                // note : 
                // 1- im terminating the the Omp process here and not in the "monitor_orders" method because the plugin 
                //    can have many carriers.
                // 2- i terminate the Omp process only if the "monitor_orders" method returns true beccause
                //    when it returns false it means that the Omp was interrupted and i don't want to override the 
                //    the "Interronpu" status of the Omp.
                AfexCarrier::updateOrderMonitoringProcess(
                    [
                        "status"=>"Terminé",
                        "ended_at"=>date('Y-m-d H:i:s')
                    ]
                );
                
            } 
            return $result;
        }

    }


    // ACID FREINDLY
    public function launchOrderMonitoringProcess(Request $request) {

        // check if the plugin didn't expire 
        $db = DzDb::getInstance();
        
        if(Settings::did_the_plugin_expire($db)){
            return new JsonResponse(['status'=>"expired"],403, ['json_options' => JSON_UNESCAPED_UNICODE]);
        }
        OrderToMonitor::init($db);
        $orders_to_monitor_cnt = OrderToMonitor::getOrdersToMonitorCount();

        if (!$orders_to_monitor_cnt){
            return new JsonResponse(['status'=>"no_orders_to_monitor"],200, ['json_options' => JSON_UNESCAPED_UNICODE]);
        }

        $employee_id = $this->getUser()->getId();
        OrderMonitoringProcess::init($db);
        $order_monitoring_process_id = OrderMonitoringProcess::insert($orders_to_monitor_cnt,$employee_id); 

        $process_execution_type = Settings::get_process_execution_type($db);
        $process = ["id"=>$order_monitoring_process_id,
                    "items_to_process_cnt"=>$orders_to_monitor_cnt,
                    "process_execution_type"=>$process_execution_type];
        // launch the order monitoring process 
        if($process_execution_type == "sync" || $process_execution_type == "async"){
            $result = $this->launchOmpScript($order_monitoring_process_id,$employee_id,$process_execution_type) ;
            $process["result"] = $result;
        }
        return new JsonResponse(["status"=>"success","process"=>$process],200, ['json_options' => JSON_UNESCAPED_UNICODE]);
    }


    // ACID FREINDLY
    public function monitorOrderMonitoringProcess($process_id){
        $db = DzDb::getInstance() ;
        OrderMonitoringProcess::init($db);
        $process_status = OrderMonitoringProcess::get_process_status($process_id);

        // handle the process was not found
        if (!$process_status){
            return new JsonResponse(['status'=>'not_found'],JsonResponse::HTTP_NOT_FOUND, ['json_options' => JSON_UNESCAPED_UNICODE]);
        }

        return new JsonResponse(['status'=>'success',"process"=>$process_status],200, ['json_options' => JSON_UNESCAPED_UNICODE]);
    }

    // ACID FRIENDLY
    public function orderMonitoringProcessList(Request $request){
        
        $query_parameter = [
            "is_json" => $request->query->get('is_json'),
            "status" => $request->query->get('status'),
            "start_date" => ($request->query->get('start_date') == "null") ? null : $request->query->get('start_date'),
            "end_date" => ($request->query->get('end_date') == "null") ? null : $request->query->get('end_date'),
            "page_nb" =>  $request->query->get('page_nb') ?? 1,
            "batch_size" => $request->query->get('batch_size') ?? self::BATCH_SIZES[0]
        ];

        $db = DzDb::getInstance();
        orderMonitoringProcess::init($db);
        $order_monitoring_processes = orderMonitoringProcess::get_order_monitoring_process_list($query_parameter);
        
        if($query_parameter['is_json']){
            return new JsonResponse(['status'=>'success','order_monitoring_processes'=>$order_monitoring_processes],200, ['json_options' => JSON_UNESCAPED_UNICODE]);
        }

        $total_pages = 1 ;
        $total_count = 0 ;
        $first_end = 0 ;
        $last_end = 0 ;

        if(count($order_monitoring_processes)){
            $total_count = $order_monitoring_processes[0]['total_count'] ;
            $total_pages = ceil($total_count / self::BATCH_SIZES[0]) ;
            $first_end = 1 ;
            $last_end = $total_count >= self::BATCH_SIZES[0] ? self::BATCH_SIZES[0] : $total_count ;
        }

        
        return $this->render('@Modules/dolzay/views/templates/admin/omp/omp_list.html.twig',[
            'order_monitoring_processes'=>$order_monitoring_processes,
            'status_types'=> orderMonitoringProcess::STATUS_TYPES,
            'batch_sizes'=>self::BATCH_SIZES,
            'total_pages'=>$total_pages,
            'first_end'=>$first_end,
            'last_end'=>$last_end,
            'total_count'=>$total_count,
            'status_colors'=>orderMonitoringProcess::STATUS_COLORS
        ]);
    }

    // ACID FRIENDLY
    public function orderMonitoringProcessDetail($process_id,Request $request){
        
        $is_json = $request->query->get('is_json');

        $updated_orders_qp = [
            "order_id" => $request->query->get('updated_orders__order_id'),
            "client" => $request->query->get('updated_orders__client'),
            "new_status" => $request->query->get('updated_orders__new_status'),
            "old_status" => $request->query->get('updated_orders__old_status'),
            "page_nb" =>  $request->query->get('updated_orders__page_nb') ?? 1,
            "batch_size" => $request->query->get('updated_orders__batch_size') ?? self::BATCH_SIZES[0],

        ];

        $orders_with_errors_qp = [
            "order_id" => $request->query->get('orders_with_errors__order_id'),
            "client" => $request->query->get('orders_with_errors__client'),
            "error_type" => $request->query->get('orders_with_errors__error_type'),
            "page_nb" =>  $request->query->get('orders_with_errors__page_nb') ?? 1,
            "batch_size" => $request->query->get('orders_with_errors__batch_size') ?? self::BATCH_SIZES[0]
        ];
        
        $defaultLanguageId = $this->getContext()->language->id;
        $db = DzDb::getInstance();
        OrderMonitoringProcess::init($db);
        $order_monitoring_process_detail = OrderMonitoringProcess::get_order_monitoring_process_detail($process_id,$updated_orders_qp,$orders_with_errors_qp,$defaultLanguageId);
        
        // handle the api request 
        if($is_json){
            if($order_monitoring_process_detail){
                return new JsonResponse(['status'=>"success",'order_monitoring_process'=>$order_monitoring_process_detail],200, ['json_options' => JSON_UNESCAPED_UNICODE]);
            }else{
                return new JsonResponse(['status'=>'not_found'],JsonResponse::HTTP_NOT_FOUND, ['json_options' => JSON_UNESCAPED_UNICODE]);
            }
        }
        
        // handle the template request 
        if($order_monitoring_process_detail){
            // setup the pagination attributes of the updated orders 
            $pagination_attributes_of_updated_orders = [
                "total_pages" => 1,
                "total_count" => 0,
                "first_end"  => 0,
                "last_end" => 0 
            ];

            $updated_orders = $order_monitoring_process_detail['updated_orders'] ;
    
            if(count($updated_orders)){
                $pagination_attributes_of_updated_orders['total_count'] = $updated_orders[0]['total_count'] ;
                $pagination_attributes_of_updated_orders['total_pages'] = ceil($updated_orders[0]['total_count'] / self::BATCH_SIZES[0]) ;
                $pagination_attributes_of_updated_orders['first_end'] = 1 ;
                $pagination_attributes_of_updated_orders['last_end'] = $updated_orders[0]['total_count'] >= self::BATCH_SIZES[0] ? self::BATCH_SIZES[0] : $updated_orders[0]['total_count'] ;
            }

            // setup the pagination attribute of the orders with errors 
            $pagination_attributes_of_orders_with_errors = [
                "total_pages" => 1,
                "total_count" => 0,
                "first_end"  => 0,
                "last_end" => 0 
            ];

            $orders_width_errors = $order_monitoring_process_detail['orders_with_errors'] ;
    
            if(count($orders_width_errors)){
                $pagination_attributes_of_orders_with_errors['total_count'] = $orders_width_errors[0]['total_count'] ;
                $pagination_attributes_of_orders_with_errors['total_pages'] = ceil($orders_width_errors[0]['total_count'] / self::BATCH_SIZES[0]) ;
                $pagination_attributes_of_orders_with_errors['first_end'] = 1 ;
                $pagination_attributes_of_orders_with_errors['last_end'] = $orders_width_errors[0]['total_count'] >= self::BATCH_SIZES[0] ? self::BATCH_SIZES[0] : $orders_width_errors[0]['total_count'] ;
            }

            // ps_order_state
            $query = "SELECT Os.id_order_state,name,color FROM "._DB_PREFIX_."order_state AS Os ";
            $query .= "INNER JOIN "._DB_PREFIX_."order_state_lang AS Osl ON Os.id_order_state=Osl.id_order_state ";
            $query .= "WHERE id_lang=".$defaultLanguageId ;
            $stmt = $db->query($query);
            $order_state_options = $stmt->fetchAll();
            
            return $this->render("@Modules/dolzay/views/templates/admin/omp/omp_detail.html.twig",
                                 ["process"=>$order_monitoring_process_detail,
                                 "order_state_options"=>$order_state_options,
                                 'batch_sizes'=>self::BATCH_SIZES,
                                 'pagination_attributes_of_updated_orders'=>$pagination_attributes_of_updated_orders,
                                 'pagination_attributes_of_orders_with_errors'=>$pagination_attributes_of_orders_with_errors
                            ]) ;
        }

        $this->redirectToRoute('dz_order_monitoring_process_list');
    }

    public function getUpdatedOrdersOfAnOmp($process_id,Request $request){
        $updated_orders_qp = [
            "order_id" => $request->query->get('order_id'),
            "client" => $request->query->get('client'),
            "new_status" => $request->query->get('new_status'),
            "old_status" => $request->query->get('old_status'),
            "page_nb" =>  (int)$request->query->get('page_nb') ?? 1,
            "batch_size" => (int)$request->query->get('batch_size') ?? self::BATCH_SIZES[0]
        ];
        $defaultLanguageId = $this->getContext()->language->id;
        $db = DzDb::getInstance();
        OrderMonitoringProcess::init($db);

        $updated_orders = OrderMonitoringProcess::get_updated_orders($process_id,$updated_orders_qp,$defaultLanguageId);

        return new JsonResponse(['status'=>"success",
                                 'orders'=>$updated_orders],200, ['json_options' => JSON_UNESCAPED_UNICODE]);
    }

    public function getOrdersWithErrorsOfAnOmp($process_id,Request $request){

        $orders_with_errors_qp = [
            "order_id" => $request->query->get('order_id'),
            "client" => $request->query->get('client'),
            "error_type" => $request->query->get('error_type'),
            "page_nb" =>  $request->query->get('page_nb') ?? 1,
            "batch_size" => $request->query->get('batch_size') ?? self::BATCH_SIZES[0]
        ];

        $db = DzDb::getInstance();
        OrderMonitoringProcess::init($db);

        $orders_with_errors = OrderMonitoringProcess::get_orders_with_errors($process_id,$orders_with_errors_qp);

        return new JsonResponse(['status'=>"success",
                                 'orders'=>$orders_with_errors],200, ['json_options' => JSON_UNESCAPED_UNICODE]);
    }  


}