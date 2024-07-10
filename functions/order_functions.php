<?php

require_once 'db_class.php';

/**
 * Check if order items have a specific status.
 *
 * @param int $orderId The order ID.
 * @param int $status The status to check.
 * @return bool True if order items have the specified status, false otherwise.
 */
function hasOrderItemsWithStatus($orderId, $status) {
    global $db;

    $query = "SELECT COUNT(*) AS count FROM auftragsposition WHERE auftragsnummer = $orderId AND status = $status";
    $result = $db->getEntity($query);

    return $result->count > 0;
}

/**
 * Adjust stock for a given artikelnummer.
 *
 * @param int $artikelnummer The product number.
 * @param int $menge The quantity to adjust.
 * @param bool $increase True to increase stock, false to decrease.
 */
function adjustStock($artikelnummer, $menge, $increase = true) {
    global $db;

    $operation = $increase ? '+' : '-';

    $query_check_stock = "
        SELECT Menge 
        FROM lagerplatz 
        WHERE Artikelnummer = $artikelnummer
    ";
    $stock = $db->getEntity($query_check_stock);

    if ($stock) {
        $newMenge = $increase ? $stock->Menge + $menge : $stock->Menge - $menge;
        
        $query_update_stock = "
            UPDATE lagerplatz 
            SET Menge = $newMenge 
            WHERE Artikelnummer = $artikelnummer
        ";
        $db->query($query_update_stock);

        // Check and update teilauftraege status
        updateTeilauftraegeStatus($artikelnummer);
    } else if (!$increase) {
        echo "Error: Stock for Artikelnummer $artikelnummer not found.";
    }
}

/**
 * Update the status of teilauftraege based on stock changes.
 *
 * @param int $artikelnummer The product number.
 */
function updateTeilauftraegeStatus($artikelnummer) {
    global $db;

    // Get all teilauftraege with Artikelnummer and status 10 or 20
    $query_get_teilauftraege = "
        SELECT auftragsnummer, position, teilauftrag, Menge, status
        FROM auftragsposition 
        WHERE Artikelnummer = $artikelnummer AND (status = 10 OR status = 20)
    ";
    $teilauftraege = $db->getEntityArray($query_get_teilauftraege);

    foreach ($teilauftraege as $teilauftrag) {
        $auftragsnummer = $teilauftrag->auftragsnummer;
        $requiredMenge = $teilauftrag->Menge;
        $currentStatus = $teilauftrag->status;

        if (hasSufficientStock($artikelnummer, $requiredMenge)) {
            if ($currentStatus == 10) {
                // Update status to 20
                $query_update_status = "
                    UPDATE auftragsposition
                    SET status = 20
                    WHERE auftragsnummer = $auftragsnummer AND position = {$teilauftrag->position}
                ";
                $db->query($query_update_status);
            }
        } else {
            if ($currentStatus == 20) {
                // Update status to 10
                $query_update_status = "
                    UPDATE auftragsposition
                    SET status = 10
                    WHERE auftragsnummer = $auftragsnummer AND position = {$teilauftrag->position}
                ";
                $db->query($query_update_status);
            }
        }
    }

    // Update order status based on the minimum status of its items
    $query_update_order_status = "
        SELECT auftragsnummer, MIN(status) AS min_status
        FROM auftragsposition
        WHERE Artikelnummer = $artikelnummer
        GROUP BY auftragsnummer
    ";
    $order_statuses = $db->getEntityArray($query_update_order_status);

    foreach ($order_statuses as $order_status) {
        $auftragsnummer = $order_status->auftragsnummer;
        $min_status = $order_status->min_status;

        $query_update_order = "
            UPDATE auftraege
            SET status = $min_status
            WHERE auftragsnummer = $auftragsnummer
        ";
        $db->query($query_update_order);
    }
}

/**
 * Cancel an order and adjust stock.
 *
 * @param int $orderId The order ID to cancel.
 */
