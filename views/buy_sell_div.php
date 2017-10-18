<div class="container-fluid background--white animated fadeInUp">

    <div class="container">
        <div class="col-sm-4 ">
            <div class="box p--2 mt--4">
                <div class="row">

                    <h2 class="text-center">Buy or Sell immediately</h2>
                    <hr>
                    <div class="col-lg-12 lazy-form">
                        <label for="market_order">Quantity</label>
                        <input type="text" name="market" id="market_order">
                    </div>
                    <div class="col-lg-12 mt--3 lazy-form">
                        <div class="radio-group">
                            <input type="radio" class="market_radio" name="marketOrder" id="buy" value="market_buy_btn"> <label for="buy">Buy</label>
                            <input type="radio" class="market_radio" name="marketOrder" id="sell" value="market_sell_btn"> <label for="sell">Sell</label>
                        </div>

                    </div>
                    <div class="col-lg-12 mt--2">
                        <input id="market_order_btn" type="submit" class="btn btn--primary-2 btn-white <?=$action_class_market?>" value="Trade Instantly">
                        <p class="text--tiny mt--2">
                            * Disclaimer will be shown here
                        </p>
                    </div>

                </div>
            </div>

        </div>
        <div class="col-sm-4 ">
            <div class="box p--2 mt--4">
                <div class="row">
                    <h2 class="text-center">Buy</h2>
                    <hr>
                    <div class="col-lg-12 lazy-form">
                        <label for="buy_btc">Quantity</label>
                        <input type="text" name="buy_btc" id="buy_btc">
                    </div>

                    <div class="col-lg-12 lazy-form">
                        <label for="buy_btc_price">Price</label>
                        <input type="text" name="buy_btc_price" id="buy_btc_price">
                    </div>

                    <div class="col-lg-12">
                        <input  type="submit" id="buy_btn" class="btn btn--primary-1 btn-white <?=$action_class_buy_sell?>" value="Buy">

                        <!-- <input  type="submit" class="btn background--primary-1" value="Buy"> -->
                        <p class="text--tiny mt--1">
                            * Disclaimer will be shown here
                        </p>
                    </div>

                </div>


            </div>

        </div>
        <div class="col-sm-4 ">
            <div class="box p--2 mt--4">
                <div class="row">

                    <h2 class="text-center">Sell</h2>
                    <hr>
                    <div class="col-lg-12 lazy-form">
                        <label for="sell_btc">Quantity</label>
                        <input type="text" name="sell_btc" id="sell_btc">
                    </div>

                    <div class="col-lg-12 lazy-form">
                        <label for="sell_btc_price">Price</label>
                        <input type="text" name="sell_btc_price" id="sell_btc_price">
                    </div>

                    <div class="col-lg-12">
                        <input  type="submit" id="sell_btn" class="btn btn--primary-3 btn-white <?=$action_class_buy_sell?>" value="Sell">
                        <p class="text--tiny mt--1">
                            * Disclaimer will be shown here
                        </p>
                    </div>


                </div>
            </div>
        </div>
    </div>
</div>