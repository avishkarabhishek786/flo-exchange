<?php
/**
 * Created by PhpStorm.
 * User: Abhishek Sinha
 * Date: 6/24/2017
 * Time: 8:38 PM
 */

require_once '../includes/autoload.php';

if (isset($_POST['task']) && trim($_POST['task'])=='loadTradeList') {

    $std = new stdClass();
    $std->trade_list = array();
    $std->error = true;

    if (class_exists('Orders')) {

        $OrderClass = new Orders();
        $tradeList = $OrderClass->last_transaction_list();

        $std->trade_list = $tradeList;
        $std->error = false;

    }
    echo json_encode($std);

} else {
    return false;
}