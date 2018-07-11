<?php
/**
 * Created by PhpStorm.
 * User: Abhishek Sinha
 * Date: 11/16/2016
 * Time: 8:36 PM
 */

require_once '../includes/imp_files.php';

if (!checkLoginStatus()) {
    return false;
}
if (isset($_POST['task']) && trim($_POST['task'])=='get_my_balance') {

    $std = new stdClass();
    $std->users = null;
    $std->cash = null;
    $std->bit = null;
    $std->message = array();
    $std->error = true;

    if (isset($OrderClass, $UserClass, $user_id)) {

        $UserClass = new Users();
        $validate_user = $UserClass->check_user($user_id);

        $OrderClass = new Orders();
        $cash_balance = $OrderClass->check_customer_balance($assetType = 'traditional', $user_id)->Balance;
        $bit_balance = $OrderClass->check_customer_balance($assetType = 'btc', $user_id)->Balance;

        $std->users = $validate_user;
        $std->cash = round_it($cash_balance, 2);
        $std->bit = $bit_balance;
        $std->error = false;

        if ($validate_user == "" || empty($validate_user)) {
            $std->message[] = "No such user exist. Please login again.";
            $std->error = true;
        }
    }
    echo json_encode($std);

} else {
    return false;
}