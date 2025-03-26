<?php
namespace Dolzay\Apps\OrderMonitoringProcess;  


class Config {

    public static $create_app_entities_order = array(
        "OrderToMonitor",
        "OrderMonitoringProcess",
        "UpdatedOrders"
    );

    public static $drop_app_entities_order = array(
        "OrderToMonitor",
        "OrderMonitoringProcess"
    );
    
}