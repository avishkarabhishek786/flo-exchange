<?php
/**
 * Created by PhpStorm.
 * User: Abhishek Kumar Sinha
 * Date: 10/5/2017
 * Time: 11:19 AM
 */

    $tradersList = array();

    if(isset($OrderClass)) {
        $tradersList = $OrderClass->UserBalanceList();
    }

    if(!empty($tradersList)) { ?>
        <div class="container">
            <div class="col-lg-12">
                <h2 class="mt--2">Traders List</h2>
                <div class="table-responsive mt--2">
                    <table class="table table-striped table-bordered" id="messages-datatable" cellspacing="0" width="100%" cellpadding="10">
                        <thead>
                        <tr>
                            <th>  Trader Name</th>

                            <th> RMT Tokens</th>

                            <th> Cash</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($tradersList as $index=>$trader) { ?>
                            <tr>
                                <td><?=$trader->Name?></td>
                                <td><?=$trader->BTC?></td>
                                <td><?=$trader->CASH?></td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    <?php } ?>

