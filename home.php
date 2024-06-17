<?php include 'header.php'?>



<?php

$query = 'SELECT DISTINCT Kategorie FROM artikel;';
$categories = $db->getEntityArray($query);

echo '<div class="category-card-container">';
if (!empty($categories)) {
    foreach ($categories as $category) {
        $category_name_underscores = $category->Kategorie;
        $category_name = str_replace("_", " ", $category_name_underscores);
        
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
        echo "No categories found.";
    }
echo '</div>';
?>




<?php include 'footer.php'?>
