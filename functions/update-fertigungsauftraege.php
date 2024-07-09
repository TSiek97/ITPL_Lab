<?php
/**
 * Function to update the production orders (Fertigungsaufträge).
 * This function checks the stock levels and pending orders for each distinct product (Artikelnummer)
 * and determines if new production orders need to be created to meet the minimum stock requirements.
 * 
 * @param DBConnector $db The database connection object.
 * @return string A summary message of the actions performed.
 */
function updateFertigungsauftraege($db) {
    // Initialize message variable
    $messages = [];

    // Query to get all distinct Artikelnummer and their production type (fertigungsart) from the artikel table
    $query = "SELECT DISTINCT Artikelnummer, fertigungsart FROM artikel";
    $artikelData = $db->getEntityArray($query);

    // Loop through each Artikelnummer
    foreach ($artikelData as $artikel) {
        $artikelnummer = $artikel->Artikelnummer;
        $fertigungsart = $artikel->fertigungsart;
        
        // Initialize variables for each Artikelnummer
        $freie_produkte = 0;
        $mindestmenge = 0;

        // Query to calculate freie_produkte (free products) for the current Artikelnummer
        $query = "
        SELECT 
            (SELECT IFNULL(SUM(Menge), 0) FROM lagerplatz WHERE Artikelnummer = $artikelnummer) -
            (SELECT IFNULL(SUM(Menge), 0) FROM auftragsposition WHERE Artikelnummer = $artikelnummer AND STATUS < 30) +
            (SELECT IFNULL(SUM(Menge), 0) FROM fertigungsauftraege WHERE Artikelnummer = $artikelnummer AND STATUS < 100 AND STATUS != 80)
            AS freie_produkte
        ";
        $data = $db->getEntityArray($query);

        if (!empty($data)) {
            $freie_produkte = $data[0]->freie_produkte;
        }

        // Query to get mindestmenge (minimum stock) for the current Artikelnummer
        $query = "SELECT Mindestbestand FROM artikel WHERE Artikelnummer = $artikelnummer";
        $data = $db->getEntityArray($query);

        if (!empty($data)) {
            $mindestmenge = $data[0]->Mindestbestand;
        }

        // Determine the priority (prio) and production goal (fertigungsziel) for the current Artikelnummer
        $prio = 'Niedrig';
        $fertigungsziel = 'Lager';

        $query = "SELECT COUNT(*) AS count FROM auftragsposition WHERE Artikelnummer = $artikelnummer";
        $data = $db->getEntityArray($query);

        if ($fertigungsart == 'Kunde' || $data[0]->count > 0) {
            $prio = 'Mittel';
            $fertigungsziel = 'Kunde';
        }

        // Check the condition and insert a new production order if necessary
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
