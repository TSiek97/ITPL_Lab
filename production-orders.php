<?php
include 'header.php'; // Include the header component
require_once 'functions/production-orders_functions.php'; // Include production orders functions

// Start session (already started in the header)
//session_start();

// Check if userType is set in the session
if (isset($_SESSION['userType'])) {
    $userType = $_SESSION['userType'];
    $userID = $_SESSION['userID']; // Assuming you also need userID for further processing

    // Handle form submission for completing, canceling orders, or changing priority
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action']) && isset($_POST['order_id'])) {
            $orderId = $_POST['order_id'];
            if ($_POST['action'] === 'complete') {
                // Call a function to complete the order
                completeProdOrder($orderId);
            } elseif ($_POST['action'] === 'cancel' && $userType == 'management') {
                // Call a function to cancel the order
                cancelProdOrder($orderId);
            } elseif ($_POST['action'] === 'change_priority' && $userType == 'management') {
                // Call a function to change the priority
                $newPriority = $_POST['new_priority'];
                changePriority($orderId, $newPriority);
            }
        }
    }

    // Check userType and retrieve data accordingly
    if ($userType == "fertigung" || $userType == "management") {
        // Display grid headers for production orders overview
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
        echo '    <div class="grid-item">Aktionen</div>';
        echo '</div>';

        // Query to get data for production orders overview
        $query_fertigungsaufträge = "
            SELECT 
                fertigungsauftraege.fertigungsnummer, 
                fertigungsauftraege.menge, 
                fertigungsauftraege.fertigungsdatum, 
                fertigungsauftraege.fertigungsziel, 
                fertigungsauftraege.auftragseingang,
                fertigungsauftraege.prio,
                artikel.artikelbezeichnung, 
                artikel.artikelnummer,
                artikel.Fertigungsart,
                statusbeschreibung.beschreibung 
            FROM fertigungsauftraege 
            LEFT JOIN artikel 
                ON fertigungsauftraege.artikelnummer = artikel.artikelnummer 
            LEFT JOIN statusbeschreibung 
                ON fertigungsauftraege.status = statusbeschreibung.status
            ORDER BY 
                CASE statusbeschreibung.beschreibung 
                    WHEN 'abgeschlossen' THEN 3
                    WHEN 'storniert' THEN 3
                    ELSE 1
                END ASC,
                CASE fertigungsauftraege.prio
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
        if (is_array($fertigungsuebersicht_data)) {
            // Proceed with processing the data
        } else {
            // Handle the error, maybe log it and set an appropriate response
            error_log("Error: Expected array, got " . gettype($fertigungsuebersicht_data));
            $fertigungsuebersicht_data = []; // Set a default empty array
        }
        if (!empty($fertigungsuebersicht_data)) {
            // Loop through $fertigungsuebersicht_data to display production orders
            foreach ($fertigungsuebersicht_data as $data) {
                echo '<div class="clickable-container">';
                echo '<div class="grid-container">';
                echo '<div class="grid-content">';
                echo '<div class="grid-item">' . ($data->fertigungsnummer ?? '') . '</div>';
                echo '<div class="grid-item"><a href="product-view.php?product=' . ($data->artikelnummer ?? '') . '">' . ($data->artikelbezeichnung ?? '') . '</a></div>';
                echo '<div class="grid-item">' . ($data->menge ?? '') . ' stk</div>';
                echo '<div class="grid-item">' . ($data->Fertigungsart ?? '') . '</div>';
                echo '<div class="grid-item">' . ($data->auftragseingang ?? '') . '</div>';
                echo '<div class="grid-item">' . ($data->fertigungsdatum ?? '') . '</div>';
                echo '<div class="grid-item">' . ($data->fertigungsziel ?? '') . '</div>';
                echo '<div class="grid-item">';
                if ($userType == "management" && $data->beschreibung != "abgeschlossen" && $data->beschreibung != "storniert") {
                    echo '<form method="POST" action="">';
                    echo '<input type="hidden" name="order_id" value="' . $data->fertigungsnummer . '">';
                    echo '<select name="new_priority" onchange="this.form.submit()">';
                    $prioOptions = ['Hoch', 'Mittel', 'Niedrig'];
                    foreach ($prioOptions as $option) {
                        $selected = ($data->prio == $option) ? 'selected' : '';
                        echo '<option value="' . $option . '" ' . $selected . '>' . $option . '</option>';
                    }
                    echo '</select>';
                    echo '<input type="hidden" name="action" value="change_priority">';
                    echo '</form>';
                } else {
                    echo ($data->prio ?? '');
                }
                echo '</div>';
                echo '<div class="grid-item">' . ($data->beschreibung ?? '') . '</div>';
                echo '<div class="grid-item">';
                echo '<form method="POST" action="">';
                echo '<input type="hidden" name="order_id" value="' . $data->fertigungsnummer . '">';
                if ($data->beschreibung != "abgeschlossen" && $data->beschreibung != "storniert") {
                    echo '<button class="round-checkmark" type="submit" name="action" value="complete"></button>';
                }
                if ($userType == "management" && $data->beschreibung != "abgeschlossen" && $data->beschreibung != "storniert") {
                    echo '<button class="round-cancel" type="submit" name="action" value="cancel"></button>';
                }
                echo '</form>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
        } else {
            // Handle case when $fertigungsuebersicht_data is empty
            echo "No data available.";
        }
    } else {
        // Handle unauthorized access
        echo "Unauthorized access.";
    }
} else {
    // Handle case when userType is not set in the session
    echo "UserType not found in session.";
}

include 'footer.php'; // Include the footer component
?>
