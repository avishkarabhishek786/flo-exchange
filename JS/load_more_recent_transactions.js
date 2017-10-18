/**
 * Created by Abhishek Kumar Sinha on 9/27/2017.
 */

$(document).ready(function(){
    var loading = false;
   
    $total_records = getTotalRecentTransactions();

    $records_per_page = 2;
    $number_of_pages = Math.ceil($total_records / $records_per_page);
    $current_page = 1;
    $start=($current_page*$records_per_page)-$records_per_page;

    loadMoreRecentTransactionsInitial();

    $current_page = 2;

    $(window).scroll(function() {
        if($(window).scrollTop() + window.innerHeight > $(document).height()-5) {
            $start = ($current_page*$records_per_page)-$records_per_page;
            console.log($current_page, $number_of_pages);
            if($current_page <= $number_of_pages && loading == false) {
                console.log('hello');
                loadMoreRecentTransactions($start,$records_per_page);
                $current_page++;
            }
        }
    });
});

function getTotalRecentTransactions() {
    $total_records = 0;
    var job = 'total_recent_transactions';
    $.ajax({
        url: "ajax/recentTransactionsTotal.php",
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

function loadMoreRecentTransactionsInitial() {
    loadMoreRecentTransactions($start,$records_per_page);
}

function loadMoreRecentTransactions($start,$records_per_page) {
    var loading = true;
    $.ajax({
        url: "ajax/loadMoreRecentTransactions.php",
        type:"post",
        data: {req: 'loadMoreRecentTransactions', records_per_page: $records_per_page, start: $start},
        beforeSend: function(){
            $('.ajax-loader-span').html("<img src='images/spinner.gif' class='ajax-loader'>");
        },
        complete: function() {
            $('.ajax-loader-span').remove();
        },
        success: function(data) {
            console.log(data);
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