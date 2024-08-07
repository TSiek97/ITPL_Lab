<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
} // Ensure session is started

// Determine the current page
$current_page = basename($_SERVER['PHP_SELF']);

// Check if userType is set in session
$userType = isset($_SESSION['userType']) ? $_SESSION['userType'] : null;
$userName = isset($_SESSION['userName']) ? $_SESSION['userName'] : '';

// Initialize userdata variable
$userdata = '';

// Set userdata based on userType
if (in_array($userType, ['management', 'lager', 'fertigung'])) {
    $userdata = 'mitarbeiter';
} elseif ($userType === 'servicepartner') {
    $userdata = 'servicepartner';
}
?>

<header>
    <div class="vs-container">
        <div class="hs-flex">
            <div class="logo">
                <!-- Logo image -->
                <img class='logo-header' src="/assets/images/Logo.png" alt="">
            </div>
            <h2>Willkommen zurück <?php echo $userName?>!</h2>
           
            <div class="account-options">
                <!-- Show cart icon only for servicepartner -->
                <?php if ($userType == "servicepartner"): ?>
                    <a href="checkout.php"><span class="mdi--cart"></span></a>
                <?php endif; ?>
                <!-- User and logout icons -->
                <a href="data.php?table=<?php echo $userdata; ?>"><span class="mdi--user"></span></a>
                <a href="logout.php"><span class="mdi--logout"></span></a>
            </div>
        </div>
        <br>
        <nav>
            <ul class="hs-flex main-page-navigation">
                <!-- Navigation links with active class for current page -->
                <li><a href="home.php" class="<?php echo $current_page == 'home.php' ? 'active' : ''; ?>">Startseite</a></li>
                <?php if ($userType == 'management' || $userType == 'servicepartner' || $userType == 'lager'): ?>
                    <li><a href="orders.php" class="<?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">Aufträge</a></li>
                <?php endif; ?>
                <?php if ($userType == 'management' || $userType == 'fertigung'): ?>
                    <li><a href="production-orders.php" class="<?php echo $current_page == 'production-orders.php' ? 'active' : ''; ?>">Fertigungsaufträge</a></li>
                <?php endif; ?>
                <li><a href="products.php" class="<?php echo $current_page == 'products.php' ? 'active' : ''; ?>">Produkte</a></li>
                <li><a href="data.php" class="<?php echo $current_page == 'data.php' ? 'active' : ''; ?>">Daten bearbeiten</a></li>
            </ul>
        </nav>
    </div>
</header>
