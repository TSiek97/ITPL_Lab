<?php
require_once 'db_class.php';
require_once 'cart_functions.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Function to add a new order (auftrag) to the database.
 *
 * @param int $kundennummer The customer number.
 * @param string $name The name of the customer.
 * @param string $address The address for delivery.
 * @param string $zip The postal code for delivery.
 * @param array $orderItems The items in the order.
 * @return int The order number (auftragsnummer) of the newly created order.
 * @throws Exception If the postal code or customer number is invalid.
 */
function add_auftrag($kundennummer, $name, $address, $zip, $orderItems) {
    global $db;
    $servicepartnernummer = $_SESSION['userID'];

    // Verify if the zip code exists in the ort table
    $zip = $db->get_escape_string($zip);
    $query_verify_zip = "SELECT COUNT(*) AS cnt FROM ort WHERE Postleitzahl = '$zip'";
    $result = $db->getEntity($query_verify_zip);
    if ($result->cnt == 0) {
        throw new Exception("Invalid Postleitzahl: $zip");
    }

    // Verify if the service partner has a valid customer
    $query_verify_customer = "SELECT COUNT(*) AS cnt FROM kunde WHERE Kundennummer = $kundennummer";
    $result = $db->getEntity($query_verify_customer);
    if ($result->cnt == 0) {
        throw new Exception("Invalid Kundennummer: $kundennummer");
    }

    $eingangsdatum = date('Y-m-d');
    $gesamtpreis = 0;
    $all_in_stock = true;

    // Check availability and calculate total price
    foreach ($orderItems as $item) {
        $productNummer = $item->Artikelnummer;
        $query_product = "
            SELECT artikel.artikelnummer, SUM(lagerplatz.Menge) AS Menge
            FROM artikel
            LEFT JOIN lagerplatz ON artikel.artikelnummer = lagerplatz.artikelnummer
            WHERE artikel.artikelnummer = $productNummer       
            GROUP BY artikel.artikelnummer
        ";
        $productInfo = $db->getEntity($query_product);
        if ($productInfo->Menge < $item->Menge) {
            $all_in_stock = false;
        }
        $gesamtpreis += $item->einzelpreis * $item->Menge;
    }

    $lieferdatum = $all_in_stock ? date('Y-m-d', strtotime('+3 days')) : date('Y-m-d', strtotime('+14 days'));
    $auftrag_status = $all_in_stock ? 20 : 10;

    // Create new auftrag
    $address = $db->get_escape_string($address);
    $query_add_auftrag = "
        INSERT INTO auftraege (Lieferdatum, Status, Gesamtpreis, Lieferadresse_straße, Lieferadresse_Hausnummer, Lieferadresse_postleitzahl, Bearbeiternummer, Abschlussdatum, Eingangsdatum, Auftragsart, Kundennummer)
        VALUES ('$lieferdatum', $auftrag_status, $gesamtpreis, '$address', '', '$zip', $servicepartnernummer, NULL, '$eingangsdatum', 'Bestellung', $kundennummer)
    ";
    $db->query($query_add_auftrag);
    $auftragsnummer = $db->getAutoIncID();

    // Add auftragspositionen
    foreach ($orderItems as $index => $item) {
        $position = $index + 1;
        $productNummer = $item->Artikelnummer;
        $query_product = "
            SELECT artikel.artikelnummer, SUM(lagerplatz.Menge) AS Menge
            FROM artikel
            LEFT JOIN lagerplatz ON artikel.artikelnummer = lagerplatz.artikelnummer
            WHERE artikel.artikelnummer = $productNummer       
            GROUP BY artikel.artikelnummer
        ";
        $productInfo = $db->getEntity($query_product);
        // Determine $teilauftrag_status
        if ($auftrag_status == 70) {
            $teilauftrag_status = 70;
        } else {
            $teilauftrag_status = ($productInfo->Menge >= $item->Menge) ? 20 : 10;
        }
        $query_add_position = "
            INSERT INTO auftragsposition (Auftragsnummer, Position, Teilauftrag, Artikelnummer, Menge, Kaufpreis, Status, Abschlussdatum)
            VALUES ($auftragsnummer, '$position', 1, {$item->Artikelnummer}, {$item->Menge}, {$item->einzelpreis}, $teilauftrag_status, NULL)
        ";
        $db->query($query_add_position);
    }

    // Clear the cart after order is placed
    clear_cart($servicepartnernummer);

    return $auftragsnummer;
}

/**
 * Function to clear the cart for a specific service partner.
 *
 * @param int $servicepartnernummer The service partner number.
 */
function clear_cart($servicepartnernummer) {
    global $db;

    // SQL query to delete all items in the cart for the service partner
    $query_clear_cart = "
        DELETE FROM warenkorb
        WHERE Servicepartnernummer = $servicepartnernummer
    ";
    $db->query($query_clear_cart);
}
?>
