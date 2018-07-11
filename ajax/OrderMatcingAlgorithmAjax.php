<?php
/**
 * Created by PhpStorm.
 * User: Abhishek Sinha
 * Date: 11/15/2016
 * Time: 11:13 AM
 */

require_once '../includes/imp_files.php';

if (isset($_POST['task']) && trim($_POST['task'])=='run_OrderMatcingAlgorithm') {

    if (isset($OrderClass, $UserClass)) {

        $refresh_orders = $OrderClass->OrderMatchingService();

        /*If user is logged in user send him messages, if any*/
        if (checkLoginStatus()) {

            $std = new stdClass();
            $std->user = null;
            $std->order = null;
            $std->error = false;
            $std->msg = null;

            if (isset($user_id)) {

                $validate_user = $UserClass->check_user($user_id);

                if($validate_user == "" || empty($validate_user)) {
                    $std->error = true;
                    $std->msg = "No such user exist. Please login again.";
                    echo json_encode($std);
                    return false;
                }

                $std->user = $validate_user;
                $std->order = $refresh_orders;
                $std->error = false;
                $std->msg = "userLoggedIn";

                echo json_encode($std);

            } else {
                return false;
            }
        }
    }
    } else {
    return false;
}