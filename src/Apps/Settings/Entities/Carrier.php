<?php

namespace Dolzay\Apps\Settings\Entities ;


use Dolzay\ModuleConfig ;

class Carrier {

    const TABLE_NAME = ModuleConfig::MODULE_PREFIX."carrier" ;
    public static $db ;

    public static function init($db){
        self::$db = $db;
    }

    public static function get_create_table_sql() {
        return 'CREATE TABLE IF NOT EXISTS `' . self::TABLE_NAME . '` (
                `name` VARCHAR(255) NOT NULL PRIMARY KEY,
                `logo` VARCHAR(255) NOT NULL,
                `website_credentials_id` INT(10) UNSIGNED UNIQUE NULL ,
                `api_credentials_id` INT(10) UNSIGNED UNIQUE NULL,
                FOREIGN KEY (`website_credentials_id`) REFERENCES `'.WebsiteCredentials::TABLE_NAME.'` (`id`) ON DELETE CASCADE,
                FOREIGN KEY (`api_credentials_id`) REFERENCES `'.ApiCredentials::TABLE_NAME.'` (`id`) ON DELETE CASCADE
            ) ;';
    }


    public const DROP_TABLE_SQL = 'DROP TABLE IF EXISTS `' . self::TABLE_NAME . '`;';

    public static function get_all(){
        $carriers = self::$db->query("SELECT name FROM ".self::TABLE_NAME)->fetchAll();
        return $carriers ;
    }


}