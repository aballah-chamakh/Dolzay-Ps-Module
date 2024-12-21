<?php

namespace Dolzay\Apps\Notifications\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Dolzay\CustomClasses\Db\DzDb ;  
use Dolzay\Apps\Settings\Entities\Setting ;
use Dolzay\Apps\Settings\Entities\Carrier ;



class SettingController extends FrameworkBundleAdminController
{



    public function getSettingDetail(Request $request)
    { 
        $db = DzDb::getInstance();
        $db->beginTransaction();

        // get the settings
        $stmt = $db->query("SELECT license_key,
                                   license_type,
                                   subscription_started,
                                   subscription_ended,
                                   post_submit_state_id FROM ".Setting::TABLE_NAME." LIMIT 1");
        $settings = $stmt->fetch();
        $defaultLanguageId = $this->getContext()->language->id;

        // get the order state options
        $stmt = $db->query("SELECT id_order_state,name from "._DB_PREFIX_."order_state_lang WHERE id_lang=".$defaultLanguageId);
        $order_state_options = $stmt->fetch();

        // get the carriers
        $stmt = $db->query("SELECT id,name,logo FROM ".Carrier::TABLE_NAME." LIMIT 1");
        $carriers = $stmt->fetchAll();

        $db->commit();

        // set the carriers and order state options in the settings
        $settings['carriers'] = $carriers;
        $settings['order_state_options'] = $order_state_options;

        return new JsonResponse(['settings' => $settings]);
    }

    public function updateSetting(Request $request)
    {
        $db = DzDb::getPDO();
        $db->beginTransaction();

        $license_key = $request->get('license_key');
        $post_submit_state_id = $request->get('post_submit_state_id');

        // update the settings
        $db = DzDb::getInstance();
        $db->beginTransaction();

        // handle the new license key
        if ($license_key){

            // todo : here i have to check this license key is valid or not in our server
        
        }


        // handle the new post submit state id 
        if ($post_submit_state_id){
            $stmt = $db->prepare("UPDATE ".Setting::TABLE_NAME." SET post_submit_state_id = :post_submit_state_id");
            $stmt->bindParam(':post_submit_state_id', $post_submit_state_id, \PDO::PARAM_INT);
            $stmt->execute();
        }

        $db->commit();
        return new JsonResponse(['message' => 'Settings updated successfully']);
    }
}