<?php


namespace Dolzay\CarrierApiClients;

class AfexCarrier extends BaseCarrier {
    
    const name = "Afex" ;
    const order_submit_api_url = "https://apis.afex.tn/v1/shipments" ;
    const order_status_api_url = "https://apis.afex.tn/v1/shipments/status" ;
    const afexToPrestaState = [
        'pre_manifest' => 3, // 'En cours de préparation'
        'awaiting_removal' => 3, // 'En cours de préparation'
        'delivered' => 5, // 'Livré'
        'returned' => 7, // 'Retour'
        'canceled' => 6, // 'Annulé'
        'pre_shipping_canceling' => 14, //'Annulé'
    ];

    public static function get_api_key(){
        $carrier = self::$db->query("SELECT token FROM "._MODULE_PREFIX_."carrier AS car INNER JOIN "._MODULE_PREFIX_."api_credentials AS crd ON car.api_credentials_id=crd.id WHERE car.name = '".self::name."'")->fetch();
        return $carrier['token'];
    }
 

    public static function submit_orders(){
        try {
            $current_datetime = date("H:i:s d/m/Y");
            file_put_contents(self::LOG_FILE, "=======  THE OSP HOLDING THE ID : " . self::$process_id . " STARTED AT : $current_datetime WITH FOLLOWING ARGS : carrier : Afex , employee_id : " . self::$employee_id . "  =======\n\n", FILE_APPEND);

            $post_submit_status_id = AfexCarrier::get_post_submit_status_id() ;
            $orders = AfexCarrier::get_the_orders_to_submit();
            $orders_cnt = count($orders);
            $token = self::get_api_key();

            // Initialize cURL session
            $ch = curl_init();

            // Conditionally disable SSL verification on Windows
            if (PHP_OS_FAMILY === 'Windows') {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            }


            // Set cURL options
            curl_setopt($ch, CURLOPT_URL, self::order_submit_api_url);
            curl_setopt($ch, CURLOPT_POST, true); // Use POST method
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response instead of outputting it
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "X-API-Key: $token",
                "Content-Type: application/text",
            ]);

            //throw new \Exception("Error Processing Request", 1);
            
            $submitted_orders_cnt = 0 ;
            $orders_with_errors_cnt = 0 ;
            
            foreach($orders as $index => $order){
                
                // prepare the goods 
                $goods = self::get_cart_products_str($order['cart_products']);

                // prepare the payload
                $payload = json_encode([
                    "nom"            => $order['firstname']." ".$order['lastname'],
                    "telephone1"     => $order['phone'],
                    "gouvernorat"    => $order['city'],
                    "delegation"     => $order['delegation'],
                    "adresse"        => $order['address1'],
                    "marchandise"    => $goods,
                    "paquets"        => 1,
                    "type_envoi"     => "Livraison à domicile",
                    "cod"            => $order['total_paid'],
                    "mode_reglement" => "Seulement en espèces",
                    "manifest"       => "0",
                ],JSON_UNESCAPED_UNICODE);

                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload); // Attach JSON payload

                // Execute the request and get the response
                $response = curl_exec($ch);

                // Handle cURL errors
                if ($response === false) {
                    throw new Exception("!!!!cURL Error: " . curl_error($ch));
                }

