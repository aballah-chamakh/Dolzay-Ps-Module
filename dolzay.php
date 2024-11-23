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
        "Notifications",
        "Processes",
        "Zones"
    ];

    const APPS_UNINIT_ORDER = [
        "Notifications",
        "Settings",
        "Processes",
        "Zones"
    ];

    const APPS_BASE_NAMESPACES = "Dolzay\\Apps\\" ;

    private const CITIES = [
        "Ariana" => "Ariana",
        "Beja" => "Beja",
        "Ben Arous" => "Ben Arous",
        "Bizerte" => "Bizerte",
        "Gabes" => "Gabes",
        "Gafsa" => "Gafsa",
        "Jendouba" => "Jendouba",
        "Kairouan" => "Kairouan",
        "Kasserine" => "Kasserine",
        "Kebili" => "Kebili",
        "La Manouba" => "La Manouba",
        "Le Kef" => "Le Kef",
        "Mahdia" => "Mahdia",
        "Medenine" => "Medenine",
        "Monastir" => "Monastir",
        "Nabeul" => "Nabeul",
        "Sfax" => "Sfax",
        "Sidi Bouzid" => "Sidi Bouzid",
        "Siliana" => "Siliana",
        "Sousse" => "Sousse",
        "Tataouine" => "Tataouine",
        "Tozeur" => "Tozeur",
        "Tunis" => "Tunis",
        "Zaghouan" => "Zaghouan"
    ];

    private const CITIES_DELEGATIONS = [
        "Ariana" => ["Ariana Ville" => "Ariana Ville", "Soukra" => "Soukra", "Raoued" => "Raoued", "Ettadhamen" => "Ettadhamen", "Mnihla" => "Mnihla"],
        "Beja" => ["Beja Nord" => "Beja Nord", "Beja Sud" => "Beja Sud", "Testour" => "Testour", "Teboursouk" => "Teboursouk", "Medjez El Bab" => "Medjez El Bab", "Nefza" => "Nefza", "Amdoun" => "Amdoun"],
        "Ben Arous" => ["Ben Arous" => "Ben Arous", "Mourouj" => "Mourouj", "Hammam Lif" => "Hammam Lif", "Hammam Chott" => "Hammam Chott", "Bou Mhel El Bassatine" => "Bou Mhel El Bassatine", "Ezzahra" => "Ezzahra", "Megrine" => "Megrine", "Mohamedia" => "Mohamedia", "Fouchana" => "Fouchana", "Radès" => "Radès", "Mornag" => "Mornag"],
        "Bizerte" => ["Bizerte Nord" => "Bizerte Nord", "Bizerte Sud" => "Bizerte Sud", "Menzel Jemil" => "Menzel Jemil", "Menzel Bourguiba" => "Menzel Bourguiba", "Tinja" => "Tinja", "Sejnane" => "Sejnane", "Ras Jebel" => "Ras Jebel", "Ghar El Melh" => "Ghar El Melh", "El Alia" => "El Alia", "Mateur" => "Mateur", "Joumine" => "Joumine"],
        "Gabes" => ["Gabes Ville" => "Gabes Ville", "Gabes Sud" => "Gabes Sud", "Gabes Ouest" => "Gabes Ouest", "El Hamma" => "El Hamma", "Matmata" => "Matmata", "Mareth" => "Mareth", "Nouvelle Matmata" => "Nouvelle Matmata", "Ghannouch" => "Ghannouch", "Menzel Habib" => "Menzel Habib"],
        "Gafsa" => ["Gafsa Nord" => "Gafsa Nord", "Gafsa Sud" => "Gafsa Sud", "Metlaoui" => "Metlaoui", "Redeyef" => "Redeyef", "Moulares" => "Moulares", "El Ksar" => "El Ksar", "Sened" => "Sened", "Oum Larayes" => "Oum Larayes", "Belkhir" => "Belkhir", "El Guettar" => "El Guettar"],
        "Jendouba" => ["Jendouba" => "Jendouba", "Bou Salem" => "Bou Salem", "Tabarka" => "Tabarka", "Ain Draham" => "Ain Draham", "Fernana" => "Fernana", "Balta Bou Aouane" => "Balta Bou Aouane", "Ghardimaou" => "Ghardimaou"],
        "Kairouan" => ["Kairouan Nord" => "Kairouan Nord", "Kairouan Sud" => "Kairouan Sud", "Chebika" => "Chebika", "Oueslatia" => "Oueslatia", "Sbikha" => "Sbikha", "Hajeb El Ayoun" => "Hajeb El Ayoun", "Nasrallah" => "Nasrallah", "Bou Hajla" => "Bou Hajla", "Cheraitia" => "Cheraitia"],
        "Kasserine" => ["Kasserine Nord" => "Kasserine Nord", "Kasserine Sud" => "Kasserine Sud", "Thala" => "Thala", "Sbeitla" => "Sbeitla", "Feriana" => "Feriana", "Hassi El Ferid" => "Hassi El Ferid", "Sbiba" => "Sbiba", "Jedelienne" => "Jedelienne", "El Ayoun" => "El Ayoun"],
        "Kebili" => ["Kebili Nord" => "Kebili Nord", "Kebili Sud" => "Kebili Sud", "Douz Nord" => "Douz Nord", "Douz Sud" => "Douz Sud", "Souk Lahad" => "Souk Lahad"],
        "La Manouba" => ["Manouba" => "Manouba", "Douar Hicher" => "Douar Hicher", "Oued Ellil" => "Oued Ellil", "Den Den" => "Den Den", "Mornaguia" => "Mornaguia", "Borj El Amri" => "Borj El Amri", "El Batan" => "El Batan", "Tebourba" => "Tebourba"],
        "Le Kef" => ["Le Kef" => "Le Kef", "Dahmani" => "Dahmani", "Jérissa" => "Jérissa", "Kalâat Snan" => "Kalâat Snan", "Kalâat Khasba" => "Kalâat Khasba", "Nebeur" => "Nebeur", "Sakiet Sidi Youssef" => "Sakiet Sidi Youssef", "Tajerouine" => "Tajerouine"],
        "Mahdia" => ["Mahdia" => "Mahdia", "Bou Merdes" => "Bou Merdes", "Chebba" => "Chebba", "El Jem" => "El Jem", "Ksour Essef" => "Ksour Essef", "Melloulech" => "Melloulech", "Sidi Alouane" => "Sidi Alouane"],
        "Medenine" => ["Medenine Nord" => "Medenine Nord", "Medenine Sud" => "Medenine Sud", "Houmt Souk" => "Houmt Souk", "Ajim" => "Ajim", "Midoun" => "Midoun", "Ben Gardane" => "Ben Gardane", "Zarzis" => "Zarzis", "Beni Khedache" => "Beni Khedache"],
        "Monastir" => ["Monastir" => "Monastir", "Sahline" => "Sahline", "Ksibet El Mediouni" => "Ksibet El Mediouni", "Jemmal" => "Jemmal", "Zeramdine" => "Zeramdine", "Moknine" => "Moknine", "Bekalta" => "Bekalta", "Teboulba" => "Teboulba", "Ksar Hellal" => "Ksar Hellal", "Beni Hassen" => "Beni Hassen"],
        "Nabeul" => ["Nabeul" => "Nabeul", "Hammamet" => "Hammamet", "Korba" => "Korba", "Kelibia" => "Kelibia", "Dar Chaabane El Fehri" => "Dar Chaabane El Fehri", "El Mida" => "El Mida", "Beni Khiar" => "Beni Khiar", "Menzel Bouzelfa" => "Menzel Bouzelfa", "Takelsa" => "Takelsa"],
        "Sfax" => ["Sfax Ville" => "Sfax Ville", "Sfax Sud" => "Sfax Sud", "Sfax Ouest" => "Sfax Ouest", "Sakiet Ezzit" => "Sakiet Ezzit", "Sakiet Eddaier" => "Sakiet Eddaier", "Thyna" => "Thyna", "El Ain" => "El Ain", "Agareb" => "Agareb", "Menzel Chaker" => "Menzel Chaker", "Bir Ali Ben Khalifa" => "Bir Ali Ben Khalifa"],
        "Sidi Bouzid" => ["Sidi Bouzid Ouest" => "Sidi Bouzid Ouest", "Sidi Bouzid Est" => "Sidi Bouzid Est", "Bir El Hafey" => "Bir El Hafey", "Meknassy" => "Meknassy", "Mezzouna" => "Mezzouna", "Regueb" => "Regueb", "Jilma" => "Jilma", "Cebbala Ouled Asker" => "Cebbala Ouled Asker"],
        "Siliana" => ["Siliana Nord" => "Siliana Nord", "Siliana Sud" => "Siliana Sud", "Gaafour" => "Gaafour", "El Krib" => "El Krib", "Bouarada" => "Bouarada", "Makthar" => "Makthar", "Bargou" => "Bargou", "Kesra" => "Kesra"],
        "Sousse" => ["Sousse Ville" => "Sousse Ville", "Sousse Jawhara" => "Sousse Jawhara", "Sousse Riadh" => "Sousse Riadh", "Hammam Sousse" => "Hammam Sousse", "Akouda" => "Akouda", "Kalaa Kebira" => "Kalaa Kebira", "Kalaa Seghira" => "Kalaa Seghira", "Enfidha" => "Enfidha", "Hergla" => "Hergla"],
        "Tataouine" => ["Tataouine Nord" => "Tataouine Nord", "Tataouine Sud" => "Tataouine Sud", "Remada" => "Remada", "Ghomrassen" => "Ghomrassen", "Bir Lahmar" => "Bir Lahmar", "Dehiba" => "Dehiba"],
        "Tozeur" => ["Tozeur" => "Tozeur", "Degache" => "Degache", "Nefta" => "Nefta", "Hazoua" => "Hazoua", "Tamerza" => "Tamerza"],
        "Tunis" => ["Bab El Bhar" => "Bab El Bhar", "Bab Souika" => "Bab Souika", "El Menzah" => "El Menzah", "El Omrane" => "El Omrane", "Carthage" => "Carthage", "La Marsa" => "La Marsa", "Le Kram" => "Le Kram", "La Goulette" => "La Goulette"],
        "Zaghouan" => ["Zaghouan" => "Zaghouan", "Zriba" => "Zriba", "Fahs" => "Fahs", "Nadhour" => "Nadhour", "Bir Mcherga" => "Bir Mcherga", "Saouaf" => "Saouaf"]
    ];

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
        $this->db = Db::getInstance();


        parent::__construct();

        $this->displayName = $this->l('Dolzay');
        $this->description = $this->l('Dolzay Dolzay');

    }


    public function install()
    {
        try {
            return parent::install() && 
                    $this->create_app_tables() &&
                    $this->registerHook('additionalCustomerAddressFields') &&
                    $this->registerHook('displayFooter') &&
                    $this->add_delegation_to_address();
        } catch (Error $e) {
            PrestaShopLogger::addLog("Error during installation: " . $e->getMessage(), 3, null, 'Dolzay');
            return false;
        }
    }

    public function uninstall()
    {
        try {
            return parent::uninstall() && $this->drop_app_tables() && $this->remove_delegation_from_address();
        } catch (Error $e) {
            PrestaShopLogger::addLog("Error during uninstallation: " . $e->getMessage(), 3, null, 'Dolzay'); 
            return false;
        }
    }



    public function create_app_tables() {
        try {    
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

                
                    // IF $entity_class DOESN'T HAVE THE STATIC METHOD get_create_table_sql SKIP IT
                    if (!method_exists($entity_class, 'get_create_table_sql')) {
                        continue ;
                    }

                    // EXECUTE THE CREATE_TABLE_SQL STATEMENT OF THE entity_obj
                    if (!$this->db->execute($entity_class::get_create_table_sql())) {
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

                    // IF $entity_obj DOESN'T HAVE THE ATTRIBUTE DROP_TABLE_SQL ATTRIBUTE SKIP IT 
                    if (!defined($entity_class."::DROP_TABLE_SQL")) {
                        continue ;
                    }

                    // EXECUTE THE DROP_TABLE_SQL STATEMENT OF THE entity_obj SUCCESSFULLY OTHERWISE QUIT THE INSTALLATION
                    if (!$this->db->execute($entity_class::DROP_TABLE_SQL)) {
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

    private function add_delegation_to_address()
    {
        try {
            $query = "ALTER TABLE " . _DB_PREFIX_ . \AddressCore::$definition['table'] . " ADD COLUMN `delegation` varchar(255) DEFAULT NULL";
            $this->db->execute($query);
            return true ;
        }
        catch (Error $e) {
            PrestaShopLogger::addLog("Error during adding delegation column: " . $e->getMessage(), 3, null, 'Dolzay');
            return false;
        }
    }

    private function remove_delegation_from_address()
    {
        try {
            $query = "ALTER TABLE " . _DB_PREFIX_ . \AddressCore::$definition['table'] ." DROP COLUMN IF EXISTS `delegation`"; 
            $this->db->execute($query);
            return true ;
        } catch (Error $e) {
            PrestaShopLogger::addLog("Error during removing delegation column: " . $e->getMessage(), 3, null, 'Dolzay');
            return false;
        }
    }

    public function hookAdditionalCustomerAddressFields($params)
    {
        //$f$params['fields'];
        PrestaShopLogger::addLog("hookActionAdditionalCustomerAddressFields", 3, null, 'Dolzay');
        $additionnalFormFields = [] ;

        // convert the city field to a select field 
        $cityFormField = new FormField();
        $cityFormField->setName('city');
        $cityFormField->setLabel("City");
        $cityFormField->setType('select');
        $cityFormField->setRequired(true);
        $cityFormField->setAvailableValues(self::CITIES);
        $cityFormField->setValue("Ariana") ;
        $params['fields']['city'] = $cityFormField ;

        // add the delegation field 
        $delgFormField = new FormField();
        $delgFormField->setName('delegation');
        $delgFormField->setType('select');
        $delgFormField->setRequired(true);
        $delgFormField->setLabel("Delegation");
        $delegation_options = self::CITIES_DELEGATIONS["Ariana"];  
        $delgFormField->setAvailableValues($delegation_options);
        $delgFormField->setValue("Ariana Ville") ;
        $city_key_pos = array_search('city', array_keys($params['fields']))  ;
        $params['fields'] = array_merge(
            array_slice($params['fields'], 0, $city_key_pos +1),
            ['delegation' => $delgFormField],
            array_slice($params['fields'], $city_key_pos + 1)
        );

        //$additionnalFormFields[$this->name] = [$cityFormField,$delgFormField] ;

        return []  ;

    }

  
/*
    public function hookActionValidateCustomerAddressForm($params)
    {
        try {
            $form = $params['form'];
            
            if (empty($form['city']) || !array_key_exists($form['city'], self::cities_delegations)) {
                $form->getErrors()->add(
                    'city', 
                    $this->l('Please select a valid city.')
                );
            }

            if (empty($form['delegation']) || !in_array($form['delegation'], self::cities_delegations[$form['city']])) {
                $form->getErrors()->add(
                    'delegation',
                    $this->l('Please select a valid delegation for the selected city.')
                );
            }
        } catch (Error $e) {
            PrestaShopLogger::addLog("Error in hookActionValidateCustomerAddressForm: " . $e->getMessage(), 3, null, 'Dolzay');
        }
    }*/

    public function hookDisplayFooter()
    {
        try {
            if ($this->context->controller->php_self == 'address' || 
                $this->context->controller->php_self == 'order') {
                Media::addJsDef([
                    'cities_delegations' => self::cities_delegations
                ]);
                $this->context->controller->addJS($this->_path.'views/js/delegation.js');
            }
        } catch (Error $e) {
            PrestaShopLogger::addLog("Error in hookDisplayFooter: " . $e->getMessage(), 3, null, 'Dolzay');
        }
    }

}
