<?php include 'header.php' // Include the header component ?>

<?php
// Query to select distinct categories from the 'artikel' table
$query = 'SELECT DISTINCT Kategorie FROM artikel;';
$categories = $db->getEntityArray($query); // Execute the query and get the results

// Container for category cards
echo '<div class="category-card-container">';
if (!empty($categories)) {
    // Iterate through each category
    foreach ($categories as $category) {
        $category_name_underscores = $category->Kategorie; // Get category name with underscores
        $category_name = str_replace("_", " ", $category_name_underscores); // Replace underscores with spaces
        
        // Construct the image source and index
        $imageSrc = "product-pictures/" . $category_name_underscores . "-category.jpg";
        $index = $category_name_underscores . "-category";

        // Output HTML for each category
        echo '<div class="category-card" id="' . $index . '">';
        echo '<div class="category-name">';
        echo '<h3>' . $category_name . '</h3>';
        echo '</div>';
        echo '<a href="/products.php?categories[]=' . $category_name . '">';
        echo '<img src="' . $imageSrc . '" alt="' . $category_name . '">';
        echo '</a>';
        echo '</div>';
    }
} else {
    // No categories found
    echo "No categories found.";
}
echo '</div>';
?>

<?php include 'footer.php' // Include the footer component ?>
