<?php

namespace Dolzay\Apps\Notifications\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Dolzay\CustomClasses\Db\DzDb ;  
use Dolzay\Apps\Settings\Entities\Carrier ;
use Dolzay\Apps\Settings\Entities\ApiCredentails ;
use Dolzay\Apps\Settings\Entities\WebsiteCredentails ;





class CarrierController extends FrameworkBundleAdminController
{



    public function getCarrierDetail(int $carrier_id,Request $request)
    { 
        // Check if the user is a super admin (id_profile = 1)
        $employee = $this->getUser();
        if (!$employee->id_profile === 1) {
            return new JsonResponse(['status'=>'unauthorized'],JsonResponse::HTTP_UNAUTHORIZED) ;
        }

        $db = DzDb::getInstance();
        $db->beginTransaction();
        

        // get the carrier
        $stmt = $db->prepare("SELECT id, name, logo, website_credentials_id, api_credentials_id FROM " . Carrier::TABLE_NAME . " WHERE id = :carrier_id LIMIT 1");
        $stmt->bindParam(':carrier_id', $carrier_id, \PDO::PARAM_INT);
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
            }else{
                unset($carrier['website_credentials_id']) ;
            }

            // get the the api credentials if they exist 
            if ($carrier['api_credentials_id']) {
                $stmt = $db->prepare("SELECT user_id, token, is_user_id_required FROM " . ApiCredentials::TABLE_NAME . " WHERE id = :api_credentials_id LIMIT 1");
                $stmt->bindParam(':api_credentials_id', $carrier['api_credentials_id'], \PDO::PARAM_INT);
                $stmt->execute();
                $api_credentials = $stmt->fetch();
                unset($carrier['api_credentials_id']);
                $carrier['api_credentials'] = $api_credentials;
            }else{
                unset($carrier['api_credentials_id']) ;
            }

            $db->commit();
            // return the the carrier with his credentials
            return new JsonResponse(['carrier' => $carrier],Response::HTTP_NOT_FOUND);
        }

        // return not found
        return new JsonResponse(['status' => 'not_found'],Response::HTTP_NOT_FOUND) ;

    }

    public function updateCarrier(int $carrier_id,Request $request)
    {
        // Check if the user is a super admin (id_profile = 1)
        $employee = $this->getUser();
        if (!$employee->id_profile === 1) {
            return new JsonResponse(['status'=>'unauthorized'],JsonResponse::HTTP_UNAUTHORIZED) ;
        }

        // get the updates
        $website_credentials = $request->get('website_credentials');
        $api_credentials = $request->get('api_credentials');

        // get the carrier
        $stmt = $db->prepare("SELECT website_credentials_id, api_credentials_id FROM " . Carrier::TABLE_NAME . " WHERE id = :carrier_id LIMIT 1");
        $stmt->bindParam(':carrier_id',\PDO::PARAM_INT);
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