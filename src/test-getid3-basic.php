<?php
// Check if getID3 is installed
$path = "vendor/james-heinrich/getid3/getid3/getid3.php";
if (file_exists($path)) {
    echo "getID3 found at: $path\n";
    require_once $path;
    
    try {
        $getID3 = new getID3();
        echo "getID3 initialized successfully\n";
    } catch (Exception $e) {
        echo "Error initializing getID3: " . $e->getMessage() . "\n";
    }
} else {
    echo "getID3 not found. Please install it with composer.\n";
}

