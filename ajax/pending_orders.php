<?php
/**
 * Created by PhpStorm.
 * User: Abhishek Sinha
 * Date: 11/9/2016
 * Time: 1:55 PM
 */

require_once '../includes/imp_files.php';

if (!checkLoginStatus()) {
    return false;
}

if (isset($_POST['subject']) && trim($_POST['subject'])=='placeOrder') {

    if (isset($_POST['btn_id'], $_POST['qty'], $_POST['price'])) {

        $btn_id = trim($_POST['btn_id']);
        $qty = two_decimal_digit($_POST['qty']);
        $item_price = two_decimal_digit($_POST['price']);
        $orderStatusId = 2; // 0 -> cancelled; 1 -> complete; 2 -> pending

        $std = new stdClass();
        $std->user = null;
        $std->order = null;
        $std->error = false;
        $std->msg = null;

        if($btn_id == 'buy_btn') {
            $orderTypeId = 0; // It is a buy
            $OfferAssetTypeId= 'USD';
            $WantAssetTypeId = 'RTM';
        } else if($btn_id == 'sell_btn') {
            $orderTypeId = 1; // It is a sell
            $OfferAssetTypeId = 'RTM';
            $WantAssetTypeId = 'USD';
        } else {
            $std->error = true;
            $std->msg = "Invalid button id.";
            echo json_encode($std);
            return false;
        }

        if ($qty < 0.01 || $item_price < 0.01) {
            $std->error = true;
            $std->msg = 'Please insert valid quantity and price. Minimum trade price is 1 cent.';
            echo json_encode($std);
            return false;
        }

        $validate_user = "";
        $place_order = "";

        if (isset($OrderClass, $UserClass, $user_id)) {

            $validate_user = $UserClass->check_user($user_id);

            if($validate_user == "" || empty($validate_user)) {
                $std->error = true;
                $std->msg = "No such user exist. Please login again.";
                echo json_encode($std);
                return false;
            }

            $place_order = $OrderClass->insert_pending_order($orderTypeId, abs($qty), abs($item_price), $orderStatusId, $OfferAssetTypeId, $WantAssetTypeId);

        } else {
            $std->error = true;
            $std->msg = "Class Users or Orders does not exist.";
        }

        $std->user = $validate_user;
        $std->order = $place_order;

        echo json_encode($std);

    }

}