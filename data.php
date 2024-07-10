<?php
include 'header.php'; // Include the header component
require_once 'db_class.php'; // Include the database connection class
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Get the user type from the session if set
$userType = isset($_SESSION['userType']) ? $_SESSION['userType'] : null;

// Function to get table definitions based on user type
function getTableDefinitions($userType) {
    $tableDefinitions = [
        'kunde' => [
            'primaryKeys' => ['Kundennummer'],
            'hiddenColumns' => ['Passwort_hash'],
            'visible' => 'all',
            'deletable' => true,
            'columns' => [
                'Kundennummer' => ['type' => 'int', 'editable' => false, 'visible' => 'all'],
                'Kundenname' => ['type' => 'string', 'editable' => true, 'visible' => 'all'],
                'Straße' => ['type' => 'string', 'editable' => true, 'visible' => 'all'],
                'Hausnummer' => ['type' => 'string', 'editable' => true, 'visible' => 'all'],
                'Postleitzahl' => ['type' => 'string', 'editable' => true, 'visible' => 'all', 'values' => getDistinctValues('ort', 'Postleitzahl')],
                'E-Mailadresse' => ['type' => 'string', 'editable' => true, 'visible' => 'all'],
                'Telefonnummer' => ['type' => 'string', 'editable' => true, 'visible' => 'all'],
                'VIP' => ['type' => 'enum', 'editable' => true, 'visible' => 'all', 'values' => ['Ja', 'Nein']],
                'Passwort_hash' => ['type' => 'string', 'editable' => false, 'visible' => 'self'],
            ]
        ],
        'lagerplatz' => [
            'primaryKeys' => ['Lagernummer', 'Bereich', 'Gang', 'Regalnummer', 'Fachnummer'],
            'hiddenColumns' => [],
            'visible' => 'all',
            'deletable' => true,
            'columns' => [
                'Lagernummer' => ['type' => 'int', 'editable' => true, 'visible' => 'all', 'values' => getDistinctValues('lager', 'Lagernummer')],
                'Bereich' => ['type' => 'string', 'editable' => true, 'visible' => 'all'],
                'Gang' => ['type' => 'string', 'editable' => true, 'visible' => 'all'],
                'Regalnummer' => ['type' => 'string', 'editable' => true, 'visible' => 'all'],
                'Fachnummer' => ['type' => 'string', 'editable' => true, 'visible' => 'all'],
                'Artikelnummer' => ['type' => 'int', 'editable' => true, 'visible' => 'all'],
                'Menge' => ['type' => 'int', 'editable' => true, 'visible' => 'all'],
                // 'Menge res' => ['type' => 'int', 'editable' => true, 'visible' => 'all'], // currently not used!
            ]
        ],
        'lager' => [
            'primaryKeys' => ['Lagernummer'],
            'hiddenColumns' => [],
            'visible' => 'all',
            'deletable' => true,
            'columns' => [
                'Lagernummer' => ['type' => 'int', 'editable' => false, 'visible' => 'all'],
                'Lagerbezeichnung' => ['type' => 'string', 'editable' => true, 'visible' => 'all'],
                'Straße' => ['type' => 'string', 'editable' => true, 'visible' => 'all'],
                'Hausnummer' => ['type' => 'string', 'editable' => true, 'visible' => 'all'],
                'Postleitzahl' => ['type' => 'string', 'editable' => true, 'visible' => 'all'],
                'Lagerauslastung' => ['type' => 'decimal', 'editable' => true, 'visible' => 'all'],
            ]
        ],
        'mitarbeiter' => [
            'primaryKeys' => ['Mitarbeiternummer'],
            'hiddenColumns' => ['Passwort_hash'],
            'visible' => 'all',
            'deletable' => true,
            'columns' => [
                'Mitarbeiternummer' => ['type' => 'int', 'editable' => false, 'visible' => 'all'],
                'Vorname' => ['type' => 'string', 'editable' => true, 'visible' => 'all'],
                'Nachname' => ['type' => 'string', 'editable' => true, 'visible' => 'all'],
                'Lagernummer' => ['type' => 'int', 'editable' => true, 'visible' => 'all', 'values' => getDistinctValues('lager', 'Lagernummer')],
                'Rechte' => ['type' => 'string', 'editable' => true, 'visible' => 'all', 'values' => ['management', 'fertigung', 'lager']],
                'Kennung' => ['type' => 'string', 'editable' => true, 'visible' => 'all'],
                'Passwort_hash' => ['type' => 'string', 'editable' => false, 'visible' => 'self'],
            ]
        ],
        'servicepartner' => [
            'primaryKeys' => ['Bearbeiternummer'],
            'hiddenColumns' => ['Passwort_hash'],
            'visible' => 'all',
            'deletable' => true,
            'columns' => [
                'Bearbeiternummer' => ['type' => 'int', 'editable' => false, 'visible' => 'self'],
                'Firmenname' => ['type' => 'string', 'editable' => true, 'visible' => 'self'],
                'Straße' => ['type' => 'string', 'editable' => true, 'visible' => 'self'],
                'Hausnummer' => ['type' => 'string', 'editable' => true, 'visible' => 'self'],
                'Postleitzahl' => ['type' => 'string', 'editable' => true, 'visible' => 'self', 'values' => getDistinctValues('ort', 'Postleitzahl')],
                'E-Mailadresse' => ['type' => 'string', 'editable' => true, 'visible' => 'self'],
                'Telefonnummer' => ['type' => 'string', 'editable' => true, 'visible' => 'self'],
                'Passwort_hash' => ['type' => 'string', 'editable' => false, 'visible' => 'self'],
            ]
        ],
        'ort' => [
            'primaryKeys' => ['Postleitzahl', 'Laenderkennung'],
            'hiddenColumns' => [],
            'visible' => 'all',
            'deletable' => true,
            'columns' => [
                'Postleitzahl' => ['type' => 'string', 'editable' => true, 'visible' => 'all'],
                'Laenderkennung' => ['type' => 'string', 'editable' => true, 'visible' => 'all'],
                'Ortsname' => ['type' => 'string', 'editable' => true, 'visible' => 'all'],
            ]
        ]
    ];

    switch ($userType) {
        case 'management':
            // Management can see all as is
            return $tableDefinitions;
        case 'lager':
        case 'fertigung':
            // Lager and Fertigung can see lagerplatz, lager but can't edit anything
            // Mitarbeiten table is visible for self
            $tables = array_intersect_key($tableDefinitions, array_flip(['lagerplatz', 'lager', 'mitarbeiter']));
            $tables['mitarbeiter']['visible'] = 'self';
            foreach ($tables as &$table) {
                $table['deletable'] = false;
                foreach ($table['columns'] as &$column) {
                    $column['editable'] = false;
                }
            }
            return $tables;
        case 'servicepartner':
            // Servicepartner can see servicepartner, kunde, ort and cannot edit kunde: VIP
            $tables = array_intersect_key($tableDefinitions, array_flip(['servicepartner', 'kunde', 'ort']));
            $tables['servicepartner']['visible'] = 'self';
            if (isset($tables['kunde']['columns']['VIP'])) {
                $tables['kunde']['columns']['VIP']['editable'] = false;
            }
            foreach ($tables as &$table) {
                $table['deletable'] = false;
            }
            return $tables;
        default:
            // Default to an empty array if no user type matches
            return [];
    }
}

