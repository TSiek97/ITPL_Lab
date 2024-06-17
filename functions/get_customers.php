<?php
require_once "../db_class.php";

$DBServer = 'localhost';
$DBHost = 'airlimited';
$DBUser = 'root';
$DBPassword = '';
$db = new DBConnector($DBServer, $DBHost, $DBUser, $DBPassword);
$db->connect();
$kundennummer = isset($_GET['kundennummer']) ? $_GET['kundennummer'] : '';
$name = isset($_GET['name']) ? $_GET['name'] : '';
$address = isset($_GET['address']) ? $_GET['address'] : '';
$zip = isset($_GET['zip']) ? $_GET['zip'] : '';


$query = "
    SELECT Kundennummer, Kundenname, Straße, Hausnummer, Postleitzahl 
    FROM kunde 
    WHERE (Kundennummer LIKE '%$kundennummer%') 
    AND (Kundenname LIKE '%$name%') 
    AND (Straße LIKE '%$address%' OR Hausnummer LIKE '%$address%') 
    AND (Postleitzahl LIKE '%$zip%') 

";

$result = $db->getEntityArray($query);

header('Content-Type: application/json');
echo json_encode($result);