function cancelOrder($orderId) {
    global $db;

    // Get the artikelnummer and menge of the order items
    $query_get_items = "SELECT Artikelnummer, Menge FROM auftragsposition WHERE auftragsnummer = $orderId";
    $items = $db->getEntityArray($query_get_items);

    foreach ($items as $item) {
        adjustStock($item->Artikelnummer, $item->Menge, true);
    }

    $query_delete_positions = "DELETE FROM auftragsposition WHERE auftragsnummer = $orderId";
    $db->query($query_delete_positions);

    $query_delete_order = "DELETE FROM auftraege WHERE auftragsnummer = $orderId";
    $db->query($query_delete_order);

    echo "Order $orderId has been canceled.";
}

/**
 * Cancel an order item and adjust stock.
 *
 * @param int $orderId The order ID.
 * @param int $teilauftrag The partial order ID.
 * @param int $orderItemId The order item ID.
 */
function cancelOrderItem($orderId, $teilauftrag, $orderItemId) {
    global $db;

    // Get the artikelnummer and menge of the order item
    $query_get_item = "SELECT Artikelnummer, Menge FROM auftragsposition WHERE auftragsnummer = $orderId AND teilauftrag = $teilauftrag AND position = $orderItemId";
    $item = $db->getEntity($query_get_item);

    if ($item) {
        adjustStock($item->Artikelnummer, $item->Menge, true);
    }

    $query_delete_item = "DELETE FROM auftragsposition WHERE auftragsnummer = $orderId AND teilauftrag = $teilauftrag AND position = $orderItemId";
    $db->query($query_delete_item);

    $query_check_items = "SELECT COUNT(*) AS remaining_items FROM auftragsposition WHERE auftragsnummer = $orderId";
    $result = $db->getEntity($query_check_items);

    if ($result && $result->remaining_items == 0) {
        $query_delete_order = "DELETE FROM auftraege WHERE auftragsnummer = $orderId";
        $db->query($query_delete_order);
    }

    echo "Order item $orderItemId for order $orderId has been canceled.";
}

/**
 * Complete partial orders by reducing stock and updating status.
 *
 * @param int $orderId The order ID.
 */
function allowSendPartialOrder($orderId) {
    global $db;

    // Select all teilaufträge which are 'Kommissioniert' (status 30)
    $query_select_kommissioniert = "SELECT auftragsnummer, position, teilauftrag, Artikelnummer, Menge FROM auftragsposition WHERE auftragsnummer = $orderId AND status = 30";
    $kommissioniert_items = $db->getEntityArray($query_select_kommissioniert);

    if (!empty($kommissioniert_items)) {
        foreach ($kommissioniert_items as $item) {
            $auftragsnummer = $item->auftragsnummer;
            $position = $item->position;
            $teilauftrag = $item->teilauftrag;

            // Reduce stock for kommissioniert items
            adjustStock($item->Artikelnummer, $item->Menge, false);

            // Update status to 50 for the current teilauftrag
            $query_update_status = "UPDATE auftragsposition SET status = 50 WHERE auftragsnummer = $auftragsnummer AND position = $position AND teilauftrag = $teilauftrag";
            $db->query($query_update_status);
        }

        // Auto-update the order status
        autoUpdateOrderStatus($orderId);

        echo "Partial orders for order $orderId have been completed and new partial orders have been created.";
    } else {
        echo "No 'Kommissioniert' items found for order $orderId.";
    }
}

/**
 * Update the status of an order item.
 *
 * @param int $orderId The order ID.
 * @param int $orderItemId The order item ID.
 * @param int $teilauftrag The partial order ID.
 * @param int $newStatus The new status.
 */
