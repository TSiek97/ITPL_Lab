<?php
require_once '../db_class.php';
include '../header.php';

$table = $_GET['table'];
$id = intval($_GET['id']);
$query = "DELETE FROM $table WHERE id = $id";

if ($db->query($query)) {
    echo "Record deleted successfully.";
} else {
    echo "Error deleting record.";
}

include '../footer.php';
?>
