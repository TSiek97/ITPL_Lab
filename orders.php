<?php
include 'header.php';

// Check if userType is set in the session
if (isset($_SESSION['userType'])) {
    $userType = $_SESSION['userType'];
    $userID = $_SESSION['userID']; // Assuming you also need userID for further processing
    require_once "functions/order_functions.php"; // Include the order functions file

    // Handle form submissions for completing or canceling orders
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['completeOrder'])) {
            $orderId = $_POST['orderId'];
            completePartialOrder($orderId);
            header("Location: orders.php");
            exit;
        } elseif (isset($_POST['completeFullOrder'])) {
            $orderId = $_POST['orderId'];
            completeOrder($orderId);
            header("Location: orders.php");
            exit;
        } elseif (isset($_POST['cancelOrder'])) {
            $orderId = $_POST['orderId'];
            cancelOrder($orderId);
            header("Location: orders.php");
            exit;
        } elseif (isset($_POST['cancelOrderItem'])) {
            $orderItemId = $_POST['orderItemId'];
            $orderId = $_POST['orderId'];
            $teilauftrag = $_POST['teilauftrag'];
            cancelOrderItem($orderId, $teilauftrag, $orderItemId);
            header("Location: orders.php");
            exit;
        } elseif (isset($_POST['updateOrderStatus'])) {
            $orderId = $_POST['orderId'];
            $newStatus = $_POST['updateOrderStatus'];
            updateOrderStatus($orderId, $newStatus);
            header("Location: orders.php");
            exit;
        } elseif (isset($_POST['updateOrderItemStatus'])) {
            $orderId = $_POST['orderId'];
            $orderItemId = $_POST['orderItemId'];
            $teilauftrag = $_POST['teilauftrag'];
            $newStatus = $_POST['updateOrderItemStatus'];
            updateOrderItemStatus($orderId,$orderItemId,$teilauftrag, $newStatus);
            header("Location: orders.php");
            exit;
        } elseif (isset($_POST['setTeilauftraegeVersendet'])) {
            $orderId = $_POST['orderId'];
            setTeilauftraegeVersendet($orderId);
            header("Location: orders.php");
            exit;
        }
    }

    echo '<div class="grid-content grid-content-header">';
    echo '    <div class="grid-item">Auftragsnummer</div>'; 
    echo '    <div class="grid-item">Gesamtpreis</div>'; 
    echo '    <div class="grid-item">Menge</div>'; 
    if ($userType == "lager") {
        echo '<div class="grid-item">Vorrat</div>'; 
    } 
    echo '    <div class="grid-item">Eingangsdatum</div>'; 
    echo '    <div class="grid-item">Lieferdatum</div>'; 
    echo '    <div class="grid-item">Beschreibung</div>'; 
    echo '    <div class="grid-item">Adresse</div>'; 
    if (($userType == "servicepartner") || ($userType == "management")) {
        echo '    <div class="grid-item">';
            if ($userType == "servicepartner") {
                echo 'Kundennummer';
            } else if ($userType == "management") {
                echo 'Bearbeiternummer';
            }
        echo '</div>';
    }
    echo '    <div class="grid-item">Aktionen</div>'; 
    echo '</div>';
    autoUpdateOrderStatusAll();
    // Get data for Auftragsübersicht
    $query_aufträge = "
    SELECT auftraege.auftragsnummer, 
        ROUND(SUM(auftragsposition.Menge * artikel.Einzelpreis),2) AS gesamtpreis, 
        auftraege.eingangsdatum, 
        auftraege.abschlussdatum, 
        auftraege.lieferdatum, 
        statusbeschreibung.beschreibung,
        auftraege.status,
        COALESCE(SUM(auftragsposition.Menge), 0) AS menge,
        CONCAT(auftraege.Lieferadresse_straße, ' ', auftraege.Lieferadresse_Hausnummer, ', ', auftraege.Lieferadresse_postleitzahl) AS adresse,
        auftraege.Kundennummer,
        auftraege.bearbeiternummer
    FROM auftraege 
    LEFT JOIN auftragsposition ON auftraege.auftragsnummer = auftragsposition.auftragsnummer
    LEFT JOIN artikel ON auftragsposition.artikelnummer = artikel.artikelnummer
    LEFT JOIN statusbeschreibung ON auftraege.status = statusbeschreibung.status ";

    if ($userType == "servicepartner") {
        $query_aufträge .= " WHERE auftraege.bearbeiternummer = $userID";
    } elseif ($userType == "lager") {
        $query_aufträge .= " WHERE auftraege.status BETWEEN 10 AND 50";
    }

    $query_aufträge .= "
        GROUP BY auftraege.auftragsnummer, auftraege.eingangsdatum, auftraege.abschlussdatum, auftraege.lieferdatum, statusbeschreibung.beschreibung, auftraege.status, auftraege.Lieferadresse_straße, auftraege.Lieferadresse_Hausnummer, auftraege.Lieferadresse_postleitzahl, auftraege.Kundennummer, auftraege.bearbeiternummer
        ORDER BY auftraege.status ASC, auftraege.eingangsdatum DESC, auftraege.auftragsnummer DESC
    ";
    $query_auftragspositionen = "
    SELECT auftragsposition.position, auftragsposition.teilauftrag, artikel.artikelbezeichnung, 
        ROUND(artikel.Einzelpreis, 2) AS Kaufpreis, 
        auftragsposition.Menge, 
        statusbeschreibung.beschreibung, 
        auftragsposition.status, 
        auftraege.auftragsnummer,
        artikel.artikelnummer,
        COALESCE((SELECT SUM(lagerplatz.Menge) FROM lagerplatz WHERE lagerplatz.Artikelnummer = auftragsposition.artikelnummer), 0) AS Vorrat    FROM auftragsposition 
    LEFT JOIN auftraege ON auftragsposition.auftragsnummer = auftraege.auftragsnummer 
    LEFT JOIN artikel ON auftragsposition.artikelnummer = artikel.artikelnummer
    LEFT JOIN statusbeschreibung ON auftragsposition.status = statusbeschreibung.status";
    
    if ($userType == "servicepartner") {
        $query_auftragspositionen .= " WHERE auftraege.bearbeiternummer = $userID";
    } elseif ($userType == "lager") {
        $query_auftragspositionen .= " WHERE auftraege.status BETWEEN 10 AND 50";
    }

    $auftragsuebersicht_data = $db->getEntityArray($query_aufträge);
    $auftragspositionen_data = $db->getEntityArray($query_auftragspositionen);

    if (!empty($auftragsuebersicht_data)) {
        // Loop through $auftragsuebersicht_data
        foreach ($auftragsuebersicht_data as $index => $data) {
            $contentId = 'hidden-content' . $index;

            // Update order status on page load
            // autoUpdateOrderStatus($data->auftragsnummer);

            echo '<div class="clickable-container" onclick="toggleContent(\'' . $contentId . '\')">';
            echo '<div class="grid-container">';
                echo '<div class="grid-content">';
                    echo '<div class="grid-item">' . ($data->auftragsnummer ?? '') . '</div>';
                    echo '<div class="grid-item">' . ($data->gesamtpreis ?? '') . ' €</div>';
                    echo '<div class="grid-item">' . ($data->menge ?? '') . ' stk</div>';
                    if ($userType == "lager") {
                        echo '<div class="grid-item"></div>';
                    }
                    echo '<div class="grid-item">' . ($data->eingangsdatum ?? '') . '</div>';
                    echo '<div class="grid-item">' . ($data->lieferdatum ?? '') . '</div>';
                    echo '<div class="grid-item">' . ($data->beschreibung ?? '') . '</div>';
                    echo '<div class="grid-item">' . ($data->adresse ?? '') . '</div>';
                    if ($userType == "servicepartner") {
                        echo '<div class="grid-item">' . ($data->Kundennummer ?? '') . '</div>';
                    } else if ($userType == "management") {
                        echo '<div class="grid-item">' . ($data->bearbeiternummer ?? '') . '</div>';
                    }
                    echo '<div class="grid-item">';
                        echo '<form method="POST" action="orders.php">';
                        echo '<input type="hidden" name="orderId" value="' . ($data->auftragsnummer ?? '') . '">';
                        
                        if ($userType == "servicepartner" || $userType == "management") {
                            if (hasOrderItemsWithStatus($data->auftragsnummer, 30)) {
                                echo '<button class="ship" type="submit" name="completeOrder"></button>';
                            }
                        } elseif ($userType == "lager") {
                            if ($data->status == 20 && hasSufficientStockForOrder($data->auftragsnummer)) {
                                echo '<button class="comissioned" type="submit" name="updateOrderStatus" value="30"></button>';
                            } elseif ($data->status == 50 && hasOrderItemsWithStatus($data->auftragsnummer, 50)) {
                                echo '<button class="ship" type="submit" name="updateOrderStatus" value="70"></button>';
                            } elseif (hasOrderItemsWithStatus($data->auftragsnummer, 50)) {
                                echo '<button class="ship" type="submit" name="setTeilauftraegeVersendet"></button>';
                            }
                        }
                        if ($data->status < 100) {
                            echo '<button class="round-cancel" type="submit" name="cancelOrder"></button>';
                        }
                        if ($data->status == 75) {
                            echo '<button class=".installation-done" type="submit" name="completeFullOrder"></button>';
                        }
                        echo '</form>';
                    echo '</div>';//grid-item   
                echo '</div>';//grid-content

            // Loop through $auftragspositionen_data for additional content
                if (!empty($auftragspositionen_data)) {
                    echo '<div class="hidden-content" id="' . $contentId . '" style="display: none;">';
                    foreach ($auftragspositionen_data as $position) {
                        if (isset($position->auftragsnummer) && $position->auftragsnummer == $data->auftragsnummer) {
                            
                            echo '<div class="grid-row">';
                                echo '<div class="grid-item"><a href="product-view.php?product=' . ($position->artikelnummer ?? '') . '">' . ($position->artikelbezeichnung ?? '') . '</a></div>';
                                echo '<div class="grid-item">' . ($position->Kaufpreis ?? '') . ' €</div>';
                                echo '<div class="grid-item">' . ($position->Menge ?? '') . ' stk</div>';
                                if ($userType == "lager") {
                                    echo '<div class="grid-item">' . ($position->Vorrat ?? '') . ' stk</div>';
                                }
                                echo '<div class="grid-item"></div>';
                                echo '<div class="grid-item"></div>';
                                echo '<div class="grid-item">' . ($position->beschreibung ?? '') . '</div>';
                                echo '<div class="grid-item"></div>';
                                if ($userType != "lager"){
                                    echo '<div class="grid-item"></div>';
                                }
                                

                                echo '<div class="grid-item">';
                                    echo '<form method="POST" action="orders.php">';
                                    echo '<input type="hidden" name="orderId" value="' . ($position->auftragsnummer ?? '') . '">';
                                    echo '<input type="hidden" name="orderItemId" value="' . ($position->position ?? '') . '">';
                                    echo '<input type="hidden" name="teilauftrag" value="' . ($position->teilauftrag ?? '') . '">';
                                    
                                    if ($userType == "lager" && $position->status == 20 && hasSufficientStock($position->artikelnummer, $position->Menge)) {
                                        echo '<button class="comissioned" type="submit" name="updateOrderItemStatus" value="30"></button>';
                                    }
                                    if ($position->status < 100) {
                                        echo '<button class="round-cancel" type="submit" name="cancelOrderItem"></button>';
                                    }
                                    
                                    echo '</form>';
                                echo '</div>';
                            echo '</div>'; 
                        
                            
                        }
                        
                    }
                   
                }
                echo '</div>';
                echo '</div>';
     
                echo '</div>';
        }
       
    } else {
        // Handle case when $auftragsuebersicht_data is empty
        echo "No data available.";
    }
    

} else {
    // Handle case when userType is not set in the session
    echo "UserType not found in session.";
}

include 'footer.php';
?>

<script>
function toggleContent(contentId) {
    var content = document.getElementById(contentId);
    if (content) {
        content.style.display = content.style.display === "block" ? "none" : "block";
    }
}
</script>

