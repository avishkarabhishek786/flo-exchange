<?php
/**
 * Created by PhpStorm.
 * User: Abhishek Kumar Sinha
 * Date: 2/9/2018
 * Time: 11:00 AM (in Bali :) )
 */

require_once '../includes/imp_files.php';

if (!checkLoginStatus()) {
    return false;
}

if (isset($_POST['job']) && trim($_POST['job']) == "transfer_tokens") {
    if (isset($_POST['_from'], $_POST['_to'], $_POST['_tokens'])) {
        $from = (int) $_POST['_from'];
        $to = (int) $_POST['_to'];
        $tokens = number_format($_POST['_tokens'], 10);

        $std = new stdClass();
        $std->mesg = array();
        $std->error = true;
        
        if ($from==$to) {
            $mess = "Sender and receiver cannot be same.";
            $std->error = true;
            $std->mesg[] = $mess;
            echo json_encode($std);
            return false;
        }

        $validate_user_from = $UserClass->check_user($from);
        $validate_user_to = $UserClass->check_user($to);

        if($validate_user_from == "" || empty($validate_user_from) || $validate_user_to == "" || empty($validate_user_to)) {
            $mess = "No such user exist. Please re-check user ids.";
            $std->error = true;
            $std->mesg[] = $mess;
            //$OrderClass->storeMessagesPublic(null, $user_id, $mess);
            echo json_encode($std);
            return false;
        }

        $customer_bal_fr = (float) $OrderClass->check_customer_balance($assetType="btc", $from)->Balance;
        $customer_bal_to = (float) $OrderClass->check_customer_balance($assetType="btc", $to)->Balance;

        if ($tokens > $customer_bal_fr) {
            $mess = "Admin Token Transfer: The user has insufficient balance to make this RMT token transfer. His current Token balance is $customer_bal_fr RMTs.";
            $std->error = true;
            $std->mesg[] = $mess;
            echo json_encode($std);
            $OrderClass->storeMessagesPublic(null, $from, $mess);
            return false;
        }

        if ($tokens < 0.0000000001) {
            $mess = "Admin Token Transfer: Please provide minimum amount of 0.0000000001 RMTs!";
            $OrderClass->storeMessagesPublic(null, $from, $mess);
            $std->mesg[] = $mess;
            echo json_encode($std);
            return false;
        }

        // Check order in sell table
        $user_active_orders = $OrderClass->get_active_order_of_user($from, TOP_SELL_TABLE);
        $frozen_bal_sells = 0;
        $allowed_bid_amount = $customer_bal_fr;
        if (is_array($user_active_orders) && !empty($user_active_orders)) {
            foreach ($user_active_orders as $uao) {
                $frozen_bal_sells += (float) $uao->quantity;
            }
            $allowed_bid_amount = $customer_bal_fr - $frozen_bal_sells;
            $ext_st = "The user can transfer up to RMT $allowed_bid_amount only.";
            if ($allowed_bid_amount == 0) {
                $ext_st = "The user doesn't have any RMTs to transfer.";
            }
            $msss = "The user has requested to transfer $frozen_bal_sells RMTs. $ext_st Please cancel it or reduce your transfer amount.";
        }

        if ($frozen_bal_sells + $tokens > $customer_bal_fr) {
            $OrderClass->storeMessagesPublic(null, $from, $msss);
            $std->error = true;
            $std->mesg[] = $msss;
            echo json_encode($std);
            return false;
        }

        /*Finally, transfer the tokens*/

        $new_from_bal = $customer_bal_fr - $tokens;
        $new_to_bal = $customer_bal_to + $tokens;
        
        // Decrease tokens of 'from'
        $update_bal_fr = $OrderClass->update_user_balance($assetType="btc", $new_from_bal, $from);
        
        // Increase tokens of 'to'
        $update_bal_to = $OrderClass->update_user_balance($assetType="btc", $new_to_bal, $to);
        
        // Record the balance transfers or errors
        if (!$update_bal_fr) {
            $msss = "Failed to update Sender's balance.";
            $std->error = true;
            $std->mesg[] = $msss;
            $OrderClass->storeMessagesPublic(null, ADMIN_ID, $msss);
            echo json_encode($std);
            return false;
        } else if(!$update_bal_to) {
            $msss = "Failed to update Receiver's balance.";
            $std->error = true;
            $std->mesg[] = $msss;
            $OrderClass->storeMessagesPublic(null, ADMIN_ID, $msss);
            echo json_encode($std);
            return false;
        } else {
            $OrderClass->record_root_bal_update($from, $customer_bal_fr, $new_from_bal, $assetType='btc');
            $OrderClass->record_root_bal_update($to, $customer_bal_to, $new_to_bal, $assetType='btc');

            $msss = "RMT transfer for user id ".$from." and ".$to." was processed successfully.";
            $mess1 = "Your ".$tokens." RMTs were transferred by Admin to user ".$to.".";
            $mess2 = "You received ".$tokens." RMTs from user ".$from." transferred by Admin.";
            $std->error = false;
            $std->mesg[] = $msss;
            $OrderClass->storeMessagesPublic(null, ADMIN_ID, $msss);
            $OrderClass->storeMessagesPublic(null, $from, $mess1);
            $OrderClass->storeMessagesPublic(null, $to, $mess2);
            echo json_encode($std);
            return true;
        }
    }
}