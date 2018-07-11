<?php
/**
 * Created by PhpStorm.
 * User: Abhishek Kumar Sinha
 * Date: 10/9/2017
 * Time: 8:05 PM
 */
?>
<?php ob_start(); date_default_timezone_set('Asia/Kolkata'); ?>
<?php $user_id = 0; ?>
    <!--Bootstrap-->
<?php require_once 'views/header.php';?>

<?php

    require_once 'includes/imp_files.php';

    if (!checkLoginStatus()) {
        redirect_to("index.php");
    }

    if (isset($_SESSION['fb_id'], $_SESSION['user_id'], $_SESSION['user_name'])) {
        $root_fb = (int) $_SESSION['fb_id'];
        $root_user_id = (int) $_SESSION['user_id'];
        $root_user_name = (string) $_SESSION['user_name'];

        /*This should match ajax/rm_root.php too*/
        if ($root_fb != ADMIN_ID && $root_user_id != ADMIN_ID && $root_user_name != ADMIN_UNAME) {
            redirect_to("index.php");
        }

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (!empty($_POST['cus_id']) && $_POST['bal'] != null && !empty($_POST['bal_type'])) {
                $investor_id = (int) $_POST['cus_id'];
                $balance =  (float) $_POST['bal'];
                $assetType = (string) $_POST['bal_type'];

                if ($investor_id < 0 || is_float($investor_id)) {
                    redirect_to("rm_root.php?msg=Invalid User Id!");
                    return false;
                }

                if ($balance < 0) {
                    redirect_to("rm_root.php?msg=Balance must be positive number!");
                    return false;
                }

                if (!isset($OrderClass, $UserClass)) {
                    redirect_to("rm_root.php?msg=System Error!");
                    return false;
                }

                $validate_user = $UserClass->check_user($investor_id);


                if($validate_user == "" || empty($validate_user)) {
                    redirect_to("rm_root.php?msg=Invalid User!");
                    return false;
                }

                $update_bal = null;

                if ($assetType == "RMT") {

                    $assetType = "btc";

                } elseif ($assetType == "Cash") {

                    $assetType = "traditional";

                } else {
                    redirect_to("rm_root.php?msg=Invalid balance type!");
                    return false;
                }

            /*Restrict decimal places while updating balance*/
                if ($assetType == "traditional") {
                    if (!validate_decimal_place($balance, 2)) {
                        redirect_to("rm_root.php?msg=Max 2 decimal places allowed in Fiat balance.!");
                        return false;
                    }
                } else if ($assetType == "btc") {
                    if (!validate_decimal_place($balance, 10)) {
                        redirect_to("rm_root.php?msg=Max 10 decimal places allowed in RMT balance.!");
                        return false;
                    }
                }
                
                //Prev balance of user
                $bal_prev = (float) $OrderClass->check_customer_balance($assetType, $investor_id)->Balance;

                $update_bal = $OrderClass->update_user_balance($assetType, $balance, $investor_id);

                if (!$update_bal) {
                    redirect_to("rm_root.php?msg=Failed to update balance!");
                    return false;
                } else if($update_bal) {
                    // Record this change
                    $OrderClass->record_root_bal_update($investor_id, $bal_prev, $balance, $assetType);
                    redirect_to("rm_root.php?msg=Successfully updated balance!&type=info");
                } else {
                    redirect_to("rm_root.php?msg= Something went wrong. Failed to update balance!");
                    return false;
                }

            } else {
                redirect_to("rm_root.php?msg= Please fill all fields!");
                return false;
            }
        }

        $traders = $OrderClass->UserBalanceList(1);

         ?>

            <div class="container mt--2">
                <h2>Actions table</h2>
                <div class="mt--2 mb--2 p--1">
                    <form class="form-inline" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                        <div class="form-group">
                            <label class="sr-only" for="cus_id">User Id</label>
                            <input type="number" class="form-control" name="cus_id" id="cus_id" placeholder="User Id">
                        </div>
                        <div class="form-group">
                            <label class="sr-only" for="bal">Input Balance</label>
                            <input type="text" class="form-control" name="bal" id="bal" placeholder="Input Balance">
                        </div>
                        <div class="radio">
                            <label>
                                <input type="radio" name="bal_type" id="rmt" value="RMT">
                                RMT
                            </label>
                        </div>
                        <div class="radio">
                            <label>
                                <input type="radio" name="bal_type" id="cash" value="Cash">
                                Cash
                            </label>
                        </div>
                        <input type="submit" class="btn-sm mt--1" value="Update balance">
                    </form>
                </div>

                <input type="text" id="search_traders" onkeyup="search_traders()" placeholder="Search for names..">

                <div class="table-responsive" id="traders_table">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Id</th>
                            <th>User</th>
                            <th>RMT</th>
                            <th>Cash</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $action_class = null;
                        $btn_name = null;
                        if (is_array($traders) && !empty($traders)) {
                            foreach ($traders as $index=>$trader) {
                                if ($trader->is_active) {
                                    $action_class = 'off';
                                    $btn_name = "Deactivate Account";
                                } else {
                                    $action_class = 'on';
                                    $btn_name = "Activate Account";
                                }
                        ?>
                                <tr>
                                    <td><?=$trader->UID?></td>
                                    <td><a href="http://facebook.com/<?=$trader->FACEBOOK_ID?>" target="_blank"><?=$trader->Name?></a></td>
                                    <td><?=$trader->BTC?></td>
                                    <td><?=$trader->CASH?></td>
                                    <td><input type="button" class="btn-ra" id="<?=$action_class.'_'.$trader->UID?>" value="<?=$btn_name?>"></td>
                                </tr>
                            <?php }
                            }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>


            <!--Transfer tokens-->
            <div class="container mt--2">
                <h2>Transfer tokens</h2>
                <div class="mt--2 mb--2 p--1">
                    <div class="form-inline">
                        <div class="form-group">
                            <label class="sr-only" for="cust_id-fr">From (User Id)</label>
                            <input type="number" class="form-control" name="cust_id-fr" id="cust_id-fr" placeholder="From (User Id)">
                        </div>
                        <div class="form-group">
                            <label class="sr-only" for="cust_id_to">To (User Id)</label>
                            <input type="number" class="form-control" name="cust_id_to" id="cust_id_to" placeholder="To (User Id)">
                        </div>
                        <div class="form-group">
                            <label class="sr-only" for="toke_amt">Amount of RMTs to transfer </label>
                            <input type="text" class="form-control" name="toke_amt" id="toke_amt" placeholder="Amount of RMTs to transfer">
                        </div>
                        <input type="submit" class="btn-sm mt--1" id="btn-tr" value="Transfer tokens">
                    </div>
                </div>
            </div>

            <!--History-->
            
            <div class="container mt--2">
            <div class="table-responsive">
                <div class="table-responsive">
            <?php $list_bal_changes = $OrderClass->list_root_bal_changes(); ?>
                    <h2>Update History</h2>
                    <input type="text" id="audit_input" onkeyup="search_audit_table()" placeholder="Search for names or id..">
                    <table class="table" id="audit_table">
                        <thead>
                        <tr>
                            <th>S.No</th>
                            <th>Investor's Id</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Previous Balance</th>
                            <th>Updated Balance</th>
                            <th>Type</th>
                            <th>Date</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php  foreach ($list_bal_changes as $ch): 
                        
                        $money_type = 'Invalid';
                            if($ch->type == 'btc') {
                                $money_type = 'Token';
                            } else if($ch->type == 'traditional') {
                                $money_type = 'Fiat';
                            }
                        ?>
                        <tr>
                            <td><?=$ch->BalStatusHistoryId?></td>
                            <td><?=$ch->user_id?></td>
                            <td><?=$ch->Name?></td>
                            <td><?=$ch->Email?></td>
                            <td><?=$ch->bal_prev?></td>
                            <td><?=$ch->bal_now?></td>
                            <td><?=$money_type?></td>
                            <td><?=$ch->UpdateDate?></td>
                        </tr>
                        <?php  endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
            
        <?php
    }
