<?php
namespace Dolzay\Apps\Processes;  ;


class Config {

    public static $create_app_entities_order = array(
        "Process",
        "OrderToSubmit",
        "OrderToMonitor"
    );

    public static $drop_app_entities_order = array(
        "OrderToMonitor",
        "OrderToSubmit",
        "Process"
    );
    
}