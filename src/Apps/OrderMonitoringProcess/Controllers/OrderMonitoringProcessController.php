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

        // launch the order monitoring process 
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
        $query_parameter = [
            "order_id" => $request->query->get('order_id'),
            "client" => $request->query->get('client'),
            "old_status" => $request->query->get('old_status'),
            "new_status" => $request->query->get('new_status'),
            "page_nb" =>  $request->query->get('page_nb') ?? 1,
            "batch_size" => $request->query->get('batch_size') ?? self::BATCH_SIZES[0],
            "is_json" => $request->query->get('is_json')
        ];
        
        $defaultLanguageId = $this->getContext()->language->id;
        $db = DzDb::getInstance();
        OrderMonitoringProcess::init($db);
        $order_monitoring_process_detail = OrderMonitoringProcess::get_order_monitoring_process_detail($process_id,$query_parameter,$defaultLanguageId);
        
        // handle the api request 
        if($query_parameter['is_json']){
            if($order_monitoring_process_detail){
                return new JsonResponse(['status'=>"success",'order_monitoring_process'=>$order_monitoring_process_detail],200, ['json_options' => JSON_UNESCAPED_UNICODE]);
            }else{
                return new JsonResponse(['status'=>'not_found'],JsonResponse::HTTP_NOT_FOUND, ['json_options' => JSON_UNESCAPED_UNICODE]);
            }
        }
        
        // handle the template request 
        if($order_monitoring_process_detail){
            // setup the variables of the pagination
            $updated_orders = $order_monitoring_process_detail['updated_orders'] ;

            $total_pages = 1 ;
            $total_count = 0 ;
            $first_end = 0 ;
            $last_end = 0 ;
    
            if(count($updated_orders)){
                $total_count = $updated_orders[0]['total_count'] ;
                $total_pages = ceil($total_count / self::BATCH_SIZES[0]) ;
                $first_end = 1 ;
                $last_end = $total_count >= self::BATCH_SIZES[0] ? self::BATCH_SIZES[0] : $total_count ;
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
                                 'total_pages'=>$total_pages,
                                 'first_end'=>$first_end,
                                 'last_end'=>$last_end,
                                 'total_count'=>$total_count]) ;
        }

        $this->redirectToRoute('dz_order_monitoring_process_list');
    }



}