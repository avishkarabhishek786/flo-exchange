<?php
/**
 * Created by PhpStorm.
 * User: Abhishek Kumar Sinha
 * Date: 12/1/2017
 * Time: 3:31 PM
 */
require_once '../includes/imp_files.php';

if (!checkLoginStatus()) {
    return false;
}

$last_trade_date = $_SESSION['last_trade_date'];

$lod = $OrderClass->get_last_order_date($last_trade_date);

if ($lod) {
    $_SESSION['last_trade_date'] = $UserClass->time_now();
}
echo $lod;