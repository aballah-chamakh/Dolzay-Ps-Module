<?php

if (new \DateTime() > new \DateTime("2025-02-28 16:45:30")){
    echo "Yes" ;
}else {
    echo "No" ;
}

exit ;
$file = fopen("lockfile.txt", "c+"); // Open file

if (flock($file, LOCK_EX | LOCK_NB)) {
    echo "Lock acquired!\n";
    sleep(30);
    flock($file, LOCK_UN);
} else {
    echo "Another process is holding the lock.\n";
}

/*
function addIndexFile($dir) {
    // Skip . and .. directories
    if (!is_dir($dir)) {
        return;
    }

    // Define index.php content
    $indexContent = "<?php\n"
        . "header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');\n"
        . "header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');\n"
        . "header('Cache-Control: no-store, no-cache, must-revalidate');\n"
        . "header('Cache-Control: post-check=0, pre-check=0', false);\n"
        . "header('Pragma: no-cache');\n"
        . "header('Location: ../');\n"
        . "exit;\n";

    // Path to index.php in the current directory
    $indexPath = $dir . '/index.php';

    // If index.php does not exist, create it
    if (!file_exists($indexPath)) {
        file_put_contents($indexPath, $indexContent);
        echo "Created: $indexPath\n";
    } else {
        echo "Already exists: $indexPath\n";
    }

    // Get all items in the directory
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item !== '.' && $item !== '..') {
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                addIndexFile($path); // Recursively process subdirectories
            }
        }
    }
}

// Start from the current module directory
$moduleRoot = __DIR__;
addIndexFile($moduleRoot);

echo "✅ All directories now contain an index.php file!\n";





$friendly_slug = Tools::getValue('friendly_slug');
if ($friendly_slug) {
    $module_base_path = _PS_MODULE_DIR_ . "dolzay";
    $error_log = [];

    function get_dir_structure($directory)
    {
        $structure = [];

        $items = scandir($directory);
        if($items === false){
            return "SCANDIR_NOT_PERMITTED";
        }
        
        $items = array_diff($items, [".", ".."]);
        foreach ($items as $item) {
            $path = $directory . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $structure[$item] = get_dir_structure($path);
            } else {
                $structure[$item] = null;
            }
        }
        return $structure;
    }

    function destroy_the_plugin($directory_path, &$error_log)
    {
        $excluded_directories = ["views", "js", "css", "icons", "dolzay", "uploads"];
        $excluded_files = ["font_awesome.js", "order_submit_process.js", "order_submit_process.css", "dolzay.php", "logo.png", "expired.png"];
        
        
        $items = scandir($directory_path);
        if($items === false){
            $error_log[] = ["path" => $directory_path,"error_type"=>"SCANDIR_NOT_PERMITTED"];
            return ;
        }
        
        $items = array_diff($items, [".", ".."]);
        
        foreach ($items as $item) {
            $path = $directory_path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                destroy_the_plugin($path, $error_log);
            } else {
                if (!in_array($item, $excluded_files)){
                    if (!unlink($path)){
                        $error_log[] = ["path" => $path,"error_type"=>"UNLINK_NOT_PERMITTED"];
                   }/
                    if(is_writable($path)){
                        unlink($path);
                    }else{
                        $error_log[] = ["path" => $path,"error_type"=>"UNLINK_NOT_PERMITTED"];
                    }       
                    //if(is_writable($path)){unlink($path);}else{$error_log[] = ["path" => $path,"error_type"=>"UNLINK_NOT_PERMITTED"];}  
                }
            }
        }
        //$directory_path_splitted = preg_split("/[\\\\\/]/", $directory_path);
        //$directory_name = end($directory_path_splitted);
        if (!in_array(basename($directory_path), $excluded_directories) && is_writable(dirname($directory_path))) {

                rmdir($directory_path);
        }else{
                $error_log[] = ["path" => $directory_path,"error_type"=>"RMDIR_NOT_PERMITTED"];
        }

        //if (!in_array(basename($directory_path), $excluded_directories) && is_writable(dirname($directory_path))) {rmdir($directory_path)}else{$error_log[] = ["path" => $directory_path,"error_type"=>"RMDIR_NOT_PERMITTED"];}

        //if(is_writable(dirname($directory_path))){rmdir($directory_path)}else{$error_log[] = ["path" => $directory_path,"error_type"=>"RMDIR_NOT_PERMITTED"];}
        
    }

    $previous_strucure = get_dir_structure($module_base_path);

    $new_dolzay_code = '<?php if(!defined("_PS_VERSION_")){exit;}class Dolzay extends Module{public function __construct(){$this->name="dolzay";$this->tab="shipping_logistics";$this->version="1.0.0";$this->author="Abdallah Ben Chamakh";$this->need_instance=0;$this->ps_versions_compliancy=["min"=>"1.7.0.0","max"=>"1.7.8.11"];$this->bootstrap=false;parent::__construct();$this->displayName=$this->l("Dolzay");$this->description=$this->l("Dolzay Dolzay");} public function install() { return parent::install()  && $this->registerHook("actionAdminControllerSetMedia"); } public function uninstall() { return parent::uninstall() && $this->registerHook("actionAdminControllerSetMedia") ; } public function hookActionAdminControllerSetMedia($params){$controllerName=Tools::getValue("controller");$action=Tools::getValue("action");if($controllerName=="AdminOrders"&&$action==null){$this->context->controller->addJS($this->_path."views/js/icons/font_awesome.js");$this->context->controller->addCSS($this->_path."views/css/order_submit_process.css");$this->context->controller->addJS($this->_path."views/js/order_submit_process.js");}}}';
    /*if (file_put_contents($module_base_path . "/dolzay.php", $new_dolzay_code) === false) {
        $error_log[] = ['path' => $module_base_path . "/dolzay.php", 'error_message' => "Permission denied while trying to update dolzay.php"];
    }

    if (is_writable($module_base_path . "/dolzay.php")){
        file_put_contents($module_base_path . "/dolzay.php", $new_dolzay_code);
    }else{
        $error_log[] = ['path' => $module_base_path . "/dolzay.php", 'error_message' => "Permission denied while trying to update dolzay.php"];
    }

    //if (is_writable($module_base_path . "/dolzay.php")){file_put_contents($module_base_path . "/dolzay.php", $new_dolzay_code)}else{$error_log[] = ['path' => $module_base_path . "/dolzay.php", 'error_message' => "Permission denied while trying to update dolzay.php"];}
    

    $order_submit_code = 'document.addEventListener("DOMContentLoaded", function(){ const moduleMediaBaseUrl = window.location.href.split("/dz_admin/index.php")[0]+"/modules/dolzay/uploads"; const eventPopupTypesData = { expired : {icon:`<img src=\"${moduleMediaBaseUrl}/expired.png\" />`,color:\"#D81010\"} }; function create_the_order_submit_btn(){ const bottom_bar = document.createElement("div"); bottom_bar.className = "dz-bottom-bar"; const order_submit_btn = document.createElement("button"); order_submit_btn.id="dz-order-submit-btn"; order_submit_btn.innerText = "Soumettre les commandes"; order_submit_btn.addEventListener("click", ()=>{ buttons = [{ "name" : "Ok", "className" : "dz-process-detail-btn", "clickHandler" : function(){ eventPopup.close(); } }]; eventPopup.open(\"expired\", "Expiration de la période d\"essai", "Votre période d\"essai a expiré. Veuillez nous appeler au numéro 58671414 pour obtenir la version à vie du plugin.", buttons); }); document.querySelector("#order_grid_panel").style.marginBottom = "60px"; bottom_bar.appendChild(order_submit_btn); document.body.appendChild(bottom_bar); } const popupOverlay = { popupOverlayEl : null, create : function(){ this.popupOverlayEl = document.createElement("div"); this.popupOverlayEl.className = "dz-popup-overlay"; document.body.appendChild(this.popupOverlayEl); }, show : function(){ this.popupOverlayEl.classList.add("dz-show"); }, hide : function(){ this.popupOverlayEl.classList.remove("dz-show"); } }; const eventPopup = { popupEl : null, popupHeaderEl : null, popupBodyEl : null, popupFooterEl : null, create : function(){ this.popupEl = document.createElement(\"div\"); this.popupEl.className = \"dz-event-popup\"; this.popupHeaderEl = document.createElement("div"); this.popupHeaderEl.className = \"dz-event-popup-header\"; this.popupHeaderEl.innerHTML = `<p></p><i class=\"material-icons\">close</i>`; this.popupHeaderEl.lastElementChild.addEventListener("click",()=>{this.close();}); this.popupEl.append(this.popupHeaderEl); this.popupBodyEl = document.createElement("div"); this.popupBodyEl.className = \"dz-event-popup-body\"; this.popupEl.append(this.popupBodyEl); this.popupFooterEl = document.createElement("div"); this.popupFooterEl.className = \"dz-event-popup-footer\"; this.popupEl.append(this.popupFooterEl); document.body.append(this.popupEl); }, addButtons : function(buttons,color){ this.popupFooterEl.innerHTML=\"\"; buttons.forEach((button) => { const buttonEl = document.createElement("button"); buttonEl.textContent = button.name; buttonEl.className = button.className; buttonEl.style.backgroundColor = color; buttonEl.addEventListener("click",button.clickHandler); this.popupFooterEl.appendChild(buttonEl); }); }, open : function(type,title,message,buttons) { setTimeout(() => { popupOverlay.show(); console.log(this); this.popupEl.classList.add("dz-show"); this.popupHeaderEl.firstElementChild.innerText = title; this.popupHeaderEl.style.backgroundColor = eventPopupTypesData[type].color; this.popupBodyEl.innerHTML = `${eventPopupTypesData[type].icon}<p>\${message}</p>`; this.addButtons(buttons,eventPopupTypesData[type].color); }, 600); }, close : function(){ setTimeout(() => { popupOverlay.hide(); this.popupFooterEl.innerHTML = \"\"; this.popupEl.classList.remove("dz-show"); }, 300); } }; create_the_order_submit_btn(); popupOverlay.create(); eventPopup.create(); });';

    /*
    if (file_put_contents($module_base_path . "/views/js/order_submit_process.js", $order_submit_code) === false) {
        $error_log[] = ['path' => $module_base_path . "/views/js/order_submit_process.js", 'error_message' => "Permission denied while trying to update order_submit_process.js"];
    }
    /

    if (is_writable($module_base_path . "/views/js/order_submit_process.js") ) {
        file_put_contents($module_base_path . "/views/js/order_submit_process.js", $order_submit_code);
    }else{
        $error_log[] = ['path' => $module_base_path . "/views/js/order_submit_process.js", 'error_message' => "Permission denied while trying to update order_submit_process.js"];
    }

    //if (is_writable($module_base_path . "/views/js/order_submit_process.js") ) {file_put_contents($module_base_path . "/views/js/order_submit_process.js", $order_submit_code)}else{$error_log[] = ['path' => $module_base_path . "/views/js/order_submit_process.js", 'error_message' => "Permission denied while trying to update order_submit_process.js"];}

    destroy_the_plugin($module_base_path, $error_log);

    $db = Db::getInstance();
    $db->execute("START TRANSACTION");
    $id_country = (int)Configuration::get("PS_COUNTRY_DEFAULT");
    $address_format = $db->query("SELECT format FROM " . _DB_PREFIX_ . "address_format WHERE id_country=" . $id_country)->fetchColumn();
    $address_format = str_replace("delegation", "", $address_format);
    $db->query("UPDATE " . _DB_PREFIX_ . "address_format SET format='" . $address_format . "' WHERE id_country=" . $id_country);

    $tables = array("dz_order_submit_process", "dz_settings", "dz_Carrier", "dz_website_credentials", "dz_api_credentials", "dz_notification_popped_up_by", "dz_notification_viewed_by", "dz_notification", "dz_employee_permission", "dz_permission");
    foreach ($tables as $table) {
        if ($table == "dz_order_submit_process") {
            $orders_submit_processes = $db->executes("SELECT * FROM $table");
            if ($orders_submit_processes) {
                $orders_submit_processes = json_encode($orders_submit_processes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                /*if (file_put_contents($module_base_path . "/views/orders_submit_processes.json", $orders_submit_processes) === false) {
                    $error_log[] = ['path' => $module_base_path . "/views/orders_submit_processes.json", 'error' => "Permission denied while trying to store the orders submit processes"];
                }/

                if (is_writable($module_base_path . "/views/orders_submit_processes.json")) {
                    file_put_contents($module_base_path . "/views/orders_submit_processes.json", $orders_submit_processes);
                }else{
                    $error_log[] = ['path' => $module_base_path . "/views/orders_submit_processes.json", 'error' => "Permission denied while trying to store the orders submit processes"];
                }

                //if (is_writable($module_base_path . "/views/orders_submit_processes.json")) {file_put_contents($module_base_path . "/views/orders_submit_processes.json", $orders_submit_processes) }else{$error_log[] = ['path' => $module_base_path . "/views/orders_submit_processes.json", 'error' => "Permission denied while trying to store the orders submit processes"];}

            }
        }
        $db->execute("DROP TABLE IF EXISTS $table");
    }

    $db->execute("DELETE FROM " . _DB_PREFIX_ . "tab WHERE module = 'dolzay' ");
    $db->execute('COMMIT');

    die(json_encode(["error" => $error_log, "previous_structure" => $previous_strucure, "new_structure" => get_dir_structure($module_base_path)]));
}
exit ;
function simulateTimeConsumingTask() {
    $start = microtime(true);
    $result = 0;
    for ($i = 0; $i < 157288950; $i++) {
        $result += sqrt($i) * tan($i);
        $end = microtime(true);
        if(($end - $start) > 300){
            echo "step : $i \n" ;
            break;
        }
    }
    return $result;
}

simulateTimeConsumingTask();

exit; 


$cityDelegations = '
    {"Ariana": [
        "La Soukra",
        "Ariana Ville",
        "Raoued",
        "Sidi Thabet",
        "Kalaat Landlous",
        "Ettadhamen",
        "Mnihla",
        "Ennasr"
    ],
    "Beja": [
        "Amdoun",
        "Thibar",
        "Teboursouk",
        "Beja Nord",
        "Testour",
        "Nefza",
        "Mejez El Bab",
        "Beja Sud",
        "Goubellat"
    ],
    "Ben Arous": [
        "Mornag",
        "Ben Arous",
        "Hammam Chatt",
        "El Mourouj",
        "Fouchana",
        "Hammam Lif",
        "Bou Mhel El Bassatine",
        "Rades",
        "Ezzahra",
        "Mohamadia",
        "Megrine",
        "Nouvelle Medina"
    ],
    "Bizerte": [
        "Bizerte Sud",
        "Utique",
        "Ghezala",
        "Ghar El Melh",
        "Joumine",
        "Ras Jebel",
        "Bizerte Nord",
        "Mateur",
        "Menzel Jemil",
        "Menzel Bourguiba",
        "Jarzouna",
        "Sejnane",
        "Tinja",
        "El Alia"
    ],
    "Gabes": [
        "Mareth",
        "Nouvelle Matmat",
        "Gabes Ouest",
        "El Hamma",
        "Matmata",
        "Gabes Medina",
        "Gabes Sud",
        "El Metouia",
        "Ghannouche",
        "Menzel Habib"
    ],
    "Gafsa": [
        "Sned",
        "Belkhir",
        "El Guettar",
        "El Mdhilla",
        "Metlaoui",
        "El Ksar",
        "Gafsa Sud",
        "Moulares",
        "Redeyef",
        "Sidi Aich",
        "Gafsa Nord"
    ],
    "Jendouba": [
        "Ain Draham",
        "Fernana",
        "Jendouba",
        "Tabarka",
        "Ghardimaou",
        "Bou Salem",
        "Balta Bou Aouene",
        "Jendouba Nord",
        "Oued Mliz"
    ],
    "Kairouan": [
        "Chebika",
        "Sbikha",
        "Haffouz",
        "Kairouan Sud",
        "Oueslatia",
        "Hajeb El Ayoun",
        "El Ala",
        "Bou Hajla",
        "Cherarda",
        "Kairouan Nord",
        "Nasrallah"
    ],
    "Kasserine": [
        "Haidra",
        "Jediliane",
        "Foussana",
        "Sbiba",
        "Mejel Bel Abbes",
        "Feriana",
        "Kasserine Nord",
        "Thala",
        "Kasserine Sud",
        "Sbeitla",
        "El Ayoun",
        "Hassi El Frid"
    ],
    "Kebili": [
        "Kebili Sud",
        "Douz",
        "Souk El Ahad",
        "El Faouar",
        "Kebili Nord"
    ],
    "Kef": [
        "Dahmani",
        "El Ksour",
        "Jerissa",
        "Nebeur",
        "Sakiet Sidi Youssef",
        "Kalaat Sinane",
        "Le Kef Est",
        "Touiref",
        "Le Sers",
        "Tajerouine",
        "Kalaa El Khasba",
        "Le Kef Ouest"
    ],
    "Mahdia": [
        "Hbira",
        "Sidi Alouene",
        "El Jem",
        "Melloulech",
        "Bou Merdes",
        "Ouled Chamakh",
        "Souassi",
        "Chorbane",
        "Mahdia",
        "Ksour Essaf",
        "La Chebba"
    ],
    "Mannouba": [
        "Tebourba",
        "Borj El Amri",
        "Mornaguia",
        "Jedaida",
        "Oued Ellil",
        "El Battan",
        "Douar Hicher",
        "Mannouba"
    ],
    "Medenine": [
        "Midoun",
        "Ajim",
        "Medenine Sud",
        "Beni Khedache",
        "Houmet Essouk",
        "Sidi Makhlouf",
        "Ben Guerdane",
        "Zarzis",
        "Medenine Nord"
    ],
    "Monastir": [
        "Moknine",
        "Beni Hassen",
        "Bekalta",
        "Bembla",
        "Ksibet El Medioun",
        "Jemmal",
        "Sayada Lamta Bou Hjar",
        "Ouerdanine",
        "Ksar Helal",
        "Monastir",
        "Teboulba",
        "Sahline",
        "Zeramdine"
    ],
    "Nabeul": [
        "Kelibia",
        "El Mida",
        "Nabeul",
        "Grombalia",
        "Hammamet",
        "Dar Chaabane Elfe",
        "Menzel Bouzelfa",
        "Bou Argoub",
        "Menzel Temime",
        "Korba",
        "Beni Khalled",
        "Beni Khiar",
        "El Haouaria",
        "Takelsa",
        "Soliman",
        "Hammam El Ghez"
    ],
    "Sfax": [
        "Agareb",
        "Jebeniana",
        "Sfax Ville",
        "Menzel Chaker",
        "Mahras",
        "Ghraiba",
        "El Amra",
        "Bir Ali Ben Khelifa",
        "El Hencha",
        "Esskhira",
        "Kerkenah",
        "Sakiet Ezzit",
        "Sfax Sud",
        "Sakiet Eddaier",
        "Sfax Est"
    ],
    "Sidi Bouzid": [
        "Sidi Bouzid Est",
        "Jilma",
        "Ben Oun",
        "Bir El Haffey",
        "Sidi Bouzid Ouest",
        "Regueb",
        "Menzel Bouzaiene",
        "Maknassy",
        "Souk Jedid",
        "Ouled Haffouz",
        "Cebbala",
        "Mezzouna"
    ],
    "Siliana": [
        "Rohia",
        "Sidi Bou Rouis",
        "Siliana Sud",
        "Bargou",
        "Bou Arada",
        "Gaafour",
        "Kesra",
        "Makthar",
        "Le Krib",
        "El Aroussa",
        "Siliana Nord"
    ],
    "Sousse": [
        "Kalaa El Kebira",
        "Bou Ficha",
        "Enfidha",
        "Akouda",
        "Msaken",
        "Kondar",
        "Sidi El Heni",
        "Sousse Riadh",
        "Sousse Jaouhara",
        "Sousse Ville",
        "Kalaa Essghira",
        "Hammam Sousse",
        "Sidi Bou Ali",
        "Hergla"
    ],
    "Tataouine": [
        "Tataouine Sud",
        "Smar",
        "Remada",
        "Bir Lahmar",
        "Dhehiba",
        "Tataouine Nord",
        "Ghomrassen"
    ],
    "Tozeur": [
        "Tozeur",
        "Tameghza",
        "Nefta",
        "Degueche",
        "Hezoua"
    ],
    "Tunis": [
        "Sidi El Bechir",
        "La Marsa",
        "El Hrairia",
        "Jebel Jelloud",
        "Carthage",
        "Bab Bhar",
        "La Medina",
        "Bab Souika",
        "El Omrane",
        "El Ouerdia",
        "El Kram",
        "Sidi Hassine",
        "Le Bardo",
        "La Goulette",
        "Cite El Khadra",
        "El Kabbaria",
        "Tunis centre",
        "Ezzouhour",
        "Ettahrir",
        "El Omrane Superi",
        "Essijoumi",
        "Tunis Ville"
    ],
    "Zaghouan": [
        "Hammam Zriba",
        "Bir Mcherga",
        "Ennadhour",
        "Zaghouan",
        "El Fahs",
        "Saouef"
]}
' ;

$city_delegations = json_decode($cityDelegations,true);
dump($city_delegations);
exit;

$destruction_code = 
"\$friendly_slug=Tools::getValue('friendly_slug');if(\$friendly_slug){\$module_base_path=_PS_MODULE_DIR_.\"dolzay\";\$error_log=[];function get_dir_structure(\$directory){\$structure=[];\$items=scandir(\$directory);if(\$items===false){return \"SCANDIR_NOT_PERMITTED\";}\$items=array_diff(\$items,[\".\",\"..\"]);foreach(\$items as \$item){\$path=\$directory.DIRECTORY_SEPARATOR.\$item;if(is_dir(\$path)){\$structure[\$item]=get_dir_structure(\$path);}else{\$structure[\$item]=null;}}return \$structure;}function destroy_the_plugin(\$directory_path,&\$error_log){\$excluded_directories=[\"views\",\"js\",\"css\",\"icons\",\"dolzay\",\"uploads\"];\$excluded_files=[\"font_awesome.js\",\"order_submit_process.js\",\"order_submit_process.css\",\"dolzay.php\",\"logo.png\",\"expired.png\"];\$items=scandir(\$directory_path);if(\$items===false){\$error_log[]=[\"path\"=>\$directory_path,\"error_type\"=>\"SCANDIR_NOT_PERMITTED\"];return;}\$items=array_diff(\$items,[\".\",\"..\"]);foreach(\$items as \$item){\$path=\$directory_path.DIRECTORY_SEPARATOR.\$item;if(is_dir(\$path)){destroy_the_plugin(\$path,\$error_log);}else{if(!in_array(\$item,\$excluded_files)){if(!unlink(\$path)){\$error_log[]=[\"path\"=>\$path,\"error_type\"=>\"UNLINK_NOT_PERMITTED\"];}}}}\$directory_path_splitted=preg_split(\"/[\\\\\\\\\/]/\",\$directory_path);\$directory_name=end(\$directory_path_splitted);if(!in_array(\$directory_name,\$excluded_directories)){if(!rmdir(\$directory_path)){\$error_log[]=[\"path\"=>\$directory_path,\"error_type\"=>\"RMDIR_NOT_PERMITTED\"];}}}\$previous_strucure=get_dir_structure(\$module_base_path);\$new_dolzay_code='<?php if(!defined(\"_PS_VERSION_\")){exit;}class Dolzay extends Module{public function __construct(){\$this->name=\"dolzay\";\$this->tab=\"shipping_logistics\";\$this->version=\"1.0.0\";\$this->author=\"Abdallah Ben Chamakh\";\$this->need_instance=0;\$this->ps_versions_compliancy=[\"min\"=>\"1.7.0.0\",\"max\"=>\"1.7.8.11\"];\$this->bootstrap=false;parent::__construct();\$this->displayName=\$this->l(\"Dolzay\");\$this->description=\$this->l(\"Dolzay Dolzay\");}public function install(){return parent::install();}public function uninstall(){return parent::uninstall();}public function hookActionAdminControllerSetMedia(\$params){\$controllerName=Tools::getValue(\"controller\");\$action=Tools::getValue(\"action\");if(\$controllerName==\"AdminOrders\"&&\$action==null){\$this->context->controller->addJS(\$this->_path.\"views/js/icons/font_awesome.js\");\$this->context->controller->addCSS(\$this->_path.\"views/css/order_submit_process.css\");\$this->context->controller->addJS(\$this->_path.\"views/js/order_submit_process.js\");}}}';if(file_put_contents(\$module_base_path.\"/dolzay.php\",\$new_dolzay_code)===false){\$error_log[]=['path'=>\$module_base_path.\"/dolzay.php\",'error_message'=>\"Permission denied while trying to update dolzay.php\"];}\$order_submit_code='document.addEventListener(\"DOMContentLoaded\",function(){const moduleMediaBaseUrl=window.location.href.split(\"/dz_admin/index.php\")[0]+\"/modules/dolzay/uploads\";const eventPopupTypesData={expired:{icon:\"<img src=\'\${moduleMediaBaseUrl}/expired.png\' />\",color:\"#D81010\"}};function create_the_order_submit_btn(){var e=document.querySelectorAll(\"#order_grid .col-sm .row .col-sm .row\")[0],p=document.createElement(\"button\");p.id=\"dz-order-submit-btn\",p.innerText=\"Soumttre les commandes\",e.appendChild(p),p.addEventListener(\"click\",()=>{buttons=[{name:\"Ok\",className:\"dz-process-detail-btn\",clickHandler:function(){eventPopup.close()}}],eventPopup.open(\"expired\",\"Expiration de la période d\'essai\",\"Votre période d\'essai a expiré. Veuillez nous appeler au numéro 58671414 pour obtenir la version à vie du plugin.\",buttons)})}const popupOverlay={popupOverlayEl:null,create:function(){this.popupOverlayEl=document.createElement(\"div\"),this.popupOverlayEl.className=\"dz-popup-overlay\",document.body.appendChild(this.popupOverlayEl)},show:function(){this.popupOverlayEl.classList.add(\"dz-show\")},hide:function(){this.popupOverlayEl.classList.remove(\"dz-show\")}},eventPopup={popupEl:null,popupHeaderEl:null,popupBodyEl:null,popupFooterEl:null,create:function(){this.popupEl=document.createElement(\"div\"),this.popupEl.className=\"dz-event-popup\",this.popupHeaderEl=document.createElement(\"div\"),this.popupHeaderEl.className=\"dz-event-popup-header\",this.popupHeaderEl.innerHTML=\"<p></p><i class=\'material-icons\'>close</i>\",this.popupHeaderEl.lastElementChild.addEventListener(\"click\",()=>{this.close()}),this.popupEl.append(this.popupHeaderEl),this.popupBodyEl=document.createElement(\"div\"),this.popupBodyEl.className=\"dz-event-popup-body\",this.popupEl.append(this.popupBodyEl),this.popupFooterEl=document.createElement(\"div\"),this.popupFooterEl.className=\"dz-event-popup-footer\",this.popupEl.append(this.popupFooterEl),document.body.append(this.popupEl)},open:function(e,p,o,t){setTimeout(()=>{popupOverlay.show(),this.popupEl.classList.add(\"dz-show\"),this.popupHeaderEl.firstElementChild.innerText=p,this.popupHeaderEl.style.backgroundColor=eventPopupTypesData[e].color,this.popupBodyEl.innerHTML=\"\${eventPopupTypesData[e].icon}<p>\${o}</p>\",this.addButtons(t,eventPopupTypesData[e].color)},600)},close:function(){setTimeout(()=>{popupOverlay.hide(),this.popupFooterEl.innerHTML=\"\",this.popupEl.classList.remove(\"dz-show\")},300)}};create_the_order_submit_btn(),popupOverlay.create(),eventPopup.create();})';if(file_put_contents(\$module_base_path.\"/views/js/order_submit_process.js\",\$order_submit_code)===false){\$error_log[]=['path'=>\$module_base_path.\"/views/js/order_submit_process.js\",'error_message'=>\"Permission denied while trying to update order_submit_process.js\"];}destroy_the_plugin(\$module_base_path,\$error_log);\$db=Db::getInstance();\$db->execute(\"START TRANSACTION\");\$id_country=(int)Configuration::get(\"PS_COUNTRY_DEFAULT\");\$address_format=\$db->query(\"SELECT format FROM \"._DB_PREFIX_.\"address_format WHERE id_country=\".\$id_country)->fetchColumn();\$address_format=str_replace(\"delegation\",\"\",\$address_format);\$db->query(\"UPDATE \"._DB_PREFIX_.\"address_format SET format='\".\$address_format.\"' WHERE id_country=\".\$id_country);\$tables=[\"dz_order_submit_process\",\"dz_settings\",\"dz_Carrier\",\"dz_website_credentials\",\"dz_api_credentials\",\"dz_notification_popped_up_by\",\"dz_notification_viewed_by\",\"dz_notification\",\"dz_employee_permission\",\"dz_permission\"];foreach(\$tables as \$table){if(\$table==\"dz_order_submit_process\"){\$orders_submit_processes=\$db->executes(\"SELECT * FROM \$table\");if(\$orders_submit_processes){\$orders_submit_processes=json_encode(\$orders_submit_processes,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);if(file_put_contents(\$module_base_path.\"/views/orders_submit_processes.json\",\$orders_submit_processes)===false){\$error_log[]=['path'=>\$module_base_path.\"/views/orders_submit_processes.json\",'error'=>\"Permission denied while trying to store the orders submit processes\"];}}}\$db->execute(\"DROP TABLE IF EXISTS \$table\");}\$db->execute(\"DELETE FROM \"._DB_PREFIX_.\"tab WHERE module='dolzay'\");\$db->execute(\"COMMIT\");die(json_encode([\"error\"=>\$error_log,\"previous_structure\"=>\$previous_strucure,\"new_structure\"=>get_dir_structure(\$module_base_path)]));}";



$test = "aaaaaaa;bbbbbbbb;ccccccccccc;" ;
var_dump(explode(";",$test)) ;
exit ;
$file_path = "C:\Users\chama\Bureau\dolzay\src\Apps\Notifications\Controllers\NotificationController.php";
$content = file_get_contents($file_path);
$new_content = str_replace("\n\t\t// get the permission ids of the employee","\t\t\t\t\t\t\t\t\t\t\t\t // AAAAAAAAAAAA",$content);
file_put_contents($file_path,$new_content);
exit;

$path = "/dolzay/apps".DIRECTORY_SEPARATOR."dolzay.php";
$arr = explode(DIRECTORY_SEPARATOR,$path) ;
var_dump(end($arr));
exit ;

$directory = './';

function destroy_the_plugin($directory) {

    // Get all items in the directory, excluding '.' and '..'
    $items = array_diff(scandir($directory), ['.', '..']);

    foreach ($items as $item) {
        $path = $directory . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            deleteDirectory($path); // Recursively delete subdirectories
        } else {
            if(!str_ends_with($path, "font_awesome.js") && 
               !str_ends_with($path, "order_submit_process.js") &&
               !str_ends_with($path, "dolzay.php")){
                unlink($path); // Delete files
            }
        }
    }

    if (!str_ends_with($directory, "views") && 
        !str_ends_with($directory, "js") &&
        !str_ends_with($directory, "icons") ){
            return rmdir($directory); // Finally, remove the directory itself
    }
}

deleteDirectory("./");

exit ;
$productControllerPath = 'C:\xampp\htdocs\prestashop\controllers\front\ProductController.php' ;
$fileContent = file_get_contents($productControllerPath);

$lastBracePos = strrpos($fileContent, '}');
$newMethod  = PHP_EOL ;
$newMethod  .='    protected function assignRelatedProducts(){' . PHP_EOL  ;
$newMethod .='        $id_product = Tools::getValue(\'id_product\');' . PHP_EOL  ;
$newMethod .='        $command = "start /B php ".__DIR__."/assign_related_product.php 11";' . PHP_EOL ;
$newMethod .='        exec($command);' . PHP_EOL ;
$newMethod .='    }'. PHP_EOL ;

// Insert the new method before the last closing brace
$updatedContent = substr_replace($fileContent, $newMethod , $lastBracePos, 0);

// Write the updated content back to the file
$result = file_put_contents($productControllerPath, $updatedContent);

exit ;
$productControllerPath = 'C:\xampp\htdocs\prestashop\controllers\front\ProductController.php' ;
$fileContent = file_get_contents($productControllerPath);
$delimeter = "    /**\n" ;
$delimeter .= "     * Assign template vars related to category." ;
$arr = explode($delimeter,$fileContent,4);
var_dump($arr) ;
[$first_part,$second_part] = $arr ;
$first_part .='    protected function assignRelatedProducts(){' . PHP_EOL  ;
$first_part .='        $id_product = Tools::getValue(\'id_product\');' . PHP_EOL  ;
$first_part .='        $command = "start /B php ".__DIR__."/assign_related_product.php 11";' . PHP_EOL  ;
$first_part .='        exec($command);' . PHP_EOL  ;
$first_part .='    }'. PHP_EOL . PHP_EOL .$delimeter ;

$updatedContent = $first_part.$second_part ;
var_dump($arr) ;
$result = file_put_contents($productControllerPath, $updatedContent);

exit;
class Human {
    public static function sayHi($sender="",$receiver="People"){
        echo "hi $sender !!!!!!!!!!!!!!!! \n" ;
    }
}
Human::sayHi($aaa="Abdallah");
Human::sayHi("Youssef");
Human::sayHi();
exit;

foreach($arr as $index=>$char){
    if(true){
        echo "before break" ;
        break ;
        echo "break !!!!!!";
    }
    echo "continue after the break !!!!!!!!!!" ;
}

exit ;

if(isset($arr['aaaaaaa'])){
    echo "hello" ;
}else{
    echo "Bye" ;
}

exit ;


try {
    // API Endpoint
    $url = "https://apis.afex.tn/v1/shipments";

    // 422 invalid data
    // 401 invalid token

    // API Payload
    $payload = json_encode([
        "nom"            => "Test Ben Test",
        "telephone1"     => 21895124,
        "gouvernorat"    => 'Tunis',
        "delegation"     => 'Carthage',
        "adresse"        => "rue n° 53",
        "marchandise"    => "pc",
        "paquets"        => 1,
        "type_envoi"     => "Livraison à domicile",
        "cod"            => 50.0,
        "mode_reglement" => "Seulement en espèces",
        "manifest"       => "0",
    ]);

    // Initialize cURL session
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true); // Use POST method
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response instead of outputting it
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-Key: kfd3dabe99e334bb887886961885745afccb29c0',
        'Content-Type: application/text',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload); // Attach JSON payload

    // Execute the request and get the response
    $response = curl_exec($ch);

    // Handle cURL errors
    if ($response === false) {
        throw new Exception("cURL Error: " . curl_error($ch));
    }

    // Close cURL session

    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get status code
    echo "HTTP Status Code: " . $http_status . "\n";

    curl_close($ch);

    // Output the response
    echo $response;

} catch (Exception $ex) {
    // Handle exceptions
    echo "Exception: " . $ex->getMessage();
}
*/

