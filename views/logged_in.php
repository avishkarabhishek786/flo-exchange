<?php
/**
 * Created by PhpStorm.
 * User: Abhishek Sinha
 * Date: 11/15/2016
 * Time: 5:02 PM
 */
?><?php
$action_class_market = 'fb_log_in';
$action_class_buy_sell = 'fb_log_in';
if ($user_logged_in) {
    $action_class_market = 'market_submit_btn';
    $action_class_buy_sell = 'process';
}
?>
<div class="container-fluid no-padding cover-price">
    <header>
        <div class="container margin-bottom-10">
            <?php $msg = array();
            if(isset($_GET['msg']) && $_GET['msg'] !== '') {
                $msg[] = $_GET['msg'];
                foreach ($msg as $ms) { ?>
                    <div class="row">
                        <ul id="error_msg">
                            <div class="alert alert-danger alert-dismissible margin-top-10" role="alert">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <?=$ms?>
                            </div>
                        </ul>
                    </div>
                <?php } } ?>
            <div class="row">
                <?php
                if (isset($customer_orders)) {
                    $LastTradedPrice = $customer_orders->LastTradedPrice();
                    $LastTradedPrice = ($LastTradedPrice !=Null) ? '₹ '. $LastTradedPrice->B_Amount : 'No Data';
                    echo '<h2 class="text-success">Last Traded Price: '.$LastTradedPrice.'</h2>';
                }
                ?>
            </div>
        </div>
    </header>
</div>
<div class="container-fluid no-padding cover">
    <header>
        <div class="container margin-bottom-10">

            <div class="row">
                <div class="col-md-4">
                    <div class="page-header">
                        <h2>Market Order</h2>
                    </div>
                    <br/>
                    <div>
                        <div class="panel-body">
                            <label for="market_order">Enter Quantity: </label><input type="text" name="market" id="market_order" class="form-control input-types"/><br/>
                            <div class="align-center">
                                <button type="button" id="market_buy_btn" class="btn btn-default button btn-sm <?=$action_class_market?>">Market Buy</button>
                                <button type="button" id="market_sell_btn" class="btn btn-default button btn-sm <?=$action_class_market?>">Market Sell</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="page-header">
                        <h2>WANT TO BUY</h2>
                    </div>
                    <br/>
                    <div>
                        <div class="panel-body">
                            <label for="buy_btc">Enter quantity: </label><input type="text" name="buy_btc" id="buy_btc" class="form-control input-types"/><br/>
                            <label for="buy_btc_price">Enter Price: </label><input type="text" name="buy_btc_price" id="buy_btc_price" class="form-control input-types"/>
                            <br/>
                            <button type="button" id="buy_btn" class="btn btn-default button btn-sm <?=$action_class_buy_sell?>">Buy</button>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="page-header">
                        <h2>WANT TO SELL</h2>
                    </div>
                    <br/>
                    <div>
                        <div class="panel-body">
                            <label for="sell_btc">Enter quantity: </label><input type="text" name="sell_btc" id="sell_btc" class="form-control input-types"/><br/>
                            <label for="sell_btc_price">Enter price: </label><input type="text" name="sell_btc_price" id="sell_btc_price" class="form-control input-types"/>
                            <br/>
                            <button type="button" id="sell_btn" class="btn btn-sm btn-default button <?=$action_class_buy_sell?>">Sell</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
</div>

