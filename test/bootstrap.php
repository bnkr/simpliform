<?php
require __DIR__ . "/../vendor/autoload.php";

spl_autoload_register(function($name) {
    $path = str_replace("\\", "/", $name) . ".php";
    if (strpos($name, 'SimpliformTest\\') === 0) {
        require_once $path;
        return true;
    } else {
        return false;
    }
});
