<?php
/**
 * Created by PhpStorm.
 * User: Abhishek Kumar Sinha
 * Date: 10/24/2017
 * Time: 9:35 AM
 */

/**
 *  This section is incomplete
    1. Check token sell order
    2. Deduct tokens after transfer to Blockchain
*/
return false;


require_once '../includes/imp_files.php';

if (!checkLoginStatus()) {
    return false;
}

if (isset($_POST['job']) && trim($_POST['job']) == "rtm_to_bchain") {
    if (isset($_POST['flo_addr'], $_POST['rmt_amnt'], $_POST['remarks_flo'])) {
        $wallet_address = (string) trim($_POST['flo_addr']);
        $balance_to_transfer = (float) $_POST['rmt_amnt'];
        $remarks = (string) trim($_POST['remarks_flo']);

        $std = new stdClass();
        $std->mesg = array();
        $std->error = true;

        if (empty($wallet_address) || empty($balance_to_transfer)) {
            $mess = "E2W error: Please fill all the required fields!";
            $OrderClass->storeMessagesPublic(null, $user_id, $mess);
            $std->mesg[] = $mess;
            echo json_encode($std);
            return false;
        }

        if (!preg_match('/^[A-Za-z0-9]*$/', $wallet_address)) {
            $mess = "E2W error (Invalid Wallet Address): Only alphanumeric characters are allowed in wallet address!";
            $std->mesg[] = $mess;
            $OrderClass->storeMessagesPublic(null, $user_id, $mess);
            echo json_encode($std);
            return false;
        }

        if (!preg_match("/^[a-zA-Z0-9 \r\n]*$/",$remarks)) {
            $mess = "E2W error: Only alphanumeric characters are allowed in remarks!";
            $std->mesg[] = $mess;
            $OrderClass->storeMessagesPublic(null, $user_id, $mess);
            echo json_encode($std);
            return false;
        }

        $customer_bal = (float) $OrderClass->check_customer_balance($assetType="btc", $user_id)->Balance;

        if ($balance_to_transfer > $customer_bal) {
            $mess = "E2W transaction failed: You have insufficient balance to make this transfer. Your current Token balance is $customer_bal RMTs.";
            $std->error = true;
            $std->mesg[] = $mess;
            echo json_encode($std);
            $OrderClass->storeMessagesPublic(null, $user_id, $mess);
            return false;
        }

        if ($balance_to_transfer < 0.0000000001) {
            $mess = "E2W error: Please provide minimum amount of 0.0000000001 RMTs!";
            $OrderClass->storeMessagesPublic(null, $user_id, $mess);
            $std->mesg[] = $mess;
            echo json_encode($std);
            return false;
        }

        if (strlen($remarks) > 250) {
            $mess = "E2W error: Remarks up to 250 characters allowed only!";
            $std->mesg[] = $mess;
            $OrderClass->storeMessagesPublic(null, $user_id, $mess);
            $std->error = true;
            echo json_encode($std);
            return false;
        }

        $validate_user = $UserClass->check_user($user_id);

        if($validate_user == "" || empty($validate_user)) {
            $mess = "No such user exist. Please login again.";
            $std->error = true;
            $std->mesg[] = $mess;
            $OrderClass->storeMessagesPublic(null, $user_id, $mess);
            echo json_encode($std);
            return false;
        }

        $email_id = trim($validate_user->Email);

        if (!is_email($email_id)) {
            $mess = "E2W error: Invalid email format!";
            $OrderClass->storeMessagesPublic(null, $user_id, $mess);
            $std->mesg[] = $mess;
            $std->error = true;
            echo json_encode($std);
            return false;
        }

        $reciever_email[] = AB;
        $email_from = RM;
        $email_sender = EMAIL_SENDER_NAME;
        $email_subject = EMAIL_SUBJECT_RTM_TRANSFER;
        $email_body = "<div style='width:100%; background-color: #6b7b6b; padding: 2em; color: gainsboro; '>
                        <div class='panel-heading'>
                        <h2 class='panel-title'>RMT Transfer Request</h2>
                        </div>
                        <div class='panel-body'>
                        <h5>Transfer Type: Exchange Website to FLORINCOIN BLOCKCHAIN WALLET(E2W)</h5>
                        <p>RECIPIENT FULL NAME: <strong>".$log_fullName."</strong></p>
                        <p>WALLET ADDRESS: <strong>".$wallet_address."</strong></p>
                        <p>AMOUNT TO TRANSFER: <strong>RMT $balance_to_transfer</strong></p>
                        <p>EMAIL: $email_id</p> 
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
            $transfer_funds = $OrderClass->fund_transfer($fund_type="E2W", $from="Exchange", $to=$wallet_address, $balance_to_transfer, $remarks, $asset_type='btc');
        }

        if ($transfer_funds) {

            $if_req_sent_to_bchain = sendReqtoURL($wallet_address, $balance_to_transfer);

            if ($if_req_sent_to_bchain) {
                $mess = "E2W Transaction Success: Your request has been recorded and will be processed very soon by our team.";
                $std->error = false;
                $std->mesg[] = $mess;
                $std->user = $validate_user;
                $OrderClass->storeMessagesPublic(null, $user_id, $mess);
            } else {
                $mess = "E2W error: API request could not be sent. ";
                $std->error = true;
                $std->mesg[] = $mess;
                $std->user = $validate_user;
                $OrderClass->storeMessagesPublic(null, $user_id, $mess);
                return false;
            }

        } else {
            $mess = "E2W error: Mail could not be sent. Try again.";
            $std->error = true;
            $std->mesg[] = $mess;
            $std->user = $validate_user;
            $OrderClass->storeMessagesPublic(null, $user_id, $mess);
        }
        echo json_encode($std);
        return true;
    }
}