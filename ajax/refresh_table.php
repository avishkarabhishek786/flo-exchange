<?php
/**
 * Created by PhpStorm.
 * User: Abhishek Sinha
 * Date: 11/15/2016
 * Time: 6:22 PM
 */

require_once '../includes/autoload.php';

if (isset($_POST['task']) && trim($_POST['task'])=='refresh') {

    $std = new stdClass();
    $std->buys = null;
    $std->sells = null;
    $std->message = array();
    $std->error = true;

    if (class_exists('Orders')) {

        $refresh_orders = new Orders();
        $buy_list = $refresh_orders->get_top_buy_sell_list($top_table='active_buy_list', $asc_desc='DESC');  // buy
        $sell_list = $refresh_orders->get_top_buy_sell_list($top_table='active_selling_list', $asc_desc='ASC');  // sell

        $std->buys = $buy_list;
        $std->sells = $sell_list;
        $std->error = false;
    }
    echo json_encode($std);

} else {
    return false;
}