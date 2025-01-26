<?php

namespace Dolzay\Apps\Settings\Entities ;


use Dolzay\ModuleConfig ;

class Settings {

    const TABLE_NAME = ModuleConfig::MODULE_PREFIX."settings" ;
    public static $db ;
    public static $employee_id ;
    // START DEFINING get_create_table_sql
    public static function get_create_table_sql() {

        return 'CREATE TABLE IF NOT EXISTS `' . self::TABLE_NAME . '` (
                `id` INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `license_type` VARCHAR(255)  NOT NULL,
                `expiration_date` DATETIME NOT NULL,
                `post_submit_state_id` INT(10) UNSIGNED NOT NULL
            );';

    }
    // END DEFINING get_create_table_sql

    public const DROP_TABLE_SQL = 'DROP TABLE IF EXISTS `' . self::TABLE_NAME . '`;';

    public static function did_the_plugin_expire($db){
        $query = "SELECT `expiration_date` FROM `".self::TABLE_NAME."` LIMIT 1;" ;
        $stmt = $db->query($query);
        $settings = $stmt->fetch();
        $expiration_date = $settings['expiration_date'];

        $expiration_date = new \DateTime($expiration_date);
        $current_date = new \DateTime();

        if ($current_date > $expiration_date){
            return true;
        }
        return false ;
    }
}