$(document).ready(function() {
    buy();
    market_order();
    refresh_tables();
    tradeList();
    tradersList();
    checkLoginStatusJS();
    //MyOrders();
    MyTransactions();
    load_messages();
});

function displayNotice(msg, _type) {
    var v = '<li>'+msg+'</li>';

    switch (_type) {
        case 'success':
            $('#MsgModel').find('ul.msg-ul').removeClass('text-danger text-warning').addClass('text-info').html(v);
            break;
        case 'failure':
            $('#MsgModel').find('ul.msg-ul').removeClass('text-info text-warning').addClass('text-danger').html(v);
            break;
        case 'warning':
            $('#MsgModel').find('ul.msg-ul').removeClass('text-danger text-info').addClass('text-warning').html(v);
            break;
        default:
            $('#MsgModel').find('ul.msg-ul').removeClass('text-danger text-warning').addClass('text-info').html(v);
    }

    $('#MsgModel').modal('toggle');
}

function buy() {

    $('.process').on('click', function() {
        var btn = $(this);
        var id = btn.attr('id');
        var item_qty = null;
        var item_price = null;

        if($.trim(id)=="buy_btn") {
            item_qty = $('#buy_btc').val();
            item_price = $('#buy_btc_price').val();
        } else if($.trim(id)=="sell_btn") {
            item_qty = $('#sell_btc').val();
            item_price = $('#sell_btc_price').val();
        } else {
            return false;
        }

        if(item_qty !== '' && item_qty !== undefined && item_qty !== null) {
            if(item_price !== '' && item_price !== undefined && item_price !== null) {
                placeOrder(id, item_qty, item_price);
            } else {
                displayNotice('Please insert some price.', "warning");
            }
        } else {
            displayNotice('Please insert some quantity.', "warning");
        }
    });
}

function placeOrder(id, item_qty, item_price) {

    var subject = 'placeOrder';
    var btn_id = id;  // buy or sell
    var qty = item_qty;
    var price = item_price;

    $.ajax({
        method: 'post',
        url: 'ajax/pending_orders.php',
        data: { subject:subject, btn_id:btn_id, qty:qty, price:price },
        error: function(xhr, status, error) {
            console.log(xhr.responseText);
        },
        success: function(data) {

            var IS_JSON = true;
            try {
                var d = jQuery.parseJSON(data);
            }
            catch(err) {
                IS_JSON = false;
            }

            if(IS_JSON) {
                //console.log(d);
                if(d.error == true) {
                    $msg  = d.msg;
                    displayNotice($msg, "failure");
                } else if(d.order != null && d.order.error == true && d.order.message != null) {
                    displayNotice(d.order.message, "failure");
                } else if(d.user == '') {
                    displayNotice('There was a problem in identifying the user.', "failure");
                } else {
                    $('#empty_msg').hide();
                    var trade = "";
                    if($.trim(btn_id)=="buy_btn") {
                        trade = "buy";
                    } else if ($.trim(btn_id)=="sell_btn") {
                        trade = "sell";
                    }
                    displayNotice('You entered a '+trade+' order for '+qty+' token at $ '+price+'.', "success");
                    run_OrderMatcingAlgorithm();
                    MyOrders();
                }
            } else {
                displayNotice('Something went wrong. Please contact the administrator.', "failure");
            }
        }
    });

}

function myTimeoutFunction()
{
    run_OrderMatcingAlgorithm();
    tradeList();
    tradersList();
    MyOrders();
    MyTransactions();
    load_messages();
    setTimeout(myTimeoutFunction, 3000);
}

myTimeoutFunction();

