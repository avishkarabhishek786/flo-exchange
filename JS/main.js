$(document).ready(function() {
    buy();
    market_order();
    refresh_tables();
    tradeList();
    checkLoginStatusJS();
});

/* function to open a link with class popup in new window */
/*$(document).on('click', 'a.popup', function () {
    newwindow=window.open($(this).attr('href'),'','height=300,width=600');
    if (window.focus) {newwindow.focus()}
    return false;
});*/

function displayError(msg) {
    var v = '<li>'+msg+'</li>';
    //$('#MsgModel').find('h4#myModalLabelMsg').addClass('text-warning').text('Warning!');
    $('#MsgModel').find('ul.msg-ul').removeClass('text-success').addClass('text-danger').html(v);
    $('#MsgModel').modal('toggle');
}

function buy() {

    $('.process').on('click', function() {
        var btn = $(this);
        var id = btn.attr('id');
        var item_qty = btn.parent().find('input[type=text]').first().val();
        var item_price = btn.parent().find('input[type=text]').last().val();

        if(item_qty !== '' && item_qty !== undefined && item_qty !== null) {
            if(item_price !== '' && item_price !== undefined && item_price !== null) {
                placeOrder(id, item_qty, item_price);
            } else {
                //alert('Please insert a price.');
                displayError('Please insert some price.');
            }
        } else {
            displayError('Please insert some quantity.');
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
        error: function() {
            console.log("An error occurred.");
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
                if(d.error == true) {
                    $msg  = d.msg;
                    displayError($msg);
                } else if(d.order != null && d.order.error == true && d.order.message != null) {
                    displayError(d.order.message);
                } else if(d.user == '') {
                    displayError('There was a problem in identifying the user.');
                } else {
                    $('#empty_msg').hide();
                    run_OrderMatcingAlgorithm();
                }
            } else {
                displayError('Something went wrong. Please contact the administrator.');
            }

        }
    });

}

function myTimeoutFunction()
{
    run_OrderMatcingAlgorithm();
    tradeList();
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
    }).error(function() {
        console.log('error')
    }).success(function(data) {
        load_fresh_table_data();
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
    }).error(function() {
        console.log('error')
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
    }).error(function() {
        console.log('error')
    }).success(function(data) {
        if(data !== '') {
            var d = jQuery.parseJSON(data);
            get_my_balance();

            var t = '';
            if(isArray(d.buys) && d.buys.length !== 0) {
                for (var j=0; j<=d.buys.length-1 ; j++) {
                    t += '';
                    t += '<tr id="'+d.buys[j].OrderId+'">';
                    t += '<td> ₹ '+d.buys[j].Price+'</td>';
                    t += '<td>'+d.buys[j].Quantity+'</td>';
                    t += '<td> ₹ '+d.buys[j].TOTAL_COST+'</td>';
                    t += '</tr>';
                }
            }
            $('#buying-list').html(t);

            var v = '';
            if(isArray(d.sells) && d.sells.length !== 0) {
                for (var k=0; k<=d.sells.length-1 ; k++) {
                    v += '';
                    v += '<tr id="'+d.sells[k].OrderId+'">';
                    v += '<td> ₹ '+d.sells[k].Price+'</td>';
                    v += '<td>'+d.sells[k].Quantity+'</td>';
                    v += '<td> ₹ '+d.sells[k].TOTAL_COST+'</td>';
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
        var btn = $(this);
        var market_order_type = btn.attr('id');

        if(market_order_qty == '' || market_order_qty < 1) {
            displayError('Please specify valid quantity!');
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
            }).error(function () {
                console.log('Error');
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
                        displayError('Oops! Something went wrong. Your order was cancelled. Please enter a valid quantity.');
                        console.log(d.msg);
                    } else{
                        var v = '';
                        if(isArray(d.order) && d.order.length != 0) {
                            if(d.order[0] == 'empty_buy_list') {
                                v += 'No buy orders available currently.';
                                //$('#MsgModel').find('h4#myModalLabelMsg').addClass('text-warning').text('Oops!');
                                $('#MsgModel').find('ul.msg-ul').removeClass('text-success').addClass('text-danger').html(v);
                                $('#MsgModel').modal('toggle');
                            } else if(d.order[0] == 'empty_sell_list') {
                                v += 'No sell orders available currently.';
                                //$('#MsgModel').find('h4#myModalLabelMsg').addClass('text-warning').text('Oops!');
                                $('#MsgModel').find('ul.msg-ul').removeClass('text-success').addClass('text-danger').html(v);
                                $('#MsgModel').modal('toggle');
                            } else {

                                for (var k=0; k<= d.order.length-1; k++) {
                                    v += '<li>'+d.order[k]+'</li>';
                                }
                                //$('#MsgModel').find('h4#myModalLabelMsg').addClass('text-success').text('Congrats!');
                                $('#MsgModel').find('ul.msg-ul').removeClass('text-danger').addClass('text-info').html(v);
                                $('#MsgModel').modal('toggle');
                            }
                        }
                        load_fresh_table_data();
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
    }).error(function() {
        console.log('error')
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
                    v += '<td> ₹ '+d.trade_list[k].TRADE_PRICE+'</td>';
                    v += '<td>'+my_date_format(d.trade_list[k].InsertDate)+'</td>';
                    v += '</tr>';
                }
            }
            $('#trade-list').html(v);
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