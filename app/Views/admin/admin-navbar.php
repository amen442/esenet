<!-- Mobile Menu Toggle -->
<button class="mobile-menu-toggle" id="mobileMenuToggle">
    <i class="fas fa-bars"></i>
</button>

<!-- Navbar Verticale Admin -->
<header id="verticalNavbar">
    <div class="logo">
        <i class="fas fa-shield-alt"></i> ESEN Connect
    </div>
    <nav class="nav-links">
        <?php
        $currentPage = basename($_SERVER['PHP_SELF']);
        $activeClass = 'class="active"';
        ?>
        <a href="dashboard.php" <?php echo $currentPage === 'dashboard.php' ? $activeClass : ''; ?>><i class="fas fa-tachometer-alt"></i> Tableau de bord</a>
        <a href="users.php" <?php echo $currentPage === 'users.php' ? $activeClass : ''; ?>><i class="fas fa-users"></i> Utilisateurs</a>
        <a href="companies.php" <?php echo $currentPage === 'companies.php' ? $activeClass : ''; ?>><i class="fas fa-building"></i> Entreprises</a>
        <a href="offers.php" <?php echo $currentPage === 'offers.php' ? $activeClass : ''; ?>><i class="fas fa-briefcase"></i> Offres</a>
        <a href="events.php" <?php echo $currentPage === 'events.php' ? $activeClass : ''; ?>><i class="fas fa-calendar-alt"></i> Événements</a>
        <a href="reports.php" <?php echo $currentPage === 'reports.php' ? $activeClass : ''; ?>><i class="fas fa-chart-bar"></i> Rapports</a>
        <a href="settings.php" <?php echo $currentPage === 'settings.php' ? $activeClass : ''; ?>><i class="fas fa-cog"></i> Paramètres</a>
    </nav>
    <div class="navbar-footer">
        <button class="toggle-dark">🌙 Dark</button>
        <a href="../../../login.php" class="logout-link">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
    </div>
</header>