function run_OrderMatcingAlgorithm() {

    $.ajax({
        method:'post',
        url:'ajax/OrderMatcingAlgorithmAjax.php',
        data: { task : 'run_OrderMatcingAlgorithm'},
        success: function(data) {
            //console.log(data);
        }
    }).error(function(xhr, status, error) {
        console.log(xhr.responseText);
    }).success(function(data) {
        load_fresh_table_data();

        var IS_JSON = true;
        try {
            var d = jQuery.parseJSON(data);
        }
        catch(err) {
            IS_JSON = false;
        }

        if(IS_JSON) {
            //console.log(d);
            if (d.error == false && d.msg=="userLoggedIn") {
                if (isArray(d.order) && d.order.length != 0) {
                    for (var k = 0; k <= d.order.length - 1; k++) {
                        $.notify({
                            title: "<strong>Congrats!:</strong> ",
                            message: d.order[k]
                        },{
                            type: 'success'
                        });
                    }
                }
            }
        }
    });
}

function get_my_balance() {
    $.ajax({
        method:'post',
        url:'ajax/get_my_balance.php',
        data: { task : 'get_my_balance'},
        success: function(data) {
            // console.log(data);
        }
    }).error(function(xhr, status, error) {
        console.log(xhr.responseText);
    }).success(function(data) {
        var IS_JSON = true;
        try {
            var d = jQuery.parseJSON(data);
        }
        catch(err) {
            IS_JSON = false;
        }

        if(IS_JSON) {
            if(d.error == false) {
                $('#my_bit_balance').text(d.bit);
                $('#my_cash_balance').text(d.cash);
            }
        }

    });
}

// function to check if JSON data is array or not
function isArray(what) {
    return Object.prototype.toString.call(what) === '[object Array]';
}

function load_fresh_table_data() {

    $.ajax({
        method:'post',
        url:'ajax/refresh_table.php',
        data: { task : 'refresh'}
    }).error(function(xhr, status, error) {
        console.log(xhr.responseText);
    }).success(function(data) {
        if(data !== '') {
            var d = jQuery.parseJSON(data);
            get_my_balance();

            var t = '';
            if(isArray(d.buys) && d.buys.length !== 0) {
                for (var j=0; j<=d.buys.length-1 ; j++) {
                    t += '';
                    t += '<tr id="'+d.buys[j].OrderId+'">';
                    t += '<td> $ '+d.buys[j].Price+'</td>';
                    t += '<td>'+d.buys[j].Quantity+'</td>';
                    t += '<td> $ '+d.buys[j].TOTAL_COST+'</td>';
                    t += '</tr>';
                }
            }
            $('#buying-list').html(t);

            var v = '';
            if(isArray(d.sells) && d.sells.length !== 0) {
                for (var k=0; k<=d.sells.length-1 ; k++) {
                    v += '';
                    v += '<tr id="'+d.sells[k].OrderId+'">';
                    v += '<td> $ '+d.sells[k].Price+'</td>';
                    v += '<td>'+d.sells[k].Quantity+'</td>';
                    v += '<td> $ '+d.sells[k].TOTAL_COST+'</td>';
                    v += '</tr>';
                }
            }
            $('#selling-list').html(v);
        }
    });

}

/**Market Order JS*/
function market_order() {

    $('.market_submit_btn').on('click', function() {
        var market_order_qty = $('#market_order').val();
        var market_order_type = $("input[name='marketOrder']:checked").val();

        if(market_order_qty == '' || market_order_qty <= 0 || market_order_qty == undefined) {
            displayNotice('Please specify valid quantity!', "warning");
            return;
        }
        if (market_order_type == 'market_buy_btn' || market_order_type == 'market_sell_btn') {
            $.ajax({
                method: 'post',
                url: 'ajax/market_order-ajax.php',
                data: { job:'market_order', qty:market_order_qty, type:market_order_type },
                success: function(data) {
                    //console.log(data);
                }
            }).error(function(xhr, status, error) {
                console.log(xhr.responseText);
            }).success(function (data) {
                var IS_JSON = true;
                try {
                    var d = jQuery.parseJSON(data);
                }
                catch(err) {
                    IS_JSON = false;
                }

                if(IS_JSON) {
                    if(d.error == true) {
                        displayNotice('Oops! Something went wrong. Your order was cancelled. Please enter a valid quantity.', "failure");
                    } else{
                        var v = '';
                        if(isArray(d.order) && d.order.length != 0) {
                            if(d.order[0] == 'empty_buy_list') {
                                v += 'No buy orders available currently.';
                                $('#MsgModel').find('ul.msg-ul').removeClass('text-success').addClass('text-danger').html(v);
                                $('#MsgModel').modal('toggle');
                            } else if(d.order[0] == 'empty_sell_list') {
                                v += 'No sell orders available currently.';
                                $('#MsgModel').find('ul.msg-ul').removeClass('text-success').addClass('text-danger').html(v);
                                $('#MsgModel').modal('toggle');
                            } else {

                                for (var k=0; k<= d.order.length-1; k++) {
                                    v += '<li>'+d.order[k]+'</li>';
                                }
                                $('#MsgModel').find('ul.msg-ul').removeClass('text-danger').addClass('text-info').html(v);
                                $('#MsgModel').modal('toggle');
                            }
                        }
                        load_fresh_table_data();
                        MyOrders();
                    }
                }
            });
        }

    });

}

