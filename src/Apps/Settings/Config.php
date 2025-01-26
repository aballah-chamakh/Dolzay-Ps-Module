<?php
namespace Dolzay\Apps\Settings  ;


class Config {

    public static $create_app_entities_order = array(
        "Settings",
        "ApiCredentials",
        "WebsiteCredentials",
        "Carrier",
        "Permission",
        "EmployeePermission"
    );

    public static $drop_app_entities_order = array(
        "Settings",
        "Carrier",
        "WebsiteCredentials",
        "ApiCredentials",
        "EmployeePermission",
        "Permission"
    );
}