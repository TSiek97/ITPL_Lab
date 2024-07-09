<?php

require_once "db_class.php";
require_once "order_functions.php";

/**
 * Function to create a new production order.
 * 
 * @param int $artikelnummer The article number.
 * @param int $quantity The quantity to produce.
 * @param string $priority The priority of the production order.
 * @return string Success or error message.
 */
function createProductionOrder($artikelnummer, $quantity, $priority) {
    global $db;

    $fertigungsziel = 'Lager';
    $status = 10; // Default status
    $auftragseingang = date('Y-m-d'); // Today's date

    // SQL query to insert a new production order
    $query = "
        INSERT INTO fertigungsauftraege (Artikelnummer, Menge, Fertigungsziel, Status, Prio, Auftragseingang) 
        VALUES ($artikelnummer, $quantity, '$fertigungsziel', $status, '$priority', '$auftragseingang')
    ";

    // Execute the query and return success or error message
    if ($db->query($query)) {
        return "Fertigungsauftrag erfolgreich erstellt!";
    } else {
        return "Fehler beim Erstellen des Fertigungsauftrags";
    }
}

/**
 * Function to complete a production order.
 * 
 * @param int $orderId The production order ID.
 */
function completeProdOrder($orderId) {
    global $db;

    // Update the production order status to complete
    $query = "UPDATE fertigungsauftraege SET 
              status = 100, 
              fertigungsdatum = CURDATE()
              WHERE fertigungsnummer = $orderId";
    
    $db->query($query);

    // Get the article number and quantity from the production order
    $query = "SELECT Artikelnummer, Menge FROM fertigungsauftraege WHERE fertigungsnummer = $orderId";
    $result = $db->getEntityArray($query);

    if (!empty($result)) {
        $artikelnummer = $result[0]->Artikelnummer;
        $menge = $result[0]->Menge;

        // Search if the article number exists in the warehouse
        $query = "SELECT Lagernummer, Bereich, Gang, Regalnummer, Fachnummer,Artikelnummer, Menge FROM lagerplatz WHERE Artikelnummer = $artikelnummer LIMIT 1";
        $lagerplatzData = $db->getEntityArray($query);

        if (!empty($lagerplatzData)) {
            // Article number exists, update the quantity
            $lagerplatzId = $lagerplatzData[0]->Lagernummer;
            $bereichId = $lagerplatzData[0]->Bereich;
            $gangId = $lagerplatzData[0]->Gang;
            $regalnummerId = $lagerplatzData[0]->Regalnummer;
            $fachnummerId = $lagerplatzData[0]->Fachnummer;
            $currentMenge = $lagerplatzData[0]->Menge;
            $newMenge = $currentMenge + $menge;

            // SQL query to update the warehouse with the new quantity
            $query = "UPDATE lagerplatz SET Menge = $newMenge 
            WHERE Lagernummer = $lagerplatzId
            AND Artikelnummer = $artikelnummer 
            AND Bereich =  '$bereichId'
            AND Gang = '$gangId'       
            AND Regalnummer = '$regalnummerId'
            AND Fachnummer = '$fachnummerId'";

            $db->query($query);
        } else {
            // Article number does not exist, create a new warehouse entry
            $query = "SELECT MAX(Regalnummer) as maxRegalnummer, MAX(Fachnummer) as maxFachnummer FROM lagerplatz WHERE Bereich = 'FA' AND Gang = '01'";
            $maxData = $db->getEntityArray($query);

            if (!empty($maxData)) {
                $maxRegalnummer = $maxData[0]->maxRegalnummer;
                $maxFachnummer = $maxData[0]->maxFachnummer;

                // Determine the next shelf and compartment number
                if ($maxFachnummer == 'A') {
                    $nextRegalnummer = $maxRegalnummer;
                    $nextFachnummer = 'B';
                } elseif ($maxFachnummer == 'B') {
                    $nextRegalnummer = $maxRegalnummer;
                    $nextFachnummer = 'C';
                } else {
                    $nextRegalnummer = $maxRegalnummer + 1;
                    $nextFachnummer = 'A';
                }
            } else {
                $nextRegalnummer = 1;
                $nextFachnummer = 'A';
            }

            // SQL query to insert a new warehouse entry
            $query = "INSERT INTO lagerplatz (Lagernummer, Bereich, Gang, Regalnummer, Fachnummer, Artikelnummer, Menge, `Menge res`) 
                      VALUES (9, 'FA', '01', $nextRegalnummer, '$nextFachnummer', $artikelnummer, $menge, 0)";
            $db->query($query);
        }

        // Update the status of the sub-orders based on stock changes
        updateTeilauftraegeStatus($artikelnummer);
    }
}

/**
 * Function to cancel a production order.
 * 
 * @param int $orderId The production order ID.
 */
function cancelProdOrder($orderId) {
    global $db;

    // SQL query to update the production order status to canceled
    $query = "UPDATE fertigungsauftraege SET status = 80 WHERE fertigungsnummer = $orderId";
    $db->query($query);
}

/**
 * Function to change the priority of a production order.
 * 
 * @param int $orderId The production order ID.
 * @param string $newPriority The new priority for the production order.
 */
function changePriority($orderId, $newPriority) {
    global $db;

    // SQL query to update the priority of the production order
    $query = "UPDATE fertigungsauftraege SET prio = '$newPriority' WHERE fertigungsnummer = $orderId";
    $db->query($query);
}

?>
