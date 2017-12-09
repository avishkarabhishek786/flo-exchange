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
        $qty = (float) $_POST['qty'];
        $item_price = (float) $_POST['price'];
        $orderStatusId = 2; // 0 -> cancelled; 1 -> complete; 2 -> pending

        $std = new stdClass();
        $std->user = null;
        $std->order = null;
        $std->error = false;
        $std->msg = null;

        if($btn_id == 'buy_btn') {
            $orderTypeId = 0; // It is a buy
            $OfferAssetTypeId= 'USD';
            $WantAssetTypeId = 'RMT';
            $assetType = 'traditional';
            $total_trade_val = $qty * $item_price;
        } else if($btn_id == 'sell_btn') {
            $orderTypeId = 1; // It is a sell
            $OfferAssetTypeId = 'RMT';
            $WantAssetTypeId = 'USD';
            $assetType = 'btc';
            $total_trade_val = $qty;
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

            $user_current_bal = (float) $OrderClass->check_customer_balance($assetType, $user_id)->Balance;

            $top_tbl = null;
            if ($orderTypeId == 0) {
                $top_tbl = TOP_BUYS_TABLE;
                $user_active_orders = $OrderClass->get_active_order_of_user($user_id, $top_tbl);

                $frozen_bal = 0;
                if (is_array($user_active_orders) && !empty($user_active_orders)) {
                    foreach ($user_active_orders as $uao) {
                        $frozen_bal += (float) $uao->price * $uao->quantity;
                    }
                }
                $allowed_bid_amount = $user_current_bal - $frozen_bal;
                $ext_st = "You can put bid up to $ $allowed_bid_amount only.";
                $ext_st2 = "";
                if ($allowed_bid_amount == 0) {
                    $ext_st = "You don't have any cash balance to spend.";
                }
                if ((float)$frozen_bal != 0) {
                    $ext_st2 = "You have already placed an order worth $ $frozen_bal.";
                }
                $msss = "Insufficient Balance: $ext_st2 $ext_st";

            } elseif ($orderTypeId == 1) {
                $top_tbl = TOP_SELL_TABLE;
                $user_active_orders = $OrderClass->get_active_order_of_user($user_id, $top_tbl);
                $frozen_bal = 0;
                if (is_array($user_active_orders) && !empty($user_active_orders)) {
                    foreach ($user_active_orders as $uao) {
                        $frozen_bal += (float) $uao->quantity;
                    }
                }
                $allowed_bid_amount = $user_current_bal - $frozen_bal;
                $ext_st = "You can sell maximum $allowed_bid_amount tokens.";
                if ($allowed_bid_amount == 0) {
                    $ext_st = "You don't have any tokens to sell.";
                }
                $msss = "Insufficient Balance: You have already placed an order of $frozen_bal tokens. $ext_st";
            }

            if ($frozen_bal + $total_trade_val > $user_current_bal) {
                $std->error = true;
                $std->msg = $msss;
                echo json_encode($std);
                return false;
            }

            $place_order = $OrderClass->insert_pending_order($orderTypeId, $qty, $item_price, $orderStatusId, $OfferAssetTypeId, $WantAssetTypeId);

        } else {
            $std->error = true;
            $std->msg = "Class Users or Orders does not exist.";
        }

        $std->user = $validate_user;
        $std->order = $place_order;

        echo json_encode($std);

    }

}