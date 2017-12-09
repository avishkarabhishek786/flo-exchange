<?php
/**
 * Created by PhpStorm.
 * User: Abhishek Kumar Sinha
 * Date: 10/3/2017
 * Time: 5:07 PM
 */

if (isset($tradersList) && is_array($tradersList) && !empty($tradersList)) { ?>

    <div class="container-fluid p--2 background-white-1 mt--4">
        <div class="container">

            <div class="col-lg-6">
                <?php include_once 'traders_list.php'; ?>
            </div>
            
            <div class="col-lg-6">
                <h4>Recent Transactions</h4>
                <h5><a href="Recent_Transactions" target="_blank">View All</a></h5>
                <div class="table-responsive mt--2">
                    <table class="table table-borderless table-striped">
                    <thead>
                    <tr>
                        <th>Seller: </th>
                        <th>Buyer: </th>
                        <th>Trade Price: </th>
                        <th>Trade Qty: </th>
                        <th>Transaction Amount: </th>
                        <th>Trade Date: </th>
                    </tr>
                    </thead>
                    <tbody id="trade-list"></tbody>
                </table>
                </div>
            </div>

        </div>
    </div>
<?php }