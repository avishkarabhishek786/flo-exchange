<?php
/**
 * Created by PhpStorm.
 * User: Abhishek Kumar Sinha
 * Date: 10/21/2017
 * Time: 5:57 PM
 */

require_once '../includes/imp_files.php';

if (!checkLoginStatus()) {
    return false;
}

if (isset($_POST['job']) && trim($_POST['job']) == "add_bank_account") {
    if (isset($_POST['account_holder_name'],$_POST['account_number'],$_POST['bank_name'],$_POST['branch_name'],$_POST['bank_addr'], $_POST['bk_ctry'])) {

        $account_holder_name = trim($_POST['account_holder_name']);
        $account_number = trim($_POST['account_number']);
        $bank_name = trim($_POST['bank_name']);
        $branch_name = trim($_POST['branch_name']);
        $bank_addr = trim($_POST['bank_addr']);
        $bk_ctry = (string) trim($_POST['bk_ctry']);

        $std = new stdClass();
        $std->mesg = array();
        $std->error = true;

        if (empty($account_holder_name) || empty($account_number) || empty($bank_name) || empty($branch_name) || empty($bank_addr) || empty($bk_ctry)) {
            $mess = "Bank Account Addition Failure: Please fill all fields with valid data!";
            $OrderClass->storeMessagesPublic(null, $user_id, $mess);
            $std->mesg[] = $mess;
            echo json_encode($std);
            return false;
        }

        if(!preg_match("/^[a-zA-Z ]+$/", $account_holder_name) == 1) {
            $mess = "Bank Account Addition Failure: Account Holder name must be only in alphabetical characters!";
            $OrderClass->storeMessagesPublic(null, $user_id, $mess);
            $std->mesg[] = $mess;
            echo json_encode($std);
            return false;
        }

        if(!preg_match("/^[a-zA-Z0-9]+$/", $account_number) == 1) {
            $mess = "Bank Account Addition Failure: Account number must be only in alphanumeric characters!";
            $OrderClass->storeMessagesPublic(null, $user_id, $mess);
            $std->mesg[] = $mess;
            echo json_encode($std);
            return false;
        }

        if((!preg_match("/^[a-zA-Z ]+$/", $bank_name) == 1) || (!preg_match("/^[a-zA-Z-,: ]+$/", $branch_name) == 1) || (!preg_match("/^[a-zA-Z ]+$/", $bk_ctry) == 1)) {
            $mess = "Bank Account Addition Failure: Bank name, Bank country and branch name must be only in alphabetical characters!";
            $OrderClass->storeMessagesPublic(null, $user_id, $mess);
            $std->mesg[] = $mess;
            echo json_encode($std);
            return false;
        }

        $add_bank_account = $OrderClass->add_bank_account($user_id, $account_holder_name, $bank_name, $account_number, $branch_name, $bank_addr, $bk_ctry);

        if ($add_bank_account) {
            $mess = "Bank Account Addition: Bank account <strong>$account_number</strong> was added successfully.!";
            $OrderClass->storeMessagesPublic(null, $user_id, $mess);
            $std->mesg[] = $mess;
            $std->error = false;
        }

        echo json_encode($std);
        exit;

    }
}