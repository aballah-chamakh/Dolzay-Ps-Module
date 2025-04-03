<?php

class BaseCarrier {

    protected static $db ;
    protected static $process_id ;
    protected static $employee_id ;

    public function init($process_id,$employee_id){
        self::$db = self::connect_to_db() ;
        self::$process_id = $process_id ;
        self::$employee_id = $employee_id ;
    }

    private static function connect_to_db(){

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

    protected static function get_post_submit_status_id(){
        $settings = self::$db->query("SELECT post_submit_state_id FROM dz_settings")->fetch() ;
        return $settings['post_submit_state_id'] ;
    }

    protected static function get_the_orders_to_submit(){
        
        // get ids of the orders to submit from the order submit process
        $stmt = self::$db->query("SELECT meta_data FROM dz_order_submit_process WHERE id=".self::$process_id) ;
        $process = $stmt->fetch();
        $order_ids = json_decode($process['meta_data'],true)['valid_order_ids'] ;

        // get orders with their info 
        $query = "SELECT id_order,total_paid,firstname,lastname,address1,city,delegation,phone FROM ". _DB_PREFIX_."orders AS Ord INNER JOIN ". _DB_PREFIX_."address AS Addr ON Ord.id_address_delivery=Addr.id_address WHERE Ord.id_order IN  (".implode(',',$order_ids).")" ;
        $stmt = self::$db->query($query);
        $orders_to_submit= $stmt->fetchAll() ;
    
        // add the cart products to each order 
        foreach($orders_to_submit as &$order_to_submit){
            $query = "SELECT product_name as name, product_quantity as quantity FROM " . _DB_PREFIX_ . "order_detail WHERE id_order=" . $order_to_submit['id_order'];
            $stmt = self::$db->query($query) ;
            $cart_products =  $stmt->fetchAll() ;
            $order_to_submit['cart_products'] = $cart_products ;
        }
    
        return $orders_to_submit ;
    }

    protected static function get_cart_products_str($cart_products){
        $goods = ''; // Initialize the variable
        $cart_product_len = count($cart_products); // Get the total number of products
        foreach ($cart_products as $idx=>$product) {
            $goods .= "{$product['quantity']} x {$product['name']}"; // Append quantity and name
            if ($idx+1 != $cart_product_len) {
                $goods .= ','; // Add a comma if not the last product
            }
        }
        return $goods ;
    }

    protected static function updateOrder($order_id,$updates){
        self::$db->query("UPDATE "._DB_PREFIX_."orders SET ".implode(", ", $updates)." WHERE id_order=".$order_id);
    }

    protected static function addOrderToMonitoring($carrier,$order_id,$carrier_order_ref){
        
        // if the order has an order to monitor update it
        $sql = "UPDATE "._MODULE_PREFIX_."order_to_monitor SET carrier=:carrier,order_id=:order_id,carrier_order_ref=:carrier_order_ref WHERE order_id=:xorder_id";
        $stmt = self::$db->prepare($sql);
        $stmt->execute(
            [
                'carrier' => $carrier,
                'order_id' => $order_id,
                'carrier_order_ref' => $carrier_order_ref,
                'xorder_id' => $order_id
            ]        
        );
        $updatedRows = $stmt->rowCount();

        // otherwise insert a new one 
        if($updatedRows == 0){
            $query = "INSERT INTO "._MODULE_PREFIX_."order_to_monitor (carrier,order_id,carrier_order_ref) VALUES (:carrier,:order_id,:carrier_order_ref);";
            $stmt = self::$db->prepare($query);
            $stmt->execute([
                'carrier'=> $carrier,
                'order_id'=> $order_id,
                'carrier_order_ref'=> $carrier_order_ref
            ]);
        }

    }

    public static function insert_an_updated_order($omp_id, $order_id, $old_status, $new_status): int {
        $query = "INSERT INTO "._MODULE_PREFIX_."updated_order (omp_id, order_id, old_status, new_status) VALUES($omp_id, $order_id, $old_status, $new_status);";
        self::$db->query($query);
        return (int)self::$db->lastInsertId();
    }

    public static function updateOrderSubmitProcess($updates){
        
        $setParts = [];
        $params = [];
        foreach ($updates as $key => $value) {
            $setParts[] = "$key = :$key";
            $params[":$key"] = $value;
        }
        $setClause = implode(', ', $setParts);

        $query = "UPDATE "._MODULE_PREFIX_."order_submit_process SET $setClause WHERE id=".self::$process_id ;
        echo $query ;
        $stmt = self::$db->prepare($query);
        $stmt->execute($params);
        
    }

    public static function updateOrderMonitoringProcess($updates,$commit=false){

        $setParts = [];
        $params = [];
        foreach ($updates as $key => $value) {
            $setParts[] = "$key = :$key";
            $params[":$key"] = $value;
        }
        $setClause = implode(', ', $setParts);

        $query = "UPDATE "._MODULE_PREFIX_."order_monitoring_process SET $setClause WHERE id=".self::$process_id ;
        $stmt = self::$db->prepare($query);
        $stmt->execute($params);
        if($commit){
            self::$db->commit();
        }
    }

    protected static function getOspStatus(){
        $query = "SELECT status FROM "._MODULE_PREFIX_."order_submit_process  WHERE id=".self::$process_id ;
        return self::$db->query($query)->fetch()['status'];
    }

    protected static function addOrderStatusHistory($order_id,$post_submit_status_id){
        $query = "INSERT INTO "._DB_PREFIX_."order_history (id_employee,id_order,id_order_state,date_add) VALUES (:employee_id,:order_id,:order_state_id,NOW());";
        $stmt = self::$db->prepare($query);
        $stmt->execute([
            'employee_id'=>self::$employee_id,
            'order_id'=>$order_id,
            'order_state_id'=>$post_submit_status_id
        ]);
    }

    protected static function getOrdersToMonitorByCarrier($carrier){
        $query = "SELECT order_id,carrier_order_ref,Ord.current_state FROM "._MODULE_PREFIX_."order_to_monitor as Otm INNER JOIN "._DB_PREFIX_."orders AS Ord ON Ord.id_order=Otm.order_id WHERE Otm.carrier='" . $carrier."' ;" ;
        return self::$db->query($query)->fetchAll();
    }

    protected static function removeOrderFromMonitoring($order_id){
        $query = "DELETE FROM "._MODULE_PREFIX_."order_to_monitor WHERE order_id=$order_id" ;
        self::$db->query($query);
    }

    
    //error='{"message":"Le token d'Afex est invalide. Veuillez le mettre \u00e0 jour avec un token valide.","status_code":401}'"
    //"UPDATE dz_order_submit_process SET status='Interrompu', error='{"message":"Le token d\\'Afex est invalide. Veuillez le mettre \u00e0 jour avec un token valide.","status_code":401}' WHERE id=2"
    //"UPDATE dz_order_submit_process SET status='Interrompu', error='{"message":"Le système d'Afex a été mis à jour. Veuillez appeler le support de Dolzay au 58671414 afin qu'ils vous fournissent la dernière mise à jour.","status_code":422,"response":"{\"message\": \"The request data contains invalid fields or fails validation.\", \"errors\": [{\"field\": \"delegation\", \"message\": \"Delegation is not valid\"}]}"}' WHERE id=13"
    //""UPDATE dz_order_submit_process SET status='Interrompu', error='{"message":"Le système d'Afex a été mis à jour. Veuillez appeler le support de Dolzay au 58671414 afin qu'ils vous fournissent la dernière mise à jour.","status_code":422,"response":"{\"message\": \"The request data contains invalid fields or fails validation.\", \"errors\": [{\"field\": \"delegation\", \"message\": \"Delegation is not valid\"}]}"}' WHERE id=15""
// "UPDATE dz_order_submit_process SET status='Interrompu', error='{"message":"Le système d'Afex a été mis à jour. Veuillez appeler le support de Dolzay au 58671414 afin qu'ils vous fournissent la dernière mise à jour.","status_code":422,"response":{"message":"The request data contains invalid fields or fails validation.","errors":[{"field":"delegation","message":"Delegation is not valid"}]}}' WHERE id=3"
// "UPDATE dz_order_submit_process SET status='Interrompu', error='{"message":"Le système d'Afex a été mis à jour. Veuillez appeler le support de Dolzay au 58671414 afin qu'ils vous fournissent la dernière mise à jour.","status_code":422,"response":{"message":"The request data contains invalid fields or fails validation.","errors":[{"field":"delegation","message":"Delegation is not valid"}]}}' WHERE id=3

}
