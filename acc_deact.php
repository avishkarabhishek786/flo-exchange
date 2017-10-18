<div class="mt--4">
    <?php
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != null) {

        $is_activated = $UserClass->check_user($_SESSION['user_id']);
        if(!$is_activated) { ?>
            <div class="container">
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-warning" role="alert">Your account is not active. Please contact admin.</div>
                    </div>
                </div>
            </div>
        <?php }} ?>
</div>
