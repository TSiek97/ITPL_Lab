<?php
// cart_functions.php

function add_to_cart($productNummer, $quantity) {
    global $db;
    $servicepartnernummer = $_SESSION['userID'];
    
    // Check if the item is already in the cart
    $query_check_item = "
        SELECT Position, Menge
        FROM warenkorb
        WHERE Servicepartnernummer = $servicepartnernummer
        AND Artikelnummer = $productNummer
    ";
    $existing_item = $db->getEntity($query_check_item);

    if ($existing_item) {
        // Item exists, update the quantity
        $new_quantity = $quantity;
        $position = $existing_item->Position;

        $query_update_quantity = "
            UPDATE warenkorb
            SET Menge = $new_quantity
            WHERE Servicepartnernummer = $servicepartnernummer
            AND Artikelnummer = $productNummer
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
            VALUES ($servicepartnernummer, $productNummer, $quantity, $next_position)
        ";
        $db->query($query_add_to_cart);
    }
}

function remove_from_cart($productNummer) {
    global $db;
    $servicepartnernummer = $_SESSION['userID'];

    $query = "
        DELETE FROM warenkorb
        WHERE Servicepartnernummer = $servicepartnernummer
        AND Artikelnummer = $productNummer
    ";
    
    $db->query($query);
}

function get_cart_items($servicepartnernummer) {
    global $db;

    $query = "
        SELECT w.Artikelnummer, w.Menge, a.artikelbezeichnung, a.einzelpreis
        FROM warenkorb w
        JOIN artikel a ON w.Artikelnummer = a.artikelnummer
        WHERE w.Servicepartnernummer = $servicepartnernummer
    ";

    return $db->getEntityArray($query);
}
?>
