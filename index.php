<?php
/**
 * Created by PhpStorm.
 * User: Abhishek Sinha
 * Date: 11/9/2016
 * Time: 12:41 PM
 */
session_start();

    require_once 'config/config.php';
    require_once 'classes/Orders.php';
    require_once 'classes/Users.php';

    $buy_list = array();
    $sell_list = array();

    if (class_exists('Users') && class_exists('Orders')) {

        $customer = new Users();
        $validate_user = null;
        if (isset($_SESSION['FBID'])):
            $fb_id = $_SESSION['FBID'];
        // check if user already registered
        $validate_user = $customer->is_fb_registered($fb_id);
        if($validate_user == "" || $validate_user == false) {
            header('Location: views/messages.php?msg= No such user exist. Please register.');
            exit;
        }
        endif;

        $customer_orders = new Orders();
        $buy_list[] = $customer_orders->get_top_buy_sell_list($top_table='active_buy_list', $asc_desc='DESC');  // buy
        $sell_list[] = $customer_orders->get_top_buy_sell_list($top_table='active_selling_list', $asc_desc='ASC');  // sell
    }

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ranchi Mall FZE Tokens</title>
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style/main.css"/>
    <!-- jQuery library -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>

    <!-- Latest compiled JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

    <link href="https://fonts.googleapis.com/css?family=Crimson+Text" rel="stylesheet">

    <script src="JS/main.js"></script>

</head>
<body>
<div class="container-fluid no-padding">
    <header>
        <nav class="navbar navbar-inverse">
            <div class="container-fluid">
                <!-- Brand and toggle get grouped for better mobile display -->
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="#">
                        <span class="glyphicon glyphicon-xbt" aria-hidden="true"></span> <span class="text-danger">Ranchi Mall FZE Tokens</span>
                    </a>
                </div>
                <?php
                include_once 'fbconfig.php';
                if(isset($_SESSION['FBID'],$_SESSION['facebook_access_token'], $_SESSION['FULLNAME'], $_SESSION['EMAIL'])) { ?>
                    <!-- Collect the nav links, forms, and other content for toggling -->
                    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                        <ul class="nav navbar-nav navbar-right">
                            <li><a><label for="my_bit_balance">FLO: </label><span id="my_bit_balance">No data</span>&nbsp;&nbsp;<label for="my_cash_balance">Cash: </label><span id="my_cash_balance">No data</span></a></li>
                            <li class="dropdown">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><?=$_SESSION['FULLNAME'];?> <span class="caret"></span></a>
                                <ul class="dropdown-menu">
                                    <li><a href="#">Action</a></li>
                                    <li><a href="#">Another action</a></li>
                                    <li><a href="#">Something else here</a></li>
                                    <li role="separator" class="divider"></li>
                                    <li><a href="logout.php">Log Out</a></li>
                                </ul>
                            </li>
                        </ul>
                    </div><!-- /.navbar-collapse -->
                <?php } else if(isset($loginUrl)):?>
                <ul class="nav navbar-nav navbar-right">
                    <li>
                        <a href="<?=$loginUrl?>" role="button" class="pull-right popup" name="fb_login">Login with Facebook</a>
                    </li>
                </ul>
                <?php endif;?>
            </div><!-- /.container-fluid -->
        </nav>
    </header>
</div>

    <?php
        $user_logged_in = false;
        if(isset($_SESSION['FBID'],$_SESSION['facebook_access_token'], $_SESSION['FULLNAME'], $_SESSION['EMAIL'])) {
            $user_logged_in = true;
        }

        include_once 'views/logged_in.php';
        include_once 'footer.php';
    ?>

</body>
</html>
