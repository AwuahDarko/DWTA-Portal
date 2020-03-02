<?php 
// require_once "config.php";
require_once "academic-sessions.php";
require_once "promo-detail.php";



function getSessionsFromDB($conn){
    $s_array = array();

    // fetch sessions from DB
    $sql = "SELECT * from dwta_sessions";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // output data of each row
        while($row = $result->fetch_assoc()) {
            array_push($s_array, new DWTA_Sessions($row['Session_ID'], $row['Name'], $row['Start_Date'], $row['End_Date']));
        }
    }
    return $s_array;
}



function getPromoCommissionsFromDB($conn){
    // promo commission
    $c_array = array();
    $sql = "SELECT Commission_ID, Name, Amount from promo_commission_details";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {

        while($row = $result->fetch_assoc()) {
            $comm = new PromoDetails();
            $comm->id = $row['Commission_ID'];
            $comm->name = $row['Name'];
            $comm->amount = $row['Amount'];

            array_push($c_array, $comm);

        }
    }

    return $c_array;
}


function getCommissionsFromDB($conn){
    // promo commission
    $c_array = array();
    $sql = "SELECT Commission_ID, Name, Amount from commission_details";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {

        while($row = $result->fetch_assoc()) {
            $comm = new PromoDetails();
            $comm->id = $row['Commission_ID'];
            $comm->name = $row['Name'];
            $comm->amount = $row['Amount'];

            array_push($c_array, $comm);

        }
    }

    return $c_array;

}

