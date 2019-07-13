<?php
function wpmlm_user_dashboard($user_id) {
    $user_row = wpmlm_getUserDetailsByParent($user_id);    
    $j_count =  wpmlm_getJoiningByTodayCountByUser($user_id);
    $user_zip = wpmlm_get_user_details_by_id_join($user_id)[0]->user_zip;
    
    
    $ewallet_credit = wpmlm_getEwalletAmountByUser('credit',$user_id);    
    $ewallet_debit = wpmlm_getEwalletAmountByUser('debit',$user_id);    
    $debit_amt = ($ewallet_debit->sum !=''? $ewallet_debit->sum:0);
    $credit_amt = ($ewallet_credit->sum !=''? $ewallet_credit->sum:0);    
    $bonus_amount = wpmlm_get_total_leg_amount_by_user_id($user_id);
    $bonus_amount_today = wpmlm_get_total_leg_amount_by_user_id_today($user_id);
    
    $bonus_total_amt = ($bonus_amount->total_amount !=''? $bonus_amount->total_amount:0);
    $bonus_total_amt_today = ($bonus_amount_today->total_amount !=''? $bonus_amount_today->total_amount:0);   
    $general = wpmlm_get_general_information();
    $year = date('Y');

    
    $joining_details = wpmlm_getJoiningDetailsUsersByMonth($user_id,$year);
    
    
    if ($joining_details) {
        $i = 0;
        foreach ($joining_details as $jdt) {
            $i++;
            if ($i == $jdt->month) {
                $joining_count[] = $jdt->count;
            } else {

                for ($j = $i; $j < $jdt->month; $j++) {
                    $joining_count[] = 0;
                }
                $joining_count[] = $jdt->count;
                $i++;
            }
        }
        $joining_count = implode($joining_count, ',');
    } else {
        $joining_count = '0,0,0,0,0,0,0,0,0,0,0,0';
    }
    ?>
    
    <div id="general-settings">
           <div class="panel-border col-md-12">
                 
   <div class="panel-border col-md-4 col-sm-4 panel-ioss-mlm">
      <div class="col-md-7 col-xs-6 col-md-7">
         <h4>Downlines</h4>
         <p>Total: <span><?php echo count($user_row);?> </span></p>
         <p>Today: <span><?php echo $j_count->count;?></span></p>
      </div>
      <div class="col-sm-5 col-xs-6 col-md-5">
         <img src="<?php echo plugins_url() . '/' . WP_MLM_PLUGIN_NAME . '/images/bar-chart.png'; ?>">
      </div>
   </div>
   <div class="panel-border col-md-4 col-sm-4 panel-ioss-mlm">
      <div class="col-md-7 col-xs-6 col-md-7">
         <h4>E-Wallet</h4>
         <p>gi cung duoc: <span><?php echo $user_zip;?></span></p>
         <p>Balance: <span><?php echo $general->company_currency;?><?php echo ($credit_amt - $debit_amt);?></span></p>
      </div>
      <div class="col-sm-5 col-xs-6 col-md-5">
         <img src="<?php echo plugins_url() . '/' . WP_MLM_PLUGIN_NAME . '/images/wallet.png'; ?>">
      </div>
   </div>
               
               
               <div class="panel-border  col-md-6" style="padding-right: 0px;padding-top: 42px !important;">
               <?php echo wpmlm_user_ewallet_details($user_id); ?>
        <table class="table table-striped table-bordered table-responsive-lg" cellspacing="0" width="100%">
          <thead>
          <caption class="user-table-profile">Recently joined users</caption>
            <tr>
              <th scope="col">#</th>
              <th scope="col">Username</th>
              <th scope="col">Fullname</th>
              <th scope="col">Email ID</th>
            </tr>
          </thead>
          
          <tbody class="panel-body content-class-mode">
            
                
            <?php
              $last_joined = wpmlm_get_recently_joined_users_by_parent($user_id,'4');
              $jcount = 0;
              foreach($last_joined as $lj){
                  $jcount++;
                  ?>
              <tr>
              <th scope="row"><?php echo $jcount;?></th>
              <td><?php echo $lj->user_login;?></td>
              <td><?php echo $lj->user_first_name.' '.$lj->user_second_name;?> </td>
              <td><?php echo $lj->user_email;?> </td>
              </tr>
            <?php }?>
              
            
            
          </tbody>
        </table>
        
      </div>           

        </div>
    </div>

    <?php
}