<?php

require_once dirname(__DIR__, 5) . '/config/config.inc.php'; // Include PrestaShop's configuration

define('_MODULE_PREFIX_','dz_') ;
define('_SUPPORT_PHONE_','58671414') ;

// get the process id and the carrier name
$process_id = (int)$argv[1] ;
$employee_id = (int)$argv[2] ;

// monitor the orders of each carrier
// require the file of the class
require_once dirname(__DIR__, 2) .DIRECTORY_SEPARATOR ."CarrierApiClients".DIRECTORY_SEPARATOR ."AfexCarrier.php";

AfexCarrier::init($process_id,$employee_id) ;
if(AfexCarrier::monitor_orders()){
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
        ],true
    );
    AfexCarrier::commit();
} 
