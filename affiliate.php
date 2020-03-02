<?php 
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] != true){
        header("location: index.php");
    exit;
  }


  // Include config file
  require_once "config.php";
  require_once "affiliate-stats.php";
  require_once "affiliates-link-stats.php";
  require_once "affiliate-code-stats.php";
  require_once "current-session.php";
//   require_once "affiliate-data.php";
  require_once "confirm-table.php";
  require_once "referral-data.php";
  require_once "commission.php";
  require_once "utils.php";


  $person_email = $_SESSION['Email'];
  $invite_code = $_SESSION['Code'];
  $general_url = "";
  $affiliate_id;
  $affiliate_link = "";
  $start_date = "";
  $end_date = "";
  $display_stats_data = array();
  $display_link_data = array();
  $display_app_data = array();
  $display_code_data = array();
  $affiliate_registration_date = "";


  // Getting the affiliate url
  //1. fetch general url
    $sql = "select uri from wp_aff_uris where uri_id = 1";
    $result = $conn->query($sql);


    if ($result->num_rows > 0) {
        // output data of each row
        while($row = $result->fetch_assoc()) {
            $general_url = $row['uri'];
        }
    } 

    //2. get affiliate id;

        $sql = "select affiliate_id, from_date from wp_aff_affiliates where email = ?";

        $st = $conn->prepare($sql); 
        $st->bind_param("s", $person_email);
        $st->execute();

        // get the mysqli result
        $res = $st->get_result(); 
        $st->close();

    
        if ($res->num_rows > 0) {
            while($row = $res->fetch_assoc()) {
                $affiliate_id =  $row['affiliate_id'];
                $affiliate_registration_date = $row['from_date'];
            }
        }

        // split general url and append id
        $gen = explode("=", $general_url);

        $affiliate_link = "$gen[0]=$affiliate_id";
