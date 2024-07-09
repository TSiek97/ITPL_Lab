<?php
include 'header.php'; // Include the header component
require_once 'functions/cart_functions.php'; // Include cart functions
require_once 'functions/checkout-add-auftrag.php'; // Include checkout add auftrag functions

// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fetch cart items for the logged-in service partner
$servicepartnernummer = $_SESSION['userID'];
$orderItems = get_cart_items($servicepartnernummer);

// Handle form submission for updating quantities and removing items
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_POST['quantity']) && isset($_POST['productNummer']) && isset($_POST['add_to_cart'])) {
        $productNummer = $_POST['productNummer'];
        $quantity = intval($_POST['quantity']);
        add_to_cart($productNummer, $quantity); // Update the cart with the new quantity
        header('Location: checkout.php'); // Refresh to reflect changes
        exit();
    } elseif (isset($_POST['productNummer']) && isset($_POST['remove_from_cart'])) {
        $productNummer = $_POST['productNummer'];
        remove_from_cart($productNummer); // Remove the item from the cart
        header('Location: checkout.php'); // Refresh to reflect changes
        exit();
    }
}

// Handle form submission for completing the order
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_order'])) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $kundennummer = $_POST['kundennummer'];
    $name = $_POST['name'];
    $address = $_POST['address'];
    $zip = $_POST['zip'];

    // Add the new auftrag and its auftragspositionen
    try {
        $auftragsnummer = add_auftrag($kundennummer, $name, $address, $zip, $orderItems);
        // Redirect to a confirmation page or order summary page
        header('Location: orders.php');
        exit();
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Calculate order summary
$subtotal = 0;
foreach ($orderItems as $item) {
    $subtotal += $item->einzelpreis * $item->Menge;
}
$tax = $subtotal * 0.09; // Example tax calculation (9%)
$total = $subtotal + $tax;

$orderSummary = [
    'subtotal' => $subtotal,
    'tax' => $tax,
    'total' => $total
];

$directory = "product-pictures";
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.quantity-input').forEach(function(input) {
        input.addEventListener('change', function() {
            var form = input.closest('form');
            form.submit(); // Submit the form when the quantity is changed
        });
    });
});
</script>

<div class="checkout-container">
    <div id="checkout-container-col-1" class="column">
        <div id="checkout-order-list">
            <!-- Start of dynamic order list -->
            <?php foreach($orderItems as $item): 
                $imagePath = $directory . '/' . $item->Artikelnummer . '.jpg';
                $image = file_exists($imagePath) ? '<img src="' . $imagePath . '" alt="' . $item->artikelbezeichnung . '">' : '<img src="' . $directory . '/placeholder.jpg" alt="' . $item->artikelbezeichnung . '">';
            ?>
                <div class="order-item">
                    <?php echo $image; ?>
                    <h3><?php echo $item->artikelbezeichnung; ?></h3>
                    <p>Preis: €<?php echo number_format($item->einzelpreis, 2); ?></p>
                    <form method="post">
                        <label for="quantity-<?php echo $item->Artikelnummer; ?>">Menge:</label>
                        <input type="number" id="quantity-<?php echo $item->Artikelnummer; ?>" name="quantity" class="quantity-input" value="<?php echo $item->Menge; ?>" min="1">
                        <input type="hidden" name="productNummer" value="<?php echo $item->Artikelnummer; ?>">
                        <input type="hidden" name="add_to_cart" value="1">
                    </form>
                    <form method="post">
                        <input type="hidden" name="productNummer" value="<?php echo $item->Artikelnummer; ?>">
                        <input type="hidden" name="remove_from_cart" value="1">
                        <button type="submit" class="remove-button">Entfernen</button>
                    </form>
                    <p>Gesamt: €<?php echo number_format($item->einzelpreis * $item->Menge, 2); ?></p>
                </div>
            <?php endforeach; ?>
            <!-- End of dynamic order list -->
        </div>
    </div>
    <div id="checkout-container-col-2" class="column">
        <div id="checkout-order-summary">
            <!-- Start of dynamic order summary -->
            <h2>Bestellübersicht</h2>
            <p>Zwischensumme: €<?php echo number_format($orderSummary['subtotal'], 2); ?></p>
            <p>Steuer: €<?php echo number_format($orderSummary['tax'], 2); ?></p>
            <p>Gesamt: €<?php echo number_format($orderSummary['total'], 2); ?></p>
            <!-- End of dynamic order summary -->
            <h2>Adressdetails</h2>
            <form method="post" id="customer-form">
                <div class="address-form-row">
                    <label for="kundennummer">Kundennummer</label>
                    <input type="text" id="kundennummer" name="kundennummer" required>
                </div>
                <div class="address-form-row">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="address-form-row">
                    <label for="address">Adresse</label>
                    <input type="text" id="address" name="address" required>
                </div>
                <div class="address-form-row">
                    <label for="zip">Postleitzahl</label>
                    <input type="text" id="zip" name="zip" required>
                </div>

                <input type="hidden" name="complete_order" value="1">
                <button type="button" id="fill-button" disabled>Füllen</button>
                <button type="submit" id="add-to-cart-button" disabled>Bestellung abschließen</button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const kundennummerInput = document.getElementById('kundennummer');
    const nameInput = document.getElementById('name');
    const addressInput = document.getElementById('address');
    const zipInput = document.getElementById('zip');
    const fillButton = document.getElementById('fill-button');
    const addToCartButton = document.getElementById('add-to-cart-button');

    let timeout = null;
    let customerData = [];

    // Function to validate the form
    function validateForm() {
        const allFieldsFilled = kundennummerInput.value && nameInput.value && addressInput.value && zipInput.value;
        const validCustomer = customerData.length === 1;
        addToCartButton.disabled = !allFieldsFilled || !validCustomer;
        fillButton.disabled = !validCustomer;
    }

    // Function to fill the form with customer data
    function fillForm() {
        if (customerData.length === 1) {
            const customer = customerData[0];
            kundennummerInput.value = customer.Kundennummer;
            nameInput.value = customer.Kundenname;
            addressInput.value = `${customer.Straße} ${customer.Hausnummer}`;
            zipInput.value = customer.Postleitzahl;
            validateForm();
        }
    }

    // Function to fetch customer data from the server
    function fetchCustomers() {
        const kundennummer = kundennummerInput.value;
        const name = nameInput.value;
        const address = addressInput.value;
        const zip = zipInput.value;

        fetch(`functions/get_customers.php?kundennummer=${kundennummer}&name=${name}&address=${address}&zip=${zip}`)
            .then(response => response.json())
            .then(data => {
                customerData = data;
                validateForm();
            })
            .catch(error => console.error('Error fetching customer data:', error));
    }

    // Handle input changes to fetch customer data
    function handleInput() {
        clearTimeout(timeout);
        timeout = setTimeout(fetchCustomers, 300);
    }

    kundennummerInput.addEventListener('input', handleInput);
    nameInput.addEventListener('input', handleInput);
    addressInput.addEventListener('input', handleInput);
    zipInput.addEventListener('input', handleInput);
    fillButton.addEventListener('click', fillForm);
});
</script>

<?php include 'footer.php'; // Include the footer component ?>
