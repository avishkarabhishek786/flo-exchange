<?php
if ($user_logged_in) { ?>

    <div class="container-fluid background-white-1 mt--4 p--2">

        <div class="container">
            <div class="col-lg-6">
                <h4 class="mt--2">My Orders</h4>
                <h5><a href="My_Orders" target="_blank">View All</a></h5>
                <div class="table-responsive mt--2">
                    <table class="table table-striped" cellpadding="10" cellspacing="0" width="100%">
                        <thead>
                        <tr>
                            <th>Item Sold</th>
                            <th>Item Bought</th>
                            <th>Price ($)</th>
                            <th>Quantity</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Cancel Order</th>
                        </tr>
                        </thead>
                        <tbody id="myOrdersTable"></tbody>
                    </table>
                </div>
            </div>

            <div class="col-lg-6">
                <h4 class="mt--2">My Transactions: </h4>
                <h5><a href="My_Transactions" target="_blank">View All</a></h5>
                <div class="table-responsive mt--2">
                    <table class="table table-striped" cellpadding="10">
                        <thead>
                        <tr>
                            <th>Seller: </th>
                            <th>Buyer: </th>
                            <th>Trade Price: </th>
                            <th>Quantity Traded: </th>
                            <th>Transaction Amount: </th>
                            <th>Trade Date: </th>
                        </tr>
                        </thead>
                        <tbody id="my-transactions-list"></tbody>
                        <?php if(isset($user_id) && $user_id !=null) {
                            $MyTransactions = $OrderClass->displayUserTransaction($user_id, 0, 10);?>
                            <?php if ($MyTransactions == null || !is_array($MyTransactions) || empty($MyTransactions)) { ?>
                            <p class="text-info">No transactions!</p>
                            <?php }} ?>
                    </table>

                </div>
            </div>

        </div>
    </div>
<?php } ?>