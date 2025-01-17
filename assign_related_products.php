<?php

$product_id = (int)$argv[1] ;

$filename = __DIR__."/example2.txt";

// Data to write into the file
$content = "Hello, this is a sample text file created by PHP $product_id.\n";

// Create and open the file for writing
$file = fopen($filename, 'w'); // 'w' mode opens the file for writing and clears its content if it exists

if ($file) {
    // Write content to the file
    fwrite($file, $content);

    // Close the file after writing
    fclose($file);

} else {
    echo "Failed to create the file.";
}

