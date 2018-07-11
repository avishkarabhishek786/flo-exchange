<?php
/**
 * Created by PhpStorm.
 * User: Abhishek Kumar Sinha
 * Date: 10/21/2017
 * Time: 3:36 PM
 */

if($user_logged_in):
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $accounts = $OrderClass->get_bank_details($user_id);
    ?>
    <div class="container-fluid background-white-1 mt--4 p--2">
        <div class="container">
            <div class="col-lg-12">
                <h4 class="mt--2">My Actions</h4>
                <ul class="nav nav-tabs">
                    <li class="active"><a data-toggle="tab" href="#home">Send Tokens to FlorinCoin Blockchain</a></li>
                    <li><a data-toggle="tab" href="#menu1">Add new Bank Account</a></li>
                    <li><a data-toggle="tab" href="#menu2">Send Cash to my Bank Account</a></li>
                    <li><a data-toggle="tab" href="#menu3">Pay me in Bitcoins</a></li>
                    <li><a data-toggle="tab" href="#menu4" id="get_btc_price">Load Cash to my trading account</a></li>
                    <!--<li><a data-toggle="tab" href="#menu5">Send RMTs to Blockchain Contracts Exchange</a></li>-->
                </ul>

                <div class="tab-content">
                    <div id="home" class="tab-pane fade in active">
                        <h3>Send Tokens to FlorinCoin Blockchain</h3>
                        <p>Please fill the form below to continue.</p>

                        <label for="flo_addr">Your Florincoin wallet address:</label>
                        <input type="text" class="form-control" id="flo_addr" required>

                        <label for="rmt_amnt">Number of Tokens to transfer:</label>
                        <input type="text" class="form-control" id="rmt_amnt" required>

                        <label for="remarks_flo">Remarks (optional)</label>
                        <textarea id="remarks_flo" cols="30" rows="10" class="form-control" placeholder="max 250 characters" maxlength="250"></textarea>

                        <input type="button" value="Transfer RMTs" class="btn btn-primary mt--1" id="btn_rtm_trans">

                    </div>
                    <div id="menu1" class="tab-pane fade">
                        <h3>Add Bank Account</h3>
                        <p>Please provide your bank details below:</p>

                        <div class="col-lg-12">

                            <div id="bk_details_form">

                                <label for="acc_name">Account Holder's Name</label>
                                <input type="text" class="form-control" id="acc_name" required>

                                <label for="acc_numb">Account Number</label>
                                <input type="text" class="form-control" id="acc_numb" required>

                                <label for="bk_name">Bank Name</label>
                                <input type="text" class="form-control" id="bk_name" required>

                                <label for="bk_branch">Branch Name</label>
                                <input type="text" class="form-control" id="bk_branch" required>

                                <label for="bk_ctry">Country</label>
                                <input type="text" class="form-control" id="bk_ctry" required>

                                <label for="bank_addr">Full Bank Detail:</label>
                                <textarea class="form-control" id="bank_addr" required></textarea>

                                <input type="button" value="Add Account" class="btn btn-primary mt--1" id="btn_bk_add">

                            </div>
                        </div>

                    </div>
                    <div id="menu2" class="tab-pane fade">
                        <h3>Send Cash to my Bank Account</h3>
                        <p>Please select your bank account and provide amount to be sent below:</p>

                        <div class="col-lg-12">
                            <div id="bk_tr_form">
                                <label for="bk_sel">Choose accounts...</label>
                                <select name="bk_sel" id="bk_sel" class="form-control">
                                    <?php if (is_array($accounts) && !empty($accounts)):
                                        foreach ($accounts as $i=>$account): ?>
                                            <option value="<?=$account->acc_num?>"><?=$account->acc_num?></option>
                                        <?php endforeach; endif; ?>
                                </select>

                                <label for="tr_amt">Provide Amount to transfer <span class="text-danger">(Amount must be in USD)</span></label>
                                <input type="text" class="form-control" id="tr_amt" placeholder="Amount must be in USD" required>

                                <label for="remarks_bal_tr">Remarks (optional)</label>
                                <textarea name="remarks_bal_tr" id="remarks_bal_tr" cols="30" rows="10" class="form-control" placeholder="max 250 characters" maxlength="250"></textarea>

                                <br<br>
                                <input type="button" id="btn_bk_tr" class="btn btn--primary-1 mt--1" value="Transfer">
                            </div>

                        </div>

                    </div>

                    <div id="menu3" class="tab-pane fade">
                        <h3>Receive refunded money in Bitcoins</h3>
                        <p>You need to have a Bitcoin address to use this feature.</p>
                        <label for="b_amnt">Please enter amount to refund <span class="text-danger">(Amount must be in USD)</span></label>
                        <input type="text" class="form-control" id="b_amnt">
                        <label for="invst_btc_addr">Your Bitcoin Address</label>
                        <input type="text" class="form-control" id="invst_btc_addr">
                        <label for="b_remarks">Remarks (optional)</label>
                        <textarea id="b_remarks" cols="30" rows="10" class="form-control"></textarea>
                        <input type="button" id="btn_btc_tr" class="btn btn--primary-1 mt--1" value="Send">
                    </div>

                    <div id="menu4" class="tab-pane fade">
                        <input type="hidden" id="btc_today" value="">
                        <h3>Load Cash to my trading account</h3>
                        <p>Please fill the form with correct information. Ranchi Mall Bitcoin address is provided below.</p>
                        <label for="usd_amt">Enter Price In USD</label>
                        <input type="text" class="form-control" id="usd_amt">
                        <label for="btc_eqv">Bitcoin Equivalent</label>
                        <span id="btc_eqv" class="text-danger">Equivalent Bitcoins will appear here</span>
                        <label for="lcma_remarks">Remarks (optional)</label>
                        <textarea id="lcma_remarks" cols="30" rows="10" class="form-control"></textarea>
                        <input type="button" value="Send Details" class="btn btn-primary mt--1" id="lcma_btn">

                        <h5>AFTER SUBMITTING THE FORM PLEASE SEND THE BITCOINS TO THE FOLLOWING ADDRESS: <strong class="text--bold text-info">1G1ERvMD1mcNuuuyL3cY4ZgmCjth5K3GuH</strong></h5>
                    </div>
                    
                    <div id="menu5" class="tab-pane fade">
                        <h3>Send RMT to Blockchain Contract Exchange</h3>
                        <label for="_nrs">Please enter the number of RMTs to send.</label>
                        <input class="form-control" id="_nrs" type="text"/>
                        <input type="button" value="Transfer RMTs" class="btn btn-primary mt--1" id="nrs_btn">
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>