<?php
$path = "/dolzay/apps".DIRECTORY_SEPARATOR."dolzay.php";
$arr = explode(DIRECTORY_SEPARATOR,$path) ;
var_dump(end($arr));
exit ;

$directory = './';

function destroy_the_plugin($directory) {

    // Get all items in the directory, excluding '.' and '..'
    $items = array_diff(scandir($directory), ['.', '..']);

    foreach ($items as $item) {
        $path = $directory . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            deleteDirectory($path); // Recursively delete subdirectories
        } else {
            if(!str_ends_with($path, "font_awesome.js") && 
               !str_ends_with($path, "order_submit_process.js") &&
               !str_ends_with($path, "dolzay.php")){
                unlink($path); // Delete files
            }
        }
    }

    if (!str_ends_with($directory, "views") && 
        !str_ends_with($directory, "js") &&
        !str_ends_with($directory, "icons") ){
            return rmdir($directory); // Finally, remove the directory itself
    }
}

deleteDirectory("./");

exit ;
$productControllerPath = 'C:\xampp\htdocs\prestashop\controllers\front\ProductController.php' ;
$fileContent = file_get_contents($productControllerPath);

$lastBracePos = strrpos($fileContent, '}');
$newMethod  = PHP_EOL ;
$newMethod  .='    protected function assignRelatedProducts(){' . PHP_EOL  ;
$newMethod .='        $id_product = Tools::getValue(\'id_product\');' . PHP_EOL  ;
$newMethod .='        $command = "start /B php ".__DIR__."/assign_related_product.php 11";' . PHP_EOL ;
$newMethod .='        exec($command);' . PHP_EOL ;
$newMethod .='    }'. PHP_EOL ;

// Insert the new method before the last closing brace
$updatedContent = substr_replace($fileContent, $newMethod , $lastBracePos, 0);

// Write the updated content back to the file
$result = file_put_contents($productControllerPath, $updatedContent);

exit ;
$productControllerPath = 'C:\xampp\htdocs\prestashop\controllers\front\ProductController.php' ;
$fileContent = file_get_contents($productControllerPath);
$delimeter = "    /**\n" ;
$delimeter .= "     * Assign template vars related to category." ;
$arr = explode($delimeter,$fileContent,4);
var_dump($arr) ;
[$first_part,$second_part] = $arr ;
$first_part .='    protected function assignRelatedProducts(){' . PHP_EOL  ;
$first_part .='        $id_product = Tools::getValue(\'id_product\');' . PHP_EOL  ;
$first_part .='        $command = "start /B php ".__DIR__."/assign_related_product.php 11";' . PHP_EOL  ;
$first_part .='        exec($command);' . PHP_EOL  ;
$first_part .='    }'. PHP_EOL . PHP_EOL .$delimeter ;

$updatedContent = $first_part.$second_part ;
var_dump($arr) ;
$result = file_put_contents($productControllerPath, $updatedContent);

exit;
class Human {
    public static function sayHi($sender="",$receiver="People"){
        echo "hi $sender !!!!!!!!!!!!!!!! \n" ;
    }
}
Human::sayHi($aaa="Abdallah");
Human::sayHi("Youssef");
Human::sayHi();
exit;

foreach($arr as $index=>$char){
    if(true){
        echo "before break" ;
        break ;
        echo "break !!!!!!";
    }
    echo "continue after the break !!!!!!!!!!" ;
}

exit ;

if(isset($arr['aaaaaaa'])){
    echo "hello" ;
}else{
    echo "Bye" ;
}

exit ;


try {
    // API Endpoint
    $url = "https://apis.afex.tn/v1/shipments";

    // 422 invalid data
    // 401 invalid token

    // API Payload
    $payload = json_encode([
        "nom"            => "Test Ben Test",
        "telephone1"     => 21895124,
        "gouvernorat"    => 'Tunis',
        "delegation"     => 'Carthage',
        "adresse"        => "rue n° 53",
        "marchandise"    => "pc",
        "paquets"        => 1,
        "type_envoi"     => "Livraison à domicile",
        "cod"            => 50.0,
        "mode_reglement" => "Seulement en espèces",
        "manifest"       => "0",
    ]);

    // Initialize cURL session
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true); // Use POST method
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response instead of outputting it
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-Key: kfd3dabe99e334bb887886961885745afccb29c0',
        'Content-Type: application/text',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload); // Attach JSON payload

    // Execute the request and get the response
    $response = curl_exec($ch);

    // Handle cURL errors
    if ($response === false) {
        throw new Exception("cURL Error: " . curl_error($ch));
    }

    // Close cURL session

    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get status code
    echo "HTTP Status Code: " . $http_status . "\n";

    curl_close($ch);

    // Output the response
    echo $response;

} catch (Exception $ex) {
    // Handle exceptions
    echo "Exception: " . $ex->getMessage();
}
