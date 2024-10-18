<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';
/*
try {
    require_once __DIR__. "/vendor/autoload.php" ;
}catch (\Exception $e) {
    PrestaShopLogger::addLog('Error during loading the autoload : ' . $e->getMessage(), 3, null, 'Dolzay');
}
*/
use Dolzay\Apps\Notifications\Entities\Notification ;

class Dolzay extends Module
{
    const APPS_INIT_ORDER = [
        "Settings",
        "Notifications"
    ]  ;
    const APPS_UNINIT_ORDER = [
        "Notifications",
        "Settings"
    ]  ;

    const APPS_BASE_NAMESPACES = "Dolzay\\Apps\\" ;

    public function __construct()
    {
        $this->name = 'dolzay';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'Abdallah Ben Chamakh';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => '1.7.8.11',
        ];

        $this->bootstrap = false ;


        parent::__construct();

        $this->displayName = $this->l('Dolzay');
        $this->description = $this->l('Dolzay Dolzay');

    }

    public function install()
    {
        return parent::install() && $this->create_app_tables() ;
    }

    public function uninstall()
    {
        return parent::uninstall() && $this->drop_app_tables()  ; 
    }

    public function create_app_tables() {
        try {
            $db = Db::getInstance();

            // FOR EACH APP CREATE TABLES OF HER ENTITIES 
            foreach (self::APPS_INIT_ORDER as $app) {

                // CONSTRUCT THE APP CONFIG CLASS NAME NAMESPACED
                $app_config_class = self::APPS_BASE_NAMESPACES . $app . "\\Config" ;
                PrestaShopLogger::addLog("the constructed config class : ".$app_config_class,1, null, 'Dolzay') ;

                // CHECK IF THE APP CONFIG CLASS EXISTS AND HAS THE STATIC PROPERTY $create_app_entities_order OTHERWISE QUIT THE INSTALLATION
                if (!class_exists($app_config_class)) {
                    PrestaShopLogger::addLog("the config class : ".$app_config_class." doesn't exist", 3, null, 'Dolzay');
                    return false ; 
                }

                if (!property_exists($app_config_class, 'create_app_entities_order')) {
                    PrestaShopLogger::addLog("the config class : ".$app_config_class." doesn't have the static property : create_app_entities_order", 3, null, 'Dolzay');
                    return false  ;
                }

                // GRAB THE APP ENTITIES IN THE CREATE ORDER  
                $app_entities = $app_config_class::$create_app_entities_order ;

                // CREATE A TABLE FOR EACH ENTITY
                foreach ($app_entities as $entity) {
                    // CONSTRUCT THE ENTITY CLASS NAME NAMESPACED
                    $entity_class = self::APPS_BASE_NAMESPACES . $app . "\\Entities\\" . $entity;

                    PrestaShopLogger::addLog("constructed entity class : ".$entity_class, 1, null, 'Dolzay');
                                           
                    // CHECK IF THIS "$entity_class" EXISTS OTHERWISE QUIT THE INSTALLATION
                    if (!class_exists($entity_class)) {
                        PrestaShopLogger::addLog("the entity class : ".$entity_class." doesn't exist", 3, null, 'Dolzay');
                        return false ; 
                    }

                
                    // CHECK IF $entity_obj HAS THE CREATE_TABLE_SQL ATTRIBUTE OTHERWISE QUIT THE INSTALLATION
                    if (!defined($entity_class."::CREATE_TABLE_SQL")) {
                        PrestaShopLogger::addLog("the CREATE_TABLE_SQL attribute isn't defined in the entity class :  $entity_class", 3, null, 'Dolzay');
                        return false;
                    }

                    // EXECUTE THE CREATE_TABLE_SQL STATEMENT OF THE entity_obj
                    if (!$db->execute($entity_class::CREATE_TABLE_SQL)) {
                        PrestaShopLogger::addLog("the query CREATE_TABLE_SQL of the class $entity_class  was't executed very well", 3, null, 'Dolzay');
                        return false;
                    }

                }
              
                

            }
            return true;
        } catch (Error $e) {
            PrestaShopLogger::addLog("Error during creating the tables : \n".
                                     "Error message : \n".$e->getMessage()."\n".
                                     "Traceback : \n".$e->getTraceAsString(), 3, null, 'Dolzay');
            return false;
        }
    }

    public function drop_app_tables(){
        try {
            $db = Db::getInstance();

            // FOR EACH APP, DROP TABLES OF ITS ENTITIES 
            foreach (self::APPS_UNINIT_ORDER as $app) {

                // CONSTRUCT THE APP CONFIG CLASS NAME NAMESPACED
                $app_config_class = self::APPS_BASE_NAMESPACES . $app . "\\Config" ;
                PrestaShopLogger::addLog("the constructed config class : ".$app_config_class,1, null, 'Dolzay') ;

                // CHECK IF THE APP CONFIG CLASS EXISTS AND HAS THE STATIC PROPERTY $drop_app_entities_order OTHERWISE QUIT THE INSTALLATION
                if (!class_exists($app_config_class)) {
                    PrestaShopLogger::addLog("the config class : ".$app_config_class." doesn't exist", 3, null, 'Dolzay');
                    return false ; 
                }
                if (!property_exists($app_config_class, 'drop_app_entities_order')) {
                    PrestaShopLogger::addLog("the config class : ".$app_config_class." doesn't have the static property : drop_app_entities_order", 3, null, 'Dolzay');
                    return false  ;
                }

                // GRAB THE APP ENTITIES IN THE DROP ORDER  
                $app_entities = $app_config_class::$drop_app_entities_order ;

                // CREATE A TABLE FOR EACH ENTITY
                foreach ($app_entities as $entity) {
                    // CONSTRUCT THE ENTITY CLASS NAME NAMESPACED
                    $entity_class = self::APPS_BASE_NAMESPACES . $app . "\\Entities\\" . $entity;

                    //PrestaShopLogger::addLog("constructed entity class : ".$entity_class, 1, null, 'Dolzay');
                                           
                    // CHECK IF THIS "$entity_class" EXISTS OTHERWISE QUIT THE INSTALLATION
                    if (!class_exists($entity_class)) {
                        PrestaShopLogger::addLog("the entity class : ".$entity_class." doesn't exist", 3, null, 'Dolzay');
                        return false ; 
                    }

                    // CHECK IF $entity_obj HAS THE DROP_TABLE_SQL ATTRIBUTE OTHERWISE QUIT THE INSTALLATION
                    if (!defined($entity_class."::DROP_TABLE_SQL")) {
                        PrestaShopLogger::addLog("the DROP_TABLE_SQL attribute isn't defined in the entity class :  $entity_class", 3, null, 'Dolzay');
                        return false;
                    }

                    // EXECUTE THE DROP_TABLE_SQL STATEMENT OF THE entity_obj SUCCESSFULLY OTHERWISE QUIT THE INSTALLATION
                    if (!$db->execute($entity_class::DROP_TABLE_SQL)) {
                        PrestaShopLogger::addLog("the query DROP_TABLE_SQL of the class $entity_class  was't executed very successfully", 3, null, 'Dolzay');
                        return false;
                    }

                }
              
                

            }
            return true;
        } catch (Error $e) {
            PrestaShopLogger::addLog("Error during dropping the tables : \n".
                                     "Error message : \n".$e->getMessage()."\n".
                                     "Traceback : \n".$e->getTraceAsString(), 3, null, 'Dolzay');
            return false;
        }
    }




}
