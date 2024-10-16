<?php
namespace Dolzay\Apps\Notifications  ;


class Config {

    public static $create_app_entities_order = array(
        "Notification",
        "NotificationPoppedUpBy",
        "NotificationViewedBy"
    );

    public static $drop_app_entities_order = array(
        "NotificationPoppedUpBy",
        "NotificationViewedBy",
        "Notification"
    );
}

