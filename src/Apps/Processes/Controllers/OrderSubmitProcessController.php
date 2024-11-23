<?php

namespace Dolzay\Apps\Processes\Controllers;

use Symfony\Component\HttpFoundation\JsonResponse;

use Dolzay\Apps\Processes\Entities\Process;
use Dolzay\Apps\Processes\Entities\OrderToMonitor;
use Dolzay\CustomClasses\Db\DzDb;


class OrderSubmitProcessController
{

    private const REQUIRED_PERMISSION = "Soumission des commandes";
    private const RESTRICTING_CONFIG_ERROR = [
        "/zones_and_coverages/#zones",
        "/zones/id/",
        "/settings/#automation"
    ] ;

    /**
     * Submit orders for processing
     * @param array $order_ids Array of order IDs to submit
     * @return array Response with process ID and status
     * @throws \Exception if permissions invalid or process already running
     */
    public function submitOrders(): array {



        $employee_id = $this->getUser()->getId();
        
        $db = DzDb::init();
        Employee::init($db, $employee_id);

        // Check if employee has permission to submit orders
        if (!Employee::has_permission(self::REQUIRED_PERMISSION)) {
            return new JsonResponse(['status' => "unauthorized",
                                     'message' => "THIS_EMPLOYEE_DOES_NOT_HAVE_ANY_PERMISSIONS"], 401);
        }
        
        // Check if there are any configuration errors that restrict order submission
        Notification::init_db($db);
        $notifications = Notification::filter_by_pathnames(self::RESTRICTING_CONFIG_ERROR); ;

        if (count($notifications) > 0) {
            $config_errors = array_map(function($notification) {
                return $notification->message;
            }, $notifications);

            return new JsonResponse(['status' => "restricted",
                                    'data' => ['config_errors' => $config_errors],
                                     'message' => "CONFIGURATION_ERRORS"], 401);
        }

     
        $db->query("LOCK TABLES Process READ");

        Process::init($db);
        $process_id = Process::insert("Soumission", 
                                      count($order_ids),
                                      "Actif",
                                       json_encode([
                                            'order_ids' => $order_ids
                                      ]));

        $db->query("UNLOCK TABLES");

        

        



        /*
        if ($this->checkExistingRunningProcess()) {
            throw new \Exception("A process is already running");
        }

        $process = new Processus();
        $process->type = "Soumission";
        $process->started_at = date('Y-m-d H:i:s');
        $process->items_to_process_cnt = count($order_ids);
        $process->status = "Actif";
        $process->meta_data = json_encode([
            'employee_id' => $this->employee_id,
            'order_ids' => $order_ids
        ]);

        $process_id = $process->save($this->db);

        foreach ($order_ids as $order_id) {
            $order = new OrderToMonitor();
            $order->process_id = $process_id;
            $order->order_id = $order_id;
            $order->save($this->db);
        }

        return [
            'process_id' => $process_id,
            'status' => 'started',
            'total_orders' => count($order_ids)
        ];*/
    }

    /**
     * Get already submitted orders for a specific process
     * @param int $process_id Process ID
     * @param int $page_nb Page number
     * @param int $batch_size Number of items per page
     * @return array List of submitted orders or process status
     */
    public function getAlreadySubmittedOrders(int $process_id, int $page_nb, int $batch_size): array
    {
        // TODO: Implement logic to fetch submitted orders
        // Should check process status
        // Return paginated list of orders or process state
    }

    /**
     * Get orders with invalid fields for a specific process
     * @param int $process_id Process ID
     * @return array List of orders with invalid fields
     */
    public function getOrdersWithInvalidFields(int $process_id): array
    {
        // TODO: Implement logic to fetch orders with invalid fields
    }

    private function validatePermissions(): bool
    {
        // TODO: Implement permission validation
        return true;
    }

    private function checkExistingRunningProcess(): bool
    {
        // TODO: Implement check for existing running processes
        return false;
    }
}