// Function to get distinct values for dropdowns
function getDistinctValues($table, $column) {
    global $db;
    $values = [];
    $result = $db->query("SELECT DISTINCT `$column` FROM `$table`");
    while ($row = mysqli_fetch_assoc($result)) {
        $values[] = $row[$column];
    }
    return $values;
}

// Get table definitions based on user type
$tableDefinitions = getTableDefinitions($userType);

// Determine the current table and allowed filters
$current_page = basename($_SERVER['PHP_SELF']);
$allowedTables = array_keys($tableDefinitions);
$table = isset($_GET['table']) && in_array($_GET['table'], $allowedTables) ? $_GET['table'] : (empty($allowedTables) ? '' : $allowedTables[0]);
$primaryKeys = isset($tableDefinitions[$table]['primaryKeys']) ? $tableDefinitions[$table]['primaryKeys'] : [];
$filters = [];
foreach ($_GET as $key => $value) {
    if ($key != 'table' && !empty($value)) {
        $filters[$key] = $value;
    }
}

// Function to build the filter query
function buildFilterQuery($filters) {
    global $db;
    $filterQuery = '';
    foreach ($filters as $column => $value) {
        $filterQuery .= " AND `$column` LIKE '%" . $db->get_escape_string($value) . "%'";
    }
    return $filterQuery;
}

// Function to get the columns of the current table
function getColumns($table) {
    global $tableDefinitions;
    return array_keys($tableDefinitions[$table]['columns']);
}

// Get the columns and filter query
$columns = getColumns($table);
$filterQuery = buildFilterQuery($filters);

// Fetch the rows of the current table based on filters
$query = "SELECT * FROM `$table` WHERE 1=1 $filterQuery";
$rows = $db->getEntityArray($query);

