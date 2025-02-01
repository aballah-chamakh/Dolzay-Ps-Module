<?php

namespace Dolzay\Apps\Settings\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Dolzay\CustomClasses\Db\DzDb ;  
use Dolzay\Apps\Settings\Entities\Carrier ;
use Dolzay\Apps\Settings\Entities\ApiCredentials ; 
use Dolzay\Apps\Settings\Entities\WebsiteCredentials ;





class CarrierController extends FrameworkBundleAdminController
{



    public function getCarrierDetail(string $carrier_name,Request $request)
    { 
        // Check if the user is a super admin (id_profile = 1)
        $employee = $this->getUser();
        $employee =  new \Employee($employee->getId());
        if ($employee->id_profile != 1) {
            return new JsonResponse(['status'=>'unauthorized'],JsonResponse::HTTP_UNAUTHORIZED) ;
        }

        $db = DzDb::getInstance();
        $db->beginTransaction();
        

        // get the carrier
        $stmt = $db->prepare("SELECT  name, logo, website_credentials_id, api_credentials_id FROM " . Carrier::TABLE_NAME . " WHERE name = :carrier_name LIMIT 1");
        $stmt->bindParam(':carrier_name', $carrier_name);
        $stmt->execute();
        $carrier = $stmt->fetch();

        if ($carrier){
            // get the website credentials if they exist
            if ($carrier['website_credentials_id']) {
                $stmt = $db->prepare("SELECT email, password FROM " . WebsiteCredentials::TABLE_NAME . " WHERE id = :website_credentials_id LIMIT 1");
                $stmt->bindParam(':website_credentials_id', $carrier['website_credentials_id'], \PDO::PARAM_INT);
                $stmt->execute();
                $website_credentials = $stmt->fetch();
                unset($carrier['website_credentials_id']) ;
                $carrier['website_credentials'] = $website_credentials;
            }
            unset($carrier['website_credentials_id']) ;
            
            // get the the api credentials if they exist 
            if ($carrier['api_credentials_id']) {
                $stmt = $db->prepare("SELECT user_id, token, is_user_id_required FROM " . ApiCredentials::TABLE_NAME . " WHERE id = :api_credentials_id LIMIT 1");
                $stmt->bindParam(':api_credentials_id', $carrier['api_credentials_id'], \PDO::PARAM_INT);
                $stmt->execute();
                $api_credentials = $stmt->fetch();
                unset($carrier['api_credentials_id']);
                $carrier['api_credentials'] = $api_credentials;
            }
            unset($carrier['api_credentials_id']) ;

            $db->commit();
            // return the the carrier with his credentials
            return new JsonResponse(['status'=>"success",'carrier' => $carrier]);
        }

        // return not found
        return new JsonResponse(['status' => 'not_found'],JsonResponse::HTTP_NOT_FOUND) ;

    }

    public function updateCarrier(string $carrier_name,Request $request)
    {
        // Check if the user is a super admin (id_profile = 1)
        $employee = $this->getUser();
        $employee =  new \Employee($employee->getId());
        if ($employee->id_profile != 1) {
            return new JsonResponse(['status'=>'unauthorized'],JsonResponse::HTTP_UNAUTHORIZED) ;
        }

        $website_credentials = $request->get('website_credentials');
        $api_credentials = $request->get('api_credentials');
        $carrierForm = [
            'email'=>
        ]
        // get the updates
        $db = DzDb::getInstance();

        // get the carrier
        $stmt = $db->prepare("SELECT website_credentials_id, api_credentials_id FROM " . Carrier::TABLE_NAME . " WHERE name = :carrier_name LIMIT 1");
        $stmt->bindParam(':carrier_name');
        $stmt->execute();
        $carrier = $stmt->fetch();

        // handle the update of the website credentials
        if($website_credentials){
            $stmt = $db->prepare("UPDATE ".WebsiteCredentials::TABLE_NAME." SET email= :email, password= :password WHERE id= :website_credentials_id");
            $stmt->execute(['email'=>$website_credentials['email'],
                            'password'=>$website_credentials['password'],
                            'website_credentials_id'=>$carrier['website_credentials_id']]);
        }

        // handle the update of the api credentials
        if($api_credentials){
            $stmt = $db->prepare("UPDATE ".ApiCredentials::TABLE_NAME." SET user_id= :user_id, token= :token WHERE id= :api_credentials_id");
            $stmt->execute(['user_id'=>$api_credentials['user_id'],
                            'token'=>$api_credentials['token'],
                            'api_credentials_id'=>$carrier['api_credentials_id']]);
        }
        
        return JsonResponse(['status'=>'success']) ;
    }
}