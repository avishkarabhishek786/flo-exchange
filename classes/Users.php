<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 17-Oct-16
 * Time: 9:22 AM
 */

class Users {

    protected $db_connection = null;
    private $orders_table = ORDERS_TABLE;
    private $customers_table = USERS_TABLE;
    private $top_buy_table = TOP_BUYS_TABLE;
    private $top_sell_table = TOP_SELL_TABLE;
    private $customer_balance_table = CREDITS_TABLE;
    private $transaction_table = TRANSACTIONS_TABLE;
    private $bal_history = CREDITS_HISTORY_TABLE;
    private $bank_acc = ACCOUNTS_TABLE;
    private $fund_trans = TRANSFER_INFO_TABLE;
    private $user_name = null;
    private $email = null;
    private $name = null;
    private $is_active = null;
    private $user_is_logged_in = false;
    private $errors = array();

    public function databaseConnection()
    {
        // if connection already exists
        if ($this->db_connection != null) {
            return true;
        } else {
            try {
                $this->db_connection = new PDO('mysql:host='. DB_HOST .';dbname='. DB_NAME . ';charset=utf8', DB_USER, DB_PASS);
                return true;
            } catch (PDOException $e) {
                $this->errors[] = MESSAGE_DATABASE_ERROR . $e->getMessage();
            }
        }
        return false;
    }

    private function insert_balance($CustomerId, $AssetTypeId, $Balance, $FrozenBalance) {
        $now = $this->time_now();
        if ($this->databaseConnection()) {
            $query = $this->db_connection->prepare("INSERT INTO `$this->customer_balance_table`(`sr_no`, `CustomerId`, `AssetTypeId`, `Balance`, `FrozenBalance`, `UpdateDate`, `InsertDate`, `SaveDate`) VALUES ('', :CustomerId,:AssetTypeId,:Balance,:FrozenBalance,NULL,'$now','$now')");
            $query->bindValue(':CustomerId', $CustomerId, PDO::PARAM_STR);
            $query->bindValue(':AssetTypeId', $AssetTypeId, PDO::PARAM_STR);
            $query->bindValue(':Balance', $Balance, PDO::PARAM_STR);
            $query->bindValue(':FrozenBalance', $FrozenBalance, PDO::PARAM_STR);

            if($query->execute()) {
                return true;
            }
        }
        return false;
    }

