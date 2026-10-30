<!-- Mobile Menu Toggle -->
<button class="mobile-menu-toggle" id="mobileMenuToggle">
    <i class="fas fa-bars"></i>
</button>

<!-- Bouton flottant pour réafficher la navbar -->
<button class="navbar-show-btn" id="navbarShowBtn">
    <i class="fas fa-bars"></i>
</button>

<!-- Navbar Verticale Teacher -->
<header id="verticalNavbar">
    <button class="navbar-toggle" id="navbarToggle">
        <i class="fas fa-chevron-left"></i>
    </button>
    <div class="logo">
        <i class="fas fa-chalkboard-teacher"></i> ESEN NET
    </div>
    <nav class="nav-links">
        <?php
        $currentPage = basename($_SERVER['PHP_SELF']);
        $activeClass = 'class="active"';
        ?>
        <a href="home.php" <?php echo $currentPage === 'home.php' ? $activeClass : ''; ?>><i class="fas fa-home"></i> Accueil</a>
        <a href="dashboard.php" <?php echo $currentPage === 'dashboard.php' ? $activeClass : ''; ?>><i class="fas fa-tachometer-alt"></i> Tableau de bord</a>
        <a href="events.php" <?php echo $currentPage === 'events.php' ? $activeClass : ''; ?>><i class="fas fa-calendar-alt"></i> Événements</a>
        <a href="recommendations.php" <?php echo $currentPage === 'recommendations.php' ? $activeClass : ''; ?>><i class="fas fa-star"></i> Recommandations</a>
        <a href="settings.php" <?php echo $currentPage === 'settings.php' ? $activeClass : ''; ?>><i class="fas fa-cog"></i> Paramètres</a>
    </nav>
    <div class="navbar-footer">
        <button class="toggle-dark">🌙 Dark</button>
        <a href="../../../login.php" class="logout-link">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
    </div>
</header>
