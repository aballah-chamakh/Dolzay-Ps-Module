<?php

namespace Dolzay\CarrierApiClients;

require_once __DIR__ . '/BaseCarrier.php';

class BigBossCarrier extends BaseCarrier
{
    const name = "BigBoss";
    const add_multiple_url = "https://client.bigboss.com.tn/api/api.v1/StColis/AjoutVMultiple";
    const list_colis_url  = "https://client.bigboss.com.tn/api/api.v2/StColis/ListColis";

    public static $post_submit_status_id ;
    public static $api_credentials ; 
    public static $orders_to_submit ; 



    // Map BigBoss statuses to PrestaShop states
    protected static array $bigBossToPrestaState = [
        'En Attente'           => 3,
        'A Enlever'            => 3,
        'Enlevé'               => 4,
        'Au Dépôt'             => 4,
        'En Cours de Livraison'=> 4,
        'Livré'                => 5,
        'Livré Payé'           => 5,
        'Retour Expéditeur'    => 7,
        'Retour Reçu'          => 7,
        // …add the rest as needed
    ];

    public static function get_api_credentials(): string
    {
        $credentials = self::$db
            ->query("SELECT user_id,token 
                     FROM "._MODULE_PREFIX_."carrier AS car 
                     INNER JOIN "._MODULE_PREFIX_."api_credentials AS crd 
                       ON car.api_credentials_id=crd.id 
                     WHERE car.name = '".self::name."'")
            ->fetch();
        return $credentials;
    }

    public static function submit_a_batch_of_orders($ch,$credentials,$batch_of_orders,$post_submit_status_id,$processed_items_cnt){
        $payload = [
            'Uilisateur' => $credentials['user_id'],
            'Pass'       => $credentials['token'],
            'listColis'  => $batch_of_orders
        ];
        $payload = json_encode($payload, JSON_UNESCAPED_UNICODE);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload); // Attach JSON payload
        $resp = curl_exec($ch);
        if ($resp === false) {
            throw new \Exception("BigBoss submit cURL error: ".curl_error($ch));
        }
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $resp = json_decode($resp, true);

        if (in_array($resp['result_type'], ['success','partial_success'])) {
            foreach ($resp['result_content']['lsCrees'] ?? [] as $created_order) {
                $processed_items_cnt += 1 ;
                $order_id = $created_order['reference'] ;
                file_put_contents(self::LOG_FILE, "THE ORDER WITH THE ID : $order_id WAS SUBMITTED | PROGRESS : $processed_items_cnt \n\n", FILE_APPEND);

                self::$db->beginTransaction();
                self::updateOrder($created_order['reference'], [
                    "tracking_code='{$created_order['codeBar']}'",
                    "current_state={$post_submit_status_id}",
                    "submitted=true"
                ]);
                self::addOrderToMonitoring(self::name, $created_order['reference'], $created_order['codeBar']);
                self::addOrderStatusHistory($created_order['reference'], $post_submit_status_id);
                self::addASubmittedOrder($created_order['reference']);
                self::updateOrderSubmitProcess(["processed_items_cnt"=>$processed_items_cnt]);
                self::$db->commit();
            }

            foreach ($resp['result_content']['lsErreurs'] ?? [] as $e) {
                $errors[] = [
                    'order_id' => $e['reference'],
                    'error'    => $e['erreur_msg']
                ];
                self::$db->beginTransaction();
                self::addAnOrderWithError("osp", $e['reference'], 'submission_error', $e['erreur_msg']);
                self::$db->commit();
            }
        } else {
            // global error
            $errors[] = ['error' => $resp['result_code'] ?? 'unknown'];
        }
        $submitted_orders_cnt = count($resp['result_content']['lsCrees'] ?? []);
        $orders_with_errors_cnt = count($resp['result_content']['lsErreurs'] ?? []);
        return ["processed_items_cnt"=>$processed_items_cnt,"submitted_orders_cnt"=>$submitted_orders_cnt,"orders_with_errors_cnt"=>$orders_with_errors_cnt];
    }

