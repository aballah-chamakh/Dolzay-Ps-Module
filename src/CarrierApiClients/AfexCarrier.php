<?php

require_once __DIR__."/BaseCarrier.php" ;

class AfexCarrier extends BaseCarrier {
    
    const name = "Afex" ;
    const url = "https://apis.afex.tn/v1/shipments" ;
    

    public static function get_api_key(){
        $carrier = self::$db->query("SELECT token FROM "._MODULE_PREFIX_."carrier AS car INNER JOIN "._MODULE_PREFIX_."api_credentials AS crd ON car.api_credentials_id=crd.id WHERE car.name = '".self::name."'")->fetch();
        return $carrier['token'];
    }
 

    public static function submit_orders(){
        $post_submit_status_id = AfexCarrier::get_post_submit_status_id() ;
        $orders = AfexCarrier::get_the_orders_to_submit();
        $orders_cnt = count($orders);
        $token = self::get_api_key();

        // Initialize cURL session
        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, self::url);
        curl_setopt($ch, CURLOPT_POST, true); // Use POST method
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response instead of outputting it
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-API-Key: $token",
            "Content-Type: application/text",
        ]);

        

        foreach($orders as $index => $order){
            sleep(5);
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
                throw new Exception("cURL Error: " . curl_error($ch));
            }

            $status_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get status code
            $order_id = $order['id_order'] ;
            $response = str_replace("'", '"', $response);
            $response = json_decode($response, true);
            if ($status_code == 200){
                $index = $index + 1 ;
                echo "=============== ORDER WITH THE ID : $order_id IS DONE (index : $index ,orders_cnt : $orders_cnt) =============== \n" ;
                
                
                self::$db->beginTransaction();

                // update the order
                self::updateOrder($order['id_order'],
                                ["submitted=true",
                                "tracking_code=".$response['barcode'],
                                "current_state=".$post_submit_status_id]);
                
                // add the order history for this status
                self::addOrderStatusHistory($order['id_order'],$post_submit_status_id);
                    
                // update the progress of the order submit process
                $orderSubmitProcessUpdates = ["processed_items_cnt=$index"] ;
                // check if it's the last order to submit
                if ($index == $orders_cnt){
                    $orderSubmitProcessUpdates[] = "status='Terminé'" ;
                }else{
                    // check if the obs was terinated by the user 
                    $obsStatus = self::getObsStatus();
                    echo "CURRENT OSP STATUS : $obsStatus" ;
                    if($obsStatus == "Pre-terminé par l'utilisateur"){ 
                        $orderSubmitProcessUpdates[] = "status='Terminé par l\\'utilisateur'" ; 
                        self::updateOrderSubmitProcess($orderSubmitProcessUpdates);
                        self::$db->commit();
                        break ;
                    }
                }
                self::updateOrderSubmitProcess($orderSubmitProcessUpdates);
                self::$db->commit();

            }else if ($status_code == 422){
                // 422 means invalid data were sent
                echo "=============== ORDER WITH THE ID : $order_id GOT 422 STATUS CODE =============== \n" ;
                
                // set the error
                $message = "Une erreur de code 422 s'est produite lors de la soumission de la 1ʳᵉ commande portant l'ID : $order_id. Veuillez appeler le support de Dolzay au " . _SUPPORT_PHONE_ . " afin qu'ils résolvent votre problème.";
                if($index > 0){
                    $message = "Après la soumission de $index/$orders_cnt, une erreur de code 422 s'est produite lors de la soumission de la commande portant l'ID : $order_id. Veuillez appeler le support de Dolzay au " . _SUPPORT_PHONE_ . " afin qu'ils résolvent votre problème.";
                }

                $error = json_encode([
                                'message' => $message,
                                'detail' =>[
                                            'status_code' => $status_code,
                                            'response'=>$response]
                            ],JSON_UNESCAPED_UNICODE);
                // escape the single quotes
                $error = str_replace("'", "\'", $error);
                
                self::updateOrderSubmitProcess(
                                                [
                                                    "status='Interrompu'",
                                                    "error='$error'"
                                                ]
                                              );
                break;
            }else if ($status_code == 401){
                echo "=============== ORDER WITH THE ID : $order_id GOT 401 STATUS CODE =============== \n" ;

                // set for the order submit process the status and the error data 
                $message = "Le token d'Afex est invalide. Veuillez le mettre à jour avec un token valide." ;
                if($index > 0){
                    $message = "Après la soumission de $index/$orders_cnt, le token d'Afex est devenu invalide. Veuillez le mettre à jour avec un token valide." ;
                }
                
                $error = json_encode([
                    'message' => $message,
                ],JSON_UNESCAPED_UNICODE);

                // escape the single quotes
                $error = str_replace("'", "\'", $error);
                self::updateOrderSubmitProcess(["status='Interrompu'",
                                                "error='$error'"
                                              ]);
                break ;
                
            }else{
                echo "=============== ORDER WITH THE ID : $order_id GOT AN UNEXPECTED ERROR =============== \n" ;
                // set for the order submit process the status and the error data 
                
                $message = "Une erreur de code $status_code s'est produite lors de la soumission de la 1ʳᵉ commande portant l'ID : $order_id. Veuillez appeler le support de Dolzay au " . _SUPPORT_PHONE_ . " afin qu'ils résolvent votre problème.";
                if($index > 0){
                    $message = "Après la soumission de $index/$orders_cnt, une erreur de code $status_code s'est produite lors de la soumission de la commande portant l'ID : $order_id. Veuillez appeler le support de Dolzay au " . _SUPPORT_PHONE_ . " afin qu'ils résolvent votre problème.";
                }

                $error = json_encode([
                            'message' => $message,
                            'detail' =>[
                                'status_code' => $status_code,
                                'response'=>$response]
                        ],JSON_UNESCAPED_UNICODE);
                // escape the single quotes
                $error = str_replace("'", "\'", $error);
                self::updateOrderSubmitProcess(
                    [
                        "status='Interrompu'",
                        "error='$error'"
                    ]
                );
                break ;
            }
        }

        // Close cURL session
        curl_close($ch);   
    }


}