// Handle form submissions for updating, adding, or deleting rows
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $db;
    $columns = getColumns($table);

    $table = isset($_POST['table']) ? $_POST['table'] : (isset($_GET['table']) ? $_GET['table'] : 'kunde');
    $primaryKeys = isset($tableDefinitions[$table]['primaryKeys']) ? $tableDefinitions[$table]['primaryKeys'] : [];

    if (isset($_POST['update'])) {
        $rowIndex = intval($_POST['update']);
        $setClause = [];
        $whereClause = [];

        foreach ($columns as $column) {
            if ($tableDefinitions[$table]['columns'][$column]['editable']) {
                $setClause[] = "`$column` = '" . $db->get_escape_string($_POST[$column][$rowIndex]) . "'";
            }
        }
        foreach ($primaryKeys as $primaryKey) {
            $whereClause[] = "`$primaryKey` = '" . $db->get_escape_string($_POST['primaryKey'][$primaryKey][$rowIndex]) . "'";
        }

        $query = "UPDATE `$table` SET " . implode(', ', $setClause) . " WHERE " . implode(' AND ', $whereClause);
        $_SESSION['last_query'] = $query;
        $db->query($query);
    }

    if (isset($_POST['add'])) {
        $columnsValues = [];
        $primaryKeys = $tableDefinitions[$table]['primaryKeys'];

        // Get the index of the last row (which is the row for adding new entries)
        $firstEditableColumn = null;
        foreach ($columns as $column) {
            if ($tableDefinitions[$table]['columns'][$column]['editable']) {
                $firstEditableColumn = $column;
                break;
            }
        }

        if ($firstEditableColumn === null || !isset($_POST[$firstEditableColumn]) || !is_array($_POST[$firstEditableColumn])) {
            die("Invalid form submission. Please try again.");
        }

        $primaryKeyIndex = count($_POST[$firstEditableColumn]) - 1;

        foreach ($columns as $column) {
            if ($tableDefinitions[$table]['columns'][$column]['editable']) {
                // Ensure the column field exists and is an array
                if (isset($_POST[$column]) && is_array($_POST[$column])) {
                    $columnsValues[$column] = $_POST[$column][$primaryKeyIndex];
                } else {
                    $columnsValues[$column] = '';
                }
            }
        }

        // Validate foreign key dependencies
        if ($table === 'mitarbeiter' && !in_array($columnsValues['Lagernummer'], getDistinctValues('lager', 'Lagernummer'))) {
            die("Invalid Lagernummer. Please select a valid Lagernummer.");
        }

        // Prepare columns and data for the query
        $columnsNames = implode(", ", array_map(function($col) { return "`$col`"; }, array_keys($columnsValues)));
        $columnsData = implode("', '", array_map(function($val) use ($db) { return $db->get_escape_string($val); }, $columnsValues));
        $query = "INSERT INTO `$table` ($columnsNames) VALUES ('$columnsData')";

        // Execute the query
        $db->query($query);
    }

    if (isset($_POST['delete'])) {
        $rowIndex = intval($_POST['delete']);
        $whereClause = [];
        foreach ($primaryKeys as $primaryKey) {
            $whereClause[] = "`$primaryKey` = '" . $db->get_escape_string($_POST['primaryKey'][$primaryKey][$rowIndex]) . "'";
        }
        $query = "DELETE FROM `$table` WHERE " . implode(' AND ', $whereClause);
        $db->query($query);
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?table=$table");
    exit();
}

if (isset($_SESSION['last_query'])): ?>
    <script>
        console.log(<?php echo json_encode($_SESSION['last_query']); ?>);
    </script>
    <?php unset($_SESSION['last_query']); // Clear the query after logging ?>
<?php endif; 
?>

<!-- Navigation for selecting the table -->
<ul class="hs-flex main-page-navigation">
    <?php foreach ($allowedTables as $allowedTable): ?>
        <li>
            <a href="?table=<?= $allowedTable ?>" 
               class="<?php echo (isset($_GET['table']) && $_GET['table'] == $allowedTable) || (!isset($_GET['table']) && $allowedTable == 'kunde') ? 'active' : ''; ?>">
                <?= $tableDefinitions[$allowedTable]['visible'] == 'self' ? 'Meine Daten' : ucfirst($allowedTable) ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<!-- Form for managing the table data -->
