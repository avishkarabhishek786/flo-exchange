$(document).ready(function() {
    buy();
    market_order();
    refresh_tables();
    tradeList();
    tradersList();
    checkLoginStatusJS();
    MyOrders();
    MyTransactions();
    load_messages();
    run_OrderMatcingAlgorithm();

    $('[data-toggle="popover"]').popover();
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
                btn.prop( "disabled", true );
                placeOrder(id, item_qty, item_price, btn);
            } else {
                displayNotice('Please insert some price.', "warning");
            }
        } else {
            displayNotice('Please insert some quantity.', "warning");
        }
    });
}

function placeOrder(id, item_qty, item_price, btn) {

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
            btn.prop( "disabled", false);
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

function myTimeoutFunction() {
    run_OrderMatcingAlgorithm();
    check_new_orders();
    setTimeout(myTimeoutFunction, 20000);
}

myTimeoutFunction();

function run_all() {
    run_OrderMatcingAlgorithm();
    tradeList();
    tradersList();
    MyOrders();
    MyTransactions();
    load_messages();
}

function check_new_orders() {
    $.ajax({
            method:'post',
            url:'ajax/check_new_orders.php',
            async: true
        }).error(function(xhr, status, error) {
            console.log(xhr, status, error);
        }).success(function(data) {
            if ($.trim(data) != '' && $.trim(data) != undefined && $.trim(data) != null) {
                run_all();
            }
        });
}

function run_OrderMatcingAlgorithm() {

    $.ajax({
        method:'post',
        async: true,
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
                            //title: "<strong>Congrats!:</strong> ",
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
                    t += '<td> '+d.buys[j].Name+'</td>';
                    t += '<td> $ '+d.buys[j].Price+'</td>';
                    t += '<td>'+d.buys[j].Quantity+'</td>';
                    t += '</tr>';
                }
            }
            $('#buying-list').html(t);

            var v = '';
            if(isArray(d.sells) && d.sells.length !== 0) {
                for (var k=0; k<=d.sells.length-1 ; k++) {
                    v += '';
                    v += '<tr id="'+d.sells[k].OrderId+'">';
                    v += '<td>'+d.sells[k].Name+'</td>';
                    v += '<td> $ '+d.sells[k].Price+'</td>';
                    v += '<td>'+d.sells[k].Quantity+'</td>';
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
        var btn = $(this);
        var market_order_qty = $('#market_order').val();
        var market_order_type = $("input[name='marketOrder']:checked").val();

        if(market_order_qty == '' || market_order_qty <= 0 || market_order_qty == undefined) {
            displayNotice('Please specify valid quantity!', "warning");
            return;
        }
        btn.prop( "disabled", true);
        if (market_order_type == 'market_buy_btn' || market_order_type == 'market_sell_btn') {
            $.ajax({
                method: 'post',
                url: 'ajax/market_order-ajax.php',
                data: { job:'market_order', qty:market_order_qty, type:market_order_type },
                success: function() {
                    btn.prop( "disabled", false);
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
                        displayNotice(d.msg, "failure");
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
                $('#_ltp').text('$ '+d.trade_list[0].TRADE_PRICE);
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

function trim_decimal_places(num, decial_allowed) {
    return parseFloat(Math.round(num * 100) / 100).toFixed(decial_allowed);
}

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
                    v += '<td> $ '+trim_decimal_places(d.traders_list[k].CASH, 2)+'</td>';
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
        run_OrderMatcingAlgorithm();
        load_messages();
        MyOrders();
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

/*Add bank account*/
$(document).on('click', '#btn_bk_add', function () {

    var job = "add_bank_account";
    var account_holder_name = $('#acc_name').val();
    var account_number = $('#acc_numb').val();
    var bank_name = $('#bk_name').val();
    var branch_name = $('#bk_branch').val();
    var bank_addr = $('#bank_addr').val();
    var bk_ctry = $('#bk_ctry').val();

    $.ajax({
        method:'post',
        url:'ajax/add_bank_account.php',
        data: {
            job: job,
            account_holder_name:account_holder_name,
            account_number:account_number,
            bank_name:bank_name,
            branch_name:branch_name,
            bank_addr:bank_addr,
            bk_ctry:bk_ctry
        }
    }).error(function(xhr, status, error) {
        console.log(xhr, status, error);
    }).success(function(data) {

        if ($.trim(data) != '' && $.trim(data) != undefined && $.trim(data) != null) {
            var IS_JSON = true;
            try {
                var d = jQuery.parseJSON(data);
            }
            catch(err) {
                IS_JSON = false;
            }

            if(IS_JSON) {
                if (isArray(d.mesg) && d.mesg.length != 0) {
                    var tp = 'info';
                    if(d.error == true) {
                        tp = 'danger'
                    } else if(d.error == false) {
                        tp = 'success';
                    }
                    for (var k = 0; k <= d.mesg.length - 1; k++) {
                        $.notify({
                            title: "<strong>Alert!:</strong> ",
                            message: d.mesg[k]
                        },{
                            type: tp
                        });
                    }
                    $('#bk_sel').append('<option id="'+account_number+'">'+account_number+'</option>');
                }
            }
        }
        load_messages();
    });
});

/*Send balance to bank account*/
$(document).on('click', '#btn_bk_tr', function () {
    var acc = $('#bk_sel').val();
    var bal = $('#tr_amt').val();
    var remarks = $('#remarks_bal_tr').val();
    var job = 'transfer_to_bank';

    if(!confirm('Are you sure to transfer $'+bal+' to '+acc+' ?') )
        return false;

    var btn = this;

    $(btn).val('Please wait....').prop( "disabled", true );

    $.ajax({
        method:'post',
        url:'ajax/transfer_balance_to_bank.php',
        data: {job:job, acc:acc, bal:bal, remarks:remarks}
    }).error(function(xhr, status, error) {
        console.log(xhr, status, error);
    }).complete(function() {
        $(btn).val('Transfer').prop( "disabled", false );
    }).success(function(data) {

        if ($.trim(data) != '' && $.trim(data) != undefined && $.trim(data) != null) {
            var IS_JSON = true;
            try {
                var d = jQuery.parseJSON(data);
            }
            catch(err) {
                IS_JSON = false;
            }

            if(IS_JSON) {

                if (isArray(d.mesg) && d.mesg.length != 0) {

                    var tp = 'info';
                    if(d.error == true) {
                        tp = 'danger'
                    } else if(d.error == false) {
                        tp = 'success';
                    }
                    for (var k = 0; k <= d.mesg.length - 1; k++) {

                        $.notify({
                            title: "<strong>Alert!:</strong> ",
                            message: d.mesg[k]
                        },{
                            type: tp
                        });
                    }
                }
            }
        }
        $('#tr_amt').val("");
        $('#remarks_bal_tr').val("");
        load_messages();
    });
});

/*Send tokens to Blockchain*/
$(document).on('click', '#btn_rtm_trans', function () {
    var btn = this;
    var flo_addr = $('#flo_addr').val();
    var rmt_amnt = $('#rmt_amnt').val();
    var remarks_flo = $('#remarks_flo').val();
    var job = 'rtm_to_bchain';

    $(btn).val('Please wait....').prop( "disabled", true );

    $.ajax({
        method:'post',
        url:'ajax/transfer_rtm_to_bchain.php',
        data: {job:job, flo_addr:flo_addr, rmt_amnt:rmt_amnt, remarks_flo:remarks_flo}
    }).error(function(xhr, status, error) {
        console.log(xhr, status, error);
    }).success(function(data) {
        $(btn).val('Transfer RMTs').prop( "disabled", false );
        if ($.trim(data) != '' && $.trim(data) != undefined && $.trim(data) != null) {
            var IS_JSON = true;
            try {
                var d = jQuery.parseJSON(data);
            }
            catch(err) {
                IS_JSON = false;
            }

            if(IS_JSON) {
                if (isArray(d.mesg) && d.mesg.length != 0) {
                    var tp = 'info';
                    if(d.error == true) {
                        tp = 'danger'
                    } else if(d.error == false) {
                        tp = 'success';
                    }
                    for (var k = 0; k <= d.mesg.length - 1; k++) {

                        $.notify({
                            title: "<strong>Alert!:</strong> ",
                            message: d.mesg[k]
                        },{
                            type: tp
                        });
                    }
                }
            }
        }
        load_messages();
    });
});

/*Load Cash in my Account*/
$(document).on('click', '#get_btc_price', function () {
    var job = 'get_btc2usd';
    $.ajax({
        method:'post',
        url:'ajax/load_cash_in_bank.php',
        data: {job:job}
    }).error(function(xhr, status, error) {
        console.log(xhr, status, error);
    }).success(function(data) {
        if ($.trim(data) != '' && $.trim(data) != undefined && $.trim(data) != null) {
            $('#btc_today').val(data);
            get_eqv_btc(data);
        } else {
            $('#btc_eqv').text("Failed to fetch data. Please refresh the page.");
        }
        load_messages();
    });
});

function get_eqv_btc(data) {
    $('#usd_amt').keyup(function () {
        var amount_in_usd = $(this).val();
        var eqv_btc = amount_in_usd/data;
        $('#btc_eqv').text(eqv_btc);
    });
}

$(document).on('click', '#lcma_btn', function () {
    var btn = this;
    var job = 'lcma';
    var btc_today = $('#btc_today').val();
    var amount_to_load = $('#usd_amt').val();
    var eqv_btc = $('#btc_eqv').text();
    var remarks = $('#lcma_remarks').val();

    $(btn).val('Please wait....').prop( "disabled", true );

    $.ajax({
        method:'post',
        url:'ajax/load_cash_in_bank.php',
        data: {job:job, amount_to_load:amount_to_load, eqv_btc:eqv_btc, remarks:remarks, btc_today:btc_today}
    }).error(function(xhr, status, error) {
        console.log(xhr, status, error);
    }).success(function(data) {
        $(btn).val('Send Details').prop( "disabled", false );
        if ($.trim(data) != '' && $.trim(data) != undefined && $.trim(data) != null) {
            var IS_JSON = true;
            try {
                var d = jQuery.parseJSON(data);
            }
            catch(err) {
                IS_JSON = false;
            }

            if(IS_JSON) {
                if (isArray(d.mesg) && d.mesg.length != 0) {
                    var tp = 'info';
                    if(d.error == true) {
                        tp = 'danger'
                    } else if(d.error == false) {
                        tp = 'success';
                    }
                    for (var k = 0; k <= d.mesg.length - 1; k++) {
                        $.notify({
                            title: "<strong>Alert!:</strong> ",
                            message: d.mesg[k],
                            autoHide: true,
                            autoHideDelay: 10000
                        },{
                            type: tp
                        });
                    }
                }
            }
        }
    });
    load_messages();
});

/*Send refund in BTC*/
$(document).on('click', '#btn_btc_tr', function (e) {
    var btn = this;
    var ref_amount = $('#b_amnt').val();
    var btc_addr = $('#invst_btc_addr').val();
    var invst_remarks = $('#b_remarks').val();
    var job = 'pay_in_btc';

    $(btn).val('Please wait....').prop( "disabled", true );

    $.ajax({
        method:'post',
        url:'ajax/pay_in_btc.php',
        data: {job:job, ref_amount:ref_amount, btc_addr:btc_addr, invst_remarks:invst_remarks},
        async: true
    }).error(function(xhr, status, error) {
        console.log(xhr, status, error);
    }).success(function(data) {
        $(btn).val('Send').prop( "disabled", false );
        if ($.trim(data) != '' && $.trim(data) != undefined && $.trim(data) != null) {
            var IS_JSON = true;
            try {
                var d = jQuery.parseJSON(data);
            }
            catch(err) {
                IS_JSON = false;
            }

            if(IS_JSON) {
                if (isArray(d.mesg) && d.mesg.length != 0) {
                    var tp = 'info';
                    if(d.error == true) {
                        tp = 'danger'
                    } else if(d.error == false) {
                        tp = 'success';
                    }
                    for (var k = 0; k <= d.mesg.length - 1; k++) {

                        $.notify({
                            title: "<strong>Alert!:</strong> ",
                            message: d.mesg[k]
                        },{
                            type: tp
                        });
                    }
                }
            }
        }
    });
    run_all();
});

// Send RMT to BCX account
$(document).on('click', '#nrs_btn', function () {
    var btn = this;
    var job = '_nrs';
    var rmt2send = $('#_nrs').val();
    $(btn).val('Please wait....').prop( "disabled", true );

    $.ajax({
        method:'post',
        url:'ajax/send_rmt_to_bcx.php',
        data: {job:job, rmt2send:rmt2send}
    }).error(function(xhr, status, error) {
        console.log(xhr, status, error);
    }).success(function(data) {
        $(btn).val('Transfer RMTs').prop( "disabled", false );
        if ($.trim(data) != '' && $.trim(data) != undefined && $.trim(data) != null) {
            var IS_JSON = true;
            try {
                var d = jQuery.parseJSON(data);
            }
            catch(err) {
                IS_JSON = false;
            }

            if(IS_JSON) {
                if (isArray(d.mesg) && d.mesg.length != 0) {
                    var tp = 'info';
                    if(d.error == true) {
                        tp = 'danger'
                    } else if(d.error == false) {
                        tp = 'success';
                    }
                    for (var k = 0; k <= d.mesg.length - 1; k++) {
                        $.notify({
                            title: "<strong>Alert!:</strong> ",
                            message: d.mesg[k],
                            autoHide: true,
                            autoHideDelay: 10000
                        },{
                            type: tp
                        });
                    }
                }
            }
        }
    });
    load_messages();
})
