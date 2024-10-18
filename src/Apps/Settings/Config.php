<?php
namespace Dolzay\Apps\Settings  ;


class Config {

    public static $create_app_entities_order = array(
        "Permission",
        "EmployeePermission"
    );

    public static $drop_app_entities_order = array(
        "EmployeePermission",
        "Permission"
    );
}