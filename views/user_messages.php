<?php
/**
 * Created by PhpStorm.
 * User: Abhishek Kumar Sinha
 * Date: 9/28/2017
 * Time: 4:58 PM
 */

?>
<?php
    if($user_logged_in) {?>
<div class="container-fluid background-white-1 mt--4 p--2">
    <div class="container">
        <div class="col-lg-12">
            <h4 class="mt--2">My Messages</h4> <h5><a href="My_Messages" target="_blank">View All</a></h5>

            <div class="table-responsive mt--2">
                <table class="table table-striped" cellpadding="10">
                    <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Order No.</th>
                        <th>Message</th>
                        <th>Date</th>
                    </tr>
                    </thead>
                    <tbody id="user_msg"></tbody>
                </table>
            </div>

        </div>
    </div>
</div>
<?php } ?>