<?php
// cart_functions.php

/**
 * Function to remove an item from the cart.
 * 
 * @param int $productNummer The article number of the product to be removed.
 */
function remove_from_cart($productNummer) {
    global $db;
    $servicepartnernummer = $_SESSION['userID'];

    // SQL query to delete the item from the cart
    $query = "
        DELETE FROM warenkorb
        WHERE Servicepartnernummer = $servicepartnernummer
        AND Artikelnummer = $productNummer
    ";
    
    // Execute the query
    $db->query($query);
}

/**
 * Function to get all items in the cart for a given service partner.
 * 
 * @param int $servicepartnernummer The service partner number.
 * @return array The array of cart items.
 */
function get_cart_items($servicepartnernummer) {
    global $db;

    // SQL query to select all items in the cart for the given service partner
    $query = "
        SELECT w.Artikelnummer, w.Menge, a.artikelbezeichnung, a.einzelpreis
        FROM warenkorb w
        JOIN artikel a ON w.Artikelnummer = a.artikelnummer
        WHERE w.Servicepartnernummer = $servicepartnernummer
    ";

    // Return the array of cart items
    return $db->getEntityArray($query);
}


/**
 * Function to add an item to the cart.
 * 
 * @param int $productNummer The article number of the product to be added.
 * @param int $quantity The quantity of the product to be added.
 */
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

        // SQL query to update the item quantity in the cart
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

        // SQL query to add the new item to the cart
        $query_add_to_cart = "
            INSERT INTO warenkorb (Servicepartnernummer, Artikelnummer, Menge, Position)
            VALUES ($servicepartnernummer, $productNummer, $quantity, $next_position)
        ";
        $db->query($query_add_to_cart);
    }
}