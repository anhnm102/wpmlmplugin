<?php

function wpmlm_user_area() {
    $user_id = get_current_user_id();
    $user_details = wpmlm_get_user_details($user_id);
    $user = get_user_by('id', $user_id);
    $parent_id = $user_details->user_parent_id;
    $package_id = $user_details->package_id;
    $user_status = $user_details->user_status;


    if (($user_id) && ($user_status == 1)) {

        if ($_GET['reg_status']) {
            echo '<div class="panel-border"><div class="col-md-8 status-msg alert alert-success alert-dismissible text-center"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><b>' . base64_decode($_GET['reg_status']) . '</b></div></div>';
            ?>




            <h3 class="mlm-title">WP MLM User</h3>
            <div class="ioss-mlm-menu">
                <p><?php echo wpmlm_user_ewallet_management(); ?></p>
            </div>
            <?php
        } else if ($_GET['reg_failed']) {
            ?>
            <h3 class="mlm-title">WP MLM User Registration</h3>
            <?php
            echo '<div class="panel-border"><div class="col-md-8 status-msg alert alert-danger text-center"><b>' . base64_decode($_GET['reg_failed']) . '</b>
</div></div>';
        } else {
            ?>
            <h3>WP MLM User</h3>
            <div class="ioss-mlm-menu">
            <p><?php echo wpmlm_user_ewallet_management(); ?></p>
            </div>

            <?php
        }
    } else {


        wpmlm_register_user_html_page();
    }
}