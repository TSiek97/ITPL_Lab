<?php include 'header.php' // Include the header component ?>

<div class="filter-row">
    <!-- Search input and filter button -->
    <input type="text" class="filter-input" id="searchInput" placeholder="Produkt Suchen...">
    <button class="button-style-1" id="filterButton">Produkte Filtern</button>
    <div id="backdrop" class="backdrop"></div>
    <div id="filterCard" class="filter-card">
        <h3>Filter</h3>
        <form id="filterForm" method="GET" action="">
            <h4>Kategorien</h4>
            <?php
            // Fetch distinct categories from the 'artikel' table
            $query_categories = 'SELECT DISTINCT Kategorie FROM artikel;';
            $categories = $db->getEntityArray($query_categories);

            // Get selected filter categories and availability from the GET request
            $filter_categories = isset($_GET['categories']) ? $_GET['categories'] : [];
            $availability = isset($_GET['availability']) ? $_GET['availability'] : '';

            // Display checkboxes for each category
            foreach ($categories as $category) {
                $categoryName_underscores = $category->Kategorie;
                $categoryName = str_replace("_", " ", $categoryName_underscores);
                $checked = in_array($categoryName, $filter_categories) ? 'checked' : '';
                echo '<input type="checkbox" id="' . htmlspecialchars($categoryName) . '" name="categories[]" value="' . htmlspecialchars($categoryName) . '" ' . $checked . '>';
                echo '<label for="' . htmlspecialchars($categoryName) . '">' . htmlspecialchars($categoryName) . '</label><br>';
            }
            ?>
            <h4>Status</h4>
            <!-- Availability checkbox -->
            <input type="checkbox" id="available" name="availability" value="Lagernd" <?php echo $availability === 'Lagernd' ? 'checked' : ''; ?>>
            <label for="available">Lagernd</label><br>

            <button class="button-style-1" type="submit">Filter anwenden</button>
        </form>
    </div>
</div>

<div class="product-card-container" id="productContainer">
    <?php
    // Prepare category filter for SQL query
    $category_filter = !empty($filter_categories) ? "('" . implode("','", array_map(function($category) {
        return str_replace(" ", "_", addslashes($category));
    }, $filter_categories)) . "')" : "";

    // Prepare availability filter for SQL query
    $availability_filter = $availability === 'Lagernd' ? "HAVING Menge > 0" : "";

    // SQL query to fetch products based on selected filters
    if (!empty($category_filter)) {
        $query_products_by_category = "
            SELECT artikel.artikelnummer, artikel.artikelbezeichnung, artikel.Kategorie, artikel.einzelpreis, SUM(lagerplatz.Menge) AS Menge
            FROM artikel
            LEFT JOIN lagerplatz ON artikel.artikelnummer = lagerplatz.artikelnummer
            WHERE artikel.Kategorie IN $category_filter
            GROUP BY artikel.artikelnummer
            $availability_filter
        ";
    } else {
        $query_products_by_category = "
            SELECT artikel.artikelnummer, artikel.artikelbezeichnung, artikel.Kategorie, artikel.einzelpreis, SUM(lagerplatz.Menge) AS Menge
            FROM artikel
            LEFT JOIN lagerplatz ON artikel.artikelnummer = lagerplatz.artikelnummer
            GROUP BY artikel.artikelnummer
            $availability_filter
        ";
    }

    $products_by_category = $db->getEntityArray($query_products_by_category); // Execute the query

    $directory = "product-pictures";
    // Display product cards
    foreach ($products_by_category as $product) {
        $productNummer = $product->artikelnummer;
        $productKategorie = $product->Kategorie;
        $productBezeichnung = $product->artikelbezeichnung;
        $productEinzelpreis = number_format($product->einzelpreis, 2);
        $productLagermenge = $product->Menge > 0 ? $product->Menge . ' Stück verfügbar' : 'Nicht auf Lager';
        $imagePath = $directory . '/' . $productNummer . '.jpg';
        $image = file_exists($imagePath) ? '<img src="' . $imagePath . '" alt="' . $productBezeichnung . '">' : '<img src="' . $directory . '/placeholder.jpg" alt="' . $productBezeichnung . '">';

        echo '<a href="/product-view.php?product=' . $productNummer . '" class="product-card" id="' . $productNummer . '" data-name="' . htmlspecialchars($productBezeichnung) . '" data-category="' . htmlspecialchars($product->Kategorie) . '">';
        echo $image;
        echo '<div class="product-details">';
        echo '<div class="product-name"><h3>' . $productBezeichnung . '</h3></div>';
        echo '<div class="product-price"><p>' . $productEinzelpreis . '€</p></div>';
        echo '<div class="product-availability"><p>' . $productLagermenge . '</p></div>';
        echo '</div></a>';
    }
    ?>
</div>

<script>
// Filter form submission event listener
document.getElementById('filterForm').addEventListener('submit', function(event) {
    event.preventDefault(); // Prevent the default form submission

    let selectedCategories = [];
    document.querySelectorAll('input[name="categories[]"]:checked').forEach(checkbox => {
        selectedCategories.push(encodeURIComponent(checkbox.value));
    });

    let availability = document.querySelector('input[name="availability"]:checked') ? 'Lagernd' : '';

    let queryString = '';
    if (selectedCategories.length) {
        queryString += '?categories[]=' + selectedCategories.join('&categories[]=');
    }
    if (availability) {
        queryString += (queryString ? '&' : '?') + 'availability=' + availability;
    }

    const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + queryString;
    
    // Navigate to the new URL
    window.location.href = newUrl;
});

// Search input event listener
document.getElementById('searchInput').addEventListener('input', function() {
    const searchValue = this.value.trim().toLowerCase(); // Trim whitespace and convert to lowercase
    const products = document.querySelectorAll('.product-card');

    products.forEach(product => {
        const productName = product.getAttribute('data-name').toLowerCase();
        const productCategory = product.getAttribute('data-category').toLowerCase();
        
        if (!searchValue || productName.includes(searchValue) || productCategory.includes(searchValue)) {
            product.style.transition = "opacity 0.5s, height 1s"; // Add transition effect
            product.style.opacity = "1"; // Show the product with full opacity
            product.style.height = "auto"; // Set height to auto to show the product
            product.style.display = ''; // Show the product
        } else {
            product.style.transition = "opacity 0.5s, height 0.5s"; // Add transition effect
            product.style.opacity = "0"; // Hide the product by reducing opacity to 0
            product.style.height = "0"; // Set height to 0 to hide the product
            // Set display to none after the transition completes
            setTimeout(() => {
                product.style.display = 'none';
            }, 500); // 500 milliseconds = 0.5 seconds
        }
    });
});
</script>

<?php include 'footer.php' // Include the footer component ?>
