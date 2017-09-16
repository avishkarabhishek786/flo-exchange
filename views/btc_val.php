<?php
$url = "https://bitpay.com/api/rates";

$json = file_get_contents($url);
$data = json_decode($json, TRUE);

$rate = $data[1]["rate"];
$usd_price = 1;     # Let cost of elephant be 1$
$bitcoin_price = round( $usd_price / $rate , 8 );
?>

<div class="jumbotron">
    <h1>Current Price: <?=$usd_price ?> $ = <?=$bitcoin_price ?> BTC</h1>
    <p><a class="btn btn-primary btn-lg" href="https://en.wikipedia.org/wiki/Bitcoin" role="button" target="_blank">Learn more</a></p>
</div>






<?php
/*
Second method


$jsnsrc = "https://blockchain.info/ticker";
$json = file_get_contents($jsnsrc);
$json = json_decode($json);
$one_Btc_To_Brl = $json->BRL->last;

print "1 BTC = " . $one_Btc_to_Brl;
*/?>