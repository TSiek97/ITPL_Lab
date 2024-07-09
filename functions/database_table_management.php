<?php
/*
require_once __DIR__ . '/../db_class.php'; // Adjusted the path to use an absolute path

// Ensure that the global $db variable is initialized
global $db;
if (!$db || !$db->isConnected()) {
    //echo "<script>alert('Error: Database connection not established.');</script>";
    require_once __DIR__ . '/../db_class.php'; // Adjusted the path to use an absolute path

}

function displayDatabaseTable($tableName) {
    global $db;

    if (!$db || !$db->isConnected()) {
        echo "<script>alert('Error: Database connection not established.');</script>";
        return;
    }

    // Fetch the table columns
    $query_columns = "SHOW COLUMNS FROM $tableName";
    $columns = $db->getEntityArray($query_columns);

    // Fetch the table data
    $query_data = "SELECT * FROM $tableName";
    $data = $db->getEntityArray($query_data);

    // Identify the primary key
    $query_primary = "SHOW KEYS FROM $tableName WHERE Key_name = 'PRIMARY'";
    $primary = $db->getEntity($query_primary);
    
    if (!$primary) {
        echo "<script>alert('Error: No primary key found in table $tableName.');</script>";
        return;
    }

    $primaryKey = $primary->Column_name;

    // Start table
    echo "<form method='POST' action='functions/database_table_management.php'>";
    echo "<table border='1'>";
    
    // Table headers
    echo "<tr>";
    foreach ($columns as $column) {
        echo "<th>{$column->Field}</th>";
    }
    echo "<th>Actions</th>";
    echo "</tr>";

    // Table rows
    foreach ($data as $row) {
        echo "<tr>";
        foreach ($columns as $column) {
            echo "<td><input type='text' name='{$column->Field}[{$row->$primaryKey}]' value='{$row->{$column->Field}}'></td>";
        }
        echo "<td>
                <button type='submit' name='action' value='update:{$row->$primaryKey}'>Update</button>
                <button type='submit' name='action' value='delete:{$row->$primaryKey}'>Delete</button>
              </td>";
        echo "</tr>";
    }

    // Add new row
    echo "<tr>";
    foreach ($columns as $column) {
        echo "<td><input type='text' name='{$column->Field}[new]' value=''></td>";
    }
    echo "<td><button type='submit' name='action' value='add'>Add</button></td>";
    echo "</tr>";

    // End table
    echo "</table>";
    echo "</form>";
}

function validateForeignKey($table, $column, $value) {
    global $db;
    $query = "SELECT COUNT(*) AS count FROM $table WHERE $column = $value";
    $result = $db->getEntity($query);
    return $result->count > 0;
}

function handleTableOperations() {
    global $db;

    if (!$db || !$db->isConnected()) {
        echo "<script>alert('Error: Database connection not established.');</script>";
        return;
    }

    if (isset($_POST['action'])) {
        list($operation, $rowId) = explode(':', $_POST['action']) + [null, null];
        $tableName = 'kunde'; // Replace with the actual table name
        $data = $_POST;

        // Fetch the primary key
        $query_primary = "SHOW KEYS FROM $tableName WHERE Key_name = 'PRIMARY'";
        $primary = $db->getEntity($query_primary);
        
        if (!$primary) {
            echo "<script>alert('Error: No primary key found in table $tableName.');</script>";
            return;
        }

        $primaryKey = $primary->Column_name;

        switch ($operation) {
            case 'update':
                $setClause = [];
                foreach ($data as $column => $values) {
                    if (isset($values[$rowId])) {
                        $value = $db->get_escape_string($values[$rowId]);
                        if ($column == 'foreign_key_column') {
                            if (!validateForeignKey('foreign_table', 'foreign_key_column', $value)) {
                                echo "<script>alert('Invalid foreign key value.');</script>";
                                return;
                            }
                        }
                        $setClause[] = "$column = '$value'";
                    }
                }
                $setClauseString = implode(", ", $setClause);
                $query = "UPDATE $tableName SET $setClauseString WHERE $primaryKey = $rowId";
                $db->query($query);
                break;

            case 'delete':
                $query = "DELETE FROM $tableName WHERE $primaryKey = $rowId";
                $db->query($query);
                break;

            case 'add':
                $columns = [];
                $values = [];
                foreach ($data as $column => $valuesArray) {
                    if (isset($valuesArray['new'])) {
                        $value = $db->get_escape_string($valuesArray['new']);
                        if ($column == 'foreign_key_column' && !validateForeignKey('foreign_table', 'foreign_key_column', $value)) {
                            echo "<script>alert('Invalid foreign key value.');</script>";
                            return;
                        }
                        $columns[] = $column;
                        $values[] = "'$value'";
                    }
                }
                $columnsString = implode(", ", $columns);
                $valuesString = implode(", ", $values);
                $query = "INSERT INTO $tableName ($columnsString) VALUES ($valuesString)";
                $db->query($query);
                break;
        }
    }
}

// Call handleTableOperations to process any pending operations
handleTableOperations();
*/
?>
