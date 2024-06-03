<?php
session_start(); // Ensure session is started

// Determine the current page
$current_page = basename($_SERVER['PHP_SELF']);

// Check if userType is set in session
$userType = isset($_SESSION['userType']) ? $_SESSION['userType'] : null;
?>

<header>
    <div class="vs-container">
        <div class="hs-flex">
            <div class="logo">
                <img class='logo-header' src="/assets/images/Logo.png" alt="">
            </div>
            <div class="account-options">
                <?php if ($userType != "mitarbeiter"): ?>
                    <a href="cart.php"><span class="mdi--cart"></span></a>
                <?php endif; ?>
                <a href="#"><span class="mdi--user"></span></a>
                <a href="logout.php"><span class="mdi--logout"></span></a>
            </div>
        </div>
        <br>
        <nav>
            <ul class="hs-flex main-page-navigation">
                <li><a href="home.php" class="<?php echo $current_page == 'home.php' ? 'active' : ''; ?>">Startseite</a></li>
                <li><a href="orders.php" class="<?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">Aufträge</a></li>
                <li><a href="products.php" class="<?php echo $current_page == 'products.php' ? 'active' : ''; ?>">Produkte</a></li>
            </ul>
        </nav>
    </div>
</header>
