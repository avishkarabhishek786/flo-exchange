<?php if (isset($OrderClass)) { ?>

    <h4>Trader's list</h4>
    <h5><a href="traders" target="_blank">View All</a></h5>
    <?php if ( isset($tradersList) && is_array($tradersList) && !empty($tradersList)) { ?>

    <table class="table table-striped " cellpadding="10">

        <thead>

        <tr>

            <th>  Trader Name</th>

            <th>  Tokens</th>

            <th> Cash ($)</th>

        </tr>

        </thead>
        <tbody id="traders-list"></tbody>
    </table>
<?php } else {

        echo '<h4>No traders found!</h4>';
}} ?>
