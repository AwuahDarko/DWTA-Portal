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
require_once "academic-sessions.php";
require_once "utils.php";


// Define variables and initialize with empty values
$partnerID = $password = $email = $formType = $success_message1 = "";
$partnerID_err = $password_err = $email_err = "";

$partnerID2 = $password2 = $email2 = $success_message2 = "";
$partnerID_err2 = $password_err2 = $email_err2 = "";

$sessionList = array();

$sessionList = getSessionsFromDB($conn);
$promo_commission_array = getPromoCommissionsFromDB($conn);
$commission_array = getCommissionsFromDB($conn);


// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){

    $formType =  $_POST['formType'];

    if ($formType === 1 || $formType === '1'){
        // Validate id
    if(empty(trim($_POST["partnerID"]))){
        $partnerID_err = "Please enter an Invite Code or Partner ID.";
    } else{
        $partnerID = trim($_POST["partnerID"]);
        $email = trim($_POST["email"]);
        // check if  code already exist
        // Prepare a select statement

        $sql = "SELECT * from wp_users where user_login = ? AND user_email = ?";


        $stmt = $conn->prepare($sql); 
        $stmt->$php_errormsg;
        $stmt->bind_param("ss", $partnerID, $email);
        $stmt->execute();

        // get the mysqli result
        $result = $stmt->get_result(); 
 
        if ($result->num_rows == 0) { 
           $partnerID_err = "This Invite Code or Partner ID does not match email or is not registered."; 
           $email_err = "This email is not registered or does not match code";  
          
        }
        // close statement
        $stmt->close();
    }

    // Validate Email

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email_err = "Invalid email format";
    }
    

    // Validate passwordformType
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter a password.";    

    } elseif(strlen(trim($_POST["password"])) < 8){
        $password_err = "Password must have at least 8 characters.";

    } else{
        $password = trim($_POST["password"]);
    }
    
    
    // Check input errors before inserting in database
    if(empty($partnerID_err) && empty($password_err) && empty($email_err)){
        // create a password hash
        $options = [
            'cost' => 15
          ];

          $hash_pass = password_hash($password, PASSWORD_BCRYPT, $options);
          $role = 'user';

        // Prepare an insert statement
        $sql = "INSERT INTO dwta_users (Email, Password, Role) values (?,?,?)";

        $stmt = $conn->prepare($sql); 
        $stmt->bind_param("sss", $email, $hash_pass, $role);
        $stmt->execute();
        $stmt->close();
        $success_message1 = "New user added successfully";
    }
    
   

