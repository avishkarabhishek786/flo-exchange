<?php

require_once '../config/config.php';
require_once 'defines.php';
function __autoload($class_name) {
    $class = explode("_", $class_name);
    $path = implode("/", $class).".php";
    require_once($path);
}
