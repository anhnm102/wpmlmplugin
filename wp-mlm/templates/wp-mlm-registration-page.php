<?php
ob_start();
include (WP_MLM_PLUGIN_DIR . '/functions/php-validation.php');
include(WP_MLM_PLUGIN_DIR . '/functions/mlm-db-functions.php');

function wpmlm_register_user_html_page() {
   
    include_once(WP_MLM_PLUGIN_DIR . '/gateway/paypal/config.php');
    include_once(WP_MLM_PLUGIN_DIR . '/gateway/paypal/functions.php');
    include_once(WP_MLM_PLUGIN_DIR . '/gateway/paypal/paypal.class.php');
    
    $paypal = new MyPayPal();

    session_start();
    global $wpdb;
    global $current_user;
    $table_prefix = $wpdb->prefix;
    $success_msg = '';
    $form_style = '';
    $err_msg = '';
     

    $result = wpmlm_get_general_information();
    $reg_pack_type = $result->registration_type;
    $current_user_name = $current_user->user_login;
    $reg_amt = $result->registration_amt;
    


    if (isset($_POST['reg_submit']) && wp_verify_nonce($_POST['reg_submit'], 'register_action')) {
        $sponsor = 'admin';
        $user_first_name = sanitize_text_field($_POST['fname']);
        $user_second_name = sanitize_text_field($_POST['lname']);
        $user_address = sanitize_text_field($_POST['address1']);
        $user_city = sanitize_text_field($_POST['city']);
        $user_state = sanitize_text_field($_POST['state']);
        $user_country = sanitize_text_field($_POST['country']);
        $user_zip = sanitize_text_field($_POST['zip']);
        $user_mobile = sanitize_text_field($_POST['contact_no']);
        $user_dob = sanitize_text_field($_POST['date_of_birth']);
        $user_registration_type = sanitize_text_field($_POST['user_registration_type']);

        $the_user = get_user_by('login', $sponsor);
        $user_parent_id = $the_user->ID;

        $invalid_usernames = array('admin');
        $username = sanitize_user($username);

        $user_level = wpmlm_get_user_level_by_parent_id($user_parent_id);
        $user_ref = get_current_user_id();
        $_SESSION['user_ref'] = $user_ref;

        $user_info = get_userdata($user_ref);
        $user_email = $user_info->user_email;
        $_SESSION['user_email'] = $user_email;


        $user_details = array(
            'user_ref_id' => $user_ref,
            'user_parent_id' => $user_parent_id,
            'user_first_name' => $user_first_name,
            'user_second_name' => $user_second_name,
            'user_address' => $user_address,
            'user_city' => $user_city,
            'user_state' => $user_state,
            'user_country' => $user_country,
            'user_zip' => $user_zip,
            'user_mobile' => $user_mobile,
            'user_email' => $user_email,
            'user_dob' => $user_dob,
            'user_level' => $user_level,
            'user_registration_type' => $user_registration_type,
            'join_date' => date("Y-m-d H:i:s"),
            'user_status' => 1,
            'package_id' => $_SESSION['session_pkg_id']
        );


        $_SESSION['user_details'] = $user_details;
        if ($user_registration_type == 'free_join') {
            wp_update_user(array('ID' => $user_ref, 'role' => 'contributor'));
            $success_msg = wpmlm_insert_user_registration_details($user_details);

            if ($success_msg) {
                if ($reg_amt != 0) {
                    wpmlm_insert_leg_amount($user_ref, $_SESSION['session_pkg_id']);
                }
                $form_style = "display:none";
                $tran_pass = wpmlm_getRandTransPasscode(8);
                $hash_tran_pass = wp_hash_password($tran_pass);
                $tran_pass_details = array(
                    'user_id' => $user_ref,
                    'tran_password' => $hash_tran_pass
                );
                wpmlm_insert_tran_password($tran_pass_details);
                wpmlm_insertBalanceAmount($user_ref);
                //sendMailRegistration($user_email, $username, $password, $user_first_name, $user_second_name);
                //sendMailTransactionPass($user_email, $tran_pass);
                unset($_SESSION['session_pkg_id']);
                global $wp;
                $current_url = admin_url();

                $reg_msg = base64_encode('Registration Completed Successfully!');

                wp_redirect($current_url . 'admin.php?page=mlm-user-settings');
                exit();
            } else {
                $reg_msg = base64_encode('Sorry! Registration Failed, Please try again');
                wp_redirect($current_url . 'admin.php?page=mlm-user-settings&reg_failed=' . $reg_msg);
                exit();
            }
        }
    }



    if ($user_registration_type == 'paypal') {
        if (isset($_GET['paypal']) == 'checkout') {

            $products = [];

            $products[0]['ItemName'] = sanitize_text_field($_POST['itemname']);
            $products[0]['ItemPrice'] = sanitize_text_field($_POST['itemprice']);
            $products[0]['ItemQty'] = sanitize_text_field($_POST['itemQty']);

            $charges = [];

            //Other important variables like tax, shipping cost
            $charges['TotalTaxAmount'] = 0;
            $charges['HandalingCost'] = 0;
            $charges['InsuranceCost'] = 0;
            $charges['ShippinDiscount'] = 0;
            $charges['ShippinCost'] = 0;

            $paypal->SetExpressCheckOut($products, $charges);
        }
    }


    if ($_GET['token'] != '' && $_GET['PayerID'] != '') {


        $paypal_res = $paypal->DoExpressCheckoutPayment();
        $user_ref = $_SESSION['user_ref'];

        if ('Completed' == $paypal_res["PAYMENTINFO_0_PAYMENTSTATUS"]) {

            if ($_SESSION['user_details']) {

                wp_update_user(array('ID' => $user_ref, 'role' => 'contributor'));
                $success_msg = wpmlm_insert_user_registration_details($_SESSION['user_details']);

                if ($success_msg) {
                    wpmlm_insert_leg_amount($user_ref, $_SESSION['session_pkg_id']);

                    $tran_pass = wpmlm_getRandTransPasscode(8);
                    $hash_tran_pass = wp_hash_password($tran_pass);
                    $tran_pass_details = array(
                        'user_id' => $user_ref,
                        'tran_password' => $hash_tran_pass
                    );
                    wpmlm_insert_tran_password($tran_pass_details);
                    wpmlm_insertBalanceAmount($user_ref);
                    //sendMailRegistration($_SESSION['user_email'],$_SESSION['user_name'],$_SESSION['password'],$_SESSION['user_first_name'],$_SESSION['user_second_name']);                    
                    //sendMailTransactionPass($_SESSION['user_email'], $tran_pass);

                    unset($_SESSION['user_details']);
                    unset($_SESSION['session_pkg_id']);
                    unset($_SESSION['user_email']);

                    $current_url = admin_url();
                    $reg_msg = base64_encode('Registration Completed Successfully!');
                    wp_redirect($current_url . 'admin.php?page=mlm-user-settings');
                    exit();
                }
            }
        } else {
            deleteUser($user_ref);
            unset($_SESSION['user_details']);
            unset($_SESSION['session_pkg_id']);
            unset($_SESSION['user_email']);
        }
    } else if (_GET('token') != '') {
        $paypal_res = $paypal->DoExpressCheckoutPayment();
        if ('Failure' == $paypal_res["ACK"]) {

            deleteUser($_SESSION['user_ref']);

            unset($_SESSION['user_details']);
            unset($_SESSION['session_pkg_id']);
            unset($_SESSION['user_second_name']);
            unset($_SESSION['user_email']);
            $reg_msg = base64_encode('Sorry! Registration Failed, Please try again');
            wp_redirect($current_url . 'admin.php?page=mlm-user-settings&reg_failed=' . $reg_msg);
            exit();
        }
    }
    ?>

    <div class="panel-border-heading" style="visibility: hidden">
        <h3>WP MLM User Registration</h3>
    </div>
    <div class="ioss-mlm-menu panel-border" style="visibility: hidden">
        <input id="ioss-mlm-tab1" class="tab_class" type="radio" name="tabs" checked>
    <!--    <section id="content1"></section>-->

        <div class="col-md-12 panel-border">
            <div class="col-md-6 regOuterDiv">
                <div class="col-md-12">

                    <h4 class="text-center">Please complete the registration to join the MLM network</h4>
                    <form id="regForm" method="post" action="" style="<?php echo $form_style; ?>">
                        <?php echo $success_msg; ?>
                        <?php echo $err_msg; ?>
                        <div class="alert alert-info selected-pkg-info"></div>


                        <?php
                        $step2 = 'STEP 1';
                        $step3 = 'STEP 2';
                        if ($reg_pack_type != 'with_out_package') {

                            $step1 = 'STEP 1';
                            $step2 = 'STEP 2';
                            $step3 = 'STEP 3';
                            ?>

                            <div class="tab"><h1><?php echo $step1; ?>: Select Package</h1>
                                <?php
                                $packages = wpmlm_select_all_packages();
                                if (count($packages) > 0) {
                                    $result2 = wpmlm_get_general_information();
                                    ?>
                                    <p><select name="package_select" oninput="this.className = ''" id="package_select">
                                            <option value="" tabindex="1">Select Package</option>
                                            <?php
                                            $results = wpmlm_select_all_packages();
                                            foreach ($results as $res) {
                                                ?>
                                                <option value="<?php echo $res->id; ?>"><?php echo $res->package_name . ' - ' . $result2->company_currency . $res->package_price; ?>
                                                </option>
                                            <?php } ?>
                                        </select></p>
                                <?php } ?>
                            </div>

                        <?php } ?>

                        <div class="tab"><h1><?php echo $step2; ?>: User Info</h1>
                            <p><input type="text" oninput="this.className = 'required-field'" name="sname" id="sname"  placeholder="* Enter Sponsor Name"  tabindex="2" class="required-field" ></p>                
                        </div>
                        <div class="tab"><h1><?php echo $step3; ?>: Payment Mode</h1>

                            <div class="row" style="font-size:18px">
                                <?php
                                if ($reg_pack_type != 'with_out_package') {
                                    echo '<div class="col-sm-12"><p>Package Amount: ' . $result->company_currency . '<span id="amount_span"><span></p></div>';
                                } else {
                                    echo '<p>Registration Amount: ' . $result->company_currency . '<span id="amount_span">' . $result->registration_amt . '<span></p>';
                                }
                                ?>
                            </div>
                            <?php
                            $results = wpmlm_select_reg_type();
                            $ckd = 0;
                            $reg_type = 'paypal';
                            foreach ($results as $res) {
                                $ckd++;
                                if ($ckd == 1) {
                                    $ckd = 'checked';
                                } else {
                                    $ckd = '';
                                }
                                ?>




                                <div class="row">

                                    <?php if ($res->reg_type == 'free_join') { ?>
                                        <div class="col-md-1"><input <?php echo $ckd; ?> type="radio"   value="<?php echo $res->reg_type; ?>" name="user_registration_type" class="free_join radiobutton" tabindex="1"> </div>
                                        <div class="col-md-4"><label><?php echo ucwords(str_replace("_", " ", $res->reg_type)); ?></label></div>


                                        <?php
                                        $reg_type = '';
                                    } else {
                                        ?> 
                                        <div class="col-md-1" style="margin-top: 15px;"><input <?php echo $ckd; ?> type="radio"   value="paypal" name="user_registration_type" class="paid_join radiobutton" tabindex="2"> </div>
                                        <div class="col-md-4"><img src="<?php echo plugins_url() . '/' . WP_MLM_PLUGIN_NAME . '/gateway/paypal/paypal.png'; ?>"></div>

                                    <?php } ?>


                                    <div class="col-md-7"></div>
                                </div>
                            <?php } ?>

                        </div>

                        <div class="reg-next-prev-div-outer">
                            <div class="col-md-12 please-wait" ><img src="<?php echo plugins_url() . '/' . WP_MLM_PLUGIN_NAME . '/images/please-wait.gif'; ?>"></div>
                            <div class="reg-next-prev-div">
                                <button type="button"  class="btn" id="prevBtn" onclick="nextPrev(-1)">Previous</button>
                                <button type="button"  class="btn btn-danger" id="nextBtn"   onclick="nextPrev(1)" tabindex="18">Next</button>
                            </div>
                        </div>
                        <!-- Circles which indicates the steps of the form: -->
                        <div class="reg-steps-circle">
                            <span class="step"></span>
                            <span class="step"></span>
                            <?php if ($reg_pack_type == 'with_package') { ?>
                                <span class="step"></span>
                            <?php } ?>
                        </div>


                        <input type="hidden" id="payment_option" name="payment_option" value="<?php echo $reg_type; ?>" >
                        <input type="hidden" id="itemname" name="itemname" value="<?php echo ($reg_pack_type == 'with_out_package' ? 'Registration Fee' : ''); ?>" /> 
                        <input type="hidden" id="itemprice" name="itemprice" value="<?php echo ($reg_pack_type == 'with_out_package' ? $result->registration_amt : ''); ?>" />
                        <input type="hidden" name="itemQty" value="1" />
                        <input type="hidden" name="field_valid" id="field_valid" value="" />
                        <input type="hidden" name="admin-path" id="admin-path" value="<?php echo admin_url(); ?>">  

                        <?php wp_nonce_field('register_action', 'reg_submit'); ?> 
                    </form>

                </div>
            </div>
        </div>
    </div>


    <script>
        jQuery(document).ready(function ($) {
            document.getElementById("regForm").submit();
        });
    </script>   

    <?php
}