//=====================================================

        // get affiliate registration date
        


        // fetch current session date
        $todayDate = date("Y-m-d");

        $sql = "SELECT Start_Date, End_Date from dwta_sessions where ? between Start_Date and End_Date";

        $stmt = $conn->prepare($sql); 
        $stmt->bind_param("s", $todayDate);
        $stmt->execute();

        // get the mysqli result
        $result = $stmt->get_result(); 
        $stmt->close();

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $start_date = $row['Start_Date'];
                $end_date = $row['End_Date'];
            }
        }
    //=================================================

    $all_hit_dates = array();
    // fetch all hits for this affiliate both current and past
    $sql = "SELECT date from wp_aff_hits where affiliate_id = ?";
    $stmt = $conn->prepare($sql); 
        $stmt->bind_param("i", $affiliate_id);
        $stmt->execute();

        // get the mysqli result
        $result = $stmt->get_result(); 
        $stmt->close();

        if ($result->num_rows > 0) {
            // output data of each row
            while($row = $result->fetch_assoc()) {
                array_push($all_hit_dates, $row['date']);
            }
        } 

        // sort out the relevant date to display
        $date_to_start = "";
        $date_to_end = "";

        if ($affiliate_registration_date > $start_date){
            $date_to_start = $affiliate_registration_date;
        }else{
            $date_to_start = $start_date;
        }


        if ($todayDate < $end_date){
            $date_to_end = $todayDate;
        }else{
            $date_to_end = $end_date;
        }

        


        $relevant_dates = array();
        $date_from = strtotime($date_to_end); // Convert date to a UNIX timestamp  

        $date_to = strtotime($date_to_end); 
        
        // Loop from the start date to end date and output all dates inbetween  
        for ($i=$date_from; $i<=$date_to; $i+=86400) {  
            array_push($relevant_dates, date("Y-m-d", $i));
        }/// to be continued................................................................

        // sorting the hits dates
        $hit_dates = array();

        foreach ($relevant_dates as $r_date){ 
            $count = 0;
            foreach($all_hit_dates as $a_date){
                if ($a_date === $r_date){
                    ++$count;
                    $hit_dates[$r_date] = $count; //------------------============================================---------------------------------------------------
                }
            }
            
          }


        // Unique visits
        $user_agent_ids = array();

        $sql = "SELECT user_agent_id from wp_aff_user_agents";

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            // output data of each row
            while($row = $result->fetch_assoc()) {
                array_push($user_agent_ids, $row['user_agent_id']);
            }
        } 

        // get date for unique visits
       $unique_visits_with_date = array();
            for ($i = 0; $i < count($user_agent_ids); $i++){
                $sql = "select date from wp_aff_hits where user_agent_id = ? and affiliate_id = ?  order by date limit 1";
    
                $stmt = $conn->prepare($sql); 
                $stmt->bind_param("ii", $user_agent_ids[$i], $affiliate_id);
                $stmt->execute();
        
                // get the mysqli result
                $result = $stmt->get_result(); 
                $stmt->close();
        
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $unique_visits_with_date[$user_agent_ids[$i]] = $row['date'];
                    }
                }
            }

        // get the number of applications
        $application_list = array();

        foreach($relevant_dates as $one_date){
            $sql = "select count(*) from wp_aff_referrals where affiliate_id = ? and (datetime > ? and datetime < ?)";

            $d1 = "$one_date 00:00:00";
            $d2 = "$one_date 23:59:59";
        

            $stmt = $conn->prepare($sql); 
            $stmt->bind_param("iss", $affiliate_id, $d1, $d2);
            $stmt->execute();

            // get the mysqli result
            $result = $stmt->get_result(); 
            $stmt->close();

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $application_list[$one_date] = $row['count(*)'];
                }
            }
        }


        // make a list from all compiled data
        foreach ($relevant_dates as $r_date){ 
            $statsTable = new AffiliateStats();
            $statsTable->date = $r_date;

            foreach($hit_dates as $date_key => $count_value) { 
                if ($date_key === $r_date){
                    $statsTable->total_hit = $count_value;
                }
                
            } 

            $count = 0;
            foreach($unique_visits_with_date as $agent_id => $date_value) { 
               if ($r_date === $date_value){
                   $count++;
               }
            } 
            $statsTable->unique_visit = $count;

            ///============================

            foreach($application_list as $app_date => $count_number) { 
                if ($r_date === $app_date){
                    $statsTable->application = $count_number;
                }
             } 

            
            $statsTable->site_visit = 0;

            array_push($display_stats_data, $statsTable);
        }
        //=================== END OF BY STATS ==========================================================================================================================================================//

        //=====================START OF BY LINK =============================================================================================================================================//

        // get email that is linked to referals as currency
        $email_arrays = array();

        $sql = "SELECT currency_id from wp_aff_referrals WHERE affiliate_id = ? and (datetime > ? and datetime < ?) order by datetime;";

        $d1 = "$start_date 00:00:00";
        $d2 = "$end_date 23:59:59";
       
        $stmt = $conn->prepare($sql); 
        $stmt->bind_param("iss", $affiliate_id, $d1, $d2);
        $stmt->execute();

        
        $result = $stmt->get_result(); 
        $stmt->close();

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                array_push($email_arrays, $row['currency_id']);
            }
        }

        // use currency to get form submission id
        $item_ids = array();
        foreach($email_arrays as $one_mail){
       
            $w = strtolower($one_mail.'%');
            $sql = "SELECT item_id from wp_frm_item_metas where meta_value like ? and (created_at between ? and ?) order by created_at";

            $stmt = $conn->prepare($sql); 
            $stmt->bind_param("sss", $w, $start_date, $end_date);
            $stmt->execute();
                    
            $result = $stmt->get_result(); 
            $stmt->close();

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    
                    array_push($item_ids, $row['item_id']);
                }
            }

        }

        // now we have item ids form, so get the application status/details
        $payment_status = array();
        $approval_status = array();
        foreach($item_ids as $id){
            $sql = "SELECT * from dwta_students_payment where Application_ID = ?";
            $stmt = $conn->prepare($sql); 
            $stmt->bind_param("s", $id);
            $stmt->execute();

            $result = $stmt->get_result(); 
            $stmt->close();

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    
                    array_push($payment_status, $row['Payment_Status']);
                    array_push($approval_status, $row['Approval_Date']);

                }
            }
        }

        // get referrals data
        $sql = "SELECT * FROM wp_aff_referrals WHERE affiliate_id = ? and (datetime > ? and datetime < ?) order by datetime;";


        $d1 = "$start_date 00:00:00";
        $d2 = "$end_date 23:59:59";
        
    
        $stmt = $conn->prepare($sql); 
        $stmt->bind_param("iss", $affiliate_id, $d1, $d2);
        $stmt->execute();

        
        $result = $stmt->get_result(); 
        $stmt->close();

        if ($result->num_rows > 0) {
            $index = 0;
            while($row = $result->fetch_assoc()) {
                $linkTable = new AffiliateLinkStats();
                $linkTable->app_number = "DWTA  ".$row['referral_id'];
                $q = explode(" ", $row['datetime']);
                $linkTable->sub_date = $q[0];
                if ($index < count($payment_status))
                $linkTable->complete_registration = $payment_status[$index];
                if ($index < count($approval_status))
                $linkTable->$com_date = $approval_status[$index];

                array_push($display_link_data, $linkTable);
                $index++;
            }
        }

        //===================END OF BY LINK ====================================================================================================//

        //===================START OF BY CODE===========================================================================================//

        // use the affiliate code to get form id
