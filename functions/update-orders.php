<?php

require_once "db_class.php";
function createOrUpdateProductionOrder($produktnummer, $quantity) {
    global $db;

    // Query to get the current stock for the product
    $stockQuery = "
        SELECT SUM(Menge) as total_stock
        FROM lagerplatz
        WHERE artikelnummer = $produktnummer
    ";
    $stockResult = $db->query($stockQuery);
    $stockRow = $stockResult->fetch_assoc();
    $currentStock = $stockRow['total_stock'] ?? 0;

    // Query to get the minimum stock requirement (Mindestbestand) for the product
    $minStockQuery = "
        SELECT mindesbestand
        FROM artikel
        WHERE artikelnummer = $produktnummer
    ";
    $minStockResult = $db->query($minStockQuery);
    $minStockRow = $minStockResult->fetch_assoc();
    $minStock = $minStockRow['mindesbestand'];

    // Query to get the outstanding order quantities for the product
    $outstandingOrdersQuery = "
        SELECT SUM(Menge) as total_ordered
        FROM auftragsposition
        WHERE artikelnummer = $produktnummer AND status <> 80
    ";
    $outstandingOrdersResult = $db->query($outstandingOrdersQuery);
    $outstandingOrdersRow = $outstandingOrdersResult->fetch_assoc();
    $totalOrdered = $outstandingOrdersRow['total_ordered'] ?? 0;

    // Calculate the production quantity needed
    $neededQuantity = max(($quantity + $minStock) - ($currentStock - $totalOrdered), 0);

    if ($neededQuantity > 0) {
        // Check if there's an existing production order for the product
        $existingOrderQuery = "
            SELECT fertigungsnummer, Menge
            FROM fertigungsauftraege
            WHERE artikelnummer = $produktnummer AND status < 80
            LIMIT 1
        ";
        $existingOrderResult = $db->query($existingOrderQuery);

        if ($existingOrderResult->num_rows > 0) {
            // Update the existing production order
            $existingOrderRow = $existingOrderResult->fetch_assoc();
            $newQuantity = $existingOrderRow['Menge'] + $neededQuantity;
            $updateOrderQuery = "
                UPDATE fertigungsauftraege
                SET Menge = $newQuantity
                WHERE fertigungsnummer = {$existingOrderRow['fertigungsnummer']}
            ";
            if ($db->query($updateOrderQuery)) {
                return "Existing production order updated successfully!";
            } else {
                return "Error updating the existing production order";
            }
        } else {
            // Create a new production order
            $priority = 'Mittel'; // Assuming priority can be set as 'Mittel'
            $fertigungsziel = 'Lager';
            $status = 10; // Default status
            $auftragseingang = date('Y-m-d'); // Today's date

            $insertOrderQuery = "
                INSERT INTO fertigungsauftraege (Artikelnummer, Menge, Fertigungsziel, Status, Prio, Auftragseingang)
                VALUES ($produktnummer, $neededQuantity, '$fertigungsziel', $status, '$priority', '$auftragseingang')
            ";
            if ($db->query($insertOrderQuery)) {
                return "New production order created successfully!";
            } else {
                return "Error creating the new production order";
            }
        }
    } else {
        return "No production needed as stock is sufficient to fulfill the order.";
    }
}

function updateAuftraege($db) {
    // Initialize message variable
    $messages = [];

    // Query to get all distinct Artikelnummer from artikel table
    $query = "SELECT DISTINCT Artikelnummer, fertigungsart FROM artikel";
    $artikelData = $db->getEntityArray($query);

    // Loop through each Artikelnummer
    foreach ($artikelData as $artikel) {
        $artikelnummer = $artikel->Artikelnummer;
        $fertigungsart = $artikel->fertigungsart;
        
        // Initialize variables for each Artikelnummer
        $freie_produkte = 0;
        $mindestmenge = 0;

        // Query to calculate freie_produkte for current Artikelnummer
        $query = "
        SELECT 
            (SELECT IFNULL(SUM(Menge), 0) FROM lagerplatz WHERE Artikelnummer = $artikelnummer) -
            (SELECT IFNULL(SUM(Menge), 0) FROM auftragsposition WHERE Artikelnummer = $artikelnummer AND STATUS < 30) +
            (SELECT IFNULL(SUM(Menge), 0) FROM fertigungsauftraege WHERE Artikelnummer = $artikelnummer AND STATUS < 100)
            AS freie_produkte
        ";

        $data = $db->getEntityArray($query);

        if (!empty($data)) {
            $freie_produkte = $data[0]->freie_produkte;
        }

        // Query to get mindestmenge for current Artikelnummer
        $query = "SELECT Mindestbestand FROM artikel WHERE Artikelnummer = $artikelnummer";
        $data = $db->getEntityArray($query);

        if (!empty($data)) {
            $mindestmenge = $data[0]->Mindestbestand;
        }

        // Determine the prio and fertigungsziel for the current Artikelnummer
        $prio = 'Niedrig';
        $fertigungsziel = 'Lager';

        $query = "SELECT COUNT(*) AS count FROM auftragsposition WHERE Artikelnummer = $artikelnummer";
        $data = $db->getEntityArray($query);

        if ($fertigungsart == 'Kunde' || $data[0]->count > 0) {
            $prio = 'Mittel';
            $fertigungsziel = 'Kunde';
        }

        // Check the condition and insert if necessary
        if ($freie_produkte < $mindestmenge) {
            $menge = $mindestmenge * 3 - $freie_produkte;
            $query = "INSERT INTO fertigungsauftraege (Artikelnummer, Menge, Fertigungsziel, Prio, Auftragseingang, Status) 
                      VALUES ($artikelnummer, $menge, '$fertigungsziel', '$prio', CURDATE(), 10)";
            if ($db->query($query) === TRUE) {
                $messages[] = "Record inserted successfully for Artikelnummer $artikelnummer";
            } else {
                $messages[] = "Error inserting record for Artikelnummer $artikelnummer";
            }
        } else {
            $messages[] = "No need to insert, sufficient products available for Artikelnummer $artikelnummer";
        }
    }

    // Return the messages
    return implode("\n", $messages);
}
?>
