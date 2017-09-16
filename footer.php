<div class="container-fluid no-padding">
    <footer id="footer">
        <ul class="icons">
            <li><i class="fa fa-facebook-official" aria-hidden="true"></i></li>
            <li><i class="fa fa-twitter-square" aria-hidden="true"></i></li>
            <li><i class="fa fa-google-plus-square" aria-hidden="true"></i></li>
            <li><i class="fa fa-linkedin-square" aria-hidden="true"></i></li>
            <li><i class="fa fa-youtube-play" aria-hidden="true"></i></li>
        </ul>
        <ul class="copyright">
            <li>© Ranchi Mall. All rights reserved.</li> <li>Florin Coin Exchange</li>
        </ul>
    </footer>
</div>

<?php if(isset($loginUrl)) { ?>
    <div id="LoginModel" class="modal fade bs-login-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" style="top:33%; text-align: center;">
        <div class="vertical-alignment-helper">
            <div class="modal-dialog modal-lg vertical-align-center">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="myModalLabel">You are not logged in!</h4>
                    </div>
                    <div class="modal-body">
                        <a href="<?=$loginUrl?>" class="btn-login popup color-1 size-1 hover-1" name="fb_login"><i class="fa fa-facebook"></i>sign up via facebook</a>
                        <p style="color: #CCC;">Sign in to buy or sell Florincoins!</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php } ?>

<div id="MsgModel" class="modal fade bs-MsgModel-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" style="top:33%; text-align: center;">
    <div class="vertical-alignment-helper">
        <div class="modal-dialog modal-lg vertical-align-center">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="myModalLabelMsg"></h4>
                </div>
                <div class="modal-body">
                    <ul class="msg-ul"></ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>