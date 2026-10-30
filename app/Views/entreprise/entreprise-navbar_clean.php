<nav class="vertical-navbar" id="verticalNavbar">
    <div class="navbar-header">
        <div class="navbar-logo">
            <i class="fas fa-graduation-cap"></i>
            <span>ESEN NET</span>
        </div>
        <button class="navbar-toggle" id="navbarToggle">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>
    
    <div class="navbar-menu">
        <div class="navbar-section">
            <div class="navbar-title">Menu</div>
            <a href="<?= Template::url('/company/dashboard') ?>" class="navbar-item">
                <i class="fas fa-tachometer-alt"></i>
                <span>Tableau de bord</span>
            </a>
            <a href="<?= Template::url('/company/offers') ?>" class="navbar-item active">
                <i class="fas fa-briefcase"></i>
                <span>Mes offres</span>
            </a>
            <a href="<?= Template::url('/company/candidates') ?>" class="navbar-item">
                <i class="fas fa-users"></i>
                <span>Candidats</span>
            </a>
            <a href="<?= Template::url('/company/events') ?>" class="navbar-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Événements</span>
            </a>
        </div>
        
        <div class="navbar-section">
            <div class="navbar-title">Compte</div>
            <a href="<?= Template::url('/company/profile') ?>" class="navbar-item">
                <i class="fas fa-user"></i>
                <span>Mon profil</span>
            </a>
            <a href="<?= Template::url('/company/settings') ?>" class="navbar-item">
                <i class="fas fa-cog"></i>
                <span>Paramètres</span>
            </a>
            <a href="<?= Template::url('/logout') ?>" class="navbar-item logout-link">
                <i class="fas fa-sign-out-alt"></i>
                <span>Déconnexion</span>
            </a>
        </div>
    </div>
</nav>

<!-- Bouton pour réafficher la navbar quand elle est cachée -->
<button class="navbar-show-btn" id="navbarShowBtn">
    <i class="fas fa-chevron-right"></i>
</button>
