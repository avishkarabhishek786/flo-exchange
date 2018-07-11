<?php
/**
 * Created by PhpStorm.
 * User: Abhishek Sinha
 * Date: 11/12/2016
 * Time: 8:01 PM
 */

if(isset($_GET['msg'])) {
    $msg = $_GET['msg'];
    ?>

    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>ERROR</title>
        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">

        <!-- jQuery library -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>

        <!-- Latest compiled JavaScript -->
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

        <link rel="stylesheet" href="style/main.css"/>

    </head>
    <body>
        <div class="container">
            <div class="col-md-12">
                <div class="row">
                    <br/><br/>
                    <div class="alert alert-warning" role="alert">
                        <?=$msg?> <a href="../index.php">Register here</a>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>

<?php } ?>