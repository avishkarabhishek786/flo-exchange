<?php
/**
 * Created by PhpStorm.
 * User: Abhishek Kumar Sinha
 * Date: 10/3/2017
 * Time: 6:33 PM
 */

function two_decimal_digit($num=0, $deci=2) {
    $decimal = abs(number_format((float)$num, $deci, '.', ''));
    return $decimal;
}

function redirect_to($url=null) {
    header('Location: '.$url);
    exit;
}

function checkLoginStatus() {
    if(!isset($_SESSION['fb_id']) || !isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
        return false;
    }
    return true;
}

function extract_int($string) {
    $int = intval(preg_replace('/[^0-9]+/', '', $string), 10);
    return $int;
}