function updateOrderItemStatus($orderId, $orderItemId, $teilauftrag, $newStatus) {
    global $db;

    // Get the artikelnummer, menge, and current status of the order item
    $query_get_item = "SELECT Artikelnummer, Menge, status 
                        FROM auftragsposition 
                        WHERE position = $orderItemId 
                        AND auftragsnummer = $orderId 
                        AND teilauftrag = $teilauftrag";
    $item = $db->getEntity($query_get_item);

    if ($item) {
        $currentStatus = $item->status;
        if (($newStatus == 30 || $newStatus == 50) && $currentStatus < 30) {
            // If status is being changed to 30 or 50 and was previously below 30, reduce stock
            adjustStock($item->Artikelnummer, $item->Menge, false);
        }
    }

    // Update the status of the specified order item
    $query_update_item = "
        UPDATE auftragsposition 
        SET status = $newStatus 
        WHERE position = $orderItemId 
        AND auftragsnummer = $orderId 
        AND teilauftrag = $teilauftrag
    ";
    $db->query($query_update_item);

    // Get the auftragsnummer for the updated order item
    $query_get_orderId = "SELECT auftragsnummer 
                        FROM auftragsposition 
                        WHERE position = $orderItemId 
                        AND auftragsnummer = $orderId 
                        AND teilauftrag = $teilauftrag";
    $result = $db->getEntity($query_get_orderId);

    if ($result) {
        $orderId = $result->auftragsnummer;

        // Auto-update the order status
        autoUpdateOrderStatus($orderId);
    }
}

/**
 * Update the status of an order.
 *
 * @param int $orderId The order ID.
 * @param int $newStatus The new status.
 */
function updateOrderStatus($orderId, $newStatus) {
    global $db;

    // Get the artikelnummer, menge, and current status of the order items
    $query_get_items = "SELECT Artikelnummer, Menge, status FROM auftragsposition WHERE auftragsnummer = $orderId";
    $items = $db->getEntityArray($query_get_items);

    foreach ($items as $item) {
        $currentStatus = $item->status;
        if (($newStatus == 30 || $newStatus == 50) && $currentStatus < 30) {
            // If status is being changed to 30 or 50 and was previously below 30, reduce stock
            adjustStock($item->Artikelnummer, $item->Menge, false);
        }
    }

    // Update the status of the specified order
    $query_update_order = "UPDATE auftraege SET status = $newStatus WHERE auftragsnummer = $orderId";
    $db->query($query_update_order);

    // Update all order items to the same status if applicable
    $query_update_items = "UPDATE auftragsposition SET status = $newStatus WHERE auftragsnummer = $orderId";
    $db->query($query_update_items);
}

/**
 * Automatically update the status of an order based on its items.
 *
 * @param int $orderId The order ID.
 */
function autoUpdateOrderStatus($orderId) {
    global $db;

    // Get the minimum status for the auftragsnummer from auftragsposition
    $query_min_status = "SELECT MIN(status) AS min_status FROM auftragsposition WHERE auftragsnummer = $orderId";
    $result = $db->getEntity($query_min_status);

    if ($result) {
        $min_status = $result->min_status;

        // Check if the minimum status is 30 and change it to 50
        if ($min_status == 30) {
            $min_status = 50;

            // Update all teilauftraege with status 30 to 50
            $query_update_teilauftraege = "UPDATE auftragsposition SET status = 50 WHERE auftragsnummer = $orderId AND status = 30";
            $db->query($query_update_teilauftraege);
        }

        // Update the status of the auftraege to the minimum status of its items
        $query_update_order = "UPDATE auftraege SET status = $min_status WHERE auftragsnummer = $orderId";
        $db->query($query_update_order);
    }

    // Check if the status of all teilauftraege is >= 70 and at least one is != 80
    $query_check_statuses = "
        SELECT status
        FROM auftragsposition
        WHERE auftragsnummer = $orderId
    ";
    $statuses = $db->getEntityArray($query_check_statuses);

    $all_ge_70 = true;
    $has_not_80 = false;

    foreach ($statuses as $status) {
        if ($status->status < 70) {
            $all_ge_70 = false;
            break;
        }
        if ($status->status != 80) {
            $has_not_80 = true;
        }
    }

    // Check if lieferdatum is <= today
    $query_check_lieferdatum = "
        SELECT lieferdatum
        FROM auftraege
        WHERE auftragsnummer = $orderId
    ";
    $lieferdatum_result = $db->getEntity($query_check_lieferdatum);
    $lieferdatum = $lieferdatum_result ? $lieferdatum_result->lieferdatum : null;
    $today = date('Y-m-d');

    if ($all_ge_70 && $has_not_80 && $lieferdatum && $lieferdatum <= $today) {
        // Update the status of all teilauftraege that are 70 to 75
        $query_update_teilauftraege_75 = "
            UPDATE auftragsposition
            SET status = 75
            WHERE auftragsnummer = $orderId AND status = 70
        ";
        $db->query($query_update_teilauftraege_75);

        // Update the status of the auftraege to the minimum status of its items again
        $query_min_status_again = "SELECT MIN(status) AS min_status FROM auftragsposition WHERE auftragsnummer = $orderId";
        $result_again = $db->getEntity($query_min_status_again);

        if ($result_again) {
            $min_status = $result_again->min_status;

            $query_update_order_again = "UPDATE auftraege SET status = $min_status WHERE auftragsnummer = $orderId";
            $db->query($query_update_order_again);
        }
    }
}