$sql = "SELECT id, created_at, Approval_Date, Payment_Status from wp_frm_items join dwta_students_payment on dwta_students_payment.Application_ID = wp_frm_items.id where name = ? and (created_at between ?  and ?);";


            $d1 = "$start_date 00:00:00";
            $d2 = "$end_date 23:59:59";

        $stmt = $conn->prepare($sql); 
        $stmt->bind_param("sss", $invite_code, $d1, $d2);
        $stmt->execute();

        
        $result = $stmt->get_result(); 
        $stmt->close();

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $codeTable = new AffiliateCodeStats();
                $codeTable->app_number = "DWTA  ".$row['id'];
                $codeTable->complete_registration = $row['Payment_Status'];
                $codeTable->com_date = $row['Approval_Date'];
                $codeTable->sub_date = $row['created_at'];

                array_push($display_code_data, $codeTable);
            }
        }
        
    // ======================PAYOUT =====================================================================================================================//

    

    class Confirm{
        public $array_for_aff_ids = array();
        public $array_for_ids = array();
        private $conn;
        private $affiliate_id;

        function __construct($conn, $aff_id)
        {
            $this->conn = $conn;
            $this->affiliate_id = $aff_id;
        }

        function confirmPayment(){
            $all_sessions = array();
                // $all_affiliates =  array();
                $table = array();
                $all_promo = array();
                $all_regular_commission = array();
           
                
        
                // get promo details
                $sql = "select Amount, Lower_Boundary, Upper_Boundary from promo_commission_details";
                $result = $this->conn->query($sql);
        
                if ($result->num_rows > 0) {
        
                    while($row = $result->fetch_assoc()) {
                        $pro = new Commission($row['Amount'], $row['Lower_Boundary'], $row['Upper_Boundary']);
        
                        array_push($all_promo, $pro);
                    }
                }
        
        
                // get regular details
                $sql = "select Amount, Lower_Boundary, Upper_Boundary from commission_details";
                $result = $this->conn->query($sql);
        
                if ($result->num_rows > 0) {
        
                    while($row = $result->fetch_assoc()) {
                        $pro = new Commission($row['Amount'], $row['Lower_Boundary'], $row['Upper_Boundary']);
        
                        array_push($all_regular_commission, $pro);
                    }
                }
        
        
        
                // get intake sessions
                $thisYear = date('Y');
        
                $sql = "SELECT * from dwta_sessions";
                $result = $this->conn->query($sql);
        
                if ($result->num_rows > 0) {
        
                    while($row = $result->fetch_assoc()) {
                        $session = new CurrentSession();
                        $session->id = $row['Session_ID']; 
                        $session->name = $row['Name'];
                        $session->start_date = getDate(strtotime($row['Start_Date']));
                        $session->end_date = getDate(strtotime($row['End_Date']));
                        $session->start_date_raw = $row['Start_Date']; 
                        $session->end_date_raw = $row['End_Date']; 
        
                        array_push($all_sessions, $session);
                    }
                }
        
    
      
                $i = 0;
                $the_data = 0 ;
                $this->array_for_aff_ids = array();
                $this->array_for_ids = array();
                
        
                foreach($all_sessions as $one_ses){
                    $sess_name = explode(" ", $one_ses->name)[0]."  ".explode("-", $one_ses->start_date_raw)[0];
                    $com = "No";
            
        
                        $i++;
        
                       $promo = 0;
        
                        // get promo referrals
                        $sql = "SELECT count(*) from promo_referals where (createdDatetime >= ? and createdDatetime <= ?) and affiliate_id = ?";
        
                        $stmt = $this->conn->prepare($sql); 
                        $stmt->bind_param("sss", $one_ses->start_date_raw, $one_ses->end_date_raw, $this->affiliate_id);
                        $stmt->execute();
        
                        $result = $stmt->get_result(); 
                        $stmt->close();
        
                        if ($result->num_rows > 0) {
        
                            while($row = $result->fetch_assoc()) {
                                $promo = $row['count(*)'];
                                
                            }
                        }

                         // get email that is linked to referals as currency
                        $email_arrays = array();
        
                        $sql = "SELECT currency_id from wp_aff_referrals WHERE affiliate_id = ? and (datetime > ? and datetime < ?) order by datetime;";
        
                        $d1 = "$one_ses->start_date_raw 00:00:00";
                        $d2 = "$one_ses->end_date_raw 23:59:59";
                    
                        $stmt = $this->conn->prepare($sql); 
                        $stmt->bind_param("iss", $this->affiliate_id, $d1, $d2);
                        $stmt->execute();
        
                        
                        $result = $stmt->get_result(); 
                        $stmt->close();
        
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                array_push($email_arrays, $row['currency_id']);
                            }
                        }
        
                        // use currency to get form submission id
                        $item_ids = array();
                        foreach($email_arrays as $one_mail){
                    
                            $w = strtolower($one_mail.'%');
                            $sql = "SELECT item_id from wp_frm_item_metas where meta_value like ? and (created_at between ? and ?) order by created_at";
        
                            $stmt = $this->conn->prepare($sql); 
                            $stmt->bind_param("sss", $w, $d1, $d2);
                            $stmt->execute();
                                    
                            $result = $stmt->get_result(); 
                            $stmt->close();
        
                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    
                                    array_push($item_ids, $row['item_id']);
                                }
                            }
        
                        }
        
                        // count the number of compelete applications using the item ids obtained above
                        $number = 0;
                        $s = array();
                        foreach($item_ids as $id){
                            $sql = "SELECT * from dwta_students_payment where Application_ID = ? and Payment_Status = 'Complete'";
                            $stmt = $this->conn->prepare($sql); 
                            $stmt->bind_param("s", $id);
                            $stmt->execute();
                
                            $result = $stmt->get_result(); 
                            $stmt->close();
                
                            
                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
        
                                    array_push($s, $row['Pay_ID']);
                                    $com = $row['Partner_Payment'];
         
                                    $number++;
                                }
                                array_push($this->array_for_ids, $s);
                            }
                        }

        
                        $the_data = $number;
        
        
                        // calcluate regular commission
                        $regular = $the_data - $promo;
        
                        foreach($all_regular_commission as $one_reg){
                            if($regular >= $one_reg->lower && $regular <= $one_reg->upper){
                                
                                $regular *= $one_reg->amount;
                                break;
                            }
                        }
        
                        // calculate promo amount
                        foreach($all_promo as $one_pro){
                            if($promo >= $one_pro->lower && $promo <= $one_pro->upper){
                                
                                $promo *= $one_pro->amount;
                                break;
                            }
                        }
        
                        $total_commission = $promo + $regular;
        
                      
                        
                        
                        $aff_data = new ConfirmTable( $i, $sess_name, "", $the_data, $promo, $regular, $total_commission, $this->affiliate_id, $com);
                        $the_data = array();
                        array_push($table, $aff_data); 
                    
        
                }
               return $table;
        
        }

    }


    $table = array();

    $confirm = new Confirm($conn, $affiliate_id);
    $table = $confirm->confirmPayment();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Affiliate</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="This is an example dashboard created using build-in elements and components.">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css">   
    <link href="./main.css" rel="stylesheet"></head>
