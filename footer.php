<?php if(isset($loginUrl)) { ?>
    <!-- Modal -->
    <div id="LoginModel" class="modal animated fadeInDown" role="dialog">
        <div class="modal-dialog">

            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header text-center">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Login to continue...</h4>
                </div>
                <p class="">
                    <a href="<?=$loginUrl?>"><div class="btn btn--facebook-2">Continue with Facebook</div></a>
                </p>
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

</body>
</html>
