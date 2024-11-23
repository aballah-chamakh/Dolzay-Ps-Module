<?php
namespace Dolzay\Apps\Zones  ;


class Config {

    public static $create_app_entities_order = array(
        "Zone",
        "City",
        "Delegation"
    );

    public static $drop_app_entities_order = array(
        "Delegation",
        "City",
        "Zone"
    );
}