<form method="POST" action="">
    <input type="hidden" name="table" value="<?= $table ?>">
    <table class="data-management-table">
        <thead>
            <tr>
                <?php foreach ($columns as $column): ?>
                    <?php if (!in_array($column, $tableDefinitions[$table]['hiddenColumns'])): ?>
                        <th><?= ucfirst($column) ?></th>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php
                $allColumnsEditable = true;
                $atLeastOneColumnEditable = false;
                foreach ($columns as $column) {
                    if (!in_array($column, $tableDefinitions[$table]['hiddenColumns'])) {
                        if ($tableDefinitions[$table]['columns'][$column]['editable']) {
                            $atLeastOneColumnEditable = true;
                        } else {
                            $allColumnsEditable = false;
                        }
                    }
                }
                ?>
                <?php if ($atLeastOneColumnEditable || $allColumnsEditable): ?>
                    <th>Aktionen</th>
                <?php endif; ?>
            </tr>
            <?php if ($tableDefinitions[$table]['visible'] !== 'self'): ?>
                <tr class="data-management-table-filter-row">
                    <?php foreach ($columns as $column): ?>
                        <?php if (!in_array($column, $tableDefinitions[$table]['hiddenColumns'])): ?>
                            <th>
                                <input type="text" name="<?= $column ?>" value="<?= isset($filters[$column]) ? $filters[$column] : '' ?>" class="table-filter-input">
                            </th>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
            <?php endif; ?>
        </thead>
        <tbody>
            <?php if (!empty($rows)): ?>
                <?php foreach ($rows as $rowIndex => $row): ?>
                    <?php if ($tableDefinitions[$table]['visible'] !== 'self' || (isset($_SESSION['userID']) && $row->{$primaryKeys[0]} == $_SESSION['userID'])): ?>
                        <tr>
                            <?php foreach ($columns as $column): ?>
                                <?php if (!in_array($column, $tableDefinitions[$table]['hiddenColumns'])): ?>
                                    <td>
                                        <?php if (isset($tableDefinitions[$table]['columns'][$column]['values']) && is_array($tableDefinitions[$table]['columns'][$column]['values'])): ?>
                                            <?php if ($tableDefinitions[$table]['columns'][$column]['editable']): ?>
                                                <select name="<?= $column ?>[]">
                                                    <?php foreach ($tableDefinitions[$table]['columns'][$column]['values'] as $value): ?>
                                                        <option value="<?= $value ?>" <?= $row->$column == $value ? 'selected' : '' ?>><?= $value ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php else: ?>
                                                <?= htmlspecialchars($row->$column) ?>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?php if ($tableDefinitions[$table]['columns'][$column]['editable']): ?>
                                                <input type="text" name="<?= $column ?>[]" value="<?= htmlspecialchars($row->$column) ?>">
                                            <?php else: ?>
                                                <?= htmlspecialchars($row->$column) ?>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php if ($atLeastOneColumnEditable): ?>
                                <td>
                                    <?php if (!empty($primaryKeys) && $atLeastOneColumnEditable): ?>
                                        <?php foreach ($primaryKeys as $primaryKey): ?>
                                            <input type="hidden" name="primaryKey[<?= $primaryKey ?>][<?= $rowIndex ?>]" value="<?= htmlspecialchars($row->$primaryKey) ?>">
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <?php if ($atLeastOneColumnEditable): ?>
                                        <button type="submit" name="update" value="<?= $rowIndex ?>">Aktualisieren</button>
                                    <?php endif; ?>  
                                    <?php if ($tableDefinitions[$table]['deletable'] && $atLeastOneColumnEditable): ?>
                                        <button type="submit" name="delete" value="<?= $rowIndex ?>" onclick="return confirm('Are you sure?')">Löschen</button>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?= count($columns) + 1 ?>">No data found</td>
                </tr>
            <?php endif; ?>
            <?php if ($tableDefinitions[$table]['visible'] !== 'self' && $atLeastOneColumnEditable): ?>
                <tr>
                    <?php foreach ($columns as $column): ?>
                        <?php if (!in_array($column, $tableDefinitions[$table]['hiddenColumns'])): ?>
                            <td>
                                <?php if ($tableDefinitions[$table]['columns'][$column]['editable']): ?>
                                    <?php if (isset($tableDefinitions[$table]['columns'][$column]['values']) && is_array($tableDefinitions[$table]['columns'][$column]['values'])): ?>
                                        <select name="<?= $column ?>[]">
                                            <?php foreach ($tableDefinitions[$table]['columns'][$column]['values'] as $value): ?>
                                                <option value="<?= $value ?>"><?= $value ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <input type="text" name="<?= $column ?>[]">
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <td>
                        <button type="submit" name="add">Hinzufügen</button>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</form>

<?php
include 'footer.php'; // Include the footer component
?>

<script>
// Script to handle filter input changes
document.addEventListener('DOMContentLoaded', function() {
    const filterInputs = document.querySelectorAll('.table-filter-input');
    filterInputs.forEach(input => {
        input.addEventListener('input', function() {
            const params = new URLSearchParams(window.location.search);
            params.set(this.name, this.value);
            window.location.search = params.toString();
        });
    });
});
</script>
