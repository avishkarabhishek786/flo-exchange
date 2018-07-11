<?php
if (!isset($user_id)) {
    $user_id = $_SESSION['user_id'];
}
if (!isset($user_email)) {
    $user_email = $_SESSION['email'];
}
if (!isset($log_fullName)) {
    $log_fullName = $_SESSION['full_name'];
}
if (($user_email == null) && ($user_logged_in == true)) {

    if (isset($_POST['user_em_id'], $UserClass) && is_email($_POST['user_em_id'])) {
        $email = trim($_POST['user_em_id']);
        $updateEmail = $UserClass->input_user_email($email, $user_id);
        if ($updateEmail) {
            redirect_to("index.php?msg=Email updated as $email successfully.&type=success");
        }
        redirect_to("index.php?msg=Email could not be updated.&type=warning");
    }
    ?>
<script>
    $(document).ready(function() {
        $('#getUserInfo').modal('show');
    });
</script>
<div id="getUserInfo" class="modal fade" role="dialog">
    <div class="modal-dialog">

        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Your Email Id Required!</h4>
            </div>
            <form action="<?=$_SERVER["PHP_SELF"]?>" method="post">
                <div class="modal-body">
                    <h5>Hi <?=$log_fullName?></h5>
                    <p id="req_em_msg">We need your email address to send you mail when you refund cash.</p>
                    <input type="text" name="user_em_id" class="form-control" placeholder="Your Email Id here...">
                </div>
                <div class="modal-footer">
                    <input type="submit" class="btn btn--primary-2" id="req_em_btn" value="Submit">
                </div>
            </form>
        </div>

    </div>
</div>
<?php }