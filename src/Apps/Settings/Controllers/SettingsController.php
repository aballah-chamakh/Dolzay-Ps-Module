<?php

namespace Dolzay\Apps\Settings\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Dolzay\CustomClasses\Db\DzDb ;  
use Dolzay\Apps\Settings\Entities\Settings ;
use Dolzay\Apps\Settings\Entities\Carrier ;
use Dolzay\Apps\OrderSubmitProcess\Entities\OrderSubmitProcess ;
use Dolzay\Apps\OrderMonitoringProcess\Entities\OrderMonitoringProcess ;
use Media ;
use Context ;

class SettingsController extends FrameworkBundleAdminController
{

    public function getSettings(Request $request)
    { 

        $employee = $this->getUser();
        $employee =  new \Employee($employee->getId());
        if ($employee->id_profile != 1) {
            return new JsonResponse(['status'=>'unauthorized'],JsonResponse::HTTP_UNAUTHORIZED) ;
        }

        $db = DzDb::getInstance();
        $db->beginTransaction();

        // get the settings
        $stmt = $db->query("SELECT license_type,
                                   expiration_date,
                                   post_submit_state_id,
                                   process_execution_type FROM ".Settings::TABLE_NAME." LIMIT 1");
        $settings = $stmt->fetch();
        $defaultLanguageId = $this->getContext()->language->id;

        // get the order state options
        $stmt = $db->query("SELECT id_order_state,name FROM "._DB_PREFIX_."order_state_lang WHERE id_lang=".$defaultLanguageId);
        $order_state_options = $stmt->fetchAll();

        // get the carriers
        $stmt = $db->query("SELECT name,logo FROM ".Carrier::TABLE_NAME);
        $carriers = $stmt->fetchAll();

        $db->commit();
        // set the carriers and order state options in the settings
        $settings['carriers'] = $carriers;
        $settings['order_state_options'] = $order_state_options;

        $link = Context::getContext()->link;
        $baseUrl = $link->getBaseLink();

        $adminBaseUrl = $link->getAdminLink('AdminModules', true);
        // Extract the base up to /index.php
        $adminBaseUrl = preg_replace('#(index\.php).*#', '', $adminBaseUrl);

        Media::addJsDef([
            'dz_module_media_base_url' => $baseUrl . 'modules/dolzay/uploads',
            'dz_module_controller_base_url' => $adminBaseUrl . 'dz'
        ]) ;
        
        return $this->render('@Modules/dolzay/views/templates/admin/settings/settings.html.twig',[
            'settings'=>$settings,
            'carriers'=>$carriers,
            'order_state_options'=> $order_state_options,
            'dz_module_media_base_url' => $baseUrl . 'modules/dolzay/uploads'
        ]);
    }

    public function updateSettings(Request $request)
    {
        $employee = $this->getUser();
        $employee =  new \Employee($employee->getId());
        if ($employee->id_profile != 1) {
            return new JsonResponse(['status'=>'unauthorized'],JsonResponse::HTTP_UNAUTHORIZED) ;
        }

        $db = DzDb::getInstance();
        $db->beginTransaction();

        $request_body = json_decode($request->getContent(), true) ;
        $request_body = is_array($request_body) ? $request_body : [] ;
        
        $order_post_submit_state_id = $request_body['order_post_submit_state_id'];
        $process_execution_type = $request_body['process_execution_type'];

        // update the settings
        $db = DzDb::getInstance();
        $stmt = $db->prepare("UPDATE ".Settings::TABLE_NAME." SET post_submit_state_id = :post_submit_state_id,
                                                                    process_execution_type = :process_execution_type");
        $stmt->bindParam(':post_submit_state_id', $order_post_submit_state_id, \PDO::PARAM_INT);
        $stmt->bindParam(':process_execution_type', $process_execution_type, \PDO::PARAM_STR);
        $stmt->execute();
        $db->commit();
        return new JsonResponse(['status'=>"success",'message' => 'Settings updated successfully']);
    }

    public function terminateAllProcesses(Request $request)
    {
        $employee = $this->getUser();
        $employee = new \Employee($employee->getId());
        if ($employee->id_profile != 1) {
            return new JsonResponse(['status' => 'unauthorized'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $db = DzDb::getInstance();
        $db->beginTransaction();

        try {
            // Update OSP processes
            $stmt = $db->prepare("UPDATE ".OrderSubmitProcess::TABLE_NAME." SET status = 'Terminé' WHERE status = 'Actif'");
            $stmt->execute();

            // Update OMP processes
            $stmt = $db->prepare("UPDATE ".OrderMonitoringProcess::TABLE_NAME." SET status = 'Terminé' WHERE status = 'Actif'");
            $stmt->execute();

            $db->commit();
            return new JsonResponse(['status' => 'success']);
        } catch (\Exception $e) {
            $db->rollBack();
            return new JsonResponse(['status' => 'error', 'message' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}