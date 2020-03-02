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
        require_once "student-data.php";


        if($_SERVER["REQUEST_METHOD"] == "POST"){

            $ID =  $_POST['ID'];
            $status = $_POST['status']; 

            $now = date('Y-m-d');

            $sql = "UPDATE dwta_students_payment set Payment_Status = ?, Approval_Date = ? where Application_ID = ?";

            if($status === 'Pending'){
                $status_value = "Complete";
            }elseif($status === 'Complete'){
                $status_value = "Pending";
            }else{
                $status_value = "Pending";
            }

            $st = $conn->prepare($sql); 
            $st->bind_param("sss", $status_value, $now, $ID);

            
            
            $st->execute();

            $result = $st->get_result(); 
            $st->close();

            


        }


        $surname_field_id;
        $firstname_field_id;
        $display_data = array();



        // get the ids if the first and surname form
        $sql = "SELECT id from wp_frm_fields where name = 'Surname/ Family Name' limit 1";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            // output data of each row
            while($row = $result->fetch_assoc()) {
                $surname_field_id = $row['id'];
            }
        } 


        // get the ids if the first and surname form
        $sql = "SELECT id from wp_frm_fields where name = 'First / Given Name(s)' limit 1";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            // output data of each row
            while($row = $result->fetch_assoc()) {
                $firstname_field_id = $row['id'];
            }
        } 

        $sql = "SELECT * from wp_frm_item_metas join dwta_students_payment on dwta_students_payment.Application_ID = wp_frm_item_metas.item_id where field_id = ?;";

        $st = $conn->prepare($sql); 
        $st->bind_param("s", $surname_field_id);
        $st->execute();

        // get the mysqli result
        $result = $st->get_result(); 
        $st->close();

        if ($result->num_rows > 0) {
            

            while($row = $result->fetch_assoc()) {
                $viewTable = new StudentsData(); 
                $viewTable->app_number = "DWTA  ".$row['item_id'];
                $viewTable->date = $row['created_at'];
                $viewTable->name = $row['meta_value'];
                $viewTable->complete = $row['Payment_Status'];
                $viewTable->id = $row['item_id'];

                array_push($display_data, $viewTable);
            }
        } 

        // append first name to surname

        foreach($display_data as $one_val){
            $sql = "SELECT meta_value from wp_frm_item_metas where item_id = ? and field_id = ?";

            $st = $conn->prepare($sql); 
            $st->bind_param("ss", $one_val->id, $firstname_field_id);
            $st->execute();

            // get the mysqli result
            $result = $st->get_result(); 
            $st->close();


            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $one_val->name = $one_val->name ."  " .$row['meta_value'];
                }
            }
        }
        


        
  
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Approve Payments</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" >
    <meta name="description" content="This is an example dashboard created using build-in elements and components.">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css">
    <link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
    <link href="./main.css" rel="stylesheet"></head>
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
                                    <h5 class="card-header" style="border-style: none;">Approve Recruited Students by Partners</h5>
                                </div>
                                <div class="col-sm-6">
                                    <div class="search-box">
                                        <div class="input-group">                               
                                            <input type="text" id="search" class="form-control" placeholder="Search by Name">
                                            <span class="input-group-addon"><i class="material-icons"></i></span>
                                        </div>
                                    </div>
                                    <br>
                                </div>
                            </div>
                            <div style="overflow-x:auto">
                            <table class="table table-striped" id="myTable">
                            <thead>
                                <tr>
                                    <th>Application No.</th>
                                    <th>Application Name</th>
                                    <th>Date</th>
                                    <th>Submitted</th>
                                    <th>Complete Registration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($display_data as $one_data) {?>
                                <tr>
                                    <td> <?php echo $one_data->app_number; ?> </td>
                                    <td><?php echo $one_data->name; ?> </td>
                                    <td><?php echo $one_data->formatDate(); ?> </td>
                                    <td><?php echo $one_data->submit; ?> </td>
                                     <td>
                                         <form class="jack-knife" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                         <input name="ID" value="<?php echo $one_data->id; ?>" style="display: none">
                                         <input class="tgl tgl-flip" id="<?php echo $one_data->id."cb5" ?>" type="checkbox" name="status"
                                         value="<?php echo $one_data->complete; ?>" <?php if($one_data->complete === "Complete"){echo "checked";} ?>>
                                         <label  style="margin-right: 10px" class="tgl-btn" data-tg-off="Pending" data-tg-on="Complete"  for="<?php echo $one_data->id."cb5";?>"></label>
                                         <input type="submit" class="btn btn-success h-25" value="Save">
                                        </form>
                                    </td>
                                </tr>
                                <?php  }?>
                                
                               
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
                          
                     </div>
                  </div>   
                </div>
             </div>
            </div>
          </div>
           
        </div>
    </div>
    <script src="http://maps.google.com/maps/api/js?sensor=true"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js" ></script>
    <script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>


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
<script type="text/javascript" src="./assets/scripts/main.js"></script></body>
</html>
