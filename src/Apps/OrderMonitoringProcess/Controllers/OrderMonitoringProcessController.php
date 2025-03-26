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


class OrderMonitoringProcessController extends FrameworkBundleAdminController
{   
    private const BATCH_SIZES = [20,50,100] ;

    public function launchOmpScript($order_monitoring_process_id, $employee_id) {
        // Path to the PHP script
        $script_path = dirname(__DIR__, 1) . '/order_monitoring_process.php';
        $logFilePath = _PS_MODULE_DIR_ . "dolzay/data/osomp.txt";
    
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
        $order_monitoring_process_id = OrderMonitoringProcess::insert($orders_to_monitor_cnt); 

        // launch the order submit process 
        $this->launchOmpScript($order_monitoring_process_id,$employee_id) ;
        return new JsonResponse(["status"=>"success","process"=>["id"=>$order_monitoring_process_id,"items_to_process_cnt"=>$orders_to_monitor_cnt]],200, ['json_options' => JSON_UNESCAPED_UNICODE]);
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
    public function orderMonitoringProcessDetail($process_id,Request $request){
        $query_parameter = [
            "order_id" => $request->query->get('order_id'),
            "client" => $request->query->get('client'),
            "new_status" => $request->query->get('new_status'),
            "page_nb" =>  $request->query->get('page_nb') ?? 1,
            "batch_size" => $request->query->get('batch_size') ?? self::BATCH_SIZES[0],
            "is_json" => $request->query->get('is_json')
        ];
        

        $db = DzDb::getInstance();
        OrderMonitoringProcess::init($db);
        $order_submit_process_detail = OrderMonitoringProcess::get_order_monitoring_process_detail($process_id,$query_parameter);
        
        // handle the api request 
        if($query_parameter['is_json']){
            if($order_submit_process_detail){
                return new JsonResponse(['status'=>"success",'order_submit_process'=>$order_submit_process_detail],200, ['json_options' => JSON_UNESCAPED_UNICODE]);
            }else{
                return new JsonResponse(['status'=>'not_found'],JsonResponse::HTTP_NOT_FOUND, ['json_options' => JSON_UNESCAPED_UNICODE]);
            }
        }
        
        // handle the template request 
        if($order_submit_process_detail){
            // setup the variables of the pagination
            $orders_to_submit = $order_submit_process_detail['orders_to_submit'] ;

            $total_pages = 1 ;
            $total_count = 0 ;
            $first_end = 0 ;
            $last_end = 0 ;
    
            if(count($orders_to_submit)){
                $total_count = $orders_to_submit[0]['total_count'] ;
                $total_pages = ceil($total_count / self::BATCH_SIZES[0]) ;
                $first_end = 1 ;
                $last_end = $total_count >= self::BATCH_SIZES[0] ? self::BATCH_SIZES[0] : $total_count ;
            }
            
            return $this->render("@Modules/dolzay/views/templates/admin/process/process_detail.html.twig",
                                 ["process"=>$order_submit_process_detail,
                                 'batch_sizes'=>self::BATCH_SIZES,
                                 'total_pages'=>$total_pages,
                                 'first_end'=>$first_end,
                                 'last_end'=>$last_end,
                                 'show_terminate_btn'=> in_array($order_submit_process_detail["status"],OrderSubmitProcess::ACTIVE_STATUSES),
                                 'total_count'=>$total_count]) ;
        }else{
            return $this->render("@Modules/dolzay/views/templates/admin/process/not_found_process.html.twig") ;
        }
    }



}