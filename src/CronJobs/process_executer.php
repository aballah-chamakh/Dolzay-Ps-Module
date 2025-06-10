<?php

require_once dirname(__DIR__, 4) . '/config/config.inc.php';

define("_MODULE_PREFIX_","dz_") ;

function connect_to_db(){

    if (PHP_OS_FAMILY === 'Linux') {
        [$dbHost,$dbPort]=explode(":",_DB_SERVER_);
    }else{
        $dbHost = _DB_SERVER_;
    }
    $dbName = _DB_NAME_;
    $dbUser = _DB_USER_;
    $dbPassword = _DB_PASSWD_;

    $dsn = "mysql:host=$dbHost;";
    if (PHP_OS_FAMILY === 'Linux'){
        $dsn .="port=$dbPort;";
    }
    $dsn .= "dbname=$dbName;charset=utf8mb4";

    $conn = new PDO($dsn, $dbUser, $dbPassword);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $conn->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $conn->exec("SET NAMES utf8mb4 COLLATE utf8mb4_general_ci");
    $conn->exec("SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ");
    return $conn;

}

function get_osps_and_omps_ordered_by_daterange_descending($db){
    $query = "SELECT id,employee_id,carrier,meta_data, status, started_at,'osp' as process_type FROM "._MODULE_PREFIX_."order_submit_process OSP WHERE status='Actif' UNION ALL SELECT id,employee_id,'' as carrier,'' as meta_data,status,started_at,'omp' as process_type FROM "._MODULE_PREFIX_."order_monitoring_process OMP WHERE status='Actif' ORDER BY started_at ASC";
    $stmt = $db->query($query);
    return $stmt->fetchAll();
}

function get_all_carriers($db){
    $query = "SELECT name FROM "._MODULE_PREFIX_."carrier";
    $stmt = $db->query($query);
    return $stmt->fetchAll();
}

function get_omp_status($db,$process_id){
    $query = "SELECT status FROM "._MODULE_PREFIX_."order_monitoring_process WHERE id=:id";
    $stmt = $db->prepare($query);
    $stmt->execute(['id'=>$process_id]);
    return $stmt->fetch()['status'];
}

function terminate_omp($db,$process_id){
    $query = "UPDATE "._MODULE_PREFIX_."order_monitoring_process SET status='Terminé' WHERE id=:id";
    $stmt = $db->prepare($query);
    $stmt->execute(['id'=>$process_id]);
}

function execute_processes(){
    $db = connect_to_db();
    $carriers = get_all_carriers($db);
    $last_carrier = end($carriers);
    $processes = get_osps_and_omps_ordered_by_daterange_descending($db);
    foreach($processes as $process){
        
        if($process['process_type'] == 'osp'){
            echo "Processing osp with id: ".$process['id']."\n";
            $carrier_name = $process['carrier'] ;
            $carrier_class_name = $carrier_name."Carrier";
            require_once dirname(__DIR__) . DIRECTORY_SEPARATOR ."CarrierApiClients".DIRECTORY_SEPARATOR .$carrier_class_name.".php";
            $carrier_class = "\\Dolzay\\CarrierApiClients\\".$carrier_class_name;
            $carrier_class::init($process['id'],$process['employee_id'],$db);
            $carrier_class::submit_orders();
        }else{
            echo "Processing omp with id: ". $process['id']."\n";
            $interrupted = false;
            foreach($carriers as $carrier){
                $carrier_class_name = $carrier['name']."Carrier";
                require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . "CarrierApiClients" . DIRECTORY_SEPARATOR . $carrier_class_name.".php";
                $carrier_class = "\\Dolzay\\CarrierApiClients\\".$carrier_class_name;
                $carrier_class::init($process['id'],$process['employee_id'],$db);
                $result = $carrier_class::monitor_orders();

                if(array_key_exists('error_message',$result)){
                    $interrupted = true;
                    break;
                }
            }
            if(!$interrupted){
                terminate_omp($db,$process['id']);
            }
        }
    }
}


execute_processes();





?>