<?php
require_once 'db_class.php';

function cancelOrder($orderId) { //set to status 80
    global $db;

    $query_delete_positions = "
        DELETE FROM auftragsposition 
        WHERE auftragsnummer = $orderId
    ";
    $db->query($query_delete_positions);

    $query_delete_order = "
        DELETE FROM auftraege 
        WHERE auftragsnummer = $orderId
    ";
    $db->query($query_delete_order);
    
    echo "Order $orderId has been canceled.";
}

function cancelOrderItem($orderId, $teilauftrag, $orderItemId) {
    global $db;

    $query_delete_item = "
        DELETE FROM auftragsposition 
        WHERE auftragsnummer = $orderId 
        AND teilauftrag = $teilauftrag
        AND position = $orderItemId
    ";
    $db->query($query_delete_item);

    $query_check_items = "
        SELECT COUNT(*) AS remaining_items 
        FROM auftragsposition 
        WHERE auftragsnummer = $orderId
    ";
    $result = $db->getEntity($query_check_items);

    if ($result && $result->remaining_items == 0) {
        $query_delete_order = "
            DELETE FROM auftraege 
            WHERE auftragsnummer = $orderId
        ";
        $db->query($query_delete_order);
    }

    echo "Order item $orderItemId for order $orderId has been canceled.";
}

function completePartialOrder($orderId) {
    global $db;

    // Select all teilaufträge which are 'Kommissioniert' (assuming 'Kommissioniert' has status 30)
    $query_select_kommissioniert = "
        SELECT auftragsnummer, position, teilauftrag
        FROM auftragsposition 
        WHERE auftragsnummer = $orderId AND status = 30
    ";
    $kommissioniert_items = $db->getEntityArray($query_select_kommissioniert);

    if (!empty($kommissioniert_items)) {
        foreach ($kommissioniert_items as $item) {
            $auftragsnummer = $item->auftragsnummer;
            $position = $item->position;
            $teilauftrag = $item->teilauftrag;

            // Update status to 70 for the current teilauftrag
            $query_update_status = "
                UPDATE auftragsposition
                SET status = 70
                WHERE auftragsnummer = $auftragsnummer AND position = $position AND teilauftrag = $teilauftrag
            ";
            $db->query($query_update_status);
        }

        echo "Partial orders for order $orderId have been completed and new partial orders have been created.";
    } else {
        echo "No 'Kommissioniert' items found for order $orderId.";
    }
}


function updateOrderStatus($orderId) {
    global $db;

    // Check if all auftragsposition.status are >= 30
    $query_check_all_status = "
        SELECT COUNT(*) AS total, SUM(CASE WHEN status >= 30 THEN 1 ELSE 0 END) AS valid_count
        FROM auftragsposition
        WHERE auftragsnummer = $orderId
    ";
    $result = $db->getEntity($query_check_all_status);

    if ($result && $result->total == $result->valid_count) {
        // Set the status of all entries < 70 to 70
        $query_update_positions = "
            UPDATE auftragsposition
            SET status = 70
            WHERE auftragsnummer = $orderId AND status < 70
        ";
        $db->query($query_update_positions);
    }

    // Check if all teilauftraege are versendet (status 70) and today is >= lieferdatum
    $query_check_versendet = "
        SELECT COUNT(*) AS total, SUM(CASE WHEN status = 70 THEN 1 ELSE 0 END) AS versendet_count
        FROM auftragsposition
        WHERE auftragsnummer = $orderId
    ";
    $result = $db->getEntity($query_check_versendet);

    if ($result && $result->total == $result->versendet_count) {
  
        $query_get_lieferdatum = "
            SELECT lieferdatum
            FROM auftraege
            WHERE auftragsnummer = $orderId
        ";
        $lieferdatum_result = $db->getEntity($query_get_lieferdatum);

        if ($lieferdatum_result) {
            $lieferdatum = $lieferdatum_result->lieferdatum;
            if (date('Y-m-d') >= $lieferdatum) {
                // Set status to 75
                $query_update_order = "
                    UPDATE auftraege
                    SET status = 75
                    WHERE auftragsnummer = $orderId
                ";
                $db->query($query_update_order);
                $query_update_order_items = "
                    UPDATE auftragsposition
                    SET status = 75
                    WHERE auftragsnummer = $orderId
                    ";
                $db->query($query_update_order_items);
            }
        }
    }

    // Get the minimum status for the auftragsnummer from auftragsposition
    $query_min_status = "
        SELECT MIN(status) AS min_status
        FROM auftragsposition
        WHERE auftragsnummer = $orderId
    ";
    $result = $db->getEntity($query_min_status);

    if ($result) {
        $min_status = $result->min_status;

        // Update the status of the auftraege
        $query_update_order = "UPDATE auftraege SET status = $min_status WHERE auftragsnummer = $orderId";
        $db->query($query_update_order);
    }
}


function completeOrder($orderId) {
    global $db;

    // Set status to 100 for all auftragsposition where auftragsnummer = $orderId
    $query_update_positions = "
        UPDATE auftragsposition
        SET status = 100
        WHERE auftragsnummer = $orderId
    ";
    $db->query($query_update_positions);

    // Set status to 100 for auftraege where auftragsnummer = $orderId
    $query_update_order = "
        UPDATE auftraege
        SET status = 100
        WHERE auftragsnummer = $orderId
    ";
    $db->query($query_update_order);

    echo "Order $orderId has been completed.";
}
?>
