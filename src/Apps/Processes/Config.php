<?php
namespace Dolzay\Apps\Processes;  ;


class Config {

    public static $create_app_entities_order = array(
        "Process",
        "OrderToMonitor"
    );

    public static $drop_app_entities_order = array(
        "OrderToMonitor",
        "Process"
    );
    
}