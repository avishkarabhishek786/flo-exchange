<?php
/**
 * Created by PhpStorm.
 * User: Abhishek Sinha
 * Date: 11/16/2016
 * Time: 8:36 PM
 */

require_once '../includes/autoload.php';

if (!isset($_SESSION['user_id'])) {
    return false;
} else {
    $user_id = $_SESSION['user_id'];
    $user_name = $_SESSION['user_name'];
}

if (isset($_POST['task']) && trim($_POST['task'])=='get_my_balance') {

    $std = new stdClass();
    $std->users = null;
    $std->cash = null;
    $std->bit = null;
    $std->message = array();
    $std->error = true;

    if (class_exists('Users') && class_exists('Orders')) {

        $customer = new Users();
        $validate_user = $customer->check_user($user_id);

        $validate_balance = new Orders();
        $cash_balance = $validate_balance->check_customer_balance($assetType = 'traditional', $user_id)->Balance;
        $bit_balance = $validate_balance->check_customer_balance($assetType = 'btc', $user_id)->Balance;

        $std->users = $validate_user;
        $std->cash = $cash_balance;
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