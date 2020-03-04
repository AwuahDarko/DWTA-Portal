<?php 
// Initialize the session
session_start();

// Check if the user is already logged in, if yes then redirect him to welcome page
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    if ($_SESSION["Role"] === 'admin'){
        header("location: register.php");
    }elseif($_SESSION["Role"] === 'user'){
        header("location: affiliate.php");
    }
    
    exit;
  }


  // Include config file
require_once "config.php";


// Define variables and initialize with empty values
$partnerID = $password = "";
$partnerID_err = $password_err = "";


// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Check if id is empty
    if(empty(trim($_POST["partnerID"]))){
        $partnerID_err = "Please enter your Invite Code / Partner ID.";
    } else{
        $partnerID = trim($_POST["partnerID"]);
    }
    
    // Check if password is empty
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if(empty($partnerID_err) && empty($password_err)){ 
        $sql = "SELECT * from wbaca_users where user_login = ?";


        $stmt = $conn->prepare($sql); 
        $stmt->bind_param("s", $partnerID);
        $stmt->execute();

        // get the mysqli result
        $result = $stmt->get_result(); 
        $stmt->close();
        
        $hash_password;

        // fetch data 
        if ($result->num_rows > 0) {
            // output data of each row
            while($row = $result->fetch_assoc()) {

                $email = $row['user_email'];
                

                $sql = "SELECT * from dwta_users where Email = ?";

                $st = $conn->prepare($sql); 
                $st->bind_param("s", $email);
                $st->execute();

                // get the mysqli result
                $res = $st->get_result(); 
                $st->close();
        
                if ($res->num_rows > 0) {

                    while($row = $res->fetch_assoc()) {
                        
                    $hash_password = $row['Password'];
                    $mail = $row['Email'];
                    $role = $row['Role'];

                // compare password hash
                if (password_verify($password, $hash_password)) {

                    // Store data in session variables
                        $_SESSION["loggedin"] = true;
                        $_SESSION["User_ID"] = $row['User_ID'];
                        $_SESSION["Email"] = $mail; 
                        $_SESSION['Role'] = $role;
                        $_SESSION['Code'] = $partnerID;

                        if ($role === 'admin'){
                            header("location: register.php");
                        }elseif ($role === 'user'){
                            header("location: affiliate.php");
                        }

                    
                } else {
                    $password_err = "The password you entered was not valid.";
                }
             }
                }else{
                    $partnerID_err = "No account found with that Code or ID";
                }

            }

        } else {
            $partnerID_err = "No account found with that Code or ID.";
        }
    }
    
    // Close connection
    $conn->close();
}

?>


<!DOCTYPE html>
<html>
<head>
     <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>DWTA Partners Portal | Log In</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="This is the login form">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css">
    <link href="./main.css" rel="stylesheet"></head>
</head>
<body>
  <div class="sidenav">
      </div>
      <div class="main">
         <div class="col-md-8 col-sm-12">
            <div class="login-form">
              <div class="">
                  <h4 class="card-header" style="border: none; text-align: center;">DWTA Partners Portal</h4>
                  </div>
               <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                  <div class="form-group <?php echo (!empty($partnerID_err)) ? 'has-error' : ''; ?>">
                     <input type="text" name="partnerID" class="form-control" placeholder="Invite Code or Partner ID" value="<?php echo $partnerID; ?>"> 
                     <span class="error-label"><?php echo $partnerID_err; ?></span>
                  </div>
                  <div class="form-group <?php echo (!empty($password_err)) ? 'has-error' : ''; ?>">
                     <input type="password"  name ="password" class="form-control" placeholder="Password">
                     <span class="error-label"><?php echo $password_err; ?></span>
                     <a href="#" style="text-decoration: none; font-weight: bold;" ><p class="forgot" data-toggle="popover" data-placement="top" data-content="Please send a  password reset email to academy@worldbankers.com">Forgot password?</p></a>
                    
                  </div>
                  <div class="form-group">
                    <div class="row">
                      <div class="col-md-3">
                        
                      </div>
                      <div class="col-md-6">
                       <input type="submit" value="Login" class="mb-2 mr-2 btn btn-primary btn-lg btn-block">
                      </div>
                      <div class="col-md-3">
                        
                      </div>
                    </div>
                  </div>
               </form>
            </div>
         </div>
      </div>
      <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
      <script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js" ></script>
      <script type="text/javascript" src="./assets/scripts/main.js"></script></body>
</body>
</html>