/**
 * Automatically update the status of all orders based on their items.
 */
function autoUpdateOrderStatusAll() {
    global $db;

    // Get all unique auftragsnummer (order IDs) from auftragsposition
    $query_all_orders = "SELECT DISTINCT auftragsnummer FROM auftragsposition";
    $order_ids = $db->getEntityArray($query_all_orders);

    foreach ($order_ids as $order) {
        $orderId = $order->auftragsnummer;
        autoUpdateOrderStatus($orderId);
    }
}

/**
 * Complete an order by reducing stock and updating status.
 *
 * @param int $orderId The order ID to complete.
 */
function completeFullOrder($orderId) {
    global $db;

    // Get the artikelnummer and menge of the order items
    $query_get_items = "SELECT Artikelnummer, Menge, status FROM auftragsposition WHERE auftragsnummer = $orderId";
    $items = $db->getEntityArray($query_get_items);

    foreach ($items as $item) {
        if ($item->status < 30) {
            // Reduce stock for items that are being set to 100 from a status below 30
            adjustStock($item->Artikelnummer, $item->Menge, false);
        }
    }

    // Set status to 100 for all auftragsposition where auftragsnummer = $orderId
    $query_update_positions = "UPDATE auftragsposition SET status = 100 WHERE auftragsnummer = $orderId";
    $db->query($query_update_positions);

    // Set status to 100 for auftraege where auftragsnummer = $orderId
    $query_update_order = "UPDATE auftraege SET status = 100 WHERE auftragsnummer = $orderId";
    $db->query($query_update_order);
}

/**
 * Check if there is sufficient stock for a specific artikelnummer.
 *
 * @param int $artikelnummer The product number.
 * @param int $requiredMenge The required quantity.
 * @return bool True if there is sufficient stock, false otherwise.
 */
function hasSufficientStock($artikelnummer, $requiredMenge) {
    global $db;

    $query_check_stock = "SELECT Menge FROM lagerplatz WHERE Artikelnummer = $artikelnummer";
    $stock = $db->getEntity($query_check_stock);

    return $stock && $stock->Menge >= $requiredMenge;
}

/**
 * Set the status of teilauftraege to "Versendet".
 *
 * @param int $orderId The order ID.
 */
function setTeilauftraegeVersendet($orderId) {
    global $db;

    // Update all teilauftraege with status 30 or 50 to 70
    $query_update_teilauftraege = "UPDATE auftragsposition SET status = 70 WHERE auftragsnummer = $orderId AND (status = 30 OR status = 50)";
    $db->query($query_update_teilauftraege);

    // Update the lieferdatum to today + 2 business days
    $lieferdatum = date('Y-m-d', strtotime('+2 weekdays'));
    $query_update_lieferdatum = "UPDATE auftraege SET lieferdatum = '$lieferdatum' WHERE auftragsnummer = $orderId";
    $db->query($query_update_lieferdatum);

    // Auto-update the order status
    autoUpdateOrderStatus($orderId);
}

/**
 * Check if there is sufficient stock for all items in an order.
 *
 * @param int $orderId The order ID.
 * @return bool True if there is sufficient stock for all items, false otherwise.
 */
function hasSufficientStockForOrder($orderId) {
    global $db;

    $query_get_items = "SELECT artikelnummer, Menge FROM auftragsposition WHERE auftragsnummer = $orderId";
    $items = $db->getEntityArray($query_get_items);

    foreach ($items as $item) {
        if (!hasSufficientStock($item->artikelnummer, $item->Menge)) {
            return false;
        }
    }

    return true;
}

?>
