<!DOCTYPE html>
<html lang="en">
<?php 
// Include header component
include 'components/header.php';

// Include the database class
require_once "db_class.php";

// Database configuration
$DBServer   = 'localhost';   // Database server
$DBHost     = 'airlimited';  // Database name
$DBUser     = 'root';        // Database user
$DBPassword = '';            // Database password

// Create a database connection
$db = new DBConnector($DBServer, $DBHost, $DBUser, $DBPassword);
$db->connect();
?>
<body>
    <?php 
        session_start();
        if (!isset($_SESSION['userType'])) {
            header("Location: login.php");
            exit();
        }
     ?>
    <div id="container">

        <div id='header-wrapper'>
            <!-- Include navbar component -->
            <?php include 'components/navbar.php'?>
        </div>

        <hr>

        <div id='content-wrapper'>
            <?php 
            // Include and execute the update function for production orders
            require_once "functions/update-fertigungsauftraege.php";
            updateFertigungsauftraege($db);
            ?>
            <div class="content-container">
                <!-- Content goes here -->