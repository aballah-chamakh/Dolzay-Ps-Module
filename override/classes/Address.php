<?php 


class Address extends AddressCore {


    /**
     * @see ObjectModel::$definition
     */
    public static $definition = ['table' => 'address', 'primary' => 'id_address', 'fields' => ['id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => false], 'id_manufacturer' => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => false], 'id_supplier' => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => false], 'id_warehouse' => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => false], 'id_country' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_state' => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId'], 'alias' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32], 'company' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 255], 'lastname' => ['type' => self::TYPE_STRING, 'validate' => 'isName', 'required' => true, 'size' => 255], 'firstname' => ['type' => self::TYPE_STRING, 'validate' => 'isName', 'required' => true, 'size' => 255], 'vat_number' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'], 'address1' => ['type' => self::TYPE_STRING, 'validate' => 'isAddress', 'required' => true, 'size' => 128], 'address2' => ['type' => self::TYPE_STRING, 'validate' => 'isAddress', 'size' => 128], 'postcode' => ['type' => self::TYPE_STRING, 'validate' => 'isPostCode', 'size' => 12], 'city' => ['type' => self::TYPE_STRING, 'validate' => 'isCityName', 'required' => true, 'size' => 255], 
    'delegation' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => false, 'size' => 255], 'other' => ['type' => self::TYPE_STRING, 'validate' => 'isMessage', 'size' => 300], 'phone' => ['type' => self::TYPE_STRING, 'validate' => 'isPhoneNumber', 'size' => 32], 'phone_mobile' => ['type' => self::TYPE_STRING, 'validate' => 'isPhoneNumber', 'size' => 32], 'dni' => ['type' => self::TYPE_STRING, 'validate' => 'isDniLite', 'size' => 16], 'deleted' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false], 'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false], 'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false]]];

}