<body>
    <div class="app-container app-theme-white body-tabs-shadow fixed-sidebar fixed-header">
        <div class="app-header header-shadow">
            <div class="app-header__mobile-menu">
               
            </div>
            <div class="app-header__menu">
                <span>
                    <button type="button" class="btn-icon btn-icon-only btn btn-primary btn-sm mobile-toggle-header-nav">
                        <span class="btn-icon-wrapper">
                            <i class="fa fa-ellipsis-v fa-w-6"></i>
                        </span>
                    </button>
                </span>
            </div>   
            <div class="app-header__content">
                <div class="app-header-right">
                    <div class="header-btn-lg pr-0">
                        <div class="widget-content p-0">
                            <div class="widget-content-wrapper">
                                <div class="widget-content-left">
                                <a href="logout.php" class="logout-link">SignOut</a>
                                    <!-- <div class="btn-group" style="text-decoration: none;">
                                        <div tabindex="-1" role="menu" aria-hidden="true" class="dropdown-menu dropdown-menu-right">
                                            <a href="approve.html"><button type="button" tabindex="0" class="dropdown-item">Recruited Students</button></a>
                                            <a href="confirm.html"><button type="button" tabindex="0" class="dropdown-item">Payout Records</button></a>
                                             <div tabindex="-1" class="dropdown-divider"></div>
                                            <a href="add.html"><button type="button" tabindex="0" class="dropdown-item">Settings</button></a>
                                         </div>
                                    </div>  -->
                                </div>
                            </div>
                        </div>
                    </div>      
               </div>
            </div>
        </div>       
         <div class="ui-theme-settings">
            <!-- <button type="button" id="TooltipDemo" class="btn-open-options btn btn-warning">
                <i class="fa fa-cog fa-w-16 fa-spin fa-2x"></i>
            </button> -->
            <div class="theme-settings__inner">
                <div class="scrollbar-container">
                    <div class="theme-settings__options-wrapper">
                        <h3 class="themeoptions-heading">Layout Options
                        </h3>
                        <div class="p-3">
                            <ul class="list-group">
                                <li class="list-group-item">
                                    <div class="widget-content p-0">
                                        <div class="widget-content-wrapper">
                                            <div class="widget-content-left mr-3">
                                                <div class="switch has-switch switch-container-class" data-class="fixed-header">
                                                    <div class="switch-animate switch-on">
                                                        <input type="checkbox" checked data-toggle="toggle" data-onstyle="success">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="widget-content-left">
                                                <div class="widget-heading">Fixed Header
                                                </div>
                                                <div class="widget-subheading">Makes the header top fixed, always visible!
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="widget-content p-0">
                                        <div class="widget-content-wrapper">
                                            <div class="widget-content-left mr-3">
                                                <div class="switch has-switch switch-container-class" data-class="fixed-sidebar">
                                                    <div class="switch-animate switch-on">
                                                        <input type="checkbox" checked data-toggle="toggle" data-onstyle="success">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="widget-content-left">
                                                <div class="widget-heading">Fixed Sidebar
                                                </div>
                                                <div class="widget-subheading">Makes the sidebar left fixed, always visible!
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="widget-content p-0">
                                        <div class="widget-content-wrapper">
                                            <div class="widget-content-left mr-3">
                                                <div class="switch has-switch switch-container-class" data-class="fixed-footer">
                                                    <div class="switch-animate switch-off">
                                                        <input type="checkbox" data-toggle="toggle" data-onstyle="success">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="widget-content-left">
                                                <div class="widget-heading">Fixed Footer
                                                </div>
                                                <div class="widget-subheading">Makes the app footer bottom fixed, always visible!
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>        <div class="app-main main1">
                  <div class="app-main__outer">
                    <div class="app-main__inner inner1">
                        <div class="app-page-title">
                        </div>           
                         <div class="row">
                            <div class="col-md-6 col-xl-4">
                                <div class="card mb-3 widget-content bg-midnight-bloom">
                                    <div class="widget-content-wrapper text-white">
                                        <div class="widget-content-left">
                                            <div class="widget-heading">Your invite Code / Partner ID :</div></div>
                                        <div class="widget-content-right">
                                            <div class="widget-numbers text-white"><span><?php echo $invite_code  ?></span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                   
                        </div>
                    <div class="row">
                            <div class="col-md-6 col-xl-4">
                                <div class="card mb-3 widget-content"  style="background-color: rgb(247, 185, 36);">
                                    <div class="widget-content-outer">
                                        <div class="widget-content-wrapper">
                                            <div class="widget-content-left">
                                                <div class="widget-heading">Your Unique Referal Link : <span><?php echo $affiliate_link ?></span></div>
                                            </div>
                                         </div>
                                    </div>
                                </div>
                            </div>
                           
                        </div>
                        <div class="row">
                          <div class="col-md-10 col-xl-4">
                                <div class="card mb-3 widget-content"  style="background-color: #fff;">
                                    <div class="widget-content-outer">
                                        <div class="widget-content-wrapper">
                                            <div class="widget-content-left">
                                                <div class="widget-heading"><p>You can also add <span class="high-jack" >?info=<?php echo $affiliate_id ?> </span> any page URL on https://wealthbankers.com/academy/ to make it a referral link. For example -- <span class="high-jack">https://wealthbankers.com/academy/apply/?info=<?php echo $affiliate_id ?> </span>
                                                </p></span></div>
                                            </div>
                                         </div>
                                    </div>
                                </div>
                            </div>
                        </div>
    <div class="row">
        <div class="col-md-12">
             <div class="main-card mb-3 card">
                        <div class="card-header"><i class="header-icon lnr-license icon-gradient bg-plum-plate"> </i>
                            <div class="btn-actions-pane-right">
                                <div class="nav">
                                    <a data-toggle="tab" href="#tab-eg2-0" class="btn-pill btn-wide active btn btn-outline-alternate btn-sm">Stats</a>
                                    <a data-toggle="tab" href="#tab-eg2-1" class="btn-pill btn-wide mr-1 ml-1  btn btn-outline-alternate btn-sm">By Link</a>
                                    <a data-toggle="tab" href="#tab-eg2-2" class="btn-pill btn-wide  btn btn-outline-alternate btn-sm">By Code / ID</a>
                                    <a data-toggle="tab" href="#tab-eg2-3" class="btn-pill btn-wide  btn btn-outline-alternate btn-sm">Payouts</a>
                                    <a data-toggle="btn" href="#" class="btn-pill btn-wide  btn btn-outline-alternate btn-sm">Promo Materials</a>
                                    <a data-toggle="btn" href="#" class="btn-pill btn-wide  btn btn-outline-alternate btn-sm">Application Form</a>
                                </div>
                            </div>
                        </div>
                     <div class="card-header">
                             <div class="btn-actions-pane-right">
                                <div class="nav">
                                 <div class="search-box">
                                 <div class="search-wrapper active">
                                   <div class="input-holder">                               
                                    <input type="text" id="search" class="search-input" placeholder="Type to search">
                                  <button class="search-icon"><span></span></button>
                                 </div>
                                 <button class="close"></button>
                                </div>
                              </div>
                            </div>
                        </div>
                    </div>
        <div class="card-body">
            <div class="tab-content">
                <div class="tab-pane active" id="tab-eg2-0" role="tabpanel">
             <div class="table-title">
                <div class="row">
                    <div class="col-sm-6">
                        <h5 class="card-header" style="border-style: none;">Applications using your Stats</h5>
                    </div>
                   
                </div>
                <div style="overflow-x:auto;">
                <table class="table table-striped" id="myTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Link Hits</th>
                        <th>Sites Visits</th>
                        <th>Unique Visitors</th>
                        <th>Application</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 0; while($i < count($display_stats_data)){?>
                    <tr>
                        <td> <?php echo $display_stats_data[$i]->date ?></td>
                        <td> <?php echo $display_stats_data[$i]->total_hit ?> </td>
                        <td> <?php echo $display_stats_data[$i]->site_visit ?> </td>
                        <td> <?php echo $display_stats_data[$i]->unique_visit ?> </td>
                        <td> <?php echo $display_stats_data[$i]->application ?> </td>
                    </tr>

                    <?php $i++; }?>
                    
                </tbody>
                </table>
                </div>
                <!-- <div class="text-center">
                <ul class="pagination justify-content-center">
                    <li class="page-item disabled"><a href="#"><i class="fa fa-long-arrow-left"></i> Previous</a></li>
                    <li class="page-item"><a href="#" class="page-link">1</a></li>
                    <li class="page-item"><a href="#" class="page-link">2</a></li>
                    <li class="page-item active"><a href="#" class="page-link">3</a></li>
                    <li class="page-item"><a href="#" class="page-link">4</a></li>
                    <li class="page-item"><a href="#" class="page-link">5</a></li>
                    <li class="page-item"><a href="#" class="page-link">Next <i class="fa fa-long-arrow-right"></i></a></li>
                </ul>
                </div> -->
             </div>     
                </div>
                <div class="tab-pane" id="tab-eg2-1" role="tabpanel">
              <div class="table-title">
                <div class="row">
                    <div class="col-sm-6">
                        <h5 class="card-header" style="border-style: none;">Applications using your Link</h5>
                    </div>
                 
                </div>
                <div style="overflow-x:auto;">
                <table class="table table-striped" id="myTable">
                <thead>
                    <tr>
                        <th>Application No.</th>
                        <th>Submitted</th>
                        <th>Submit Date</th>
                        <th>Complete Registration</th>
                        <th>Registration Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($display_link_data as $one_data){ ?>
                    <tr>
                        <td> <?php echo $one_data->app_number; ?> </td>
                        <td><?php echo $one_data->submit; ?> </td>
                        <td> <?php echo $one_data->sub_date; ?> </td>
                        <td> <?php echo $one_data->complete_registration; ?> </td>
                        <td> <?php echo $one_data->com_date; ?> </td>
                    </tr>
                    <?php }?>
                    
                </tbody>
                </table>
                </div>
                <!-- <div class="text-center">
                <ul class="pagination justify-content-center">
                    <li class="page-item disabled"><a href="#"><i class="fa fa-long-arrow-left"></i> Previous</a></li>
                    <li class="page-item"><a href="#" class="page-link">1</a></li>
                    <li class="page-item"><a href="#" class="page-link">2</a></li>
                    <li class="page-item active"><a href="#" class="page-link">3</a></li>
                    <li class="page-item"><a href="#" class="page-link">4</a></li>
                    <li class="page-item"><a href="#" class="page-link">5</a></li>
                    <li class="page-item"><a href="#" class="page-link">Next <i class="fa fa-long-arrow-right"></i></a></li>
                </ul>
                </div> -->
             </div>  
                </div> 

                <div class="tab-pane" id="tab-eg2-2" role="tabpanel"> 
                 <div class="table-title">
                <div class="row">
                    <div class="col-sm-6">
                        <h5 class="card-header" style="border-style: none;">Applications using your Code</h5>
                    </div>
                    
                </div>
                <div style="overflow-x:auto;">
                <table class="table table-striped" id="myTable">
                <thead>
                    <tr>
                        <th>Application No.</th>
                        <th>Submitted</th>
                        <th>Submit Date</th>
                        <th>Complete Registration</th>
                        <th>Registration Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($display_code_data as $one_data){ ?>
                    <tr>
                        <td> <?php echo $one_data->app_number; ?> </td>
                        <td><?php echo $one_data->submit; ?> </td>
                        <td> <?php echo $one_data->sub_date; ?> </td>
                        <td> <?php echo $one_data->complete_registration; ?> </td>
                        <td> <?php echo $one_data->com_date; ?> </td>
                    </tr>
                    <?php }?>

                </tbody>
                </table>
                <!-- <div class="text-center">
                <ul class="pagination justify-content-center">
                    <li class="page-item disabled"><a href="#"><i class="fa fa-long-arrow-left"></i> Previous</a></li>
                    <li class="page-item"><a href="#" class="page-link">1</a></li>
                    <li class="page-item"><a href="#" class="page-link">2</a></li>
                    <li class="page-item active"><a href="#" class="page-link">3</a></li>
                    <li class="page-item"><a href="#" class="page-link">4</a></li>
                    <li class="page-item"><a href="#" class="page-link">5</a></li>
                    <li class="page-item"><a href="#" class="page-link">Next <i class="fa fa-long-arrow-right"></i></a></li>
                </ul>
                </div> -->
                </div>
             </div>  </div>
                <div class="tab-pane" id="tab-eg2-3" role="tabpanel">
                     <div class="table-title">
                <div class="row">
                    <div class="col-sm-6">
                        <h5 class="card-header" style="border-style: none;">Payout Records</h5>
                    </div>
                    <div class="col-sm-6">
                        <div class="search-box">
                            <div class="input-group">                               
                                <input type="text" id="search" class="form-control" placeholder="Search by Name">
                                <span class="input-group-addon"><i class="material-icons">&#xE8B6;</i></span>
                            </div>
                        </div>
                        <br>
                    </div>
                </div>
                <div style="overflow-x:auto;">
                <table class="table table-striped" id="myTable">
                    <thead>
                        <tr>
                            <th>Date - Intake Sessions</th>
                            <!--<th>Partners' Name</th>-->
                            <th>Total Completed Registration</th>
                            <th>Promo</th>
                            <th>Regular</th>
                            <th>Total Commission</th>
                            <th>Commission Paid</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <?php foreach($table as $one_data) { ?>
                            <td> <?php echo $one_data->session ?> </td>
                            <!--<td> <?php echo $one_data->name ?> </td>-->
                            <td> <?php echo $one_data->total_reg ?> </td>
                            <td> GH&#8373 <?php echo $one_data->promo ?> </td>
                            <td> GH&#8373 <?php echo $one_data->regular ?> </td>
                            <td> GH&#8373 <?php echo $one_data->total ?> </td>
                            <td> <?php echo $one_data->complete ?> </td>
                        </tr>
                            <?php }?>
                    </tbody>
                </table>
                <!-- <div class="text-center">
                <ul class="pagination justify-content-center">
                    <li class="page-item disabled"><a href="#"><i class="fa fa-long-arrow-left"></i> Previous</a></li>
                    <li class="page-item"><a href="#" class="page-link">1</a></li>
                    <li class="page-item"><a href="#" class="page-link">2</a></li>
                    <li class="page-item active"><a href="#" class="page-link">3</a></li>
                    <li class="page-item"><a href="#" class="page-link">4</a></li>
                    <li class="page-item"><a href="#" class="page-link">5</a></li>
                    <li class="page-item"><a href="#" class="page-link">Next <i class="fa fa-long-arrow-right"></i></a></li>
                </ul>
                </div> -->
            </div>
             </div>  
                </div>
                <div class="tab-pane" id="tab-eg2-4" role="tabpanel"><p>page 5</p></div>
                <div class="tab-pane" id="tab-eg2-5" role="tabpanel"><p>page 6</p></div>
            </div>
        </div>
        <div class="d-block text-right card-footer">
            
        </div>
    </div>
                            </div>
                        </div>
                    </div>
                  </div>
                 
                <script src="http://maps.google.com/maps/api/js?sensor=true"></script>
        </div>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js" ></script>
    <script type="text/javascript">
    $(document).ready(function(){
        // Activate tooltips
        
        
        // Filter table rows based on searched term
        $("#search").on("keyup", function() {
            var term = $(this).val().toLowerCase();
            $("table tbody tr").each(function(){
                $row = $(this);
                var name = $row.find("td:nth-child(2)").text().toLowerCase();
                console.log(name);
                if(name.search(term) < 0){                
                    $row.hide();
                } else{
                    $row.show();
                }
            });
        });
    });
</script>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script type="text/javascript" src="./assets/scripts/main.js"></script>
</body>
</html>