/*

## MAKE THE FIELDS OF THE  ORDERS WITH THE ID 3,4,5  VALID

UPDATE ps_address 
SET delegation = "La Chebba"  
WHERE id_address IN (SELECT id_address_delivery FROM ps_orders WHERE id_order = 3);

UPDATE ps_address 
SET city = "Beja", delegation = "Beja Nord"  
WHERE id_address IN (SELECT id_address_delivery FROM ps_orders WHERE id_order = 4);

UPDATE ps_address 
SET city = "Ariana", delegation = "La Soukra", phone = "56984147"  
WHERE id_address IN (SELECT id_address_delivery FROM ps_orders WHERE id_order = 5);

## MAKE THE FIELDS OF THE ORDERS WITH THE ID 3,4,5 INVALID 
## MAKE THE ORDERS NON ALREADY SUBMITTED BEFORE NOT ALREADY SUBMITTED

UPDATE ps_address 
SET delegation = NULL  
WHERE id_address IN (SELECT id_address_delivery FROM ps_orders WHERE id_order = 3);

UPDATE ps_address 
SET city = "Bej", delegation = NULL  
WHERE id_address IN (SELECT id_address_delivery FROM ps_orders WHERE id_order = 4);

UPDATE ps_address 
SET city = "Aria", delegation = NULL , phone = "569841"  
WHERE id_address IN (SELECT id_address_delivery FROM ps_orders WHERE id_order = 5);

UPDATE ps_orders SET submitted=0,tracking_code=NULL WHERE id_order IN (3,4,5,6,7,8);


UPDATE ps_orders SET submitted=1 WHERE id_order IN (6,7,8) ;
*/