//=================================================================================================================================//
    }elseif ($formType === 2 || $formType === '2'){
        // Validate id
    if(empty(trim($_POST["partnerID2"]))){
        $partnerID_err2 = "Please enter an Invite Code or Partner ID.";
    } else{
        $partnerID2 = trim($_POST["partnerID2"]);
        $email2 = trim($_POST["email2"]);
        // check if  code already exist
        // Prepare a select statement

        $sql = "SELECT * from wp_users where user_login = ? AND user_email = ?";


        $stmt = $conn->prepare($sql); 
        $stmt->bind_param("ss", $partnerID2, $email2);
        $stmt->execute();

        // get the mysqli result
        $result = $stmt->get_result(); 
 
        if ($result->num_rows == 0) { 
           $partnerID_err2 = "This Invite Code or Partner ID does not match email or is not registered."; 
           $email_err2 = "This email is not registered or does not match code";  
          
        }
        // close statement
        $stmt->close();
    }

    // Validate Email

    if (!filter_var($email2, FILTER_VALIDATE_EMAIL)) {
        $email_err2 = "Invalid email format";
    }
    

    // Validate passwordformType
    if(empty(trim($_POST["password2"]))){
        $password_err2 = "Please enter a password.";    

    } elseif(strlen(trim($_POST["password2"])) < 8){
        $password_err2 = "Password must have at least 8 characters.";

    } else{
        $password2 = trim($_POST["password2"]);
    }
    
    
    // Check input errors before inserting in database
    if(empty($partnerID_err2) && empty($password_err2) && empty($email_err2)){
        // create a password hash
        $options = [
            'cost' => 15
          ];

          $hash_pass = password_hash($password2, PASSWORD_BCRYPT, $options);
          

        // Prepare an insert statement
        // $sql = "INSERT INTO dwta_users (Email, Password, Role) values (?,?,?)";
        $sql = "UPDATE dwta_users set Password = ? where Email = ?";

        $stmt = $conn->prepare($sql); 
        $stmt->bind_param("ss",  $hash_pass, $email2);
        $stmt->execute();
        $stmt->close();
        $success_message2 = "Password updated successfully";
    }
    
    
        //==================================================================================================================================//
    }elseif ($formType === 3 || $formType === "3"){
        $startDate = $_POST['startDate'];
        $endDate = $_POST['endDate'];

        for ($i = 0; $i < count($sessionList); $i++){
            $sql = "UPDATE dwta_sessions set Start_Date = ?, End_Date = ? where Session_ID = ?";

                $st = $conn->prepare($sql); 
                $st->bind_param("ssi", $startDate[$i], $endDate[$i], $sessionList[$i]->session_id);
                $st->execute();

                // get the mysqli result
                $res = $st->get_result(); 
                $st->close();
                
                // get the new data from DB
                $sessionList = array();
                $sessionList = getSessionsFromDB($conn);

        }
    }elseif($formType === 4 || $formType === "4"){
        $start = $_POST['start'];
        $end = $_POST['end'];


        $sql = "UPDATE promo_status set Start_Date = ?, End_Date = ? ";

        $st = $conn->prepare($sql); 
        $st->bind_param("ss", $start, $end);
        $st->execute();

 
        $res = $st->get_result(); 
        $st->close();
    }elseif($formType === 5 || $formType === "5"){
        $amount = $_POST['amount'];

        $i = 0;
        foreach($promo_commission_array as $blood){
            $sql = "UPDATE promo_commission_details set Amount = ? where Commission_ID = ?";
            $st = $conn->prepare($sql); 
            $st->bind_param("di", $amount[$i], $blood->id);
            $st->execute();
    
            
            $res = $st->get_result(); 
            $st->close();
            $i++;
        }

        $promo_commission_array = array();
        $promo_commission_array = getPromoCommissionsFromDB($conn);
       

    }elseif ($formType === 6 || $formType === "6"){
        $amount = $_POST['amount'];

        $i = 0;
        foreach($commission_array as $blood){
            $sql = "UPDATE commission_details set Amount = ? where Commission_ID = ?";
            $st = $conn->prepare($sql); 
            $st->bind_param("di", $amount[$i], $blood->id);
            $st->execute();
    
            
            $res = $st->get_result(); 
            $st->close();
            $i++;
        }

        $commission_array = array();
        $commission_array = getCommissionsFromDB($conn);
    }
}