    public function is_fb_registered($fb_id) {

        if ($this->databaseConnection()) {
            $now = $this->time_now();
            $query = $this->db_connection->prepare("SELECT * FROM $this->customers_table WHERE `fb_id`=:fb_id");
            $query->bindValue(':fb_id', $fb_id, PDO::PARAM_STR);
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount) {

                $user_obj = $query->fetchObject();

                $update_query = $this->db_connection->prepare("UPDATE $this->customers_table
                                                            SET `SaveDate`='$now'
                                                            WHERE `fb_id`=:fb_id
                                                            LIMIT 1");
                $update_query->bindValue(':fb_id', $fb_id, PDO::PARAM_STR);
                $update_query->execute();

                $_SESSION['user_id'] = $user_obj->CustomerId;
                $_SESSION['user_name'] = $user_obj->Username;
                $_SESSION['email'] = $user_obj->Email;

                if (!isset($_SESSION['last_trade_date'])) {
                    $_SESSION['last_trade_date'] = $user_obj->SaveDate;
                }
                return true;

            } else {

                $this->user_name = $_SESSION['first_name'].time();
                $this->name = $_SESSION['full_name'];
                $this->email = $_SESSION['email'];

                $query = $this->db_connection->prepare("
                    INSERT INTO $this->customers_table (`CustomerId`, `fb_id`, `Username`, `Email`, `Name`, `UpdateDate`, `InsertDate`, `SaveDate`, `is_active`)
                    VALUES ('',:fb_id,:Username,:Email,:Name,NULL,'$now',NULL,0)
                ");

                $query->bindValue(':fb_id', $fb_id, PDO::PARAM_INT);
                $query->bindValue(':Username', $this->user_name, PDO::PARAM_STR);
                $query->bindValue(':Email', $this->email, PDO::PARAM_STR);
                $query->bindValue(':Name', $this->name, PDO::PARAM_STR);
                if($query->execute()) {
                    $_SESSION['user_id'] = $this->db_connection->lastInsertId();
                    $_SESSION['user_name'] = $this->user_name;
                    $AssetTypeId = 'btc';
                    $Balance = 0.00;
                    $FrozenBalance = 0.00;
                    $crypto = $this->insert_balance($_SESSION['user_id'], $AssetTypeId, $Balance, $FrozenBalance);

                    $AssetTypeId = 'traditional';
                    $Balance = 0.00;
                    $FrozenBalance = 0.00;
                    $cash = $this->insert_balance($_SESSION['user_id'], $AssetTypeId, $Balance, $FrozenBalance);

                    $user_exist = $this->check_user($_SESSION['user_id']);
                    if($user_exist && $crypto && $cash) {
                        return true;
                    }
                    return false;
                }
                return false;
            }
        } else {
            return false;
        }
    }

    public function check_user($customerId) {

        if ($this->databaseConnection()) {

            $query = $this->db_connection->prepare("SELECT * FROM $this->customers_table WHERE customerId = :customerId AND is_active = 1 LIMIT 1");
            $query->bindParam('customerId', $customerId);

            if ($query->execute()) {
                $row_count = $query->rowCount();
                if ($row_count == 1) {
                    return $user_details = $query->fetchObject();
                }
                return false;
            } else {
                return false;
            }
        }
        return false;
    }

    public function displayUserTransaction($user_id, $start=0, $limit=10) {
        if ($this->databaseConnection()) {
            $transactions = array();

            $query = $this->db_connection->prepare("
                SELECT TransactionId AS T_ID, a_buyer AS BUYER_ID, b_seller AS SELLER_ID, (SELECT ".USERS_TABLE.".Name FROM ".USERS_TABLE." WHERE ".USERS_TABLE.".CustomerId=BUYER_ID) AS BUYER, (SELECT ".USERS_TABLE.".Name FROM ".USERS_TABLE." WHERE ".USERS_TABLE.".CustomerId=SELLER_ID) AS SELLER, B_AMOUNT AS TRADE_PRICE, ".TRANSACTIONS_TABLE.".InsertDate, ".TRANSACTIONS_TABLE.".qty_traded AS TRADED_QTY
                FROM ".TRANSACTIONS_TABLE.", ".USERS_TABLE."
                WHERE `a_buyer`= :u_id OR `b_seller`= :u_id
                GROUP BY T_ID
                ORDER BY T_ID DESC
                LIMIT $start, $limit
            ");
            $query->bindParam('u_id', $user_id);
            if ($query->execute()) {
                $rowCount = $query->rowCount();
                if ($rowCount > 0) {
                    while ($tr = $query->fetchObject()) {
                        $transactions[] = $tr;
                    }
                }
            }
            return $transactions;
        }
        return false;
    }

    public function list_messages_by_userId($user_id, $start=0, $limit=10) {
        if ($this->databaseConnection()) {
            $messages = array();

            $query = $this->db_connection->prepare("
                SELECT * FROM ".MSG_TABLE." WHERE `username_key`= :uk
                ORDER BY datetime DESC 
                LIMIT $start, $limit
             ");
            $query->bindParam("uk", $user_id);
            if ($query->execute()) {
                $rowCount = $query->rowCount();
                if ($rowCount > 0) {
                    while ($tr = $query->fetchObject()) {
                        $messages[] = $tr;
                    }
                }
            }
            return $messages;
        }
        return false;
    }

    public function actions_user($u_id, $act=1) {
        if ($this->databaseConnection()) {
            if (!empty($u_id)) {

                $act = (int) $act;
                $u_id = (int) $u_id;

                $query = $this->db_connection->prepare("
                    UPDATE ".USERS_TABLE." SET `is_active`= $act 
                    WHERE CustomerId = :u_id
                    LIMIT 1
                ");
                $query->bindParam('u_id', $u_id);

                if ($query->execute()) {
                    return true;
                }
            }
        }
        return false;
    }
    
    public function get_total_users_count() {
        if ($this->databaseConnection()) {
            $total_users = 0;
            $query = $this->db_connection->query("SELECT COUNT(*) AS TOTAL_COUNT FROM ".USERS_TABLE." WHERE `is_active`=1");
            if ($query->rowCount()) {
                $total_users = $query->fetchObject()->TOTAL_COUNT;
            }
            return (int) $total_users;
        }
        return false;
    }

    public function time_now() {
        $n = new DateTime("now", new DateTimeZone("Asia/Kolkata"));
        $now = $n->format('Y-m-d H:i:s');
        return $now;
    }

    public function get_username($customerId=0) {

        if ($this->databaseConnection()) {
            $customerId = (int) $customerId;
            $query = $this->db_connection->prepare("SELECT Username FROM ".USERS_TABLE." WHERE customerId = :id LIMIT 1");
            $query->bindParam('id', $customerId);

            $query->execute();
            $row_count = $query->rowCount();
            if ($row_count == 1) {
                return $query->fetchObject()->Username;
            }
        }
        return false;
    }

    public function input_user_email($email=null, $user_id=null) {
        if ($this->databaseConnection()) {
            $query = $this->db_connection->prepare("
            UPDATE ".USERS_TABLE." SET `Email`= :em WHERE CustomerId = :cid
            ");
            $query->bindParam('em', $email);
            $query->bindParam('cid', $user_id);

            if ($query->execute()) {
                return true;
            }
        }
        return false;
    }

}