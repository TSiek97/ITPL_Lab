<?php
require_once '../db_class.php';
include '../header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $table = $_POST['table'];
    $id = $_POST['id'];
    $columns = getColumns($table);

    $setClause = [];
    foreach ($columns as $column) {
        if ($column !== 'id') {
            $setClause[] = "$column = '" . $db->get_escape_string($_POST[$column]) . "'";
        }
    }
    $query = "UPDATE $table SET " . implode(', ', $setClause) . " WHERE id = " . intval($id);
    if ($db->query($query)) {
        echo "Record updated successfully.";
    } else {
        echo "Error updating record.";
    }
} else {
    $table = $_GET['table'];
    $id = $_GET['id'];
    $columns = getColumns($table);
    $query = "SELECT * FROM $table WHERE id = " . intval($id);
    $row = $db->getEntity($query);
}
?>

<form method="POST" action="edit.php">
    <input type="hidden" name="table" value="<?= $table ?>">
    <input type="hidden" name="id" value="<?= $id ?>">
    <?php foreach ($columns as $column): ?>
        <label><?= ucfirst($column) ?>:
            <input type="<?= $column == 'id' ? 'number' : 'text' ?>" name="<?= $column ?>" value="<?= htmlspecialchars($row->$column) ?>" <?= $column == 'id' ? 'readonly' : '' ?>>
        </label>
    <?php endforeach; ?>
    <button type="submit">Update</button>
</form>

<?php
include '../footer.php';
?>
