<!DOCTYPE html>
<html lang="en">
<?php include 'components/header.php';
require_once "db_class.php";


// Database configuration
$DBServer   = 'localhost';
$DBHost     = 'airlimited';
$DBUser     = 'root';
$DBPassword = '';

// Create a database connection
$db = new DBConnector($DBServer, $DBHost, $DBUser, $DBPassword);
$db->connect();
?>
<body>
    <div id="container">

        <div id='header-wrapper'>
            <?php include 'components/navbar.php'?>
        </div>

        <hr>



        <div id='content-wrapper'>
       
        <?php 
            require_once "functions/update-fertigungsauftraege.php";
            updateFertigungsauftraege($db);
            ?>
            <div class="content-container">
