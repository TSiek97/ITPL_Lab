<?php
include 'header.php';
require_once 'functions/database_table_management.php';
require_once 'db_class.php';
echo '<div class="table-content">';
displayDatabaseTable('kunde');
echo '</div>';

include 'footer.php';
?>
