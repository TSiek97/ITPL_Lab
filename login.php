<!DOCTYPE html>
<html lang="en">
<?php include 'components/header.php'; // Include the header component ?>
<body>
<div id="container">
    <div class="login-wrapper">
        <div class="login-container">
            <div id="login-column-1" class="login-column">
                <div class="login-logo">
                    <!-- Logo image -->
                    <img class="login-image" src="/assets/images/Logo.png" alt="Logo">
                </div>
                <div class="background-image-container">
                    <!-- Background image -->
                    <img class="background-image" src="assets/images/background-image.jpg" alt="Background Image">
                </div>
            </div>
            <div id="login-column-2" class="login-column">
                <form class="login-form" action="login.php" method="post">
                    <div class="login-form-container">
                        <!-- Username/email input field -->
                        <label for="uname"><b>E-Mail/ Benutzername</b></label>
                        <input type="text" placeholder="E-Mail oder Benutzernamen eingeben" name="uname" required>

                        <!-- Password input field -->
                        <label for="psw"><b>Passwort</b></label>
                        <input type="password" placeholder="Passwort eingeben" name="psw" required>

                        <!-- Login button -->
                        <button type="submit">Login</button>
                    </div>
                    <div class="container">
                        <!-- Forgot password link -->
                        <span class="psw"><a href="#">Passwort vergessen?</a></span>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
session_start(); // Start session

// Include database connection
require_once "db_class.php";

// Database connection parameters
$DBServer   = 'localhost';
$DBHost     = 'airlimited';
$DBUser     = 'root';
$DBPassword = '';

// Create a database connection
$db = new DBConnector($DBServer, $DBHost, $DBUser, $DBPassword);
$db->connect();

// Initialize username variable
$userName = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['uname']) && isset($_POST['psw'])) {
    // Retrieve username/email and password from the login form
    $uname = $_POST['uname'];
    $psw = $_POST['psw'];

    // Check if username/email exists in the 'kunde' table
    $query = "SELECT * FROM kunde WHERE `E-Mailadresse` = '$uname'";
    $result = $db->getEntityArray($query);

    if (!empty($result)) {
        // User found in the 'kunde' table
        $userType = "kunde";
        $row = $result[0]; // Assuming only one row is returned
        $userID = $row->Kundennummer; // Accessing the property directly
    } else {
        // Check 'servicepartner' table
        $query = "SELECT * FROM servicepartner WHERE `E-Mailadresse` = '$uname'";
        $result = $db->getEntityArray($query);
        
        if (!empty($result)) {
            // User found in the 'servicepartner' table
            $userType = "servicepartner";
            $row = $result[0]; // Assuming only one row is returned
            $userID = $row->Bearbeiternummer; // Accessing the property directly
            $userName = $row->Firmenname;
        } else {
            // Check 'mitarbeiter' table
            $query = "SELECT Mitarbeiternummer, Rechte, Vorname, Nachname FROM mitarbeiter WHERE Kennung = '$uname'";
            $result = $db->getEntityArray($query);
            
            if (!empty($result)) {
                // User found in the 'mitarbeiter' table
                $row = $result[0]; // Assuming only one row is returned
                $userID = $row->Mitarbeiternummer; // Accessing the property directly
                $rechte = $row->Rechte;
                $userName = (string)$row->Vorname . ' ' . (string)$row->Nachname;
                
                // Determine user type based on rights
                if ($rechte === 'management') {
                    $userType = "management";
                } else if ($rechte === 'fertigung') {
                    $userType = "fertigung";
                } else if ($rechte === 'lager') {
                    $userType = "lager";
                }
            } else {
                // User not found, handle login failure
                echo "Benutzer nicht gefunden.";
            }
        }
    }

    // Set session variables if user is authenticated
    if (isset($userType) && isset($userID)) {
        $_SESSION['userType'] = $userType;
        $_SESSION['userID'] = $userID;
        $_SESSION['userName'] = $userName;
        
        // Redirect to home page or any other page
        header("Location: home.php");
        exit();
    }
}
?>

</body>
</html>
