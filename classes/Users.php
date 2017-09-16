<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 17-Oct-16
 * Time: 9:22 AM
 */

class Users {

    protected $db_connection = null;
    private $customers_table = "customer";
    private $top_buy_table = "active_buy_list";
    private $top_sell_table = "active_selling_list";
    private $customer_balance_table = "assetbalance";
    private $user_name = null;
    private $email = null;
    private $name = null;
    private $mob = null;
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

        if ($this->databaseConnection()) {
            $query = $this->db_connection->prepare("INSERT INTO `$this->customer_balance_table`(`sr_no`, `CustomerId`, `AssetTypeId`, `Balance`, `FrozenBalance`, `UpdateDate`, `InsertDate`, `SaveDate`) VALUES ('', :CustomerId,:AssetTypeId,:Balance,:FrozenBalance,NULL,NOW(),NOW())");
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

            $query = $this->db_connection->prepare("SELECT * FROM $this->customers_table WHERE `fb_id`=:fb_id");
            $query->bindValue(':fb_id', $fb_id, PDO::PARAM_STR);
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount) {

                $user_obj = $query->fetchObject();

                $user_email = $user_obj->Email;

                if($user_email !== '' || $user_email !== null) {

                    $update_query = $this->db_connection->prepare("UPDATE $this->customers_table
                                                            SET `Email`=:email, `UpdateDate`=NOW(), `SaveDate`=NOW()
                                                            WHERE `fb_id`=:fb_id
                                                            LIMIT 1");
                    $update_query->bindValue(':email', $user_email, PDO::PARAM_STR);
                    $update_query->bindValue(':fb_id', $fb_id, PDO::PARAM_STR);
                    $update_query->execute();
                }

                $_SESSION['user_id'] = $user_obj->CustomerId;
                $_SESSION['user_name'] = $user_obj->Username;
                return true;

            } else {

                $this->user_name = $_SESSION['FIRSTNAME'].time();
                $this->name = $_SESSION['FULLNAME'];
                $this->email = $_SESSION['EMAIL'];

                $query = $this->db_connection->prepare("
                    INSERT INTO $this->customers_table (`CustomerId`, `fb_id`, `Username`, `Email`, `Name`, `UpdateDate`, `InsertDate`, `SaveDate`, `Mobile`)
                    VALUES ('',:fb_id,:Username,:Email,:Name,NULL,NOW(),NULL,NULL)
                ");

                $query->bindValue(':fb_id', $fb_id, PDO::PARAM_INT);
                $query->bindValue(':Username', $this->user_name, PDO::PARAM_STR);
                $query->bindValue(':Email', $this->email, PDO::PARAM_STR);
                $query->bindValue(':Name', $this->name, PDO::PARAM_STR);
                if($query->execute()) {
                    $_SESSION['user_id'] = $this->db_connection->lastInsertId();
                    $_SESSION['user_name'] = $this->user_name;
                    $AssetTypeId = 'btc';
                    $Balance = 10.00;
                    $FrozenBalance = 0.00;
                    $crypto = $this->insert_balance($_SESSION['user_id'], $AssetTypeId, $Balance, $FrozenBalance);

                    $AssetTypeId = 'traditional';
                    $Balance = 1000.00;
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

            $query = $this->db_connection->prepare("SELECT * FROM $this->customers_table WHERE customerId = :customerId LIMIT 1");
            $query->bindParam('customerId', $customerId);

            if ($query->execute()) {
                $row_count = $query->rowCount();
                if ($row_count > 0) {
                    return $user_details = $query->fetchObject();
                }
                return false;
            } else {
                return false;
            }
        }
        return false;
    }

    public function displayUserTransaction($user_id) {
        if ($this->databaseConnection()) {
            $transactions = array();
            $query = $this->db_connection->prepare("
                SELECT TransactionId AS T_ID, a_buyer AS BUYER_ID, b_seller AS SELLER_ID, (SELECT customer.Name FROM customer WHERE customer.CustomerId=BUYER_ID) AS BUYER, (SELECT customer.Name FROM customer WHERE customer.CustomerId=SELLER_ID) AS SELLER, B_AMOUNT AS TRADE_PRICE, transaction.InsertDate
                FROM transaction, customer
                WHERE `a_buyer`= :u_id OR `b_seller`= :u_id
                GROUP BY T_ID
                ORDER BY T_ID DESC
                LIMIT 50
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


}