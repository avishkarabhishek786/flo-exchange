<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 17-Oct-16
 * Time: 9:22 AM
 */

class Orders {

    private  $db_connection = null;
    private $errors = array();
    private $orders_table = "orderbook";
    private $customers_table = "customer";
    private $top_buy_table = "active_buy_list";
    private $top_sell_table = "active_selling_list";
    private $customer_balance_table = "assetbalance";
    private $transaction_table = "transaction";
    private $customerId = 0;
    private $orderTypeId = 0;
    private $quantity = 0;
    private $demanded_qty = 0;
    private $price = 0;
    private $orderStatusId = 2; //pending
    private $max_top_bids = 20;
    private $customer_balance = null; // Don't make it 0
    private $customer_frozen_balance = null; // Don't make it 0

    protected function databaseConnection()
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

    private function insert_order_in_active_table($top_table, $orderId, $price, $quantity) {

        if ($this->databaseConnection()) {
            $query = $this->db_connection->prepare("INSERT INTO $top_table(`price`, `orderId`, `quantity`, `customerId`, `insertDate`)
                      VALUES (:price, :orderId, :quantity, :user_id, NOW())");
            $query->bindParam("price", $price);
            $query->bindParam("orderId", $orderId);
            $query->bindParam("quantity", $quantity);
            $query->bindParam("user_id", $_SESSION['user_id']);

            if ($query->execute()) {
                $query = $this->db_connection->prepare("UPDATE $this->orders_table
                                    SET `OrderStatusId`='1',`UpdateDate`=NOW()
                                    WHERE `OrderId`=:order_id AND `CustomerId`= :customer_id
                                    LIMIT 1");
                $query->bindParam("order_id", $orderId);
                $query->bindParam("customer_id", $_SESSION['user_id']);

                if ($query->execute()) {
                    return true;
                }
            }
            return false;
        }
        return false;
    }

    public function check_customer_balance($assetType, $user_id) {

        if ($this->databaseConnection()) {

            $customer_balance = null;
            $query = $this->db_connection->prepare("SELECT `Balance`, FrozenBalance
                                    FROM $this->customer_balance_table
                                    WHERE `CustomerId`= :user_id AND `AssetTypeId`='$assetType'");
            $query->bindParam(":user_id", $user_id);
            if ($query->execute()) {
                if ($query->rowCount()) {
                    $customer_balance = $query->fetchObject();
                }
            }
            return $customer_balance;
        }
        return false;
    }


     public function update_user_balance($assetType, $balance=null, $user_id) {

        if ($this->databaseConnection()) {

            $sql = "";
            if ($balance != null) {
                $sql .= "UPDATE $this->customer_balance_table ";
                $sql .= " SET `Balance`= :balance, ";
                $sql .= " `UpdateDate`= NOW() ";
                $sql .= " WHERE `CustomerId`= :user_id ";
                $sql .= " AND `AssetTypeId`= :asset_type ";
                $sql .= "LIMIT 1";

            $query = $this->db_connection->prepare($sql);

            if ($balance != null) {
                $query->bindParam("balance", $balance);
            }
            $query->bindParam("user_id", $user_id);
            $query->bindParam("asset_type", $assetType);
            if ($query->execute()) {
                return true;
            }
        }
            return false;
        }
        return false;
    }

    public function insert_pending_order($orderTypeId, $qty, $price, $orderStatusId, $OfferAssetTypeId=null, $WantAssetTypeId=null) {

        if ($this->databaseConnection()) {

            $this->customerId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
            $this->orderTypeId = $orderTypeId;  // 0-> buy; 1 -> sell;
            $this->quantity = $qty;
            $this->price = $price;
            $this->orderStatusId = $orderStatusId;  // 0 -> cancelled; 1 -> complete; 2 -> pending

            $std = new stdClass();
            $std->insertedrowid = null;
            $std->orderTypeId = null; // 0 -> buy; 1 -> sell
            $std->item_qty = null;
            $std->item_price = null;
            $std->orderStatusId = null;
            $std->insert_date = null;
            $std->error = true;
            $std->message = null;

            // check user balance
            $assetType = null;
            if ($this->orderTypeId == 0) {
                $assetType = 'traditional';
            } else if ($this->orderTypeId == 1) {
                $assetType = 'btc';
            }

            $customer_bal = (float)$this->check_customer_balance($assetType, $this->customerId)->Balance;

            $this->customer_balance = $customer_bal;

            if ($this->customer_balance == '' || $this->customer_balance == null || !is_float($this->customer_balance)) {
                $std->message = "0 balance: Your account balance is nill.";
                return $std;
            }
            $total_trade_val = $this->quantity * $this->price;
            if ($total_trade_val > $this->customer_balance) {
                $std->message = "Insufficient balance: You have insufficient balance to continue this trade. Please recharge your wallet or lower the quantity.";
                return $std;
            }

            $query = $this->db_connection->prepare("INSERT INTO $this->orders_table (`OrderId`, `CustomerId`, `OrderTypeId`, `OfferAssetTypeId`, `WantAssetTypeId`, `Quantity`, `Price`, `OrderStatusId`, `UpdateDate`, `InsertDate`, `SaveDate`)
                                    VALUES ('', " . $this->customerId . ", :a, :e, :f, :b, :c, :d, NULL, Now(), NULL)
                                  ");
            $query->bindParam(':a', $this->orderTypeId, PDO::PARAM_STR);
            $query->bindParam(':e', $OfferAssetTypeId, PDO::PARAM_STR);
            $query->bindParam(':f', $WantAssetTypeId, PDO::PARAM_STR);
            $query->bindParam(':b', $this->quantity, PDO::PARAM_STR);
            $query->bindParam(':c', $this->price, PDO::PARAM_STR);
            $query->bindParam(':d', $this->orderStatusId, PDO::PARAM_STR);

            if ($query->execute()) {

                $insertedrowid = $this->db_connection->lastInsertId();

                // Check if $price is eligible to be inserted into top_buy or top_sell table
                $top_table = null;
                $asc_desc = null;
                $new_balance = null;
                $new_frozenbalance = null;

                if ($orderTypeId == '0') {
                    $top_table = $this->top_buy_table;
                } else if ($orderTypeId == '1') {
                    $top_table = $this->top_sell_table;
                }

                // Change the order status to active and insert in active table in DB
                $insert_in_active_table = $this->insert_order_in_active_table($top_table, $insertedrowid, $this->price, $this->quantity);

                $this->orderStatusId = 1;

                $std = new stdClass();
                $std->insertedrowid = $insertedrowid;
                $std->made_to_active_list = $insert_in_active_table;
                $std->orderTypeId = $this->orderTypeId; // 0 -> buy; 1 -> sell
                $std->item_qty = $qty;
                $std->item_price = $price;
                $std->orderStatusId = $this->orderStatusId;
                $std->insert_date = date('Y-m-d H:i:s');
                $std->error = false;
                $std->message = "Order moved to active table.";
                return $std;
            }
            return null;
        }
        return false;
    }

    public function get_top_buy_sell_list($top_table, $asc_desc) {

        if ($this->databaseConnection()) {

            $top_list = array();

            $query = $this->db_connection->query("SELECT OrderId, customerId, Quantity, Price, (Quantity * Price) AS TOTAL_COST
                                    FROM $top_table
                                    ORDER BY price $asc_desc
                                    LIMIT $this->max_top_bids
                                    ");

            if ($query) {

                $rowCount = $query->rowCount();

                if ($rowCount > 0) {

                    while ($orders = $query->fetchObject()) {

                        $top_list[] = $orders;

                    }
                }

            } else {
                return false;
            }
            return $top_list;
        }
        return false;
    }

    public function get_all_buy_sell_list($buy_or_sell_id, $AscDesc) {

        if ($this->databaseConnection()) {

            $buy_or_sell_list = array();

            $query = $this->db_connection->prepare("SELECT $this->orders_table.OrderId, $this->customers_table.CustomerId, $this->customers_table.Name, $this->orders_table.Quantity, $this->orders_table.Price, ($this->orders_table.Quantity * $this->orders_table.Price) AS TOTAL_COST,  $this->orders_table.OrderStatusid, $this->orders_table.InsertDate
                                    FROM $this->orders_table, $this->customers_table
                                    WHERE $this->orders_table.OrderTypeId = :id
                                    GROUP BY $this->orders_table.Price $AscDesc");

            $query->bindParam("id", $buy_or_sell_id);

            if ($query->execute()) {

                $rowCount = $query->rowCount();

                if ($rowCount > 0) {

                    while ($orders = $query->fetchObject()) {

                        $buy_or_sell_list[] = $orders;

                    }
                }

            } else {
                return false;
            }
            return $buy_or_sell_list;
        }
        return false;
    }

    public function OrderMatchingQuery() {

        if ($this->databaseConnection()) {

            $query = $this->db_connection->query("
                SELECT $this->top_sell_table.price, $this->top_sell_table.Quantity, $this->top_sell_table.orderId, (SELECT `CustomerId` FROM $this->top_buy_table ORDER BY price DESC LIMIT 1) AS BUYER_ID, $this->top_sell_table.CustomerId AS SELLER_ID
                FROM $this->top_sell_table, $this->orders_table
                WHERE (
                 ($this->top_sell_table.price <= (SELECT `price` FROM `$this->top_buy_table` ORDER BY price DESC LIMIT 1))
                 AND ($this->orders_table.OrderId = $this->top_sell_table.orderId)
                 AND ($this->orders_table.OrderStatusId = '1')
                 AND ($this->orders_table.OrderTypeId= '1') )
                ORDER BY $this->top_sell_table.price ASC
            ");

            if($rowCount = $query->rowCount() > 0) { // Transaction is possible
                $matched_orders = array();
                while ($obj = $query->fetchObject()) {
                    $matched_orders[] = $obj;
                }
                return $matched_orders;
            }
            return false;
        }
        return false;
    }

    private function get_highest_demand() {
        if ($this->databaseConnection()) {

            $query = $this->db_connection->query("SELECT $this->top_buy_table.OrderId, $this->top_buy_table.Price, $this->top_buy_table.Quantity FROM `$this->orders_table`, `$this->top_buy_table` WHERE $this->orders_table.OrderId = $this->top_buy_table.orderId ORDER BY $this->top_buy_table.price DESC LIMIT 1");
            $rowCount_Qty = $query->rowCount();
            if (!$rowCount_Qty) {
                return false;
            }
            return $highest_demanded = $query->fetchObject();
        }
        return false;
    }

    public function OrderMatchingService() {

        if ($this->databaseConnection()) {

            $this->demanded_qty = ($this->get_highest_demand() != false) ? $this->get_highest_demand()->Quantity : 0;
            $buy_order_id = ($this->get_highest_demand() != false) ? $this->get_highest_demand()->OrderId : 0;
            $buy_amount = ($this->get_highest_demand() != false) ? $this->get_highest_demand()->Price : 0;

            $supply_available = $this->OrderMatchingQuery();

            if ($this->demanded_qty == false || $this->demanded_qty == '' || $this->demanded_qty == '0' || $supply_available == false || $supply_available == '' || !is_array($supply_available) || empty($supply_available)) {
                return false;
            }

            $this->demanded_qty = (float)$this->demanded_qty;

            if(is_array($supply_available) && !empty($supply_available) ) {

                //for($i=0; $i<=count($supply_available); $i++) {
                foreach ($supply_available as $available) {

                    if ($this->demanded_qty > 0) {
                        $supply_available = $this->OrderMatchingQuery();
                        $available->Quantity = (float)$available->Quantity;
                        $seller_id = $available->SELLER_ID;
                        $seller_balance_btc = $this->check_customer_balance($assetType = 'btc', $seller_id)->Balance;
                        $seller_balance_cash = $this->check_customer_balance($assetType = 'traditional', $seller_id)->Balance;

                        $buyer_id = $available->BUYER_ID;
                        $buyer_balance_btc = $this->check_customer_balance($assetType = 'btc', $buyer_id)->Balance;
                        $buyer_balance_cash = $this->check_customer_balance($assetType = 'traditional', $buyer_id)->Balance;

                        $cost_of_total_supply = $available->Quantity * $available->price; // traditional or cash

                        $new_seller_balance_cash = $seller_balance_cash + $cost_of_total_supply;  // traditional or cash
                        $new_seller_balance_btc = $seller_balance_btc - $available->Quantity; // deduct the btc sold

                        $new_buyer_balance_btc = $buyer_balance_btc + $available->Quantity; // btc
                        $new_buyer_balance_cash = $buyer_balance_cash - $cost_of_total_supply; // traditional or cash

                        if ($this->demanded_qty > $available->Quantity) {

                            // subtract the debit access (customers balance of $ or BTC)
                            $this->update_user_balance($assetType = 'btc', $balance = $new_buyer_balance_btc, $user_id = $buyer_id);

                            // increment the credit asset (customers balance of $ or BTC)
                            $this->update_user_balance($assetType = 'traditional', $balance = $new_seller_balance_cash, $user_id = $seller_id);

                            // record the commission in the commission account
                            // decrease respective balances
                            $this->update_user_balance($assetType = 'btc', $balance = $new_seller_balance_btc, $user_id = $seller_id);
                            $this->update_user_balance($assetType = 'traditional', $balance = $new_buyer_balance_cash, $user_id = $buyer_id);

                            $this->demanded_qty = $this->demanded_qty - $available->Quantity;

                            // update the quantity field for demand
                            $this->update_quantity($top_table = $this->top_buy_table, $this->demanded_qty, $buy_order_id);

                            // Delete the row from Sell list
                            $this->delete_order($this->top_sell_table, $available->orderId);
                            // save changes

                            $available->Quantity = 0;

                        } elseif ($this->demanded_qty == $available->Quantity) {

                            // subtract the debit access (customers balance of $ or BTC)
                            $this->update_user_balance($assetType = 'btc', $balance = $new_buyer_balance_btc, $user_id = $buyer_id);

                            // increment the credit asset (customers balance of $ or BTC)
                            $this->update_user_balance($assetType = 'traditional', $balance = $new_seller_balance_cash, $user_id = $seller_id);

                            // record the commission in the commission account
                            // decrease respective balances
                            $this->update_user_balance($assetType = 'btc', $balance = $new_seller_balance_btc, $user_id = $seller_id);
                            $this->update_user_balance($assetType = 'traditional', $balance = $new_buyer_balance_cash, $user_id = $buyer_id);

                            // Delete the row from Sell list And Buy list
                            $this->delete_order($this->top_sell_table, $available->orderId);
                            $this->delete_order($this->top_buy_table, $buy_order_id);

                            // save changes
                            $this->demanded_qty = 0;
                            $available->Quantity = 0;

                        } elseif ($this->demanded_qty < $available->Quantity) {

                            $cost_of_total_supply = $this->demanded_qty * $available->price; // traditional or cash
                            $new_seller_balance_cash = $seller_balance_cash + $cost_of_total_supply;  // traditional or cash
                            $new_seller_balance_btc = $seller_balance_btc - $this->demanded_qty; // deduct the btc sold

                            $new_buyer_balance_btc = $buyer_balance_btc + $this->demanded_qty; // btc
                            $new_buyer_balance_cash = $buyer_balance_cash - $cost_of_total_supply; // traditional or cash

                            $availableQuantity = $available->Quantity - $this->demanded_qty;
                            // subtract the debit access (customers balance of $ or BTC)
                            $this->update_user_balance($assetType = 'btc', $balance = $new_buyer_balance_btc, $user_id = $buyer_id);

                            // increment the credit asset (customers balance of $ or BTC)
                            $this->update_user_balance($assetType = 'traditional', $balance = $new_seller_balance_cash, $user_id = $seller_id);

                            // record the commission in the commission account
                            // decrease respective balances
                            $this->update_user_balance($assetType = 'btc', $balance = $new_seller_balance_btc, $user_id = $seller_id);
                            $this->update_user_balance($assetType = 'traditional', $balance = $new_buyer_balance_cash, $user_id = $buyer_id);

                            // update the quantity field for $availableQuantity
                            $this->update_quantity($top_table = $this->top_sell_table, $availableQuantity, $available->orderId);

                            // Delete the row from Buy list
                            $this->delete_order($this->top_buy_table, $buy_order_id);

                            // save changes
                            $this->demanded_qty = 0;

                        }

                        // Record the transaction
                        $this->record_transaction($buyer_id,$buy_order_id, $buy_amount, $buy_commission='0', $seller_id, $available->orderId, $available->price, $sell_commission = '0');
                    } else {
                        return false;
                    }
                    $this->OrderMatchingQuery();
                }
                return true;
            }
            return false;
        }
        return false;
    }

    private function record_transaction($buyer, $buy_order_id, $buy_amount, $buy_commission, $seller, $sell_order_id, $sell_amount, $sell_commission) {
        if ($this->databaseConnection()) {

            $query = $this->db_connection->prepare("
            INSERT INTO $this->transaction_table(`TransactionId`, `a_buyer`, `A_OrderId`, `A_Amount`, `A_Commission`, `b_seller`, `B_OrderId`, `B_Amount`, `B_Commission`, `UpdateDate`, `InsertDate`, `SaveDate`)
            VALUES ('', :buyer,:buy_order_id, :buy_amount, :buy_commission, :seller, :sell_order_id, :sell_amount, :sell_commission, NULL, NOW(), NOW())
            ");
            $query->bindParam("buyer", $buyer);
            $query->bindParam("buy_order_id", $buy_order_id);
            $query->bindParam("buy_amount", $buy_amount);
            $query->bindParam("buy_commission", $buy_commission);
            $query->bindParam("seller", $seller);
            $query->bindParam("sell_order_id", $sell_order_id);
            $query->bindParam("sell_amount", $sell_amount);
            $query->bindParam("sell_commission", $sell_commission);
            if($query->execute()) {
                return true;
            }
        }
        return false;
    }

    private function delete_order($top_table, $orderId) {
        if ($this->databaseConnection()) {
            $query = $this->db_connection->prepare("DELETE FROM `$top_table` WHERE `orderId`=:id LIMIT 1");
            $query->bindParam('id', $orderId);
            if($query->execute()) {
                return true;
            }
            return false;
        }
        return false;
    }

    private function update_quantity($top_table, $qty, $orderId) {

        if ($this->databaseConnection()) {
            $query = $this->db_connection->prepare("
                UPDATE $top_table
                SET `quantity`= :qty, `insertDate`=NOW()
                WHERE orderId = :orderId
                LIMIT 1
            ");
            $query->bindParam('qty', $qty);
            $query->bindParam('orderId', $orderId);
            if($query->execute()) {
                return true;
            }
            return false;
        }
        return false;
    }

    private function UpdateOrderStatus($order_id) {
        if ($this->databaseConnection()) {
            $query = $this->db_connection->prepare("
                UPDATE `orderbook` SET `OrderStatusId`= '1' WHERE `OrderId`= :id LIMIT 1
            ");
            $query->bindParam(':id', $order_id);
            if ($query->execute()) {
                return true;
            }
        }
        return false;
    }

    private function insert_market_order($customerId, $orderTypeId, $OfferAssetTypeId=null, $WantAssetTypeId=null, $qty, $price) {
        if ($this->databaseConnection()) {

            $query = $this->db_connection->prepare("INSERT INTO $this->orders_table (`OrderId`, `CustomerId`, `OrderTypeId`, `OfferAssetTypeId`, `WantAssetTypeId`, `Quantity`, `Price`, `OrderStatusId`, `MarketOrder`, `UpdateDate`, `InsertDate`, `SaveDate`)
                                VALUES ('', :u, :a, :d, :e, :b, :c, 1, 1, NULL, Now(), NULL)
                              ");
            $query->bindParam(':u', $customerId, PDO::PARAM_INT);
            $query->bindParam(':a', $orderTypeId, PDO::PARAM_INT);
            $query->bindParam(':d', $OfferAssetTypeId, PDO::PARAM_STR);
            $query->bindParam(':e', $WantAssetTypeId, PDO::PARAM_STR);
            $query->bindParam(':b', $qty, PDO::PARAM_STR);
            $query->bindParam(':c', $price, PDO::PARAM_STR);

            if ($query->execute()) {
                $insertedrowid = $this->db_connection->lastInsertId();
                return (int) $insertedrowid;
            }
            return false;
        }
        return false;
    }

    public function market_order($order_type, $qty) {

        if ($this->databaseConnection()) {

            $message = array();

         // Check if it is a buy or sell
            if ($order_type == 'buy') {

                if(is_float($qty) || is_int($qty)) {
                    if ($qty > 0) {

                        $sell_list = $this->get_top_buy_sell_list($top_table='active_selling_list', $asc_desc='ASC');

                        if (!empty($sell_list)) {
                            foreach ($sell_list as $available) {

                                $available->Quantity = (float)$available->Quantity;

                                if ($available->Quantity <= 0 || $qty <= 0) {
                                    if (isset($message)) {
                                        if ($available->Quantity <= 0) {
                                            $message[] = "The available asset is nill.";
                                        } else if($qty <= 0) {
                                            //$message[] = "The demanded asset is nill.";
                                        }
                                        return $message;
                                    } else {
                                        exit;
                                    }
                                }

                                $seller_id = $available->customerId;
                                $seller_cash_balance = (float) $this->check_customer_balance($assetType = 'traditional', $seller_id)->Balance;
                                $seller_bit_balance = (float) $this->check_customer_balance($assetType = 'btc', $seller_id)->Balance;
                                $buyer_id = $_SESSION['user_id'];
                                $buyer_cash_balance = (float) $this->check_customer_balance($assetType = 'traditional', $buyer_id)->Balance;
                                $buyer_bit_balance = (float) $this->check_customer_balance($assetType = 'btc', $buyer_id)->Balance;

                                switch ($qty) {
                                    case ($qty > $available->Quantity):

                                        $cost_of_total_supply = $available->Quantity * $available->Price; // traditional or cash

                                        if ($buyer_cash_balance < $cost_of_total_supply) {
                                            $message[] = "Transaction failed: You have insufficient cash balance.";
                                            return $message;
                                        }
                                        if ($seller_bit_balance == 0) {
                                            $message[] = "Transaction failed: The seller has insufficient FLO balance";
                                            return $message;
                                        }

                                        $new_seller_cash_balance = $seller_cash_balance + $cost_of_total_supply;  // traditional or cash
                                        $new_seller_bit_balance = $seller_bit_balance - $available->Quantity;
                                        $new_buyer_bit_balance = $buyer_bit_balance + $available->Quantity; // traditional or cash
                                        $new_buyer_cash_balance = $buyer_cash_balance - $cost_of_total_supply; // traditional

                                        $insert_market_order = $this->insert_market_order($_SESSION['user_id'], $orderTypeId='0', $OfferAssetTypeId='INR', $WantAssetTypeId='FLO', $available->Quantity, $available->Price);

                                        $buy_order_id = 0;
                                        if($insert_market_order == false) {
                                            return false;
                                        } else if(is_int($insert_market_order)) {
                                            $buy_order_id = (int)$insert_market_order;
                                        } else {
                                            return false;
                                        }

                                        // increment the bits of buyer
                                        $this->update_user_balance($assetType = 'btc', $balance = $new_buyer_bit_balance, $user_id = $buyer_id);

                                        // deduct cash of buyer
                                        $this->update_user_balance($assetType = 'traditional', $balance = $new_buyer_cash_balance, $user_id = $buyer_id);

                                        // increase the cash of seller
                                        $this->update_user_balance($assetType = 'traditional', $balance = $new_seller_cash_balance, $user_id = $seller_id);

                                        // deduct bits of seller
                                        $this->update_user_balance($assetType = 'btc', $balance = $new_seller_bit_balance, $user_id = $seller_id);

                                        // Record the transaction
                                        $this->record_transaction($buyer_id, $buy_order_id, $available->Price, $buy_commission='0', $seller_id, $available->OrderId, $available->Price, $sell_commission = '0');

                                        // Delete the row from Sell list
                                        $this->delete_order($this->top_sell_table, $available->OrderId);

                                        // Update Order Status in Orderbook
                                        $this->UpdateOrderStatus($buy_order_id);

                                        $message[] = "You bought $available->Quantity coins for $cost_of_total_supply.";

                                        $qty = $qty - $available->Quantity;

                                        // save changes
                                        break;
                                    case ($qty == $available->Quantity):

                                        $cost_of_total_supply = $available->Quantity * $available->Price; // traditional or cash

                                        if ($buyer_cash_balance < $cost_of_total_supply) {
                                            $message[] = "Transaction failed: You have insufficient cash balance.";
                                            return $message;
                                        }
                                        if ($seller_bit_balance == 0) {
                                            $message[] = "Transaction failed: The seller has insufficient FLO balance";
                                            return $message;
                                        }

                                        $new_seller_cash_balance = $seller_cash_balance + $cost_of_total_supply;  // traditional or cash
                                        $new_seller_bit_balance = $seller_bit_balance - $qty;
                                        $new_buyer_cash_balance = $buyer_cash_balance - $cost_of_total_supply; // traditional
                                        $new_buyer_bit_balance = $buyer_bit_balance + $available->Quantity; // traditional or cash

                                        $insert_market_order = $this->insert_market_order($_SESSION['user_id'], $orderTypeId='0', $OfferAssetTypeId='INR', $WantAssetTypeId='FLO', $available->Quantity, $available->Price);

                                        $buy_order_id = 0;
                                        if($insert_market_order == false) {
                                            return false;
                                        } else if(is_int($insert_market_order)) {
                                            $buy_order_id = (int)$insert_market_order;
                                        } else {
                                            return false;
                                        }

                                        // increment the bits of buyer
                                        $this->update_user_balance($assetType = 'btc', $balance = $new_buyer_bit_balance, $user_id = $buyer_id);

                                        // deduct cash of buyer
                                        $this->update_user_balance($assetType = 'traditional', $balance = $new_buyer_cash_balance, $user_id = $buyer_id);

                                        // increase the cash of seller
                                        $this->update_user_balance($assetType = 'traditional', $balance = $new_seller_cash_balance, $user_id = $seller_id);

                                        // deduct bits of seller
                                        $this->update_user_balance($assetType = 'btc', $balance = $new_seller_bit_balance, $user_id = $seller_id);

                                        $message[] = "You bought $qty coins for $cost_of_total_supply.";

                                        $qty = $qty - $available->Quantity; // should be equal to 0

                                        // Record the transaction
                                        $this->record_transaction($buyer_id, $buy_order_id, $available->Price, $buy_commission='0', $seller_id, $available->OrderId, $available->Price, $sell_commission = '0');

                                        // Delete the row from Sell list
                                        $this->delete_order($this->top_sell_table, $available->OrderId);

                                        // Update Order Status in Orderbook
                                        $this->UpdateOrderStatus($buy_order_id);

                                        break;
                                    case ($qty < $available->Quantity):

                                        $cost_of_total_supply = $qty * $available->Price; // traditional or cash

                                        if ($buyer_cash_balance < $cost_of_total_supply) {
                                            $message[] = "Transaction failed: You have insufficient cash balance.";
                                            return $message;
                                        }
                                        if ($seller_bit_balance == 0) {
                                            $message[] = "Transaction failed: The seller has insufficient FLO balance";
                                            return $message;
                                        }

                                        $new_seller_cash_balance = $seller_cash_balance + $cost_of_total_supply;  // traditional or cash
                                        $new_seller_bit_balance = $seller_bit_balance - $qty;
                                        $new_buyer_cash_balance = $buyer_cash_balance - $cost_of_total_supply; // traditional
                                        $new_buyer_bit_balance = $buyer_bit_balance + $qty; // traditional or cash

                                        $insert_market_order = $this->insert_market_order($_SESSION['user_id'], $orderTypeId='0', $OfferAssetTypeId='INR', $WantAssetTypeId='FLO', $qty, $available->Price);

                                        $buy_order_id = 0;
                                        if($insert_market_order == false) {
                                            return false;
                                        } else if(is_int($insert_market_order)) {
                                            $buy_order_id = (int)$insert_market_order;
                                        } else {
                                            return false;
                                        }

                                        // increment the bits of buyer
                                        $this->update_user_balance($assetType = 'btc', $balance = $new_buyer_bit_balance, $user_id = $buyer_id);

                                        // deduct cash of buyer
                                        $this->update_user_balance($assetType = 'traditional', $balance = $new_buyer_cash_balance, $user_id = $buyer_id);

                                        // increase the cash of seller
                                        $this->update_user_balance($assetType = 'traditional', $balance = $new_seller_cash_balance, $user_id = $seller_id);

                                        // deduct bits of seller
                                        $this->update_user_balance($assetType = 'btc', $balance = $new_seller_bit_balance, $user_id = $seller_id);

                                        // Record the transaction
                                        $this->record_transaction($buyer_id, $buy_order_id, $available->Price, $buy_commission='0', $seller_id, $available->OrderId, $available->Price, $sell_commission = '0');

                                        $available->Quantity = $available->Quantity - $qty;

                                        // update the quantity field for supply
                                        $this->update_quantity($top_table = $this->top_sell_table, $available->Quantity, $available->OrderId);

                                        // Update Order Status in Orderbook
                                        $this->UpdateOrderStatus($buy_order_id);

                                        $message[] = "You bought $qty coins for $cost_of_total_supply.";

                                        // update the quantity field for demand
                                        $qty = 0;

                                        break;
                                }
                            }
                            return $message;
                        } else {
                            $message[] = "empty_sell_list";
                            return $message;
                        }
                    }
                }

            } elseif ($order_type == 'sell') {
                if(is_float($qty) || is_int($qty)) {
                    if ($qty > 0) {

                        $buy_list = $this->get_top_buy_sell_list($top_table='active_buy_list', $asc_desc='DESC');

                        if (!empty($buy_list)) {
                            foreach ($buy_list as $available) {

                                $available->Quantity = (float)$available->Quantity;

                                if ($available->Quantity <= 0 || $qty <= 0) {
                                    if (isset($message)) {
                                        if ($available->Quantity <= 0) {
                                            $message[] = "The available asset is nill.";
                                        } else if($qty <= 0) {
                                            //$message[] = "The demanded asset is nill.";
                                        }
                                        return $message;
                                    } else {
                                        exit;
                                    }
                                }

                                $seller_id = $_SESSION['user_id'];
                                $seller_cash_balance = $this->check_customer_balance($assetType = 'traditional', $seller_id)->Balance;
                                $seller_bit_balance = $this->check_customer_balance($assetType = 'btc', $seller_id)->Balance;
                                $buyer_id = $available->customerId;
                                $buyer_cash_balance = $this->check_customer_balance($assetType = 'traditional', $buyer_id)->Balance;
                                $buyer_bit_balance = $this->check_customer_balance($assetType = 'btc', $buyer_id)->Balance;

                                switch ($qty) {
                                    case ($qty > $available->Quantity):

                                        $cost_of_total_supply = $available->Quantity * $available->Price; // traditional or cash

                                        if ($buyer_cash_balance < $cost_of_total_supply) {
                                            $message[] = "Transaction failed: The buyer has insufficient cash balance.";
                                            return $message;
                                        }
                                        if ($seller_bit_balance == 0) {
                                            $message[] = "Transaction failed: You have insufficient FLO balance.";
                                            return $message;
                                        }

                                        $new_seller_cash_balance = $seller_cash_balance + $cost_of_total_supply;  // traditional or cash
                                        $new_seller_bit_balance = $seller_bit_balance - $available->Quantity; // deduct the btc sold
                                        $new_buyer_cash_balance = $buyer_cash_balance - $cost_of_total_supply; // traditional
                                        $new_buyer_bit_balance = $buyer_bit_balance + $available->Quantity; // btc

                                        $insert_market_order = $this->insert_market_order($_SESSION['user_id'], $orderTypeId='1', $OfferAssetTypeId='FLO', $WantAssetTypeId='INR', $available->Quantity, $available->Price);

                                        $sell_order_id = 0;
                                        if($insert_market_order == false) {
                                            return false;
                                        } else if(is_int($insert_market_order)) {
                                            $sell_order_id = (int)$insert_market_order;
                                        } else {
                                            return false;
                                        }

                                        // increment the bits of buyer
                                        $this->update_user_balance($assetType = 'btc', $balance = $new_buyer_bit_balance, $user_id = $buyer_id);

                                        // deduct cash of buyer
                                        $this->update_user_balance($assetType = 'traditional', $balance = $new_buyer_cash_balance, $user_id = $buyer_id);

                                        // increase the cash of seller
                                        $this->update_user_balance($assetType = 'traditional', $balance = $new_seller_cash_balance, $user_id = $seller_id);

                                        // deduct bits of seller
                                        $this->update_user_balance($assetType = 'btc', $balance = $new_seller_bit_balance, $user_id = $seller_id);

                                        // Delete the row from buy list
                                        $this->delete_order($this->top_buy_table, $available->OrderId);

                                        // Update Order Status in Orderbook
                                        $this->UpdateOrderStatus($sell_order_id);

                                        // Record the transaction
                                        $this->record_transaction($buyer_id, $available->OrderId, $available->Price, $buy_commission='0', $seller_id, $sell_order_id, $available->Price, $sell_commission = '0');

                                        $message[] = "You sold $available->Quantity coins for $cost_of_total_supply.";

                                        $qty = $qty - $available->Quantity;

                                        break;
                                    case ($qty == $available->Quantity):

                                        $cost_of_total_supply = $available->Quantity * $available->Price; // traditional or cash

                                        if ($buyer_cash_balance < $cost_of_total_supply) {
                                            $message[] = "Transaction failed: The buyer has insufficient cash balance.";
                                            return $message;
                                        }
                                        if ($seller_bit_balance == 0) {
                                            $message[] = "Transaction failed: You have insufficient FLO balance.";
                                            return $message;
                                        }

                                        $new_seller_cash_balance = $seller_cash_balance + $cost_of_total_supply;  // traditional or cash
                                        $new_seller_bit_balance = $seller_bit_balance - $available->Quantity; // deduct the btc sold
                                        $new_buyer_cash_balance = $buyer_cash_balance - $cost_of_total_supply; // traditional
                                        $new_buyer_bit_balance = $buyer_bit_balance + $available->Quantity; // traditional or cash

                                        $insert_market_order = $this->insert_market_order($_SESSION['user_id'], $orderTypeId='1', $OfferAssetTypeId='FLO', $WantAssetTypeId='INR', $available->Quantity, $available->Price);

                                        $sell_order_id = 0;
                                        if($insert_market_order == false) {
                                            return false;
                                        } else if(is_int($insert_market_order)) {
                                            $sell_order_id = (int)$insert_market_order;
                                        } else {
                                            return false;
                                        }

                                        // increment the bits of buyer
                                        $this->update_user_balance($assetType = 'btc', $balance = $new_buyer_bit_balance, $user_id = $buyer_id);

                                        // deduct cash of buyer
                                        $this->update_user_balance($assetType = 'traditional', $balance = $new_buyer_cash_balance, $user_id = $buyer_id);

                                        // subtract the cash of seller
                                        $this->update_user_balance($assetType = 'traditional', $balance = $new_seller_cash_balance, $user_id = $seller_id);

                                        // deduct bits of seller
                                        $this->update_user_balance($assetType = 'btc', $balance = $new_seller_bit_balance, $user_id = $seller_id);

                                        $message[] = "You sold $qty coins for $cost_of_total_supply.";

                                        $qty = $qty - $available->Quantity;

                                        // Update Order Status in Orderbook
                                        $this->UpdateOrderStatus($sell_order_id);

                                        // Record the transaction
                                        $this->record_transaction($buyer_id, $available->OrderId, $available->Price, $buy_commission='0', $seller_id, $sell_order_id, $available->Price, $sell_commission = '0');

                                        // Delete the row from buy list
                                        $this->delete_order($this->top_buy_table, $available->OrderId);

                                        break;
                                    case ($qty < $available->Quantity):

                                        $available->Quantity = $available->Quantity - $qty;
                                        $cost_of_total_supply = $qty * $available->Price; // traditional or cash

                                        if ($buyer_cash_balance < $cost_of_total_supply) {
                                            $message[] = "Transaction failed: The buyer has insufficient cash balance.";
                                            return $message;
                                        }
                                        if ($seller_bit_balance == 0) {
                                            $message[] = "Transaction failed: You have insufficient FLO balance.";
                                            return $message;
                                        }

                                        $new_seller_cash_balance = $seller_cash_balance + $cost_of_total_supply;  // traditional or cash
                                        $new_seller_bit_balance = $seller_bit_balance - $qty; // deduct the btc sold
                                        $new_buyer_cash_balance = $buyer_cash_balance - $cost_of_total_supply; // traditional
                                        $new_buyer_bit_balance = $buyer_bit_balance + $qty; // traditional or cash

                                        $insert_market_order = $this->insert_market_order($_SESSION['user_id'], $orderTypeId='1', $OfferAssetTypeId='FLO', $WantAssetTypeId='INR', $qty, $available->Price);

                                        $sell_order_id = 0;
                                        if($insert_market_order == false) {
                                            return false;
                                        } else if(is_int($insert_market_order)) {
                                            $sell_order_id = (int)$insert_market_order;
                                        } else {
                                            return false;
                                        }

                                        // increment the bits of buyer
                                        $this->update_user_balance($assetType = 'btc', $balance = $new_buyer_bit_balance, $user_id = $buyer_id);

                                        // deduct cash of buyer
                                        $this->update_user_balance($assetType = 'traditional', $balance = $new_buyer_cash_balance, $user_id = $buyer_id);

                                        // subtract the cash of seller
                                        $this->update_user_balance($assetType = 'traditional', $balance = $new_seller_cash_balance, $user_id = $seller_id);

                                        // deduct bits of seller
                                        $this->update_user_balance($assetType = 'btc', $balance = $new_seller_bit_balance, $user_id = $seller_id);

                                        // Record the transaction
                                        $this->record_transaction($buyer_id, $available->OrderId, $available->Price, $buy_commission='0', $seller_id, $sell_order_id, $available->Price, $sell_commission = '0');

                                        // update the quantity field for supply
                                        $this->update_quantity($top_table = $this->top_buy_table, $available->Quantity, $available->OrderId);

                                        // Update Order Status in Orderbook
                                        $this->UpdateOrderStatus($sell_order_id);

                                        $message[] = "You sold $qty coins for $cost_of_total_supply.";

                                        // update the quantity field for demand
                                        $qty = 0;

                                        break;
                                }
                            }
                            return $message;
                        } else {
                            $message[] = "empty_buy_list";
                            return $message;
                        }
                    }
                }
            }
        }
        return false;
    }

    public function last_transaction_list() {
        if ($this->databaseConnection()) {

            $list = array();
            $query = $this->db_connection->query("
                SELECT TransactionId AS T_ID, a_buyer AS BUYER_ID, b_seller AS SELLER_ID, (SELECT customer.Name FROM customer WHERE customer.CustomerId=BUYER_ID) AS BUYER, (SELECT customer.Name FROM customer WHERE customer.CustomerId=SELLER_ID) AS SELLER, B_AMOUNT AS TRADE_PRICE, transaction.InsertDate
                FROM transaction, customer
                GROUP BY T_ID
                ORDER BY T_ID DESC
                LIMIT 50
            ");

            if ($query->rowCount() > 0) {
                while ($ls = $query->fetchObject()) {
                    $list[] = $ls;
                }
                return $list;
            }
            return false;
        }
        return false;
    }

    public function UserBalanceList() {
        if ($this->databaseConnection()) {

            $list = array();
            $query = $this->db_connection->query("
                SELECT customer.CustomerId AS UID, customer.Name, (SELECT assetbalance.Balance FROM assetbalance WHERE assetbalance.AssetTypeId='btc' AND assetbalance.CustomerId=UID) AS BTC, (SELECT assetbalance.Balance FROM assetbalance WHERE assetbalance.AssetTypeId='traditional' AND assetbalance.CustomerId=UID) AS CASH FROM customer, assetbalance GROUP BY UID ORDER BY MAX(BTC) DESC
            ");

            if ($query->rowCount() > 0) {
                while ($ls = $query->fetchObject()) {
                    $list[] = $ls;
                }
                return $list;
            }
            return false;
        }
        return false;
    }

    public function LastTradedPrice() {
        if ($this->databaseConnection()) {

            $query = $this->db_connection->query("
                SELECT `B_Amount` FROM `transaction` ORDER BY `InsertDate` DESC LIMIT 1
            ");

            if ($query->rowCount() == 1) {
                $ls = $query->fetchObject();
                return $ls;
            }
            return false;
        }
        return false;
    }

    public function UserOrdersList($user_id) {
        if ($this->databaseConnection()) {

            $list = array();
            $query = $this->db_connection->prepare("
            SELECT `OrderId`, `CustomerId`, `OrderTypeId`, `OfferAssetTypeId`, `WantAssetTypeId`, `Quantity`, `Price`, `OrderStatusId`, `MarketOrder`, `InsertDate`
            FROM `orderbook`
            WHERE `CustomerId`=:u_id
            ORDER  BY InsertDate DESC
            ");
            $query->bindParam('u_id', $user_id);
            if ($query->execute()) {
                if ($query->rowCount() > 0) {
                    while ($ls = $query->fetchObject()) {
                        $list[] = $ls;
                    }
                    return $list;
                }
            }
            return false;
        }
        return false;
    }
}

