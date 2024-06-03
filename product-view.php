<?php include 'header.php'?>

<?php   
if (!isset($_GET['product'])) {
    echo "Product number not found in URL.";
} else {
    $productNummer = $_GET['product'];

    //session_start();
    
    if (isset($_SESSION['userType'])) {
        $userType = $_SESSION['userType'];
        $userID = $_SESSION['userID']; 
    }
    require_once "db_class.php";
    $DBServer   = 'localhost';
    $DBHost     = 'airlimited';
    $DBUser     = 'root';
    $DBPassword = '';

    $db = new DBConnector($DBServer, $DBHost, $DBUser, $DBPassword);
    $db->connect();
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

    if ($artikelnummer == '') {
        $artikelimage = 'product-pictures/placeholder.jpg';
    } else {
        $artikelimage = 'product-pictures/' . $artikelnummer . '.jpg';
    }
}
?>

<div class="product-view-container">
    <div id="product-view-container-col-1" class="column">
        <div class="product-view-container">
            <img class="product-view-img" src="<?php echo $artikelimage; ?>" alt="product-view">
        </div>
        <h2><?php echo $artikelbezeichnung != '' ? $artikelbezeichnung : 'Produktbezeichnung Platzhalter'; ?></h2>
    </div>
    <div id="product-view-container-col-2" class="column">
        <h2>Stückpreis</h2>
        <p><?php echo $einzelpreis != '' ? number_format($einzelpreis, 2) . ' €' : 'Platzhalter €'; ?></p>
        <hr>
        <h2>Vorratsmenge</h2>
        <p><?php echo $Menge != '' ? $Menge . ' stk.' : 'Platzhalter stk.'; ?></p>
        <hr>
        <h2>Artikelbeschreibung</h2>
        <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Ea maiores voluptatibus natus cupiditate aliquam reiciendis numquam, distinctio, incidunt doloremque expedita perspiciatis voluptate iste! Quisquam nihil accusamus illum expedita voluptate molestias.</p>
        <hr>
        <h2>Artikelabmessungen</h2>
        <p><?php echo $laenge != '' && $breite != '' && $hoehe != '' && $gewicht != '' ? $laenge . ' cm x ' . $breite . ' cm x ' . $hoehe . ' cm  Gewicht: ' . $gewicht . ' kg' : 'HxBxL Gewicht'; ?></p>
        <hr>
        <?php
        if (isset($_SESSION['userType']) && $_SESSION['userType'] == 'mitarbeiter') {
            // Display Fertigungsart for 'mitarbeiter'
            echo '<h2>Fertigungsart</h2>';
            echo '<p>' . $fertigungsart . '</p>';
        } else {
            // Display the add-to-cart form for other users
            ?>
            <form action="/add-to-cart" method="post" class="add-to-cart-form">
                <div class="form-row">
                    <div class="col-30">
                        <input type="number" name="quantity" class="form-control" value="1" min="1">
                    </div>
                    <div class="col-70">
                        <button type="submit" class="btn btn-primary add-to-cart-btn">
                            <h2>hinzufügen</h2>
                            <span class="mdi--cart"></span>
                        </button>
                    </div>
                </div>
            </form>
            <?php
        }
        ?>
    </div>
</div>

<?php include 'footer.php'?>
