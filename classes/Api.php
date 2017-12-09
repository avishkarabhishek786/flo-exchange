<?php

/**
 * Created by PhpStorm.
 * User: Abhishek Kumar Sinha
 * Date: 11/7/2017
 * Time: 3:31 PM
 */
class Api extends Orders {

    public function trade_volume($t2, $t1) {

        if ($this->databaseConnection()) {
            if (trim($t1)==null || trim($t2)==null) {
                return false;
            }

            $query = $this->db_connection->prepare(
                "SELECT *, DAYOFWEEK(InsertDate) as DWNum FROM ".ORDERS_TABLE." WHERE `InsertDate` BETWEEN :t2 AND :t1"
            );
            $query->bindParam('t2', $t2);
            $query->bindParam('t1', $t1);

            $query->execute();

            $vol_arr = array();

            if ($cffuount = $query->rowCount() > 0) {
                while ($vol = $query->fetchObject()) {
                    $vol_arr[] = $vol;
                }
            }
            return $vol_arr;
        }
        return false;
    }

    public function user_token_to_total_tokens_ratio($user_id, $asset_type) {
        if ($this->databaseConnection()) {

            $assets = array(
                'user' => null,
                'total' => null
            );

            $query = $this->db_connection->prepare(
                "SELECT SUM(Balance) AS TOTAL_BAL, 
                (SELECT Balance FROM ".CREDITS_TABLE." WHERE `AssetTypeId`=:ast AND `CustomerId`= :u_id) AS USER_BAL 
                 FROM ".CREDITS_TABLE." WHERE `AssetTypeId`=:ast LIMIT 1"
            );
            $query->bindParam('u_id', $user_id);
            $query->bindParam('ast', $asset_type);

            $query->execute();

            if ($query->rowCount() ==1) {
               $balances = $query->fetchObject();
                $assets['user'] = (float) $balances->USER_BAL;
                $assets['total'] = (float) $balances->TOTAL_BAL;
            }
            return $assets;
        }
        return false;
    }
    
    public function total_assets($asset_type=null) {
        $total_asset = null;
        if ($this->databaseConnection()) {
            $query = $this->db_connection->prepare(
                "SELECT SUM(Balance) AS TOTAL_BAL
                 FROM ".CREDITS_TABLE." WHERE `AssetTypeId`= :ast LIMIT 1
                ");
            $query->bindParam('ast', $asset_type);

            $query->execute();

            if ($query->rowCount() == 1) {
                $balances = $query->fetchObject();
                $total_asset = (float) $balances->TOTAL_BAL;
            }
        }
        return $total_asset;
    }
    
    public function TradedPriceHistory($t2, $t1) {
        if ($this->databaseConnection()) {

            $query = $this->db_connection->prepare("
                SELECT `B_Amount`,InsertDate FROM ".TRANSACTIONS_TABLE." 
                WHERE InsertDate BETWEEN :t2 AND :t1
                ORDER BY `InsertDate` ASC
            ");

            $query->bindParam('t2', $t2);
            $query->bindParam('t1', $t1);

            $query->execute();

            $vol_arr = array();

            if ($cffuount = $query->rowCount() > 0) {
                while ($vol = $query->fetchObject()) {
                    $vol_arr[] = $vol;
                }
            }
            return $vol_arr;
        }
        return false;
    }
    
    public function number_of_tokens_on_buy_sell() {
        if ($this->databaseConnection()) {
            $total = array();

            $query = $this->db_connection->query(
                "SELECT  (
                    SELECT SUM(quantity)
                    FROM ".TOP_BUYS_TABLE."
                    ) AS TOTAL_BUYS,
                    ( SELECT SUM(quantity)
                    FROM   ".TOP_SELL_TABLE."
                    ) AS TOTAL_SELLS,
                    ( SELECT SUM(Balance)
                    FROM   ".CREDITS_TABLE."
                    WHERE AssetTypeId='btc'     
                    ) AS TOTAL_TOKEN_BALANCE
                ");

            $query->execute();

            $arr = [
              'token_on_buys'=>0,
              'token_on_sells'=>0,
              'all_tokens'=>0
            ];

            if ($query->rowCount() > 0) {
                foreach ($query->fetchObject() as $q) {
                    $total[] = $q;
                }
                $arr['token_on_buys'] = $total[0];
                $arr['token_on_sells'] = $total[1];
                $arr['all_tokens'] = $total[2];
            }

            return $arr;
        }
        return false;
    }
    
    public function asset_gain_list() {
        if ($this->databaseConnection()) {

            $list = array();

            $ltp = (float) $this->LastTradedPrice()->B_Amount;

            $query = $this->db_connection->query("
                SELECT DISTINCT(".TRANSACTIONS_TABLE.".a_buyer) AS INVESTOR_ID, (SELECT Name FROM ".USERS_TABLE." WHERE CustomerId = INVESTOR_ID) AS INVESTOR, SUM(".TRANSACTIONS_TABLE.".qty_traded * ".TRANSACTIONS_TABLE.".B_Amount) AS INVESTMENT,  
                (SELECT Balance * ".$ltp." FROM ".CREDITS_TABLE." WHERE `AssetTypeId`='btc' AND `CustomerId`=INVESTOR_ID) AS CURRENT_VALUE
                FROM ".TRANSACTIONS_TABLE." 
                GROUP BY INVESTOR_ID
            ");

            $query->execute();

            if ($query->rowCount() > 0) {
                while ($li = $query->fetchObject()) {
                    $list[] = $li;
                }
            }
            return $list;
        }
        return false;
    }
    
    public function asset_value_by_month() {
        if ($this->databaseConnection()) {
            $query = $this->db_connection->query("
            SELECT SUM(qty_traded) AS QTY_TRADED, InsertDate, AVG(B_Amount) AS MAX_TRADE_PRICE
            FROM ".TRANSACTIONS_TABLE." GROUP BY MONTH(InsertDate)
            ");

            $arr = array();

            if ($query->rowCount() > 0) {
                while ($stmt = $query->fetchObject()) {
                    $arr[] = $stmt;
                }
            }
            return $arr;
        }
        return false;
    }

    
    public function my_actions_numbers() {
        if ($this->databaseConnection()) {

            $arr = array();
            $query = $this->db_connection->query("SELECT fund_type, COUNT(`fund_type`) AS TOTAL, datetime 
            FROM ".TRANSFER_INFO_TABLE." GROUP BY fund_type");
            while ($dat = $query->fetchObject()) {
                $arr[] = $dat;
            }
            return $arr;
        }
        return false;
    }

    public function week_wise_trade_volume($t2, $t1) {

        if ($this->databaseConnection()) {
            if (trim($t1)==null || trim($t2)==null) {
                return false;
            }

            $query = $this->db_connection->prepare(
                "SELECT *, (SELECT WEEK(InsertDate)) AS WEEK_NUM 
                 FROM ".ORDERS_TABLE." WHERE `InsertDate` BETWEEN :t2 AND :t1
                 AND `OrderStatusId`= 1
                 ORDER BY InsertDate ASC"
            );
            $query->bindParam('t2', $t2);
            $query->bindParam('t1', $t1);

            $query->execute();

            $vol_arr = array();

            if ($cffuount = $query->rowCount() > 0) {
                while ($vol = $query->fetchObject()) {
                    $vol_arr[] = $vol;
                }
            }
            return $vol_arr;
        }
        return false;
    }




}