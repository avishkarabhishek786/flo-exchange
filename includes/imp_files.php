<?php
/**
 * Created by PhpStorm.
 * User: Abhishek Kumar Sinha
 * Date: 10/3/2017
 * Time: 7:49 PM
 */


if(!isset($_SESSION)) {
    session_start();
}

include_once 'autoload.php';
include_once 'functions.php';

//if logged in store user DB details
$fb_id = null;
$user_name = null;
$user_id = null;
$log_fullName = null;

if (checkLoginStatus()) {
    $fb_id = $_SESSION['fb_id'];
    $user_name = $_SESSION['user_name'];
    $user_id = $_SESSION['user_id'];
    $log_fullName = $_SESSION['full_name'];
}

$UserClass = null;
$OrderClass = null;

if (class_exists('Users') && class_exists('Orders')) {
    $UserClass = new Users();
    $OrderClass = new Orders();
}