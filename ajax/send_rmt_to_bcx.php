<?php
/**
 * Created by PhpStorm.
 * User: Abhishek Kumar Sinha
 * Date: 6/2/2018
 * Time: 3:18 PM
 */
require_once '../includes/imp_files.php';

if (!checkLoginStatus()) {
    return false;
}

if (isset($_POST['job'])) {

    if ($_POST['job']=='_nrs') {
        if (isset($_POST['rmt2send'], $_POST['rmt2send'])) {
            $amount_to_load= (float) trim($_POST['rmt2send']);

            /*Check if user has account in BCX*/
            if (!isset($_SESSION['email'])||trim($_SESSION['email'])=='') {
                $mess[] = "RMT2BCX Error: No email found. Please provide your email id in My Account link.";
                $OrderClass->storeMessagesPublic(null, $user_id, $mess);
                $std->mesg[] = $mess;
                echo json_encode($std);
                return false;
            }
            $user_email = $_SESSION['email'];
            $bcx_user = get_bcx_user_by_email($user_email);

            $usr_rmt_bal = (float)$OrderClass->check_customer_balance($assetType='btc', $_SESSION['user_id'])->Balance;

            $std = new stdClass();
            $std->mesg = array();
            $std->error = true;

            if (empty($amount_to_load) || $amount_to_load<0) {
                $mess[] = "RMT2BCX Error: Please fill valid amount.";
                $OrderClass->storeMessagesPublic(null, $user_id, $mess);
                $std->mesg[] = $mess;
                echo json_encode($std);
                return false;
            }

            if ($usr_rmt_bal<0.0000000001 || !is_float($usr_rmt_bal) || $usr_rmt_bal==null || ($amount_to_load > $usr_rmt_bal)) {
                $mess[] = "RMT2BCX Error: Insufficient RMT balance. ";
                $OrderClass->storeMessagesPublic(null, $user_id, $mess." Balance: $usr_rmt_bal.");
                $std->mesg[] = $mess;
                echo json_encode($std);
                return false;
            }

            $validate_user = $UserClass->check_user($user_id);

            if($validate_user == "" || empty($validate_user)) {
                $mess = "RMT2BCX error: No such user exist. Please login again.";
                $OrderClass->storeMessagesPublic(null, $user_id, $mess);
                $std->error = true;
                $std->mesg[] = $mess;
                echo json_encode($std);
                return false;
            }

            $email_id = trim($validate_user->Email);

            if (!is_email($email_id)) {
                $mess = "BTC2CASH error: Please provide a valid email id!";
                $OrderClass->storeMessagesPublic(null, $user_id, $mess);
                $std->mesg[] = $mess;
                $std->error = true;
                echo json_encode($std);
                return false;
            }


            // Check order in sell table
            $user_active_orders = $OrderClass->get_active_order_of_user($user_id, TOP_SELL_TABLE);
            $frozen_bal_sells = 0;
            $allowed_bid_amount = $usr_rmt_bal;
            if (is_array($user_active_orders) && !empty($user_active_orders)) {
                foreach ($user_active_orders as $uao) {
                    $frozen_bal_sells += (float) $uao->quantity;
                }
                $allowed_bid_amount = $usr_rmt_bal - $frozen_bal_sells;
                $ext_st = "The user can transfer up to RMT $allowed_bid_amount only.";
                if ($allowed_bid_amount == 0) {
                    $ext_st = "The user doesn't have any RMTs to transfer.";
                }
                $msss = "The user has requested to transfer $frozen_bal_sells RMTs to BC Exchange. $ext_st Please cancel it or reduce your transfer amount.";
            }

            if ($frozen_bal_sells + $amount_to_load > $usr_rmt_bal) {
                $OrderClass->storeMessagesPublic(null, $user_id, $msss);
                $std->error = true;
                $std->mesg[] = $msss;
                echo json_encode($std);
                return false;
            }

            /*Finally, transfer the tokens*/

            $new_rmt_bal = $usr_rmt_bal - $amount_to_load;

            // Decrease tokens of 'from'
            $update_bal_fr = $OrderClass->update_user_balance($assetType="btc", $new_rmt_bal, $user_id);

            // Record the balance transfers or errors
            if (!$update_bal_fr) {
                $msss = "RMT2BCX Warning: Failed to update user balance. User id: ".$user_id;
                $std->error = true;
                $std->mesg[] = $msss;
                $OrderClass->storeMessagesPublic(null, ADMIN_ID, $msss);
                $OrderClass->storeMessagesPublic(null, $user_id, $msss);
                echo json_encode($std);
                return false;
            } else {
                /*Transfer RMT to bcx*/
                $transfer_successful = false;
                try {
                    $url = "https://bcx.ranchimall.net/bcx/api/up_val/rmt/$user_id";

                    $data = array('new_bal' => $new_rmt_bal, 'pass'=>'OmNamahShivay-HarHarMahadev-RanchiM');

                    // use key 'http' even if you send the request to https://...
                    $options = array(
                        'http' => array(
                            'header'  => "Content-type: application/x-www-form-urlencoded",
                            'method'  => 'PUT',
                            'content' => http_build_query($data)
                        )
                    );
                    if (($stream = fopen('php://input', "r")) !== FALSE) {
                        $context  = stream_context_create($options);
                        $result = file_get_contents($url, false, $context);
                        if ($result === FALSE) {
                            /* Handle error */
                            $msss = "RMT2BCX FATAL ERROR: Failed to transfer RMT to BC Exchange. User id: ".$user_id. ". Report admin as soon as possible.";
                            $std->error = true;
                            $std->mesg[] = $msss;
                            $OrderClass->storeMessagesPublic(null, ADMIN_ID, $msss);
                            $OrderClass->storeMessagesPublic(null, $user_id, $msss);
                            echo json_encode($std);
                            return false;
                        }

                        $data = json_decode($result);
                        $transfer_successful = $data->process->text;
                    }

                } catch (Exception $e) {
                    //
                }
                if ($transfer_successful==trim("success")) {
                    $OrderClass->record_root_bal_update($user_id, $usr_rmt_bal, $new_rmt_bal, $assetType='btc');

                    $msss = "$amount_to_load RMTs transfer from RMT Exchange to BC Exchange was processed successfully. Your new balance is RMT ".$new_rmt_bal;
                    $std->error = false;
                    $std->mesg[] = $msss;
                    $OrderClass->storeMessagesPublic(null, $user_id, $msss);
                    echo json_encode($std);
                    return true;
                }
                $msss = "RMT2BCX Warning: Failed to transfer RMT to BC Exchange. User id: ".$user_id;
                $std->error = true;
                $std->mesg[] = $msss;
                $OrderClass->storeMessagesPublic(null, ADMIN_ID, $msss);
                $OrderClass->storeMessagesPublic(null, $user_id, $msss);
                echo json_encode($std);
                return false;
            }
        }
    }
}

return false;