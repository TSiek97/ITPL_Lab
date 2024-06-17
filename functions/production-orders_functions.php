<?php

require_once "db_class.php";

function createProductionOrder($artikelnummer, $quantity, $priority) {
    global $db;
    
    $fertigungsziel = 'Lager';
    $status = 10;  // Default status
    $auftragseingang = date('Y-m-d');  // Today's date

    $query = "
        INSERT INTO fertigungsauftraege (Artikelnummer, Menge, Fertigungsziel, Status, Prio, Auftragseingang) 
        VALUES ($artikelnummer, $quantity, '$fertigungsziel', $status, '$priority', '$auftragseingang')
    ";

    if ($db->query($query)) {
        return "Fertigungsauftrag erfolgreich erstellt!";
    } else {
        return "Fehler beim Erstellen des Fertigungsauftrags";
    }
}

// Existing functions for managing production orders
function completeOrder($orderId) {
    global $db;

    // Update fertigungsauftraege table
    $query = "UPDATE fertigungsauftraege SET 
              status = 100, 
              fertigungsdatum = CURDATE()
              WHERE fertigungsnummer = $orderId";
    
    $db->query($query);

    // Get the Artikelnummer and Menge from fertigungsauftraege
    $query = "SELECT Artikelnummer, Menge FROM fertigungsauftraege WHERE fertigungsnummer = $orderId";
    $result = $db->getEntityArray($query);

    if (!empty($result)) {
        $artikelnummer = $result[0]->Artikelnummer;
        $menge = $result[0]->Menge;

        // Search if Artikelnummer exists in lagerplatz
        $query = "SELECT * FROM lagerplatz WHERE Artikelnummer = $artikelnummer";
        $lagerplatzData = $db->getEntityArray($query);

        if (!empty($lagerplatzData)) {
            // Artikelnummer exists, update Menge
            $lagerplatzId = $lagerplatzData[0]->Lagernummer;
            $currentMenge = $lagerplatzData[0]->Menge;

            $newMenge = $currentMenge + $menge;

            $query = "UPDATE lagerplatz SET Menge = $newMenge WHERE Lagernummer = $lagerplatzId";
            $db->query($query);
        } else {
            // Artikelnummer does not exist, create a new lagerplatz entry
            $query = "SELECT MAX(Regalnummer) as maxRegalnummer, MAX(Fachnummer) as maxFachnummer FROM lagerplatz WHERE Bereich = 'FA' AND Gang = '01'";
            $maxData = $db->getEntityArray($query);

            if (!empty($maxData)) {
                $maxRegalnummer = $maxData[0]->maxRegalnummer;
                $maxFachnummer = $maxData[0]->maxFachnummer;

                // Determine next Regalnummer and Fachnummer
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

            $query = "INSERT INTO lagerplatz (Lagernummer, Bereich, Gang, Regalnummer, Fachnummer, Artikelnummer, Menge, `Menge res`) 
                      VALUES (9, 'FA', '01', $nextRegalnummer, '$nextFachnummer', $artikelnummer, $menge, 0)";
            $db->query($query);
        }
    }
}


function cancelOrder($orderId) {
    global $db;
    $query = "UPDATE fertigungsauftraege SET status = 80 WHERE fertigungsnummer = $orderId";
    $db->query($query);
}

function changePriority($orderId, $newPriority) {
    global $db;
    $query = "UPDATE fertigungsauftraege SET 
    prio = '$newPriority' 
    WHERE fertigungsnummer = $orderId";
    $db->query($query);
}
?>
