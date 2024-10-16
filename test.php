<?php 

class Human {

    public static $hobbies = [
        "Football",
        "Video games"
    ] ;


}

$class = "Human" ;
var_dump($class::$hobbies) ;




//        file_put_contents(_PS_MODULE_DIR_ . 'dolzay/composer_autoload_log.txt', "$class\n", FILE_APPEND);




