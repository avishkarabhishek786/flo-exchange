<?php
/**
 * Created by PhpStorm.
 * User: Abhishek Kumar Sinha
 * Date: 9/27/2017
 * Time: 2:41 PM
 */

require_once '../includes/imp_files.php';

if (isset($_POST['task']) && trim($_POST['task'])=='loadTradersList') {

    $std = new stdClass();
    $std->traders_list = array();
    $std->error = true;

    if (isset($OrderClass)) {

        $tradersList = $OrderClass->UserBalanceList();
        if (is_array($tradersList) && !empty($tradersList)) {
            $std->traders_list = $tradersList;
            $std->error = false;
        }
    }
    echo json_encode($std);

} else {
    return false;
}