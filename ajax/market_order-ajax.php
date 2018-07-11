<?php
/**
 * Created by PhpStorm.
 * User: Abhishek Sinha
 * Date: 11/26/2016
 * Time: 7:10 PM
 */

require_once '../includes/imp_files.php';

if (!checkLoginStatus()) {
    return false;
}

if(isset($_POST['job']) && $_POST['job'] == 'market_order') {

    $std = new stdClass();
    $std->user = null;
    $std->order = null;
    $std->error = false;
    $std->msg = null;

    if (isset($OrderClass, $UserClass, $user_id)) {

        $validate_user = $UserClass->check_user($user_id);

        if($validate_user == "" || empty($validate_user)) {
            $std->error = true;
            $std->msg = "No such user exist. Please login again.";
            echo json_encode($std);
            return false;
        }

    } else {
        return false;
    }

    $std->user = $validate_user;

    if(isset($_POST['qty'], $_POST['type'])) {
        $qty = (float) $_POST['qty'];
        $order_type = $_POST['type'];
        
        $is_valid_qty = validate_decimal_place($qty, 10);

        if(($qty >= 0.0000000001) && $is_valid_qty) {
            if(is_string($order_type)) {
                if(trim($order_type) == 'market_buy_btn' || trim($order_type) == 'market_sell_btn') {

                    if ($order_type == 'market_buy_btn') {
                        $order_type = 'buy';
                    } elseif($order_type == 'market_sell_btn') {
                        $order_type = 'sell';
                    } else {
                        $std->error = true;
                        $std->msg = 'Invalid Order type';
                        echo json_encode($std);
                        return false;
                    }

                    $run_market_order = $OrderClass->market_order($order_type, $qty);

                    $std->user = $validate_user;
                    $std->order = $run_market_order;
                    $std->error = false;
                    $std->msg = 'Success';
                }
            }
        } else {
            $std->error = true;
            $std->msg = 'Please insert a valid quantity. Maximaum 10 decimal places allowed and minimum trade quantity is 0.0000000001 RMTs.';
        }
    }
    echo json_encode($std);
} else {
    return false;
}