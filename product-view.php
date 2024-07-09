<?php include 'header.php' // Include the header component ?>

<?php   
if (!isset($_GET['product'])) {
    // Check if the product number is present in the URL
    echo "Product number not found in URL.";
} else {
    $productNummer = $_GET['product'];

    // session_start(); // Commented out as it's already started in header

    if (isset($_SESSION['userType'])) {
        $userType = $_SESSION['userType'];
        $userID = $_SESSION['userID']; 
    }
    // require_once "db_class.php"; // Database connection is already established in the header
    // $DBServer   = 'localhost';
    // $DBHost     = 'airlimited';
    // $DBUser     = 'root';
    // $DBPassword = '';

    // $db = new DBConnector($DBServer, $DBHost, $DBUser, $DBPassword);
    // $db->connect();

    // Query to fetch product details
    $query_product = "
        SELECT artikel.artikelnummer, artikel.artikelbezeichnung, artikel.laenge, artikel.breite, artikel.hoehe, artikel.gewicht, artikel.einzelpreis, artikel.fertigungsart,
               SUM(lagerplatz.Menge) AS Menge
        FROM artikel
        LEFT JOIN lagerplatz ON artikel.artikelnummer = lagerplatz.artikelnummer
        WHERE artikel.artikelnummer = $productNummer       
        GROUP BY artikel.artikelnummer
    ";

    $productInfo = $db->getEntityArray($query_product);

    // Initialize variables with default values
    $artikelnummer = '';
    $artikelbezeichnung = '';
    $laenge = '';
    $breite = '';
    $hoehe = '';
    $gewicht = '';
    $einzelpreis = '';
    $Menge = '';
    $fertigungsart = '';

    // Extract variables from the array of stdClass objects
    foreach ($productInfo as $product) {
        $artikelnummer = $product->artikelnummer;
        $artikelbezeichnung = $product->artikelbezeichnung;
        $fertigungsart = $product->fertigungsart;
        $laenge = $product->laenge;
        $breite = $product->breite;
        $hoehe = $product->hoehe;
        $gewicht = $product->gewicht;
        $einzelpreis = $product->einzelpreis;
        $Menge = $product->Menge;
    }

    // Determine the product image
    if ($artikelnummer == '') {
        $artikelimage = 'product-pictures/placeholder.jpg';
    } else {
        $artikelimage = 'product-pictures/' . $artikelnummer . '.jpg';
    }

    // Query to fetch storage locations and quantities for the product
    $query_lagerplaetze = "
        SELECT lagerplatz.Lagernummer, lagerplatz.Bereich, lagerplatz.Gang, lagerplatz.Regalnummer, lagerplatz.Fachnummer, lagerplatz.Menge
        FROM lagerplatz
        WHERE lagerplatz.artikelnummer = $productNummer
    ";

    $lagerplaetze = $db->getEntityArray($query_lagerplaetze);
}
?>

