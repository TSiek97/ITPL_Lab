<?php


function getColumns($table)
{

    global $db;
    $columns = [];
    $result = $db->query("SHOW COLUMNS FROM $table");
    while ($row = mysqli_fetch_assoc($result)) {
        $columns[] = $row['Field'];
    }
    return $columns;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $db;

    if (isset($_POST['update'])) {
        $table = $_POST['table'];
        $id = intval($_POST['update']);
        $columns = getColumns($table);

        $setClause = [];
        foreach ($columns as $column) {
            if ($column !== 'id') {
                $setClause[] = "$column = '" . $db->get_escape_string($_POST[$column][0]) . "'";
            }
        }
        $query = "UPDATE $table SET " . implode(', ', $setClause) . " WHERE id = " . $id;
        $db->query($query);
    }

    if (isset($_POST['add'])) {
        $table = $_POST['table'];
        $columns = getColumns($table);
        
        $columnsValues = [];
        foreach ($columns as $column) {
            if ($column !== 'id') {
                $columnsValues[$column] = $db->get_escape_string($_POST[$column][count($_POST['id'])]);
            }
        }
        $columnsNames = implode(", ", array_keys($columnsValues));
        $columnsData = implode("', '", $columnsValues);
        $query = "INSERT INTO $table ($columnsNames) VALUES ('$columnsData')";
        $db->query($query);
    }

    header("Location: ../data.php?table=$table");
    exit();
}
?>
