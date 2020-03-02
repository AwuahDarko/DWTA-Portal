<?php
session_start();
$_SESSION["loggedin"] = false;
unset($_SESSION["loggedin"]);
unset($_SESSION["User_ID"]);
unset($_SESSION["Email"]);
unset($_SESSION["Role"]);
unset($_SESSION["Code"]);

header('location:index.php');