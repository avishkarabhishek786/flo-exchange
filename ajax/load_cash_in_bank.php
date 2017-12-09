<?php
/**
 * Created by PhpStorm.
 * User: Abhishek Kumar Sinha
 * Date: 10/24/2017
 * Time: 9:37 PM
 */

require_once '../includes/imp_files.php';

if (!checkLoginStatus()) {
    return false;
}

if (isset($_POST['job'])) {
    if ($_POST['job']=='get_btc2usd') {
        $btc2usd = bitcoin_price_today();
        echo (float) $btc2usd;
        exit;
    }

    if ($_POST['job']=='lcma') {
        if (isset($_POST['amount_to_load'], $_POST['eqv_btc'], $_POST['remarks'], $_POST['btc_today'])) {
            $amount_to_load= (float) trim($_POST['amount_to_load']);
            $equivalent_btc = (float) trim($_POST['eqv_btc']);
            $remarks = trim($_POST['remarks']);
            $btc_today = (float) trim($_POST['btc_today']);

            $std = new stdClass();
            $std->mesg = array();
            $std->error = true;

            if (empty($btc_today)) {
                $mess[] = "BTC2CASH Error: Something went wrong. Please refresh the page and try again.";
                $OrderClass->storeMessagesPublic(null, $user_id, $mess." Failed to fetch price of 1 bitcoin today.");
                $std->mesg[] = $mess;
                echo json_encode($std);
                return false;
            }

            if (empty($amount_to_load) || empty($equivalent_btc)) {
                $mess[] = "BTC2CASH Error: Please fill all the required fields.";
                $OrderClass->storeMessagesPublic(null, $user_id, $mess);
                $std->mesg[] = $mess;
                echo json_encode($std);
                return false;
            }

            $validate_user = $UserClass->check_user($user_id);

            if($validate_user == "" || empty($validate_user)) {
                $mess = "BTC2CASH error: No such user exist. Please login again.";
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

            if (strlen($remarks) > 250) {
                $mess = "BTC2CASH error: Remarks up to 250 characters allowed only!";
                $std->mesg[] = $mess;
                $OrderClass->storeMessagesPublic(null, $user_id, $mess);
                $std->error = true;
                echo json_encode($std);
                return false;
            }

            if (!preg_match("/^[a-zA-Z0-9 \r\n]*$/", $remarks)) {
                $mess = "BTC2CASH error: Only alphanumeric characters are allowed in remarks!";
                $std->mesg[] = $mess;
                $OrderClass->storeMessagesPublic(null, $user_id, $mess);
                echo json_encode($std);
                return false;
            }

            $reciever_email[] = RT;
            $email_from = RM;
            $email_sender = EMAIL_SENDER_NAME;
            $email_subject = EMAIL_SUBJECT_BTC_TO_CASH;
            $email_body = "<div style='width:100%; background-color: #6b7b6b; padding: 2em; color: gainsboro; '>
                        <div class='panel-heading'>
                        <h2 class='panel-title'>Load Cash in Exchange Request</h2>
                        </div>
                        <div class='panel-body'>
                        <h5>Transfer Type: BITCOIN to CASH in Exchange(BTC2CASH)</h5>
                        <p>RECIPIENT FULL NAME: <strong>".$log_fullName."</strong></p>                     
                        <p>BTC TO SEND: <strong>BTC $equivalent_btc</strong></p>
                        <p>CASH TO RECEIVE: <strong>$ $amount_to_load</strong></p>
                        <p>1 BTC AT THE TIME OF REQUEST: $ $btc_today</p>
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

            if($send_mail) {
                //$mess = "BTC2CASH Request: You sent a request to deposit BTC $equivalent_btc to Ranchi Mall to receive $ $amount_to_load. You will receive an email from Ranchi Mall. Please follow the instructions provided in that email.";
                $mess = "BTC2CASH Request: You sent a request to deposit BTC $equivalent_btc to Ranchi Mall to receive $ $amount_to_load. Please send the Bitcoins to address provided in the 'Load Cash to my trading account' tab below.";
                $OrderClass->storeMessagesPublic(null, $user_id, $mess);
                $std->error = false;
                $std->mesg[] = $mess;
            }
            echo json_encode($std);
            return false;
        }
    }
}


return false;