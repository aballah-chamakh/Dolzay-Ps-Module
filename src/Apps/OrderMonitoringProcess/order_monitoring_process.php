<?php

require_once dirname(__DIR__, 5) . '/config/config.inc.php'; // Include PrestaShop's configuration


// get the process id and the carrier name
$process_id = (int)$argv[1] ;
$employee_id = (int)$argv[2] ;

// monitor the orders of each carrier
// require the file of the class
$class_path =  dirname(__DIR__, 2) .DIRECTORY_SEPARATOR ."CarrierApiClients".DIRECTORY_SEPARATOR ."AfexCarrier.php";
require_once $class_path ;
$carrier_class = "\\Dolzay\\CarrierApiClients\\AfexCarrier";
$carrier_class::init($process_id,$employee_id) ;
$result = $carrier_class::monitor_orders();

if(!array_key_exists('error_message', $result)){
    // note : 
    // 1- im terminating the the Omp process here and not in the "monitor_orders" method because the plugin 
    //    can have many carriers.
    // 2- i terminate the Omp process only if the "monitor_orders" method returns true beccause
    //    when it returns false it means that the Omp was interrupted and i don't want to override the 
    //    the "Interronpu" status of the Omp.
    $carrier_class::updateOrderMonitoringProcess(
        [
            "status"=>"Terminé",
            "ended_at"=>date('Y-m-d H:i:s')
        ],true
    );
    $carrier_class::commit();
} 
