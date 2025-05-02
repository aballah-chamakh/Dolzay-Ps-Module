<?php

namespace Dolzay\Apps\OrderSubmitProcess\Entities;

use Dolzay\ModuleConfig;
use Dolzay\CustomClasses\Db\DzDb;

class OrderToSubmit {

    public const TABLE_NAME = ModuleConfig::MODULE_PREFIX."order_to_submit";
    
    private static $db;

    public static function init(DzDb $db) {
        self::$db = $db;
    }
    
    public static function get_create_table_sql() {
        return 'CREATE TABLE IF NOT EXISTS `'.self::TABLE_NAME.'` (
            `order_id` INT(10) UNSIGNED NOT NULL,
            `process_id` INT(10) UNSIGNED NOT NULL,
            `error_type` VARCHAR(60) NULL,
            `error_detail` JSON NULL,
            PRIMARY KEY(`order_id`, `process_id`),
            FOREIGN KEY (`order_id`) REFERENCES `' . _DB_PREFIX_.\OrderCore::$definition['table'] . '` (`id_order`),
            FOREIGN KEY (`process_id`) REFERENCES `'.OrderSubmitProcess::TABLE_NAME.'` (`id`)
        );';
    }
    
    public const DROP_TABLE_SQL = 'DROP TABLE IF EXISTS `'.self::TABLE_NAME.'`;';

    public static function insert(int $order_id, int $process_id, string $carrier_name, bool $submitted = false): bool {
        $sql = "INSERT INTO " . self::TABLE_NAME . " 
            (order_id, process_id, carrier_name, submitted) 
            VALUES (:order_id, :process_id, :carrier_name, :submitted)";
            
        $stmt = self::$db->prepare($sql);
        return $stmt->execute([
            ':order_id' => $order_id,
            ':process_id' => $process_id,
            ':carrier_name' => $carrier_name,
            ':submitted' => $submitted
        ]);
    }

    public static function updateSubmissionStatus(int $order_id, int $process_id, bool $submitted): bool {
        $sql = "UPDATE " . self::TABLE_NAME . " 
            SET submitted = :submitted 
            WHERE order_id = :order_id AND process_id = :process_id";
            
        $stmt = self::$db->prepare($sql);
        return $stmt->execute([
            ':order_id' => $order_id,
            ':process_id' => $process_id,
            ':submitted' => $submitted
        ]);
    }

    public static function getByProcessId(int $process_id): array {
        $sql = "SELECT * FROM " . self::TABLE_NAME . " WHERE process_id = :process_id";
            
        $stmt = self::$db->prepare($sql);
        $stmt->execute([':process_id' => $process_id]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getUnsubmitted(): array {
        $sql = "SELECT * FROM " . self::TABLE_NAME . " WHERE submitted = FALSE";
            
        $stmt = self::$db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}