?>

<!--footer-->
<?php include_once 'footer.php'; ?>

<script>
    $(document).on('click', '.btn-ra', function (e) {
        e.preventDefault();
        var btn = $(this);
        var btn_id = $(this).attr('id');
        var btn_val = parseInt(btn_id.replace ( /[^\d.]/g, '' ));
        $.ajax({
            method:'post',
            url:'ajax/rm_root.php',
            data: { task : 'act_user', btn_id:btn_id}
        }).error(function(xhr, status, error) {
            console.log(error);
        }).success(function(data) {
            data = $.trim(data);
            if ($.trim(data) != '' && $.trim(data) != undefined && $.trim(data) != null) {
                if (data == 'on') {
                    btn.attr("id", 'off_'+btn_val);
                    btn.prop("value", "Deactivate Account");
                    $.notify({
                        title: "<strong>Success!:</strong> ",
                        message: "User activated successfully."
                    },{
                        type: 'info'
                    });
                } else if (data == 'off') {
                   btn.attr("id", 'on_'+btn_val);
                    btn.prop("value", "Activate Account");
                    $.notify({
                        title: "<strong>Success!:</strong> ",
                        message: "User de-activated successfully."
                    },{
                        type: 'info'
                    });
                } else {
                    $.notify({
                        title: "<strong>Process Failed!:</strong> ",
                        message: "Process could not be completed."
                    },{
                        type: 'warning'
                    });
                }

            } else {
                displayNotice("Process could not be completed. Try again later.", "failure");
            }
            run_all();
        });
    });
    
    function search_traders() {
        // Declare variables
        var input, filter, table, tr, td, i;
        input = document.getElementById("search_traders");
        filter = input.value.toUpperCase();
        table = document.getElementById("traders_table");
        tr = table.getElementsByTagName("tr");

        // Loop through all table rows, and hide those who don't match the search query
        for (i = 0; i < tr.length; i++) {
            td = tr[i].getElementsByTagName("td")[1];

            if (td) {
                if (td.innerHTML.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    }
    
    // Audit table
    function search_audit_table() {
        var input, filter, table, tr, td, i;
        input = document.getElementById("audit_input");
        filter = input.value.toUpperCase();
        table = document.getElementById("audit_table");
        tr = table.getElementsByTagName("tr");

        // Loop through all table rows, and hide those who don't match the search query
        if(!isNaN(filter)) {
            for (i = 0; i < tr.length; i++) {
            tdi = tr[i].getElementsByTagName("td")[1];
            
            if (tdi) {
                //filter = input.value;
                if (tdi.innerHTML.indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
            
            }
        } else {
            for (i = 0; i < tr.length; i++) {
            td = tr[i].getElementsByTagName("td")[2];
            if (td) {
                if (td.innerHTML.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }  
            }
        } 
        
    }

    // Token transfer
    $(document).on('click', '#btn-tr', function (e) {
        var _from = $('#cust_id-fr').val();
        var _to = $('#cust_id_to').val();
        var _tokens = $('#toke_amt').val();
        var job = 'transfer_tokens';
        var btn = this;

        $(btn).val('Please wait....').prop( "disabled", true );

        $.ajax({
                method: 'post',
                url: 'ajax/transfer_tokens.php',
                data: {job:job, _from:_from, _to:_to, _tokens:_tokens}
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
            });

    });
    

</script>
