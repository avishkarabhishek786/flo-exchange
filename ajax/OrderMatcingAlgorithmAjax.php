<?php
/**
 * Created by PhpStorm.
 * User: Abhishek Sinha
 * Date: 11/15/2016
 * Time: 11:13 AM
 */

require_once '../includes/autoload.php';

if (isset($_POST['task']) && trim($_POST['task'])=='run_OrderMatcingAlgorithm') {

    if (class_exists('Orders')) {

        $order_matching = new Orders();
        $refresh_orders = $order_matching->OrderMatchingService();
    }

    } else {
    return false;
}