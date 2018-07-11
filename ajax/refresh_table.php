<?php
/**
 * Created by PhpStorm.
 * User: Abhishek Sinha
 * Date: 11/15/2016
 * Time: 6:22 PM
 */

require_once '../includes/imp_files.php';

if (isset($_POST['task']) && trim($_POST['task'])=='refresh') {

    $std = new stdClass();
    $std->buys = null;
    $std->sells = null;
    $std->message = array();
    $std->error = true;

    if (isset($OrderClass, $UserClass)) {

        $buy_list = $OrderClass->get_top_buy_sell_list(TOP_BUYS_TABLE, $asc_desc='DESC');  // buy
        $sell_list = $OrderClass->get_top_buy_sell_list(TOP_SELL_TABLE, $asc_desc='ASC');  // sell

        $std->buys = $buy_list;
        $std->sells = $sell_list;
        $std->error = false;
    }
    echo json_encode($std);

} else {
    return false;
}