<!-- Mobile Menu Toggle -->
<button class="mobile-menu-toggle" id="mobileMenuToggle">
    <i class="fas fa-bars"></i>
</button>

<!-- Bouton flottant pour réafficher la navbar -->
<button class="navbar-show-btn" id="navbarShowBtn">
    <i class="fas fa-bars"></i>
</button>

<!-- Navbar Verticale Student -->
<header id="verticalNavbar">
    <button class="navbar-toggle" id="navbarToggle">
        <i class="fas fa-chevron-left"></i>
    </button>
    <div class="logo">
        <i class="fas fa-graduation-cap"></i> ESEN NET
    </div>
    <nav class="nav-links">
        <?php
        $currentPage = basename($_SERVER['PHP_SELF']);
        $activeClass = 'class="active"';
        ?>
        <a href="home.php" <?php echo $currentPage === 'home.php' ? $activeClass : ''; ?>><i class="fas fa-home"></i> Accueil</a>
        <a href="dashboard.php" <?php echo $currentPage === 'dashboard.php' ? $activeClass : ''; ?>><i class="fas fa-tachometer-alt"></i> Tableau de bord</a>
        <a href="profile.php" <?php echo $currentPage === 'profile.php' ? $activeClass : ''; ?>><i class="fas fa-user"></i> Mon profil</a>
        <a href="offers.php" <?php echo $currentPage === 'offers.php' ? $activeClass : ''; ?>><i class="fas fa-briefcase"></i> Offres</a>
        <a href="events.php" <?php echo $currentPage === 'events.php' ? $activeClass : ''; ?>><i class="fas fa-calendar-alt"></i> Événements</a>
        <a href="network.php" <?php echo $currentPage === 'network.php' ? $activeClass : ''; ?>><i class="fas fa-users"></i> Réseau</a>
        <a href="messages.php" <?php echo $currentPage === 'messages.php' ? $activeClass : ''; ?>><i class="fas fa-pen"></i> Partager</a>
        <a href="settings.php" <?php echo $currentPage === 'settings.php' ? $activeClass : ''; ?>><i class="fas fa-cog"></i> Paramètres</a>
    </nav>
    <div class="navbar-footer">
        <button class="toggle-dark">🌙 Dark</button>
        <a href="../../../login.php" class="logout-link">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
    </div>
</header>
