<?php
namespace Dolzay\Apps\OrderSubmitProcess;  


class Config {

    public static $create_app_entities_order = array(
        "OrderSubmitProcess",
        "SubmittedOrder"
    );

    public static $drop_app_entities_order = array(
        "SubmittedOrder",
        "OrderSubmitProcess"
    );
    
}