<div class="container-fluid box-sea-green">
    <div class="container margin-bottom-10 margin-top-10">
        <div class="row">
            <div class="col-md-6 buy_region">
                <h3>Buy Order List </h3>

                <table class='table table-borderless'>
                    <thead>
                    <tr>
                        <th>Price: </th>
                        <th>Quantity: </th>
                        <th>Total: </th>
                    </tr>
                    </thead>
                    <tbody id="buying-list"></tbody>
                </table>
            </div>

            <div class="col-md-6 sell_region">
                <h3>Sell Order List</h3>

                <table class='table table-borderless'>
                    <thead>
                    <tr>
                        <th>Price: </th>
                        <th>Quantity: </th>
                        <th>Total: </th>
                    </tr>
                    </thead>
                    <tbody id="selling-list"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php if (isset($customer_orders)) {
    $tradersList = $customer_orders->UserBalanceList();
    if (is_array($tradersList) && !empty($tradersList)) { ?>

        <div class="container-fluid box-green">
            <div class="row">
                <div class="container margin-bottom-10 margin-top-10">
                    <div class="row">
                        <div class="col-md-6">

                            <?php if(isset($_SESSION['user_id'])) {
                                $MyTransactions = $customer->displayUserTransaction($_SESSION['user_id']);
                                if ($MyTransactions != null && is_array($MyTransactions) && !empty($MyTransactions)) { ?>

                                    <div class="margin-padd-2">
                                        <h3>My Transactions: </h3>
                                        <table class="table table-borderless">
                                            <thead>
                                            <tr>
                                                <th>Seller: </th>
                                                <th>Buyer: </th>
                                                <th>Trade Price: </th>
                                                <th>Trade Date: </th>
                                            </tr>
                                            </thead>
                                            <?php foreach($MyTransactions as $MyTransaction): ?>
                                                <tbody id="traders-list">
                                                <tr>
                                                    <td><?=$MyTransaction->SELLER?></td>
                                                    <td><?=$MyTransaction->BUYER; ?></td>
                                                    <td>₹ <?=$MyTransaction->TRADE_PRICE; ?></td>
                                                    <td><?=strftime("%d %B, %Y" . ' at ' . " %X ", strtotime($MyTransaction->InsertDate));?></td>
                                                </tr>
                                                </tbody>
                                            <?php endforeach; ?>
                                        </table>
                                    </div>
                                <?php }} ?>

                            <div class="margin-padd-2">
                                <h3>Traders List</h3>
                                <table class="table table-borderless">
                                    <thead>
                                    <tr>
                                        <th>Trader name: </th>
                                        <th>FLO: </th>
                                        <th>INR: </th>
                                    </tr>
                                    </thead>
                                    <?php foreach($tradersList as $trader): ?>
                                        <tbody id="traders-list">
                                        <tr>
                                            <td><?=$trader->Name?></td>
                                            <td><?=$trader->BTC == null ? 0 : $trader->BTC; ?></td>
                                            <td>₹ <?=$trader->CASH == null ? 0 : $trader->CASH; ?></td>
                                        </tr>
                                        </tbody>
                                    <?php endforeach; ?>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="margin-padd-2">
                                <?php if (isset($customer_orders)) {
                                    $transactionList = $customer_orders->last_transaction_list();
                                    if (is_array($transactionList) && !empty($transactionList)) {
                                        ?>
                                        <h3>Recent Transactions</h3>
                                        <table class="table table-borderless">
                                            <thead>
                                            <tr>
                                                <th>Seller: </th>
                                                <th>Buyer: </th>
                                                <th>Trade Price: </th>
                                                <th>Trade Date: </th>
                                            </tr>
                                            </thead>
                                            <?php foreach($transactionList as $transaction): ?>
                                                <tbody id="trade-list"></tbody>
                                            <?php endforeach; ?>
                                        </table>
                                    <?php }} ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php }} ?>

<?php if (isset($customer_orders, $_SESSION['user_id'])) {
    $myOrders = $customer_orders->UserOrdersList($_SESSION['user_id']);
    if (is_array($myOrders) && !empty($myOrders)) {
?>

<div class="container-fluid box-blue">
    <div class="row">
        <div class="container margin-bottom-10 margin-top-10">
            <div class="row">
                <div class="col-md-12">
                    <h2>My Orders: </h2>
                    <table class="table">
                        <thead>
                          <tr>
                            <th>Item Sold</th>
                            <th>Item Bought</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Status</th>
                            <th>Date</th>
                          </tr>
                        </thead>
                        <tbody>
                <?php foreach($myOrders as $myOrder):
                    if($myOrder->OrderStatusId == '1') {
                        $status = 'Successful';
                    } else if ($myOrder->OrderStatusId == '2') {
                        $status = 'Pending';
                    } else {
                        $status = 'Cancelled';
                    }

                    if($myOrder->OrderTypeId == '1') {
                        $OrderType = 'Sell';
                    } elseif($myOrder->OrderTypeId == '0') {
                        $OrderType = 'Buy';
                    }
                ?>
                          <tr>
                            <td><?=$myOrder->OfferAssetTypeId; ?></td>
                            <td><?=$myOrder->WantAssetTypeId; ?></td>
                            <td><?=$myOrder->Price; ?></td>
                            <td><?=$myOrder->Quantity; ?></td>
                            <td><?=$status; ?></td>
                            <td><?=strftime("%d %B, %Y" . ' at ' . " %X ", strtotime($myOrder->InsertDate));?></td>
                          </tr>
                <?php endforeach; ?>
                        </tbody>
                      </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php }} ?>