function refresh_tables() {
    $(document).on('click', '#refresh_link', function (e) {
        e.preventDefault();
        load_fresh_table_data();
    });
}

function tradeList() {
    $.ajax({
        method:'post',
        url:'ajax/tradeList.php',
        data: { task : 'loadTradeList'}
    }).error(function(xhr, status, error) {
        console.log(xhr.responseText);
    }).success(function(data) {

        var IS_JSON = true;
        try {
            var d = jQuery.parseJSON(data);
        }
        catch(err) {
            IS_JSON = false;
        }

        if(IS_JSON) {
            var v = '';
            if(isArray(d.trade_list) && d.trade_list.length != 0) {
                for (var k=0; k<= d.trade_list.length-1; k++) {
                    v += '';
                    v += '<tr>';
                    v += '<td>'+d.trade_list[k].SELLER+'</td>';
                    v += '<td>'+d.trade_list[k].BUYER+'</td>';
                    v += '<td>$ '+d.trade_list[k].TRADE_PRICE+'</td>';
                    v += '<td>'+d.trade_list[k].TRADED_QTY+'</td>';
                    v += '<td>$ '+(d.trade_list[k].TRADED_QTY * d.trade_list[k].TRADE_PRICE).toFixed(5)+'</td>';
                    v += '<td>'+my_date_format(d.trade_list[k].InsertDate)+'</td>';
                    v += '</tr>';
                }
            } else {
                v += '<p class="text-info">No transactions.</p>';
            }
            $('#trade-list').html(v);
        }
    });
}

/*My Transactions*/
function MyTransactions() {
    $.ajax({
        method:'post',
        url:'ajax/myTransactions.php',
        data: { task : 'myTransactions'}
    }).error(function(xhr, status, error) {
        console.log(xhr.responseText);
    }).success(function(data) {

        var IS_JSON = true;
        try {
            var d = jQuery.parseJSON(data);
        }
        catch(err) {
            IS_JSON = false;
        }

        if(IS_JSON) {
            var v = '';
            if(isArray(d.trade_list) && d.trade_list.length != 0) {
                for (var k=0; k<= d.trade_list.length-1; k++) {
                    v += '';
                    v += '<tr>';
                    v += '<td>'+d.trade_list[k].SELLER+'</td>';
                    v += '<td>'+d.trade_list[k].BUYER+'</td>';
                    v += '<td>$ '+d.trade_list[k].TRADE_PRICE+'</td>';
                    v += '<td>'+d.trade_list[k].TRADED_QTY+'</td>';
                    v += '<td>$ '+(d.trade_list[k].TRADED_QTY * d.trade_list[k].TRADE_PRICE).toFixed(5)+'</td>';
                    v += '<td>'+my_date_format(d.trade_list[k].InsertDate)+'</td>';
                    v += '</tr>';
                }
            }
            $('#my-transactions-list').html(v);
        }
    });
}

function checkLoginStatusJS() {

    $(document).on('click drop', '.fb_log_in', function (e) {
        e.preventDefault();
        $('#LoginModel').modal('toggle');
    });
}