    public static function submit_orders(): array
    {
        $post_submit_status_id = self::get_post_submit_status_id();
        $orders = self::get_the_orders_to_submit();
        $cnt    = count($orders);
        $credentials = self::get_api_credentials();

        file_put_contents(self::LOG_FILE, "=== BigBoss submit start: process ".self::$process_id." ===\n", FILE_APPEND);

        // Build payload
        
        $orders_list = [];
        foreach ($orders as $o) {
            $orders_list[] = [
                'reference'    => $o['id_order'],
                'client'       => $o['firstname'].' '.$o['lastname'],
                'adresse'      => $o['address1'],
                'gouvernorat'  => $o['city'],
                'ville'        => $o['delegation'],
                'nb_pieces'    => $o['total_individual_items'],
                'prix'         => $o['total_paid'],
                'tel1'         => $o['phone'],
                'tel2'         => null,
                'designation'  => $o['cart_products'],
                'commentaire'  => '',
                'type'         => 'FIX',
                'echange'      => 0,
            ];
        }

        // cURL setup
        $ch = curl_init(self::add_multiple_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ["Content-Type: application/json"],
        ]);

        $processed_items_cnt = 0 ;
        $submitted_orders_cnt = 0 ;
        $orders_with_errors_cnt = 0 ;
        $batches_of_orders = array_chunk($orders_list, 50);
        foreach ($batches_of_orders as $batch_of_orders) {
            $result = self::submit_a_batch_of_orders($ch,$credentials,$batch_of_orders,$post_submit_status_id,$processed_items_cnt);
            $processed_items_cnt = $result['processed_items_cnt'];
            $submitted_orders_cnt += $result['submitted_orders_cnt'];
            $orders_with_errors_cnt += $result['orders_with_errors_cnt'];
        }
        return [
            "submitted_orders_cnt"=>$submitted_orders_cnt,
            "orders_with_errors_cnt"=>$orders_with_errors_cnt
        ];
    }

    public static function monitor_orders(): array
    {
        $toMonitor = self::getOrdersToMonitorByCarrier(self::name);
        self::updateOrderMonitoringProcess(['items_to_process_cnt'=>count($toMonitor)]);

        if (empty($toMonitor)) {
            return ['monitored_orders_cnt'=>0,'orders_with_errors_cnt'=>0];
        }

        // Build codeBar list
        $codes = implode(';', array_column($toMonitor, 'carrier_order_ref'));
        $payload = json_encode([
            'Uilisateur' => self::get_api_key(),
            'Pass'       => self::get_api_key(),
            'codeBar'    => $codes
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init(self::list_colis_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ["Content-Type: application/json"],
            CURLOPT_POSTFIELDS     => $payload,
        ]);

        $resp = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $resp = json_decode($resp, true);
        $monitored = $errors = $idx = 0;

        if ($resp['result_type'] === 'success') {
            foreach ($resp['result_content']['colis'] as $col) {
                $idx++; $monitored++;
                $newState = self::$bigBossToPrestaState[$col['etat']] ?? 4;
                $orderId  = array_search((string)$col['code'], array_column($toMonitor, 'carrier_order_ref')) !== false
                          ? $toMonitor[array_search((string)$col['code'], array_column($toMonitor, 'carrier_order_ref'))]['order_id']
                          : null;
                if ($orderId && $newState != $toMonitor[$idx-1]['current_state']) {
                    self::$db->beginTransaction();
                    self::updateOrder($orderId, ["current_state=$newState"]);
                    self::addOrderStatusHistory($orderId, $newState);
                    if (in_array($newState, [5,7])) {
                        self::removeOrderFromMonitoring($orderId);
                    }
                    self::insertAnUpdatedOrder(self::$process_id, $orderId, $toMonitor[$idx-1]['current_state'], $newState);
                    self::$db->commit();
                }
                self::updateOrderMonitoringProcess([
                    'processed_items_cnt' => $idx,
                    'monitored_orders_cnt'=> $monitored
                ]);
            }
        } else {
            // handle errors for all
            foreach ($toMonitor as $i => $o) {
                $idx++; $errors++;
                self::$db->beginTransaction();
                self::addAnOrderWithError("omp", $o['order_id'], $resp['result_code'] ?? 'monitor_error', json_encode($resp));
                self::updateOrderMonitoringProcess([
                    'processed_items_cnt'=>$idx,
                    'orders_with_errors_cnt'=>$errors
                ], true);
            }
        }

        return ['monitored_orders_cnt'=>$monitored,'orders_with_errors_cnt'=>$errors];
    }
}
