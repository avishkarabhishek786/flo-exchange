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

if (isset($_POST['job']) && trim($_POST['job']) == "pay_in_btc") {

    if (isset($_POST['ref_amount'], $_POST['btc_addr'])) {
        $balance_to_transfer = (float) $_POST['ref_amount'];
        $btc_addr = (string) strtoupper($_POST['btc_addr']);
        $remarks = (string) $_POST['invst_remarks'];

        $std = new stdClass();
        $std->mesg = array();
        $std->error = true;
        $std->user = null;

        if (empty($balance_to_transfer) || empty($btc_addr)) {
            $mess = "E2BTC error: Please fill all the required fields!";
            $OrderClass->storeMessagesPublic(null, $user_id, $mess);
            $std->mesg[] = $mess;
            $std->error = true;
            echo json_encode($std);
            return false;
        }

        if ((!preg_match("/^[a-zA-Z0-9]+$/", $btc_addr) == 1) || strlen(trim($btc_addr)) !== 34) {
            $mess = "E2BTC error: Invalid Bitcoin address!";
            $OrderClass->storeMessagesPublic(null, $user_id, $mess);
            $std->mesg[] = $mess;
            $std->error = true;
            echo json_encode($std);
            return false;
        }

        if (strlen($remarks) > 250) {
            $mess = "E2BTC error: Remarks up to 250 characters allowed only!";
            $OrderClass->storeMessagesPublic(null, $user_id, $mess);
            $std->mesg[] = $mess;
            $std->error = true;
            echo json_encode($std);
            return false;
        }

        if (!preg_match("/^[a-zA-Z0-9 \r\n]*$/",$remarks)) {
            $mess = "E2BTC error: Only alphanumeric characters allowed in Remarks!";
            $OrderClass->storeMessagesPublic(null, $user_id, $mess);
            $std->mesg[] = $mess;
            $std->error = true;
            echo json_encode($std);
            return false;
        }

        $validate_user = $UserClass->check_user($user_id);

        if($validate_user == "" || empty($validate_user)) {
            $mess = "E2BTC error: No such user exist. Please login again.";
            $OrderClass->storeMessagesPublic(null, $user_id, $mess);
            $std->error = true;
            $std->mesg[] = $mess;
            echo json_encode($std);
            return false;
        }

        $senders_email = trim($validate_user->Email);

        if ($senders_email == null || !is_email($senders_email)) {
            $mess = "E2BTC error: Invalid email format!";
            $OrderClass->storeMessagesPublic(null, $user_id, $mess);
            $std->mesg[] = $mess;
            $std->error = true;
            echo json_encode($std);
            return false;
        }

        $customer_bal = (float) $OrderClass->check_customer_balance($assetType="traditional", $user_id)->Balance;

        if ($balance_to_transfer > $customer_bal) {
            $mess = "E2BTC transaction failed: You have insufficient balance to make this transfer. Your current Cash balance is $ $customer_bal.";
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
        $allowed_bid_amount = $customer_bal;
        $user_active_orders = $OrderClass->get_active_order_of_user($user_id, TOP_BUYS_TABLE);
        $frozen_bal_buys = 0;
        if (is_array($user_active_orders) && !empty($user_active_orders)) {
            foreach ($user_active_orders as $uao) {
                $frozen_bal_buys += (float) $uao->price * $uao->quantity;
            }
            $allowed_bid_amount = $customer_bal - $frozen_bal_buys;
            $ext_st = "You can refund up to $ $allowed_bid_amount only.";
            if ($allowed_bid_amount == 0) {
                $ext_st = "You don't have any cash balance to refund.";
            }
            $msss = "E2BTC Refund error: You have placed an order worth $ $frozen_bal_buys $ext_st Please cancel it or reduce your refund amount.";
        }

        if ($frozen_bal_buys + $balance_to_transfer > $customer_bal) {
            $OrderClass->storeMessagesPublic(null, $user_id, $msss);
            $std->error = true;
            $std->mesg[] = $msss;
            echo json_encode($std);
            return false;
        }

        $reciever_email = [$senders_email, PI, FINANCE];
        $email_from = RM;
        $email_sender = EMAIL_SENDER_NAME;
        $email_subject = EMAIL_SUBJECT;
        $email_body = "<div style='width:100%; background-color: #6b7b6b; padding: 2em; color: gainsboro; '>
                        <div class='panel-heading'>
                        <h2 class='panel-title'>E2BTC Fund Transfer Request</h2>
                        </div>
                        <div class='panel-body'>
                        <h5>Transfer Type: Exchange Website to BITCOIN(E2BTC)</h5>
                        
                        <p>Hello $log_fullName</p>
            
                        <p>We have received a request to refund $ $balance_to_transfer to you in Bitcoins.<br> Below is the details of your request. 
                        Please approve the Bitcoin address again before we send you Bitcoins.<br> If you did not send any request or if any data below 
                        is incorrect please report immediately. 
                        </p>
                        
                        <p>BTC ADDRESS: $btc_addr</p>
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
            /*Transfer funds from site to bank account*/
            $transfer_funds = $OrderClass->fund_transfer($fund_type="E2BTC", $from="Exchange", $to=$btc_addr, $balance_to_transfer, $remarks, $assetType = 'traditional');
        }

        if ($transfer_funds) {
            $mess = "E2BTC Transaction Success: Please check your mail to approve this request.";
            $OrderClass->storeMessagesPublic(null, $user_id, $mess);
            $std->error = false;
            $std->mesg[] = $mess;
            $std->user = $validate_user;

        } else {
            $mess = "E2BTC error: Mail could not be sent. Try again.";
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