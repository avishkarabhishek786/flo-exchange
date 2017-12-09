<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Carbon\Carbon;
use Carbon\CarbonInterval;

require '../includes/imp_files.php';
require '../vendor/autoload.php';

$app = new \Slim\App;

if (isset($OrderClass, $UserClass)) {

    // Get Market Price
    $app->get('/market_price', function (Request $request, Response $response) {
        try {
            $OrderClass = new Orders();
            $stmt = $OrderClass->LastTradedPrice();
            $market_price = $stmt;

            echo json_encode($market_price);

        } catch (PDOException $e) {
            echo '{"error": {"text": ' . $e->getMessage() . '}}';
        }
    });

    // Get Trade Volume in last 24 hours
    $app->get("/trade_volume_in_24hr/{start}/{end}", function (Request $request, Response $response) {
        try {

            $starting = (string) trim($request->getAttribute('start'));
            $ending = (string) trim($request->getAttribute('end'));

            $t1 = Carbon::now()->subDays($starting)->format('Y-m-d 00:00:00');
            $t2 = Carbon::now()->subDays($ending)->format('Y-m-d 00:00:00');

            $ApiClass = new Api();
            $vol = $ApiClass->trade_volume($t2, $t1);
            $volume = $vol;
            echo json_encode($volume);

        } catch (PDOException $e) {
            echo '{"error": {"text": ' . $e->getMessage() . '}}';
        }
    });

    // Get user to total balance ratio
    $app->get("/token_ratio/{user_id}/{asset_type}", function (Request $request, Response $response) {
        try {

            $uid = (string) trim($request->getAttribute('user_id'));
            $asset = (string) trim($request->getAttribute('asset_type'));

            if ($asset == 'rmt') {
                $asset = 'btc';
            } else if($asset == 'cash') {
                $asset = 'traditional';
            } else {
                echo '{"error": {"text": Invalid asset!}}';
                return false;
            }

            $ApiClass = new Api();
            $ratio = $ApiClass->user_token_to_total_tokens_ratio($uid, $asset);
            $ratio_bal = $ratio;
            echo json_encode($ratio_bal);

        } catch (PDOException $e) {
            echo '{"error": {"text": ' . $e->getMessage() . '}}';
        }
    });
    
    // Get total token volume and market cap
    $app->get("/market_cap", function (Request $request, Response $response) {
        try {
            $OrderClass = new Orders();
            $ApiClass = new Api();
            $total_tokens_sold = $ApiClass->total_assets('btc');
            $total = $total_tokens_sold;
            $ltp = $OrderClass->LastTradedPrice();
            
            $obj = [
                'total_token_sold'=> $total,
                'last_traded_price'=> $ltp 
            ];
            
            echo json_encode($obj);
        } catch (PDOException $e) {
            echo '{"error": {"text": ' . $e->getMessage() . '}}';
        }
    });
    
    // Traders list
    $app->get("/traders_list", function(Request $request, Response $response) {
        try {
            $OrderClass = new Orders();
            $stmt = $OrderClass->UserBalanceList(1);
            $list = $stmt;

            echo json_encode($list);

        } catch (PDOException $e) {
            echo '{"error": {"text": ' . $e->getMessage() . '}}';
        }      
    });
    
    // Trade Price History
    $app->get("/trade_price_history/{start}/{end}", function(Request $request, Response $response) {
        try {

            $starting = (string) trim($request->getAttribute('start'));
            $ending = (string) trim($request->getAttribute('end'));

            $t1 = Carbon::now()->subDays($starting)->format('Y-m-d 00:00:00');
            $t2 = Carbon::now()->subDays($ending)->format('Y-m-d 00:00:00');

            $ApiClass = new Api();
            $stmt = $ApiClass->TradedPriceHistory($t2, $t1);
            $list = $stmt;

            echo json_encode($list);

        } catch (PDOException $e) {
            echo '{"error": {"text": ' . $e->getMessage() . '}}';
        }
    });
    
    // Number of token in Buy or Sell
    $app->get("/tokens_on_trade", function (Request $request, Response $response) {
        try {
            $ApiClass = new Api();
            $total_tokens = $ApiClass->number_of_tokens_on_buy_sell();
            $total = $total_tokens;
             echo json_encode($total);
        } catch (PDOException $e) {
            echo '{"error": {"text": ' . $e->getMessage() . '}}';
        }
    });
    
    // Asset gain list
    $app->get("/returns", function (Request $request, Response $response) {
        try {
            $ApiClass = new Api();
            $return_list = $ApiClass->asset_gain_list();
            $total = $return_list;
            echo json_encode($total);
        } catch (PDOException $e) {
            echo '{"error": {"text": ' . $e->getMessage() . '}}';
        }
    });
    
    // Asset value by month
    $app->get("/asset_by_month", function (Request $request, Response $response) {
        try {
            $ApiClass = new Api();
            $asset_list = $ApiClass->asset_value_by_month();
            $total = $asset_list;
            echo json_encode($total);
        } catch (PDOException $e) {
            echo '{"error": {"text": ' . $e->getMessage() . '}}';
        }
    });
    
    // My Actions
    $app->get("/my_actions", function (Request $request, Response $response) {
        try {
            $ApiClass = new Api();
            $action_list = $ApiClass->my_actions_numbers();
            $total = $action_list;
            echo json_encode($total);
        } catch (PDOException $e) {
            echo '{"error": {"text": ' . $e->getMessage() . '}}';
        }
    });
    
    // Get total number of users
    $app->get("/total_users", function (Request $request, Response $response) {
        try {
            $UserClass = new Users();
            $users_sum = $UserClass->get_total_users_count();
            $total = $users_sum;
            echo json_encode($total);
        } catch (PDOException $e) {
            echo '{"error": {"text": ' . $e->getMessage() . '}}';
        }
    });
    
    
    // Get Trade Volume week wise
    $app->get("/trade_volume_by_week/{start}/{end}", function (Request $request, Response $response) {
        try {

            $starting = (string) trim($request->getAttribute('start'));
            $ending = (string) trim($request->getAttribute('end'));

            $t1 = Carbon::now()->subDays($starting)->format('Y-m-d 00:00:00');
            $t2 = Carbon::now()->subDays($ending)->format('Y-m-d 00:00:00');

            $ApiClass = new Api();
            $vol = $ApiClass->week_wise_trade_volume($t2, $t1);
            $volume = $vol;
            echo json_encode($volume);

        } catch (PDOException $e) {
            echo '{"error": {"text": ' . $e->getMessage() . '}}';
        }
    });

} else {
    echo '{"error": {"text": "API could not be loaded."}}';
}

$app->run();