<?php

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
