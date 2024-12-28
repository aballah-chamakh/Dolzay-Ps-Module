<?php
echo "executed !!!!!!!!!!!!!!!!!" ;

require_once dirname(__DIR__, 5) . '/config/config.inc.php'; // Include PrestaShop's configuration

define('_MODULE_PREFIX_','dz_') ;
define('_SUPPORT_PHONE_','58671414') ;

// get the process id and the carrier name
$process_id = (int)$argv[1] ;
$carrier = $argv[2] ;
$employee_id = (int)$argv[3] ;

echo "arguments : $process_id $carrier $employee_id";

// construct the carrier class name
$carrier_class_name = $carrier."Carrier"; // example "Afex"."Carrier"

// require the file of the class
require_once dirname(__DIR__, 2) . "/CarrierApiclients//" .$carrier_class_name.".php"; 

$carrier_class_name::init($process_id,$employee_id) ;
$carrier_class_name::submit_orders(); 


/*
define

function connect_to_db(){
    $host = _DB_SERVER_;
    $dbname = _DB_NAME_;
    $username = _DB_USER_;
    $password = _DB_PASSWD_;

    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

    $conn = new PDO($dsn, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $conn->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $conn->exec("SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ");
    return $conn;
}

function get_the_order_submit_process($process_id){
    $stmt = $db->query("SELECT carrier_id,metadata FROM dz_order_submit_process WHERE id=$process_id") ;
    return $stmt->fetch() ;
}

function get_the_orders_to_submit($order_ids){
    $query = "SELECT id_order,total_paid,address1,city,delegation,phone FROM ". _DB_PREFIX_."orders AS Ord INNER JOIN ". _DB_PREFIX_."address AS addr ON Ord.id_delivery_address=Addr.id WHERE Ord.id_order IN  (".implode(',',$order_ids).")" ;
    $stmt = $db->query($query);
    $orders_to_submit= $stmt->fetchAll() ;

    // add the cart products to each order 
    foreach($orders_to_submit as &$order_to_submit){
        $query = "SELECT product_name as name product_quantity as quantity FROM" . _DB_PREFIX_ . "order_detail WHERE id_order=" . $order_to_submit['id_order'];
        $stmt = $db->query($query) ;
        $cart_products =  $stmt->fetch() ;
        $order_to_submit['cart_products'] = $cart_products ;
    }

    return $orders_to_submit ;
}

function get_carrier_credentials($carrier_id){
    $query = "SELECT u,  FROM dz_carrier INNER JOIN ";
    $stmt = $db->query($query) ;
}


$db = connect_to_db();
$process_id = (int)$argv[1] ;

// get the order submit process 
$order_submit_process = get_the_order_submit_process($process_id) ;
$carrier_id = $order_submit_process['carrier_id'] ;
$order_to_submit_ids = $order_submit_process['meta_data']['valid_order_ids'] ;

// get the orders to submit 
$orders_to_submit = get_the_orders_to_submit($order_to_submit_ids);

// get the credentials of the carrier 


// start submitting the orders 



//$logFile = _PS_MODULE_DIR_ . 'dolzay/log.txt';
//file_put_contents($logFile, "Result: process_id : $process_id || " . json_encode($orders) . PHP_EOL, FILE_APPEND);

*/

             