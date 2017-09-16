<?php
/**
 * Created by PhpStorm.
 * User: Abhishek Sinha
 * Date: 11/9/2016
 * Time: 1:55 PM
 */

require_once '../includes/autoload.php';

if (!isset($_SESSION['user_id'])) {
    return false;
} else {
    $user_id = $_SESSION['user_id'];
    $user_name = $_SESSION['user_name'];
}

if (isset($_POST['subject']) && trim($_POST['subject'])=='placeOrder') {

    if (isset($_POST['btn_id'], $_POST['qty'], $_POST['price'])) {

        $btn_id = $_POST['btn_id'];
        $qty = (float) $_POST['qty'];
        $item_price = (float) $_POST['price'];
        $orderStatusId = 2; // 0 -> cancelled; 1 -> complete; 2 -> pending

        if($btn_id == 'buy_btn') {
            $orderTypeId = 0; // It is a buy
            $OfferAssetTypeId= 'INR';
            $WantAssetTypeId = 'FLO';
        } else if($btn_id == 'sell_btn') {
            $orderTypeId = 1; // It is a sell
            $OfferAssetTypeId = 'FLO';
            $WantAssetTypeId = 'INR';
        }

        $std = new stdClass();
        $std->user = null;
        $std->order = null;
        $std->error = false;
        $std->msg = null;

        if ($qty == 0 || $item_price == 0) {
            $std->error = true;
            $std->msg = 'Please insert valid quantity and price.';
            echo json_encode($std);
            return false;
        }

        $validate_user = "";
        $place_order = "";

        if (class_exists('Users') && class_exists('Orders')) {

            $customer = new Users();
            $validate_user = $customer->check_user($user_id);

            if($validate_user == "" || empty($validate_user)) {
                $std->error = true;
                $std->msg = "No such user exist. Please login again.";
                echo json_encode($std);
                return false;
            }

            $initial_orders = new Orders();
            $place_order = $initial_orders->insert_pending_order($orderTypeId, $qty, $item_price, $orderStatusId, $OfferAssetTypeId, $WantAssetTypeId);

        } else {
            $std->error = true;
            $std->msg = "Class Users or Orders does not exist.";
        }

        $std->user = $validate_user;
        $std->order = $place_order;

        echo json_encode($std);

    }

}