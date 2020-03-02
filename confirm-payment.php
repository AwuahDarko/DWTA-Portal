<?php

        session_start();
        if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] != true){
                header("location: index.php");
            exit;
          }

          if ($_SESSION["Role"] != "admin"){
            header("location: index.php");
            die;
          }

        // Include config file
        require_once "config.php";
        require_once "current-session.php";
        require_once "affiliate-data.php";
        require_once "confirm-table.php";
        require_once "referral-data.php";
        require_once "commission.php";
        require_once "utils.php";


        

        class Confirm{
            public $array_for_aff_ids = array();
            public $array_for_ids = array();
            private $conn;

            function __construct($conn)
            {
                $this->conn = $conn;
            }

            function confirmPayment(){
                $all_sessions = array();
                    $all_affiliates =  array();
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
            
            
                    // get all active affiliates;
                    $sql = "SELECT name, email, affiliate_id  FROM wp_aff_affiliates where status = 'active'"; 
                    $result = $this->conn->query($sql);
            
                    if ($result->num_rows > 0) {
            
                        while($row = $result->fetch_assoc()) {
                            $aff = new Affiliate();
                            $aff->id = $row['affiliate_id'];
                            $aff->name = $row['name'];
                            $aff->email = $row['email'];
            
                            array_push($all_affiliates, $aff); 
                        }
                    }
            
            
                    
                    $i = 0;
                    $the_data = array(); 
                    $this->array_for_aff_ids = array();
                    $this->array_for_ids = array();
                    
            
                    foreach($all_sessions as $one_ses){
                        $sess_name = explode(" ", $one_ses->name)[0]." ".$thisYear;
                        $com = "No";
                        
                        foreach($all_affiliates as $one_aff){
                            $com = "No";
            
                            array_push($this->array_for_aff_ids, $one_aff->id);
            
                            $i++;
            
                           $promo = 0;
            
                            // get promo referrals
                            $sql = "SELECT count(*) from promo_referals where (createdDatetime >= ? and createdDatetime <= ?) and affiliate_id = ?";
            
                            $stmt = $this->conn->prepare($sql); 
                            $stmt->bind_param("sss", $one_ses->start_date_raw, $one_ses->end_date_raw, $one_aff->id);
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
                            $stmt->bind_param("iss", $one_aff->id, $d1, $d2);
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
                            
                            
                            
            
            
                            $the_data[$one_aff->id] = $number;
            
            
                            // calcluate regular commission
                            $regular = $the_data[$one_aff->id] - $promo;
            
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
            
                          
                            
                            
                            $aff_data = new ConfirmTable( $i, $sess_name, $one_aff->name, $the_data[$one_aff->id], $promo, $regular, $total_commission, $one_aff->id, $com);
                            $the_data = array();
                            array_push($table, $aff_data); 
                        }
            
                    }
                   return $table;
            
            }

            function doPost(){

                if($_SERVER["REQUEST_METHOD"] == "POST"){

                    $ID =  $_POST['ID'];
                    $status = $_POST['status']; 
                    $session = $_POST['session'];
                    $affiliate = $_POST['affiliate'];  
        
        
                    if($status === 'No'){
                        $status_value = "Yes";
                    }elseif($status === 'Yes'){
                        $status_value = "No";
                    }else{
                        $status_value = "No";
                    }
                    
        
                        $list_to_be_update = array();
        
                        for($i = 0; $i < count($this->array_for_aff_ids); $i++){
        
                            if ($affiliate == $this->array_for_aff_ids[$i]){
                                $list_to_be_update = $this->array_for_ids[$i];
                            break;
                            }
                            }
        
        
                    foreach($list_to_be_update as $one_id){
                        $sql = "UPDATE dwta_students_payment set Partner_Payment = ?  where Pay_ID = ?";
                        $st = $this->conn->prepare($sql); 
                        $st->bind_param("si", $status_value, $one_id );
        
                        $st->execute();
        
                        $result = $st->get_result(); 
                        $st->close();
                        }
        
                }
            }

        }

       
       
        $table = array();

        $confirm = new Confirm($conn);
        $table = $confirm->confirmPayment();

        $confirm->doPost();

        

        $table = array();
        $table = $confirm->confirmPayment();




?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Confirm Payment</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" >
    <meta name="description" content="This is the confirmation of payments page">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css">
    <link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
    <link href="./main.css" rel="stylesheet">
</head>

<body>
    <div class="app-container app-theme-white body-tabs-shadow fixed-sidebar fixed-header">
        <div class="app-header header-shadow">
            <div class="app-header__mobile-menu">
                <div>
                    <button type="button" class="hamburger hamburger--elastic mobile-toggle-nav">
                        <span class="hamburger-box">
                            <span class="hamburger-inner"></span>
                        </span>
                    </button>
                </div>
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
                                <div class="jack-knife">
                                <a href="register.php" class="logout-link">Register</a>
                                <a href="confirm-payment.php" class="logout-link">Affiliates</a>
                                    <a href="approve.php" class="logout-link">Fees</a>
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
        <div class="app-main">
            <div class="app-main__outer">
                <div class="app-main__inner">
                    <div class="card-body main-card mb-3 card">
                        <div class="tab-content">
                            <div class="tab-pane active" id="tab-eg2-0" role="tabpanel">
                                <div class="table-title">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <h5 class="card-header" style="border-style: none;">Confirm Payout Records</h5>
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
                                                    <th>Partners' Name</th>
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
                                                    <td> <?php echo $one_data->name ?> </td>
                                                    <td> <?php echo $one_data->total_reg ?> </td>
                                                    <td> GH&#8373 <?php echo $one_data->promo ?> </td>
                                                    <td> GH&#8373 <?php echo $one_data->regular ?> </td>
                                                    <td> GH&#8373 <?php echo $one_data->total ?> </td>
                                                    <td>
                                                    <form style="display: flex; justify-content: space-evenly" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                                        <input name="ID" value="<?php echo $one_data->id; ?>" style="display: none">
                                                        <input name="session" value="<?php echo $one_data->session; ?>" style="display: none">
                                                        <input name="affiliate" value="<?php echo $one_data->affiliate_id; ?>" style="display: none">
                                                        <input class="tgl tgl-flip" id="<?php echo $one_data->id."cb5" ?>" type="checkbox" name="status"
                                                        value="<?php echo $one_data->complete; ?>" <?php if($one_data->complete === "Yes"){echo "checked";} ?>>
                                                        <label class="tgl-btn" data-tg-off="No" data-tg-on="Yes"  for="<?php echo $one_data->id."cb5";?>"></label>
                                                        <input type="submit" class="btn btn-success h-25" value="Save">
                                                    </form>
                                                    </td>
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

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    </div>
    </div>
    
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
     <script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
    <script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>
        <script src="http://maps.google.com/maps/api/js?sensor=true"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script type="text/javascript" src="./assets/scripts/main.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            // Activate tooltips

            // Filter table rows based on searched term
            $("#search").on("keyup", function() {
                var term = $(this).val().toLowerCase();
                $("table tbody tr").each(function() {
                    $row = $(this);
                    var name = $row.find("td:nth-child(2)").text().toLowerCase();
                    
                    if (name.search(term) < 0) {
                        $row.hide();
                    } else {
                        $row.show();
                    }
                });
            });
        });
    </script>
</body>

</html>