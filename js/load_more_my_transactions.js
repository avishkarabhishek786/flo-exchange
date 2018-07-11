/**
 * Created by Abhishek Kumar Sinha on 9/27/2017.
 */

$(document).ready(function(){
    var loading = false;
   
    $total_records = getTotalMyTransactions();

    $records_per_page = 50;
    $number_of_pages = Math.ceil($total_records / $records_per_page);
    $current_page = 1;
    $start=($current_page*$records_per_page)-$records_per_page;

    loadMoreMyTransactionsInitial();

    $current_page = 2;

    $(window).scroll(function() {
        if($(window).scrollTop() + window.innerHeight > $(document).height()-5) {
            $start = ($current_page*$records_per_page)-$records_per_page;
            if($current_page <= $number_of_pages && loading == false) {
                loadMoreMyTransactions($start,$records_per_page);
                $current_page++;
            }
        }
    });
});

function getTotalMyTransactions() {
    $total_records = 0;
    var job = 'total_my_transactions';
    $.ajax({
        url: "ajax/MyTransactionsTotal.php",
        data: {job: job},
        async: false,
        type:"post",
        success: function(data) {
            $total_records = data;
        },
        error: function(xhr) {
            $total_records = 0;
        }
    });
    return $total_records;
}

function loadMoreMyTransactionsInitial() {
    loadMoreMyTransactions($start,$records_per_page);
}

function loadMoreMyTransactions($start,$records_per_page) {
    var loading = true;
    $.ajax({
        url: "ajax/loadMoreMyTransactions.php",
        type:"post",
        data: {req: 'loadMoreMyTransactions', records_per_page: $records_per_page, start: $start},
        beforeSend: function(){
            $('.ajax-loader-span').html("<img src='images/spinner.gif' class='ajax-loader'>");
        },
        complete: function() {
            $('.ajax-loader-span').remove();
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
                var v = '0 transactions';
                if(isArray(d.trade_list) && d.trade_list.length != 0) {
                    v = '';
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
                $("#myTransactionsTable").append(v);
            }
        },
        error: function(xhr) {
            //console.log(xhr);
        }
    });
    loading = false;
}