                $status_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get status code
                $order_id = $order['id_order'] ;
                $response = str_replace("'", '"', $response);
                $response = json_decode($response, true);
                if ($status_code == 200){
                    $submitted_orders_cnt += 1 ;
                    $index += 1 ;
                    file_put_contents(self::LOG_FILE, "THE ORDER WITH THE ID : $order_id WAS SUBMITTED | PROGRESS : $index / $orders_cnt  \n\n", FILE_APPEND);
                    
                    self::$db->beginTransaction();

                    // update the order
                    self::updateOrder($order['id_order'],
                                    ["submitted=true",
                                    "tracking_code=".$response['barcode'],
                                    "current_state=".$post_submit_status_id]);
                    
                    // add the order to the monitoring phase 
                    self::addOrderToMonitoring("Afex",$order['id_order'],$response['barcode']);
                    
                    // add the order history for this status
                    self::addOrderStatusHistory($order['id_order'],$post_submit_status_id);

                    // add an submittedOrder
                    self::addAsubmittedOrder($order['id_order']);  
                    
                    // update the progress of the order submit process
                    self::updateOrderSubmitProcess(["processed_items_cnt"=>$index]);

                    self::$db->commit();

                }else if ($status_code == 422){
                    
                    $orders_with_errors_cnt += 1 ;
                    $index += 1 ;
                    // 422 means invalid data were sent
                    file_put_contents(self::LOG_FILE, "THE ORDER WITH THE ID : $order_id GOT THE 422 STATUS CODE | PROGRESS : $index / $orders_cnt  \n\n", FILE_APPEND);

                    $error_details = json_encode(
                                    [
                                        'status_code' => $status_code,
                                        'response'=>$response
                                    ]
                                ,JSON_UNESCAPED_UNICODE);

                    self::$db->beginTransaction();
                    // add an submittedOrder
                    self::addAnOrderWithError("osp",$order['id_order'],"Champ(s) invalide(s)",$error_details); 
                    // update the progress of the Osp 
                    self::updateOrderSubmitProcess(["processed_items_cnt"=>$index]);
                    self::$db->commit();

                }else if ($status_code == 401 || !$token){

                    $error_details = json_encode(
                        [
                            'status_code' => $status_code,
                            'response'=>$response
                        ]
                    ,JSON_UNESCAPED_UNICODE);

                    $remaining_orders = array_slice($orders, $index);
                    
                    foreach($remaining_orders as $remaining_order){
                        $orders_with_errors_cnt += 1 ;
                        $index += 1 ;
                        file_put_contents(self::LOG_FILE, "THE ORDER WITH THE ID : ".$remaining_order['id_order']." GOT 401 STATUS CODE | PROGRESS : $index / $orders_cnt  \n\n", FILE_APPEND);

                        self::$db->beginTransaction();
                        self::addAnOrderWithError("osp",$remaining_order['id_order'],"Token invalide",$error_details);  
                        // increase the counter of the osp 
                        self::updateOrderSubmitProcess(["processed_items_cnt"=>$index]);
                        self::$db->commit();
                    }
                    break ;
                }else{
                    $orders_with_errors_cnt += 1 ;
                    $index += 1 ;
                    file_put_contents(self::LOG_FILE, "THE ORDER WITH THE ID : $order_id GOT AN UNEXPECTED ERROR | PROGRESS : $index / $orders_cnt  \n\n", FILE_APPEND);
                    // set for the order submit process the status and the error data 
        
                    $error_details = json_encode(
                                [
                                    'status_code' => $status_code,
                                    'response'=>$response
                                ]
                            ,JSON_UNESCAPED_UNICODE);

                    self::$db->beginTransaction();
                    self::addAnOrderWithError("osp",$order['id_order'],"Erreur inattendue",$error_details);  
                    self::updateOrderSubmitProcess(["processed_items_cnt"=>$index ]);
                    self::$db->commit();

                }
            }
            self::updateOrderSubmitProcess(
                [
                    "status"=>"Terminé",
                    "ended_at"=>date('Y-m-d H:i:s')
                ]
            );

            $current_datetime = date("H:i:s d/m/Y");
            file_put_contents(self::LOG_FILE, "=======  THE OSP HOLDING THE ID : " . self::$process_id . " ENDED AT : $current_datetime AFTER SUBMITTING $index/$orders_cnt =======\n\n\n\n", FILE_APPEND);

            // Close cURL session
            curl_close($ch);   
            return [
                "submitted_orders_cnt"=>$submitted_orders_cnt,
                "orders_with_errors_cnt"=>$orders_with_errors_cnt
            ];
        }catch(\Throwable $e){

            $current_datetime = date("H:i:s d/m/Y");
            file_put_contents(self::LOG_FILE, "=======  THE OSP HOLDING THE ID : " . self::$process_id . " ENDED AT : $current_datetime WITH AN UNEXPECTED EXCEPTION ======= \n\n\n\n", FILE_APPEND);

            $error = json_encode([
                'message' => "Une erreur inattendue est survenue lors de la soumission des commandes : ". $e->getMessage(),
                'detail' => $e->getTraceAsString()
            ],JSON_UNESCAPED_UNICODE);
            //$error = addslashes($error) ;
            self::updateOrderSubmitProcess(
                [
                    "status"=>"Interrompu",
                    "error"=>$error,
                    "ended_at"=> date('Y-m-d H:i:s')
                ]
            );
            // i have to commit here to handle the case of having an active transaction
            if(self::$db->inTransaction()){
                self::$db->commit();
            }
            return ["error_message"=>"Une erreur inattendue est survenue lors de la soumission des commandes : ". $e->getMessage()] ;
        }

    }

    public static function afexToPrestaStateConverter($afexOrderState) {

        return self::afexToPrestaState[$afexOrderState] ?? 4 ; // Expidié 
    }

    public static function monitor_orders(){
        try {
            sleep(60);
            $monitored_orders_cnt = 0 ;
            $orders_with_errors_cnt = 0 ;
            // collect the afex orders in the monitoring phase 
            
            $afex_orders_to_monitor = self::getOrdersToMonitorByCarrier("Afex");
            
            if(count($afex_orders_to_monitor) == 0){
                return [
                    "monitored_orders_cnt"=>0,
                    "orders_with_errors_cnt"=>0
                ];
            }

            // prepare the payload / shipement ids
            $payload = [
                "shipmentIds" => []
            ];
            foreach($afex_orders_to_monitor as $order_to_monitor){
                $payload['shipmentIds'][] = $order_to_monitor['carrier_order_ref'];
            }
            $payload = json_encode($payload, JSON_UNESCAPED_UNICODE);

            $token = self::get_api_key();

            // Initialize cURL session
            $ch = curl_init();

            // Conditionally disable SSL verification on Windows
            if (PHP_OS_FAMILY === 'Windows') {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            }

            // Set cURL options
            curl_setopt($ch, CURLOPT_URL, self::order_status_api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response instead of outputting it
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "X-API-Key: $token",
                "Content-Type: application/text",
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload); // Attach JSON payload

            // Execute the request and get the response
            $response = curl_exec($ch);

            // Handle cURL errors
            if ($response === false) {
                throw new \Exception("!!!!cURL Error: " . curl_error($ch));
            }
            // get the response status code 
            $status_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get status code
            $afex_orders_to_monitor_count = count($afex_orders_to_monitor);
            //throw new \Exception("The status code is : $status_code");
            if ($status_code == 200){
                $response = str_replace("'", '"', $response);
                $response = json_decode($response,true);
                foreach($afex_orders_to_monitor as $index => $order_to_monitor){
                    $monitored_orders_cnt += 1 ;
                    self::$db->beginTransaction();
                    // check if the current afex order to monitor is in the response
                    $shipments = array_values(array_filter($response['shipments'], fn($shipment) => $shipment['barcode'] == (int)$order_to_monitor["carrier_order_ref"]));
                    dump($shipments);
                    dump(count($shipments));
                    // if the current afex order to monitor is in the response, check if his state has changed and update it accordingly
                    if (count($shipments)){
                        $shipment = $shipments[0];
                        $new_afex_state = self::afexToPrestaStateConverter($shipment['state']) ;
                        if($new_afex_state != $order_to_monitor['current_state']){
                            
                            // update the order state 
                            self::updateOrder($order_to_monitor['order_id'],["current_state=".$new_afex_state]);
                            
                            // add the order history for this status
                            self::addOrderStatusHistory($order_to_monitor['order_id'],$new_afex_state);
                            
                            // if the new state is an exit state remove the order from the monitoring phase
                            if (in_array($new_afex_state, $exit_states)){
                                self::removeOrderFromMonitoring($order_to_monitor['order_id']);
                            }
                            
                            self::insertAnUpdatedOrder(self::$process_id,$order_to_monitor['order_id'],$order_to_monitor['current_state'],$new_afex_state);    
                        }
                    }else{ // otherwise set it to pre-shipping canceling since it was deleted by the user in the carrier platform
                        // update the order state to pre-shipping canceling
                        self::updateOrder($order_to_monitor['order_id'],["current_state=".self::afexToPrestaState["pre_shipping_canceling"]]);

                        // add the order history for this status
                        self::addOrderStatusHistory($order_to_monitor['order_id'],self::afexToPrestaState["pre_shipping_canceling"]);

                        self::insertAnUpdatedOrder(self::$process_id,$order_to_monitor['order_id'],$order_to_monitor['current_state'],self::afexToPrestaState["pre_shipping_canceling"]);    

                        // remove the order from the monitoring phase
                        self::removeOrderFromMonitoring($order_to_monitor['order_id']);
                    }

                    // increase the counter of order monitoring process 
                    $index += 1 ;

                    self::updateOrderMonitoringProcess([
                        "processed_items_cnt"=>$index,
                    ]);
                    self::$db->commit();
                }

                return [
                    "monitored_orders_cnt"=>$monitored_orders_cnt,
                    "orders_with_errors_cnt"=>$orders_with_errors_cnt
                ];
            }else if ($status_code == 401 || !$token){
                $response = str_replace("'", '"', $response);
                $response = json_decode($response,true);
                // add an Order with error for all of the orders to monitor
                foreach($afex_orders_to_monitor as $index => $order_to_monitor){
                    $orders_with_errors_cnt += 1 ;
                    self::$db->beginTransaction();
                    $error_details = json_encode(
                        [
                            'status_code' => $status_code,
                            'response'=>$response
                        ]
                    ,JSON_UNESCAPED_UNICODE);
                    self::addAnOrderWithError("omp",$order_to_monitor['order_id'], "Token invalide", $error_details);
                    self::updateOrderMonitoringProcess([
                        "processed_items_cnt"=>$index + 1,
                    ]);
                    self::$db->commit();
                }

                
                return [
                    "monitored_orders_cnt"=>$monitored_orders_cnt,
                    "orders_with_errors_cnt"=>$orders_with_errors_cnt
                ];
            }else if($status_code == 404){
                foreach($afex_orders_to_monitor as $index => $order_to_monitor){
                    $monitored_orders_cnt += 1 ;
                    self::$db->beginTransaction();
                    // update the order state to pre-shipping canceling
                    self::updateOrder($order_to_monitor['order_id'],["current_state=".self::afexToPrestaState["pre_shipping_canceling"]]);

                    // add the order history for this status
                    self::addOrderStatusHistory($order_to_monitor['order_id'],self::afexToPrestaState["pre_shipping_canceling"]);

                    // remove the order from the monitoring phase
                    self::removeOrderFromMonitoring($order_to_monitor['order_id']);
                    self::insertAnUpdatedOrder(self::$process_id,$order_to_monitor['order_id'],$order_to_monitor['current_state'],self::afexToPrestaState["pre_shipping_canceling"]);    

                    $index += 1  ;
                    self::updateOrderMonitoringProcess([
                        "processed_items_cnt"=>$index,
                    ]);
                    self::$db->commit();
                }
                
                return [
                    "monitored_orders_cnt"=>$monitored_orders_cnt,
                    "orders_with_errors_cnt"=>$orders_with_errors_cnt
                ];
            }
            else{
                // add an Order with error for all of the orders to monitor
                foreach($afex_orders_to_monitor as $index => $order_to_monitor){
                    $orders_with_errors_cnt += 1 ;
                    self::$db->beginTransaction();
                    $error_details = json_encode(
                        [
                            'status_code' => $status_code,
                            'response'=>$response
                        ]
                    ,JSON_UNESCAPED_UNICODE);
                    self::addAnOrderWithError("omp",$order_to_monitor['order_id'], "Champ(s) invalide(s)", $error_details);
                    self::updateOrderMonitoringProcess([
                        "processed_items_cnt"=>$index + 1,
                    ]);
                    self::$db->commit();
                }
                return [
                    "monitored_orders_cnt"=>$monitored_orders_cnt,
                    "orders_with_errors_cnt"=>$orders_with_errors_cnt
                ];
            }

        }catch(\Throwable $e){
            $current_datetime = date("H:i:s d/m/Y");
            file_put_contents(self::LOG_FILE, "=======  THE OMP HOLDING THE ID : " . self::$process_id . " ENDED AT : $current_datetime WITH AN UNEXPECTED EXCEPTION \n\n\n\n", FILE_APPEND);

            $error = json_encode([
                'message' => "Une erreur inattendue est survenue lors du suivi des commandes : ".$e->getMessage(),
                'detail' => $e->getTraceAsString()
            ],JSON_UNESCAPED_UNICODE);
            //$error = addslashes($error) ;

            self::updateOrderMonitoringProcess(
                [
                    "status"=>"Interrompu",
                    "error"=>$error,
                    "ended_at"=>date('Y-m-d H:i:s') 
                ]
            );
            // i have to commit here to handle the case of having an active transaction
            if(self::$db->inTransaction()){
                self::$db->commit();
            }
            return ["error_message"=>"Une erreur inattendue est survenue lors du suivi des commandes : ". $e->getMessage()];
        }

    }


}