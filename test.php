<?php

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
