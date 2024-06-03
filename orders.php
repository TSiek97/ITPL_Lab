<?php

include 'header.php';


// Start session
//session_start();

// Check if userType is set in the session
if (isset($_SESSION['userType'])) {
    $userType = $_SESSION['userType'];
    $userID = $_SESSION['userID']; // Assuming you also need userID for further processing
    require_once "db_class.php";
    // Database connection parameters
    $DBServer   = 'localhost';
    $DBHost     = 'airlimited';
    $DBUser     = 'root';
    $DBPassword = '';

    // Create a database connection
    $db = new DBConnector($DBServer, $DBHost, $DBUser, $DBPassword);
    $db->connect();

    // Check userType and retrieve data accordingly
    if ($userType == "mitarbeiter") {

        echo '<div class="grid-content grid-content-header">';                        
        echo '    <div class="grid-item">Fertigungs Nr.</div>'; 
        echo '    <div class="grid-item">Artikel</div>'; 
        echo '    <div class="grid-item">Stückzahl</div>'; 
        echo '    <div class="grid-item">Fertigungsart</div>'; 
        echo '    <div class="grid-item">Auftragsdatum</div>'; 
        echo '    <div class="grid-item">Abschlussdatum</div>'; 
        echo '    <div class="grid-item">Fertigungsziel</div>'; 
        echo '    <div class="grid-item">Prio</div>'; 
        echo '    <div class="grid-item">Status</div>'; 
        echo '</div>'; 
        echo '<div class="content-container">'; 

        // Get data for Fertigungsübersicht
        $query_fertigungsaufträge = "SELECT fertigungsauftraege.fertigungsnummer, fertigungsauftraege.menge, fertigungsauftraege.auftragseingang, fertigungsauftraege.fertigungsdatum,fertigungsauftraege.prio, fertigungsauftraege.fertigungsziel, artikel.artikelbezeichnung, statusbeschreibung.beschreibung 
        FROM fertigungsauftraege 
        LEFT JOIN artikel 
        ON fertigungsauftraege.artikelnummer = artikel.artikelnummer 
        LEFT JOIN statusbeschreibung 
        ON fertigungsauftraege.status = statusbeschreibung.status";        
        $query_fertigungsaufträge = "
                    SELECT 
                        fertigungsauftraege.fertigungsnummer, 
                        fertigungsauftraege.menge, 
                        fertigungsauftraege.fertigungsdatum, 
                        fertigungsauftraege.fertigungsziel, 
                        fertigungsauftraege.auftragseingang,
                        fertigungsauftraege.prio,
                        artikel.artikelbezeichnung, 
                        artikel.Fertigungsart,
                        statusbeschreibung.beschreibung 
                        FROM fertigungsauftraege 
                        LEFT JOIN artikel 
                            ON fertigungsauftraege.artikelnummer = artikel.artikelnummer 
                        LEFT JOIN statusbeschreibung 
                            ON fertigungsauftraege.status = statusbeschreibung.status
                            ORDER BY CASE statusbeschreibung.beschreibung 
                            WHEN 'Hoch' THEN 1 
                            WHEN 'Mittel' THEN 2 
                            WHEN 'Niedrig' THEN 3 
                 
                            ELSE 4 
                         END ASC, 
                         CASE fertigungsauftraege.fertigungsziel 
                            WHEN 'Kunde' THEN 1 
                            WHEN 'Lager' THEN 2 
                   
                            ELSE 3
                         END ASC, 
                         auftragseingang DESC";
               
        $fertigungsuebersicht_data = $db->getEntityArray($query_fertigungsaufträge);

        if (!empty($fertigungsuebersicht_data)) {
            foreach ($fertigungsuebersicht_data as $data) {
                echo '<div class="clickable-container" onclick="toggleCheckbox(\'toggle\', \'hidden-content\')">';
                echo '<input type="checkbox" id="toggle" class="toggle-input">';
                echo '<label for="toggle" class="toggle-label">Click me</label>';
                echo '<div class="grid-container">';
                echo '<div class="grid-content">';
                echo '<div class="grid-item">' . ($data->fertigungsnummer ?? '') . '</div>';
                echo '<div class="grid-item">' . ($data->artikelbezeichnung ?? '') . '</div>';
                echo '<div class="grid-item">' . ($data->menge ?? '') . ' stk</div>';
                echo '<div class="grid-item">' . ($data->Fertigungsart ?? '') . '</div>';
                echo '<div class="grid-item">' . ($data->auftragseingang ?? '') . '</div>';
                echo '<div class="grid-item">' . ($data->fertigungsdatum ?? '') . '</div>';
                echo '<div class="grid-item">' . ($data->fertigungsziel ?? '') . '</div>';
                echo '<div class="grid-item">' . ($data->prio ?? '') . '</div>';
                echo '<div class="grid-item">' . ($data->beschreibung ?? '') . '</div>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
  
                
            }
          
           
            // right place?
       
  
           
        } else {
            // Handle case when $fertigungsuebersicht_data is empty
            echo "No data available.";
        }

        


    } elseif ($userType == "servicepartner") {
        echo '<div class="grid-content grid-content-header">';                       
        echo '<div class="grid-item">Bestell Nr.</div>';   
        echo '<div class="grid-item">Preis</div>';   
        echo '<div class="grid-item">Stückzahl</div>';   
        echo '<div class="grid-item">Bestelldatum</div>';   
        echo '<div class="grid-item">Zustellung am</div>';   
        echo '<div class="grid-item">Status</div>';   
        echo '</div>';   
        echo '<div class="content-container">'; 

        // Get data for Auftragsübersicht
        $query_aufträge = "SELECT auftraege.auftragsnummer, auftraege.gesamtpreis, auftraege.eingangsdatum, auftraege.abschlussdatum, statusbeschreibung.beschreibung 
        FROM auftraege 
        LEFT JOIN statusbeschreibung 
        ON auftraege.status = statusbeschreibung.status 
        WHERE auftraege.bearbeiternummer = $userID";


        $query_aufträge = "
            SELECT auftraege.auftragsnummer, 
                auftraege.gesamtpreis, 
                auftraege.eingangsdatum, 
                auftraege.abschlussdatum, 
                statusbeschreibung.beschreibung,
                COALESCE(positions_sum.Menge, 0) AS menge
            FROM auftraege 
            LEFT JOIN statusbeschreibung ON auftraege.status = statusbeschreibung.status 
            LEFT JOIN (
                SELECT auftragsnummer, SUM(Menge) AS Menge
                FROM auftragsposition 
                GROUP BY auftragsnummer
            ) AS positions_sum ON auftraege.auftragsnummer = positions_sum.auftragsnummer
            WHERE auftraege.bearbeiternummer = $userID";

        $query_aufträge = "
            SELECT auftraege.auftragsnummer, 
                   SUM(auftragsposition.Kaufpreis * auftragsposition.Menge) AS gesamtpreis, 
                   auftraege.eingangsdatum, 
                   auftraege.abschlussdatum, 
                   statusbeschreibung.beschreibung,
                   COALESCE(positions_sum.Menge, 0) AS menge
            FROM auftraege 
            LEFT JOIN statusbeschreibung ON auftraege.status = statusbeschreibung.status 
            LEFT JOIN (
                SELECT auftragsnummer, SUM(Menge) AS Menge
                FROM auftragsposition 
                GROUP BY auftragsnummer
            ) AS positions_sum ON auftraege.auftragsnummer = positions_sum.auftragsnummer
            LEFT JOIN auftragsposition ON auftraege.auftragsnummer = auftragsposition.auftragsnummer
            WHERE auftraege.bearbeiternummer = $userID
            GROUP BY auftraege.auftragsnummer, auftraege.eingangsdatum, auftraege.abschlussdatum, statusbeschreibung.beschreibung;
        ";
        $query_aufträge = "
        SELECT auftraege.auftragsnummer, 
            ROUND(SUM(auftragsposition.Menge * artikel.Einzelpreis),2) AS gesamtpreis, 
               auftraege.eingangsdatum, 
               auftraege.abschlussdatum, 
               statusbeschreibung.beschreibung,
               COALESCE(positions_sum.Menge, 0) AS menge
        FROM auftraege 
        LEFT JOIN statusbeschreibung ON auftraege.status = statusbeschreibung.status 
        LEFT JOIN (
            SELECT auftragsnummer, SUM(Menge) AS Menge
            FROM auftragsposition 
            GROUP BY auftragsnummer
        ) AS positions_sum ON auftraege.auftragsnummer = positions_sum.auftragsnummer
        LEFT JOIN auftragsposition ON auftraege.auftragsnummer = auftragsposition.auftragsnummer
        LEFT JOIN artikel ON auftragsposition.artikelnummer = artikel.artikelnummer
        WHERE auftraege.bearbeiternummer = $userID
        GROUP BY auftraege.auftragsnummer, auftraege.eingangsdatum, auftraege.abschlussdatum, statusbeschreibung.beschreibung;
    ";
    
        $query_auftragspositionen = "SELECT artikel.artikelbezeichnung, auftragsposition.Kaufpreis, auftragsposition.Menge, statusbeschreibung.beschreibung, auftraege.auftragsnummer
        FROM auftragsposition 
        LEFT JOIN auftraege 
        ON auftragsposition.auftragsnummer = auftraege.auftragsnummer 
        LEFT JOIN artikel ON auftragsposition.artikelnummer = artikel.artikelnummer
        LEFT JOIN statusbeschreibung ON auftragsposition.status = statusbeschreibung.status 
        WHERE auftraege.bearbeiternummer = $userID";
        $query_auftragspositionen = "
        SELECT artikel.artikelbezeichnung, 
            ROUND(auftragsposition.Menge * artikel.Einzelpreis, 2) AS Kaufpreis, 
            auftragsposition.Menge, 
            statusbeschreibung.beschreibung, 
            auftraege.auftragsnummer
        FROM auftragsposition 
        LEFT JOIN auftraege ON auftragsposition.auftragsnummer = auftraege.auftragsnummer 
        LEFT JOIN artikel ON auftragsposition.artikelnummer = artikel.artikelnummer
        LEFT JOIN statusbeschreibung ON auftragsposition.status = statusbeschreibung.status 
        WHERE auftraege.bearbeiternummer = $userID
        ";



        $auftragsuebersicht_data = $db->getEntityArray($query_aufträge);
        $auftragspositionen_data = $db->getEntityArray($query_auftragspositionen);

        if (!empty($auftragsuebersicht_data)) {
            // Loop through $auftragsuebersicht_data
            foreach ($auftragsuebersicht_data as $data) {
                echo '<div class="clickable-container" onclick="toggleCheckbox(\'toggle\', \'hidden-content\')">';
                echo '<input type="checkbox" id="toggle" class="toggle-input">';
                echo '<label for="toggle" class="toggle-label">Click me</label>';
                echo '<div class="grid-container">';
                echo '<div class="grid-content">';
                // Check if the property 'auftragsnummer' exists before accessing it
                $auftragsnummer = isset($data->auftragsnummer) ? $data->auftragsnummer : '';
                echo '<div class="grid-item">' . $auftragsnummer . '</div>';
                // Check if the property 'gesamtpreis' exists before accessing it
                $gesamtpreis = isset($data->gesamtpreis) ? $data->gesamtpreis : '';
                echo '<div class="grid-item">' . $gesamtpreis . ' €</div>';
                // Check if the property 'menge' exists before accessing it
                $menge = isset($data->menge) ? $data->menge : '';
                echo '<div class="grid-item">' . $menge . ' stk</div>';
                // Check if the property 'eingangsdatum' exists before accessing it
                $eingangsdatum = isset($data->eingangsdatum) ? $data->eingangsdatum : '';
                echo '<div class="grid-item">' . $eingangsdatum . '</div>';
                // Check if the property 'abschlussdatum' exists before accessing it
                $abschlussdatum = isset($data->abschlussdatum) ? $data->abschlussdatum : '';
                echo '<div class="grid-item">' . $abschlussdatum . '</div>';
                // Check if the property 'status' exists before accessing it
                $status = isset($data->beschreibung) ? $data->beschreibung : '';
                echo '<div class="grid-item">' . $status . '</div>';
                echo '</div>';
        
                // Loop through $auftragspositionen_data for additional content
                if (!empty($auftragspositionen_data)) {
                    foreach ($auftragspositionen_data as $position) {
                        
                        if (isset($position->auftragsnummer) && $position->auftragsnummer == $auftragsnummer ) {
                    
                            echo '<div class="hidden-content" id="hidden-content">';
                            echo '<div class="hidden-content">';
                            echo '<div class="hidden-content">';
                            echo '<div class="grid-row">';
                            // Check if the property 'artikelbezeichnung' exists before accessing it
                            $artikelbezeichnung = isset($position->artikelbezeichnung) ? $position->artikelbezeichnung : '';
                            echo '<div class="grid-item">' . $artikelbezeichnung . '</div>';
                            // Check if the property 'Kaufpreis' exists before accessing it
                            $kaufpreis = isset($position->Kaufpreis) ? $position->Kaufpreis : '';
                            echo '<div class="grid-item">' . $kaufpreis . ' €</div>';
                            // Check if the property 'Menge' exists before accessing it
                            $mengePosition = isset($position->Menge) ? $position->Menge : '';
                            echo '<div class="grid-item">' . $mengePosition . ' stk</div>';
                            // Placeholder for additional columns if needed
                            echo '<div class="grid-item"></div>';
                            echo '<div class="grid-item"></div>';
                            // Check if the property 'beschreibung' exists before accessing it
                            $beschreibung = isset($position->beschreibung) ? $position->beschreibung : '';
                            echo '<div class="grid-item">' . $beschreibung . '</div>';
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                        }
                    }
                }
                echo '</div>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
        } else {
            // Handle case when $auftragsuebersicht_data is empty
            echo "No data available.";
        }

    } elseif ($userType == "kunde") {
        echo "Kunde";
        // For kunde, do nothing for now
        // You can add further logic here if needed
    } else {
        // Handle unknown userType
        echo "Unknown userType";
        // header("Location: logout.php");
    }
} else {
    // Handle case when userType is not set in the session
    echo "UserType not found in session.";
    // header("Location: logout.php");
}
// include 'templates/aufträge.php';
echo '</div>';

include 'footer.php';

