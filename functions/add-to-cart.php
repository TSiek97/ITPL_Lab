<?php
//include '../header.php'; // Adjust the path as needed
require_once '../db_class.php'; // Adjust the path as needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if the user is a servicepartner
    if (isset($_SESSION['userType']) && $_SESSION['userType'] == 'servicepartner') {
        // Retrieve form data
        $quantity = intval($_POST['quantity']);
        $artikelnummer = intval($_POST['artikelnummer']);
        $servicepartnernummer = $_SESSION['userID'];

        // Database connection details
        $DBServer   = 'localhost';
        $DBHost     = 'airlimited';
        $DBUser     = 'root';
        $DBPassword = '';

        $db = new DBConnector($DBServer, $DBHost, $DBUser, $DBPassword);
        $db->connect();

        // Check if the item is already in the cart
        $query_check_item = "
            SELECT Position, Menge
            FROM warenkorb
            WHERE Servicepartnernummer = $servicepartnernummer
            AND Artikelnummer = $artikelnummer
        ";
        $existing_item = $db->getEntity($query_check_item);

        if ($existing_item) {
            // Item exists, update the quantity
            $new_quantity = $existing_item->Menge + $quantity;
            $position = $existing_item->Position;

            $query_update_quantity = "
                UPDATE warenkorb
                SET Menge = $new_quantity
                WHERE Servicepartnernummer = $servicepartnernummer
                AND Artikelnummer = $artikelnummer
                AND Position = $position
            ";
            $db->query($query_update_quantity);
        } else {
            // Item does not exist, determine the next position and insert it
            $query_get_position = "
                SELECT IFNULL(MAX(Position), 0) + 1 AS next_position
                FROM warenkorb
                WHERE Servicepartnernummer = $servicepartnernummer
            ";
            $result = $db->getEntity($query_get_position);
            $next_position = $result->next_position;

            $query_add_to_cart = "
                INSERT INTO warenkorb (Servicepartnernummer, Artikelnummer, Menge, Position)
                VALUES ($servicepartnernummer, $artikelnummer, $quantity, $next_position)
            ";
            $db->query($query_add_to_cart);
        }

        // Redirect to a confirmation page or back to the product page with a success message
        header('Location: /product-view.php?product=' . $artikelnummer );
        exit();
    } else {
        // Handle unauthorized access
        echo 'unauthorized';
        header('Location: /unauthorized.php');
        exit();
    }
}
?>

<?php include '../footer.php'; // Adjust the path as needed ?>
