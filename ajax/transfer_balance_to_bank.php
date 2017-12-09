<?php
/**
 * Created by PhpStorm.
 * User: Abhishek Kumar Sinha
 * Date: 10/21/2017
 * Time: 8:19 PM
 */

require_once '../includes/imp_files.php';

if (!checkLoginStatus()) {
    return false;
}

if (isset($_POST['job']) && trim($_POST['job']) == "transfer_to_bank") {

    if (isset($_POST['acc'], $_POST['bal'])) {
        $account_number = $_POST['acc'];
        $balance_to_transfer = (float) $_POST['bal'];
        $remarks = (string) $_POST['remarks'];

        $std = new stdClass();
        $std->mesg = array();
        $std->error = true;
        $std->user = null;

        if (empty($account_number) || empty($balance_to_transfer)) {
            $mess = "E2B error: Please fill all the required fields!";
            $OrderClass->storeMessagesPublic(null, $user_id, $mess);
            $std->mesg[] = $mess;
            $std->error = true;
            echo json_encode($std);
            return false;
        }

        if (!preg_match("/^[a-zA-Z0-9 \r\n]*$/",$remarks)) {
            $mess = "E2B error: Only alphanumeric characters allowed in Remarks!";
            $OrderClass->storeMessagesPublic(null, $user_id, $mess);
            $std->mesg[] = $mess;
            $std->error = true;
            echo json_encode($std);
            return false;
        }

        if (strlen($remarks) > 250) {
            $mess = "E2B error: Remarks up to 250 characters allowed only!";
            $OrderClass->storeMessagesPublic(null, $user_id, $mess);
            $std->mesg[] = $mess;
            $std->error = true;
            echo json_encode($std);
            return false;
        }

        $validate_user = $UserClass->check_user($user_id);

        if($validate_user == "" || empty($validate_user)) {
            $mess = "E2B error: No such user exist. Please login again.";
            $OrderClass->storeMessagesPublic(null, $user_id, $mess);
            $std->error = true;
            $std->mesg[] = $mess;
            echo json_encode($std);
            return false;
        }

        $senders_email = trim($validate_user->Email);

        if (!is_email($senders_email)) {
            $mess = "E2B error: Please provide a valid email id!";
            $OrderClass->storeMessagesPublic(null, $user_id, $mess);
            $std->mesg[] = $mess;
            $std->error = true;
            echo json_encode($std);
            return false;
        }

        $user_bank_details = $OrderClass->get_bank_details($user_id, $account_number);

        if($user_bank_details == "" || empty($user_bank_details)) {
            $mess = "E2B error: No such bank account exist. Please check bank details again.";
            $OrderClass->storeMessagesPublic(null, $user_id, $mess);
            $std->error = true;
            $std->mesg[] = $mess;
            echo json_encode($std);
            return false;
        }

        $customer_bal = (float) $OrderClass->check_customer_balance($assetType="traditional", $user_id)->Balance;

        if ($balance_to_transfer > $customer_bal) {
            $mess = "E2B transaction failed: You have insufficient balance to make this transfer. Your current Cash balance is $ $customer_bal.";
            $std->error = true;
            $std->mesg[] = $mess;
            echo json_encode($std);
            $OrderClass->storeMessagesPublic(null, $user_id, $mess);
            return false;
        }

        $msss = '';

        // Check order in buys table
        $OfferAssetTypeId= 'USD';
        $WantAssetTypeId = 'RMT';
        $assetType = 'traditional';
        $user_active_orders = $OrderClass->get_active_order_of_user($user_id, TOP_BUYS_TABLE);
        $frozen_bal_buys = 0;
        $allowed_bid_amount = $customer_bal;
        if (is_array($user_active_orders) && !empty($user_active_orders)) {
            foreach ($user_active_orders as $uao) {
                $frozen_bal_buys += (float) $uao->price * $uao->quantity;
            }
            $allowed_bid_amount = $customer_bal - $frozen_bal_buys;
            $ext_st = "You can refund up to $ $allowed_bid_amount only.";
            if ($allowed_bid_amount == 0) {
                $ext_st = "You don't have any cash balance to refund.";
            }
            $msss = "Refund error: You have placed an order worth $ $frozen_bal_buys $ext_st Please cancel it or reduce your refund amount.";
        }

        if ($frozen_bal_buys + $balance_to_transfer > $customer_bal) {
            $OrderClass->storeMessagesPublic(null, $user_id, $msss);
            $std->error = true;
            $std->mesg[] = $msss;
            echo json_encode($std);
            return false;
        }

        $reciever_email = [PI, FINANCE];
        $email_from = RM;
        $email_sender = EMAIL_SENDER_NAME;
        $email_subject = EMAIL_SUBJECT;
        $email_body = "<div style='width:100%; background-color: #6b7b6b; padding: 2em; color: gainsboro; '>
                        <div class='panel-heading'>
                        <h2 class='panel-title'>Fund Transfer Request</h2>
                        </div>
                        <div class='panel-body'>
                        <h5>Transfer Type: Exchange Website to Bank Account(E2B)</h5>
                        <p>RECIPIENT FULL NAME: <strong>".$user_bank_details[0]->acc_holder."</strong></p>
                        <p>BANK NAME: <strong>".$user_bank_details[0]->bank_name."</strong></p>
                        <p>BANK ACCOUNT NUMBER: <strong>".$user_bank_details[0]->acc_num."</strong></p>
                        <p>BRANCH: <strong>".$user_bank_details[0]->branch_name."</strong></p>
                        <p>FULL BANK ADDRESS: <strong>".$user_bank_details[0]->bank_addr."</strong></p>
                        <p>COUNTRY: ".$user_bank_details[0]->bank_ctry."</p>
                        <p>AMOUNT TO TRANSFER: <strong>$ $balance_to_transfer</strong> (DO NOT SEND MORE THAN $ $allowed_bid_amount.)</p>
                        <p>EMAIL: $senders_email</p> 
                        <p>REMARKS: <strong>".$remarks."</strong></p>  
                        <p>SENDER FB ID: facebook.com/".$fb_id."</p>
                        </div>
                        <footer>
                        <p>Thank You</p>
                        <span>Regards</span><br><br>
                        <a href='http://ranchimall.net' style='color:aliceblue'>Ranchi Mall</a>
                        </footer>
                        </div>";

        $send_mail = $OrderClass->send_notice_mail($reciever_email, $email_from, $email_sender, $email_subject, $email_body);
        $transfer_funds = null;
        if($send_mail) {
            /*Transfer funds fro site to bank account*/
            $transfer_funds = $OrderClass->fund_transfer($fund_type="E2B", $from="Exchange", $to=$user_bank_details[0]->acc_num, $balance_to_transfer, $remarks, $assetType = 'traditional');
        }

        if ($transfer_funds) {
            $mess = "E2B Transaction Success: Your request has been recorded and will be processed very soon by our team.";
            $OrderClass->storeMessagesPublic(null, $user_id, $mess);
            $std->error = false;
            $std->mesg[] = $mess;
            $std->user = $validate_user;

        } else {
            $mess = "E2B error: Mail could not be sent. Try again.";
            $OrderClass->storeMessagesPublic(null, $user_id, $mess);
            $std->error = true;
            $std->mesg[] = $mess;
            $std->user = $validate_user;
        }
        echo json_encode($std);
        return true;
    }
}
return false;