<div class="product-view-container">
    <div id="product-view-container-col-1" class="column">
        <div class="product-view-container">
            <!-- Product image -->
            <img class="product-view-img" src="<?php echo $artikelimage; ?>" alt="product-view">
        </div>
        <!-- Product name -->
        <h2><?php echo $artikelbezeichnung != '' ? $artikelbezeichnung : 'Produktbezeichnung Platzhalter'; ?></h2>
    </div>
    <div id="product-view-container-col-2" class="column">
        <!-- Product price -->
        <h2>Stückpreis</h2>
        <p><?php echo $einzelpreis != '' ? number_format($einzelpreis, 2) . ' €' : 'Platzhalter €'; ?></p>
        <hr>
        <!-- Product quantity -->
        <h2>Vorratsmenge</h2>
        <p><?php echo $Menge != '' ? $Menge . ' stk.' : 'Platzhalter stk.'; ?></p>
        <hr>
        <!-- Product description -->
        <h2>Artikelbeschreibung</h2>
        <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Ea maiores voluptatibus natus cupiditate aliquam reiciendis numquam, distinctio, incidunt doloremque expedita perspiciatis voluptate iste! Quisquam nihil accusamus illum expedita voluptate molestias.</p>
        <hr>
        <!-- Product dimensions -->
        <h2>Artikelabmessungen</h2>
        <p><?php echo $laenge != '' && $breite != '' && $hoehe != '' && $gewicht != '' ? $laenge . ' cm x ' . $breite . ' cm x ' . $hoehe . ' cm  Gewicht: ' . $gewicht . ' kg' : 'HxBxL Gewicht'; ?></p>
        <hr>
        <?php
        if (isset($_SESSION['userType'])) {
            // Display Fertigungsart for 'fertigung' and 'management'
            if ($_SESSION['userType'] == 'fertigung' || $_SESSION['userType'] == 'management') {
                echo '<h2>Fertigungsart</h2>';
                echo '<p>' . $fertigungsart . '</p>';
            }
            // Display storage locations and quantities for 'management' and 'lager'
            if ($_SESSION['userType'] == 'management' || $_SESSION['userType'] == 'lager') {
                echo '<hr>';
                echo '<h2>Lagerplätze und Mengen</h2>';
                foreach ($lagerplaetze as $lagerplatz) {
                    echo '<p>Lagernummer: ' . $lagerplatz->Lagernummer . ', Bereich: ' . $lagerplatz->Bereich . ', Gang: ' . $lagerplatz->Gang . ', Regalnummer: ' . $lagerplatz->Regalnummer . ', Fachnummer: ' . $lagerplatz->Fachnummer . ', Menge: ' . $lagerplatz->Menge . ' stk.</p>';
                }
            }
            // Display form to create production order for 'management'
            if ($_SESSION['userType'] == 'management') {
                echo '<hr>';
                ?>
                <h2>Fertigungsauftrag erstellen</h2>
                <form action="" method="post" class="production-order-form">
                    <div class="form-row">
                        <div class="col-30">
                            <label for="quantity">Menge:</label>
                            <input type="number" name="quantity" class="form-control" value="1" min="1">
                            <input type="hidden" name="artikelnummer" value="<?php echo $artikelnummer; ?>">
                        </div>
                        <div class="col-30">
                            <label for="priority">Priorität:</label>
                            <select name="priority" class="form-control">
                                <option value="Niedrig">Niedrig</option>
                                <option value="Mittel">Mittel</option>
                                <option value="Hoch">Hoch</option>
                            </select>
                        </div>
                        <div class="col-40">
                            <button type="submit" class="btn btn-primary create-production-order-btn">
                                <h4>Fertigungsauftrag hinzufügen</h4>
                                <!-- <span class="mdi--cart"></span> -->
                            </button>
                        </div>
                    </div>
                </form>
                <?php
                // Handle form submission for creating a production order
                if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['artikelnummer']) && isset($_POST['quantity']) && isset($_POST['priority'])) {
                    require_once 'functions/production-orders_functions.php';
                    $artikelnummer = $_POST['artikelnummer'];
                    $quantity = $_POST['quantity'];
                    $priority = $_POST['priority'];
                    createProductionOrder($artikelnummer, $quantity, $priority);
                }
            }
            // Display Add to Cart form for 'servicepartner'
            if ($_SESSION['userType'] == 'servicepartner') {
                ?>
                <form action="/functions/add-to-cart.php" method="post" class="add-to-cart-form">
                    <div class="form-row">
                        <div class="col-30">
                            <input type="number" name="quantity" class="form-control" value="1" min="1">
                            <input type="hidden" name="artikelnummer" value="<?php echo $artikelnummer; ?>">
                        </div>
                        <div class="col-70">
                            <button type="submit" class="btn btn-primary add-to-cart-btn">
                                <span class="mdi--cart"></span>
                            </button>
                        </div>
                    </div>
                </form>
                <?php
            }
        }
        ?>
    </div>
</div>

<?php include 'footer.php' // Include the footer component ?>
