<?php 
ob_start();
date_default_timezone_set('Asia/Kolkata'); ?>
<?php $user_id = 0; ?>
<!--Bootstrap-->
<?php require_once 'views/header.php';?>

<?php include_once 'acc_deact.php';?>

<!--Buy Sell div-->
<?php include_once 'views/buy_sell_div.php'; ?>

<!--Buy Sell Lists-->
<?php include_once 'views/buy_sell_list.php'; ?>

<!--My Orders List-->
<?php include_once 'views/myOrdersList.php'; ?>

<!--Traders and Transactions List-->
<?php include_once 'views/traders_trans_list.php'; ?>

<!--Messages-->
<?php include_once 'views/user_messages.php'; ?>

<!--footer-->
<?php include_once 'footer.php'; ?>