//======================PROMO ============================================================================//

        // is promotion active
        
        
        $sql = "SELECT * from promo_status limit 1";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {

            while($row = $result->fetch_assoc()) {
                $promo_start = $row['Start_Date'];
                $promo_end = $row['End_Date'];
            }
        }



 // Close connection
 $conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Register</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" >
    <meta name="description" content="This is the registration form">
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
        <div class="app-main main1">
            <div class="app-main__outer">
                <div class="app-main__inner inner1">
                    <!-- add -->
                    <div class="row">
                        <div class="col-md-12 col-lg-6">
                            <div class="mb-3 card">
                                <div class="card-header-tab card-header-tab-animation card-header">
                                    <div class="card-header-title">
                                        <i class="header-icon lnr-apartment icon-gradient bg-love-kiss"> </i> ADD NEW USER
                                        <span style="margin-left: 15%; color: limegreen"><?php echo $success_message1 ?> </span>
                                    </div>

                                </div>
                                <div class="card-body">
                                       <div class="main-card mb-3 card">
                                            <div class="card-body">
                                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                                <input name="formType" value="1" style="display: none"><!-- added to give this form an identity... nothing more -->
                                                    <div class="position-relative form-group <?php echo (!empty($partnerID_err)) ? 'has-error' : ''; ?>">
                                                        <label>Invite Code / Partner ID</label>
                                                        <input name="partnerID" value="<?php echo $partnerID; ?>" placeholder="Enter Code" type="text" class="form-control">
                                                        <span class="error-label"><?php echo $partnerID_err; ?></span>
                                                    </div>
                                                    <div class="position-relative form-group <?php echo (!empty($email_err)) ? 'has-error' : ''; ?>">
                                                        <label>Email</label>
                                                    <input name="email" value="<?php echo $email; ?>" placeholder="Enter User Email" type="email" class="form-control">
                                                    <span class="error-label"><?php echo $email_err; ?></span>
                                                </div>
                                                     <div class="position-relative form-group <?php echo (!empty($password_err)) ? 'has-error' : ''; ?>">
                                                        <label>New Password</label>
                                                        <input name="password" placeholder="Enter Password" type="password" class="form-control">
                                                        <span class="error-label"><?php echo $password_err; ?></span>
                                                    </div>
                                                    <input type="submit" value="Add User" class="mt-1 btn btn-success">
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                            </div>
                        </div>

                        <div class="col-md-12 col-lg-6">
                                <div class="mb-3 card">
                                    <div class="card-header-tab card-header-tab-animation card-header">
                                        <div class="card-header-title">
                                            <i class="header-icon lnr-apartment icon-gradient bg-love-kiss"> </i>
                                            CHANGE PASSWORD
                                            <span style="margin-left: 15%; color: limegreen"><?php echo $success_message2 ?> </span>
                                        </div>
                                        
                                    </div>
                                    <div class="card-body">
                                       <div class="main-card mb-3 card">
                                            <div class="card-body">
                                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                                <input name="formType" value="2" style="display: none"><!-- added to give this form an identity... nothing more -->
                                                    <div class="position-relative form-group <?php echo (!empty($partnerID_err2)) ? 'has-error' : ''; ?>">
                                                        <label>Invite Code / Partner ID</label>
                                                        <input name="partnerID2" value="<?php echo $partnerID2; ?>" placeholder="Enter Code" type="text" class="form-control">
                                                        <span class="error-label"><?php echo $partnerID_err2; ?></span>
                                                    </div>
                                                    <div class="position-relative form-group <?php echo (!empty($email_err)) ? 'has-error' : ''; ?>">
                                                        <label>Email</label>
                                                        <input name="email2" placeholder="Enter User Email" type="email" class="form-control" value="<?php echo $email2 ?>">
                                                        <span class="error-label"><?php echo $email_err2; ?></span>
                                                    </div>
                                                    
                                                     <div class="position-relative form-group">
                                                        <label>New Password</label>
                                                        <input name="password2" placeholder="Enter Password" type="password" class="form-control">
                                                        <span class="error-label"><?php echo $password_err2; ?></span>
                                                    </div>
                                                    <input type="submit" value="Update Password" class="mt-1 btn  btn-success">
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <!-- first -->
                        <div class="col-md-12 col-lg-4">
                                <div class="mb-3 card">
                                    <div class="card-header-tab card-header-tab-animation card-header">
                                        <div class="card-header-title">
                                            <i class="header-icon lnr-apartment icon-gradient bg-love-kiss"> </i>
                                            INTAKE SESSION SETTINGS
                                        </div>
                                        
                                    </div>
                                   <div class="main-card mb-3 card mx-auto" id="frm_field_[id]_container agency">
                                <div class="card-body">
                                     
                                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                    <input name="formType" value="3" style="display: none"><!-- added to give this form an identity... nothing more -->

                                    <?php $i = 0; while($i < count($sessionList)){ ?>
                                    <div class="form-row">
                                        <label class="col-md-12"> <?php echo $sessionList[$i]->session_name; ?> </label>
                                        </div>
                                        <div class="form-row">
                                            <div class="col-md-6">
                                                <div class="position-relative form-group">
                                                    <input name="startDate[]" placeholder="Start Date *" type="text" onfocus="this.type='date'" onblur="this.type='text'" value="<?php echo $sessionList[$i]->session_start; ?>"  class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="position-relative form-group">
                                                    <input name="endDate[]" placeholder="End Date *" type="text" onfocus="this.type='date'" onblur="this.type='text'" value="<?php echo $sessionList[$i]->session_end; ?>" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                        <?php  $i++; } ?>
                                        
                                        <div class="form-row">
                                            <div class="col-md-1">
                                            </div>
                                            <div class="col-md-10 mybtn">
                                                <button class="mb-2 mr-2 btn btn-success btn-lg btn-block">Save Changes</button>
                                            </div>
                                            <div class="col-md-1">
                                         </div>
                                        </div>
                                        
                                    </form>
                                </div>
                            </div>
                                </div>
                            </div> 
                        <!-- second -->

                        <div class="col-md-12 col-lg-4">
                            <div class="card">
                            <form id="promo-form" style="display: flex; flex-direction: column" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <input name="formType" value="4" style="display: none"><!-- added to give this form an identity... nothing more -->
                            
                                <div class="form-row">
                                    <div class="col-md-6 mx-auto">
                                        <div  class="align-items-center">
                                        <div class="card-header-title mb-3">
                                         PROMO PERIOD
                                    </div>
                                
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="align-date-pickers">
                                        <input type="text" class="mb-3" placeholder="begins on" type="text" name="start" onfocus="this.type='date'" onblur="this.type='text'" value="<?php echo $promo_start ?>">
                                        <input  type="text" class="mb-3"  placeholder="ends on" type="text" name="end" onfocus="this.type='date'" onblur="this.type='text'" value="<?php echo $promo_end ?>">
                                </div>
                                 <input type="submit" value="Save" class="w-75 mx-auto btn btn-success" >  
                             </form>
                            </div>
                            <div class="mb-3 card">
                                <div class="card-header-tab card-header-tab-animation card-header">
                                    <div class="card-header-title">
                                         PROMO COMMISSIONS
                                    </div>
                                </div>
                                <div class="main-card mb-3 card mx-auto">  
                                    <div class="card-body">
                                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                        <input name="formType" value="5" style="display: none"><!-- added to give this form an identity... nothing more -->

                                        <?php foreach($promo_commission_array as $one_data){ ?>
                                            <div class="form-row" style="text-align: center;">
                                                <div class="col-md-4">
                                                    <div class="position-relative form-group">
                                                        <p><?php echo $one_data->name ?></p>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="position-relative form-group">
                                                        <input name="amount[]" placeholder="Amount" value="<?php echo $one_data->amount ?>" type="number" step="0.01" class="form-control">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="position-relative form-group">
                                                        <p>per student</p>
                                                    </div>
                                                </div>

                                            </div>
                                        <?php }?>

                                            <div class="form-row">
                                                <div class="col-md-1">
                                                </div>
                                                <div class="col-md-10 mybtn">
                                                    <button class="mb-2 mr-2 btn btn-success btn-lg btn-block">Save Changes</button>
                                                </div>
                                                <div class="col-md-1">
                                                </div>
                                            </div>

                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- third -->
                        <div class="col-md-12 col-lg-4">
                            <div class="mb-3 card">
                                <div class="card-header-tab card-header-tab-animation card-header">
                                    <div class="card-header-title">
                                        <i class="header-icon lnr-apartment icon-gradient bg-love-kiss"> </i> REGULAR COMMISSIONS
                                    </div>
                                </div>
                                <div class="main-card mb-3 card mx-auto" id="agency">
                                    <div class="card-body">

                                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                        <input name="formType" value="6" style="display: none"><!-- added to give this form an identity... nothing more -->
                                            <?php foreach($commission_array as $one_data){ ?>
                                            <div class="form-row" style="text-align: center;">
                                                <div class="col-md-4">
                                                    <div class="position-relative form-group">
                                                        <p><?php echo $one_data->name ?></p>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="position-relative form-group">
                                                        <input name="amount[]" placeholder="GH 200" value="<?php echo $one_data->amount ?>" type="text" class="form-control">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="position-relative form-group">
                                                        <p>Per Students</p>
                                                    </div>
                                                </div>

                                            </div>
                                            <?php }?>
                                        

                                            <div class="form-row">
                                                <div class="col-md-1">
                                                </div>
                                                <div class="col-md-10 mybtn">
                                                    <button class="mb-2 mr-2 btn btn-success btn-lg btn-block">Save Changes</button>
                                                </div>
                                                <div class="col-md-1">
                                                </div>
                                            </div>

                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="http://maps.google.com/maps/api/js?sensor=true"></script>
    <script type="text/javascript" src="./assets/scripts/main.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js" ></script>
    <!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script> -->
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
</body>
</html>