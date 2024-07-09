<?php 
require_once "../db_class.php";

// Database connection details
$DBServer = 'localhost';
$DBHost = 'airlimited';
$DBUser = 'root';
$DBPassword = '';

// Create a new database connector instance
$db = new DBConnector($DBServer, $DBHost, $DBUser, $DBPassword);

// Connect to the database
$db->connect();

// Retrieve parameters from the GET request
$kundennummer = isset($_GET['kundennummer']) ? $_GET['kundennummer'] : '';
$name = isset($_GET['name']) ? $_GET['name'] : '';
$address = isset($_GET['address']) ? $_GET['address'] : '';
$zip = isset($_GET['zip']) ? $_GET['zip'] : '';

// Construct the query to search for customers based on provided parameters
$query = "
    SELECT Kundennummer, Kundenname, Straße, Hausnummer, Postleitzahl 
    FROM kunde 
    WHERE (Kundennummer LIKE '%$kundennummer%') 
    AND (Kundenname LIKE '%$name%') 
    AND (Straße LIKE '%$address%' OR Hausnummer LIKE '%$address%') 
    AND (Postleitzahl LIKE '%$zip%')
";

// Execute the query and get the result as an array of entities
$result = $db->getEntityArray($query);

// Set the response content type to JSON
header('Content-Type: application/json');

// Output the result as a JSON-encoded string
echo json_encode($result);
?>