var my_date_format = function(input){
    var d = new Date(Date.parse(input.replace(/-/g, "/")));
    var month = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    var date = d.getDate() + " " + month[d.getMonth()] + ", " + d.getFullYear();
    var time = d.toLocaleTimeString().toLowerCase().replace(/([\d]+:[\d]+):[\d]+(\s\w+)/g, "$1$2");
    return (date + " " + time);
};

/*Rename market button name depending on radio btn*/
$(document).on("change", ".market_radio", function () {
    var btn_id = $(this).attr('id');
    var btn_name = "Trade Instantly";
    if($.trim(btn_id)=="buy") {
        btn_name = "Buy Instantly";
    } else if($.trim(btn_id)=="sell") {
        btn_name = "Sell Instantly";
    }
    $("#market_order_btn").prop("value",btn_name);
});

/*Traders List*/
function tradersList() {
    $.ajax({
        method:'post',
        url:'ajax/tradersList.php',
        data: { task : 'loadTradersList'}
    }).error(function(xhr, status, error) {
        console.log(xhr.responseText);
    }).success(function(data) {

        var IS_JSON = true;
        try {
            var d = jQuery.parseJSON(data);
        }
        catch(err) {
            IS_JSON = false;
        }

        if(IS_JSON) {
            var v = '';
            if(isArray(d.traders_list) && d.traders_list.length != 0) {
                for (var k=0; k<= d.traders_list.length-1; k++) {
                    v += '';
                    v += '<tr>';
                    v += '<td>'+d.traders_list[k].Name+'</td>';
                    v += '<td>'+d.traders_list[k].BTC+'</td>';
                    v += '<td> $ '+d.traders_list[k].CASH+'</td>';
                    v += '</tr>';
                }
            }
            $('#traders-list').html(v);
        }
    });
}

/*My Orders*/
function MyOrders() {
    $.ajax({
        method:'post',
        url:'ajax/myOrders.php',
        data: { task : 'loadMyOrdersList'}
    }).error(function(xhr, status, error) {
        console.log(xhr.responseText);
    }).success(function(data) {
        if ($.trim(data) != '' && $.trim(data) != undefined && $.trim(data) != null) {
            $('#myOrdersTable').html(data);
        }
    });
}

/*Delete Orders*/
$(document).on('click', '.del_order', function (e) {
    e.preventDefault();
    var id = $(this).attr("id");

    $.ajax({
        method:'post',
        url:'ajax/delOrder.php',
        data: { task : 'delOrder', id:id}
    }).error(function(xhr, status, error) {
        console.log(error);
    }).success(function(data) {
        //console.log(data);
        if ($.trim(data) != '' && $.trim(data) != undefined && $.trim(data) != null) {
            $.notify({
                title: "<strong>Order Deleted!:</strong> ",
                message: "You deleted the order successfully."
            },{
                type: 'success'
            });
        } else {
            displayNotice("The order could not be deleted. Try again later.", "failure");
        }
    });
});

/*Messages*/
function load_messages() {
    $.ajax({
        method:'post',
        url:'ajax/myMessages.php',
        data: { task : 'loadMyMessagesList'}
    }).error(function(xhr, status, error) {
        console.log(error);
    }).success(function(data) {
        if ($.trim(data) != '' && $.trim(data) != undefined && $.trim(data) != null) {
            var IS_JSON = true;
            try {
                var d = jQuery.parseJSON(data);
            }
            catch(err) {
                IS_JSON = false;
            }

            if (IS_JSON) {
                var v = '0 message';
                if(isArray(d.msg) && d.msg.length != 0) {
                    v = '';
                    var si = 0;
                    for (var k=0; k<= d.msg.length-1; k++) {
                        si = k+1;
                        v += '<tr>';
                        v += '<td>'+si+'</td>';
                        v += '<td>'+d.msg[k].order_id+'</td>';
                        v += '<td>'+d.msg[k].messages+'</td>';
                        v += '<td>'+my_date_format(d.msg[k].datetime)+'</td>';
                        v += '</tr>';
                    }
                }
                $('#user_msg').html(v);
            }
        }
    });
}
