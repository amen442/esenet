<?php
require_once __DIR__ . '/../layouts/components.php';
require_once __DIR__ . '/../../config/db_connect.php';


// Obtenir l'ID de l'admin connecté
$admin_id = get_admin_id();

// Si aucun admin n'est connecté, rediriger vers la page de connexion
if (!$admin_id) {
    header("Location: ../../login.php");
    exit();
}

// Obtenir toutes les entreprises
$companies = get_all_companies();

// Statistiques entreprises
$stats = [
    'total' => count($companies),
    'total_offres' => 0,
    'avec_offres' => 0,
    'sans_offres' => 0
];

foreach ($companies as $company) {
    $nb = (int)($company['nb_offres'] ?? 0);
    $stats['total_offres'] += $nb;
    if ($nb > 0) {
        $stats['avec_offres']++;
    } else {
        $stats['sans_offres']++;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entreprises - ESEN Connect</title>
    <meta name="description" content="Gérez les entreprises">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../public/assets/css/main.css">
    <link rel="stylesheet" href="../../public/assets/css/admin-navbar.css">
    <link rel="stylesheet" href="../../public/assets/css/admin-dashboard.css">
    <link rel="icon" href="../../public/assets/images/1.jpeg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.querySelector('.toggle-dark');
            const savedTheme = localStorage.getItem('theme') || 'light';
            if (savedTheme === 'dark') {
                document.body.classList.add('dark');
            }
            if (themeToggle) {
                themeToggle.addEventListener('click', function() {
                    document.body.classList.toggle('dark');
                    const isDark = document.body.classList.contains('dark');
                    localStorage.setItem('theme', isDark ? 'dark' : 'light');
                });
            }
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const verticalNavbar = document.getElementById('verticalNavbar');
            if (mobileMenuToggle && verticalNavbar) {
                mobileMenuToggle.addEventListener('click', function() {
                    verticalNavbar.classList.toggle('active');
                });
                document.addEventListener('click', function(e) {
                    if (!verticalNavbar.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
                        verticalNavbar.classList.remove('active');
                    }
                });
            }

            // Toggle navbar avec la flèche
            const navbarToggle = document.getElementById('navbarToggle');
            const navbarShowBtn = document.getElementById('navbarShowBtn');

            if (navbarToggle && verticalNavbar) {
                navbarToggle.addEventListener('click', function() {
                    verticalNavbar.classList.toggle('collapsed');
                    updateNavbarShowBtn();
                });
            }

            if (navbarShowBtn && verticalNavbar) {
                navbarShowBtn.addEventListener('click', function() {
                    verticalNavbar.classList.remove('collapsed');
                    updateNavbarShowBtn();
                });
            }

            function updateNavbarShowBtn() {
                if (verticalNavbar.classList.contains('collapsed')) {
                    navbarShowBtn.classList.add('visible');
                } else {
                    navbarShowBtn.classList.remove('visible');
                }
            }

            if (verticalNavbar && navbarShowBtn) {
                updateNavbarShowBtn();
            }

            // Custom Cursor
            const cursor = document.querySelector('.custom-cursor');
            const cursorDot = document.querySelector('.custom-cursor-dot');
            let mouseX = 0, mouseY = 0;
            let cursorX = 0, cursorY = 0;

            if (cursor && cursorDot) {
                document.addEventListener('mousemove', (e) => {
                    mouseX = e.clientX;
                    mouseY = e.clientY;
                });

                function animateCursor() {
                    const dx = mouseX - cursorX;
                    const dy = mouseY - cursorY;
                    cursorX += dx * 0.15;
                    cursorY += dy * 0.15;

                    cursor.style.left = cursorX + 'px';
                    cursor.style.top = cursorY + 'px';
                    cursorDot.style.left = mouseX + 'px';
                    cursorDot.style.top = mouseY + 'px';

                    requestAnimationFrame(animateCursor);
                }
                animateCursor();

                const interactiveElements = document.querySelectorAll('a, button, input, .btn, .stat-card, .dashboard-card, .company-item, .nav-item, .search-input, .filter-btn');
                interactiveElements.forEach(el => {
                    el.addEventListener('mouseenter', () => cursor.classList.add('hover'));
                    el.addEventListener('mouseleave', () => cursor.classList.remove('hover'));
                });

                document.addEventListener('mousedown', () => cursor.classList.add('clicking'));
                document.addEventListener('mouseup', () => cursor.classList.remove('clicking'));
            }

            // Search & Filter
            const searchInput = document.getElementById('searchCompanies');
            const filterBtns = document.querySelectorAll('.filter-btn');
            const companyItems = document.querySelectorAll('.company-item');

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const query = this.value.toLowerCase();
                    const activeFilter = document.querySelector('.filter-btn.active');
                    const filterType = activeFilter ? activeFilter.dataset.filter : 'all';
                    
                    companyItems.forEach(item => {
                        const name = item.dataset.name.toLowerCase();
                        const email = item.dataset.email.toLowerCase();
                        const hasOffers = item.dataset.hasoffers === '1';
                        const matchesSearch = name.includes(query) || email.includes(query);
                        const matchesFilter = filterType === 'all' || (filterType === 'with' && hasOffers) || (filterType === 'without' && !hasOffers);
                        item.style.display = matchesSearch && matchesFilter ? '' : 'none';
                    });
                });
            }

            filterBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    filterBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    const filterType = this.dataset.filter;
                    const query = searchInput ? searchInput.value.toLowerCase() : '';
                    
                    companyItems.forEach(item => {
                        const name = item.dataset.name.toLowerCase();
                        const email = item.dataset.email.toLowerCase();
                        const hasOffers = item.dataset.hasoffers === '1';
                        const matchesSearch = name.includes(query) || email.includes(query);
                        const matchesFilter = filterType === 'all' || (filterType === 'with' && hasOffers) || (filterType === 'without' && !hasOffers);
                        item.style.display = matchesSearch && matchesFilter ? '' : 'none';
                    });
                });
            });
        });
    </script>
</head>
<body>
    <!-- Custom Cursor -->
    <div class="custom-cursor"></div>
    <div class="custom-cursor-dot"></div>

    <!-- Animated Background Shapes -->
    <div class="bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-4"></div>
    </div>

    <!-- Grid Pattern -->
    <div class="grid-pattern"></div>

    <?php include 'admin-navbar.php'; ?>

    <main class="main-content-wrapper">
        <div class="dashboard-container">
            <div class="dashboard-header">
                <h1>Entreprises</h1>
                <p>Gérez les entreprises de la plateforme</p>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon blue"><i class="fas fa-building"></i></div>
                    <div class="value"><?php echo $stats['total']; ?></div>
                    <div class="label">Total entreprises</div>
                </div>
                <div class="stat-card">
                    <div class="icon green"><i class="fas fa-briefcase"></i></div>
                    <div class="value"><?php echo $stats['total_offres']; ?></div>
                    <div class="label">Offres publiées</div>
                </div>
                <div class="stat-card">
                    <div class="icon orange"><i class="fas fa-check-circle"></i></div>
                    <div class="value"><?php echo $stats['avec_offres']; ?></div>
                    <div class="label">Avec offres</div>
                </div>
                <div class="stat-card">
                    <div class="icon purple"><i class="fas fa-clock"></i></div>
                    <div class="value"><?php echo $stats['sans_offres']; ?></div>
                    <div class="label">Sans offres</div>
                </div>
            </div>

            <!-- Search & Filter Bar -->
            <div class="dashboard-card" style="margin-bottom: 2rem;">
                <div class="search-filter-bar">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchCompanies" class="search-input" placeholder="Rechercher une entreprise...">
                    </div>
                    <div class="filter-group">
                        <button class="filter-btn active" data-filter="all">
                            <i class="fas fa-globe"></i> Toutes
                        </button>
                        <button class="filter-btn" data-filter="with">
                            <i class="fas fa-briefcase"></i> Avec offres
                        </button>
                        <button class="filter-btn" data-filter="without">
                            <i class="fas fa-clock"></i> Sans offres
                        </button>
                    </div>
                </div>
            </div>

            <!-- Companies List -->
            <div class="dashboard-card">
                <h3>Liste des entreprises</h3>
                <div class="companies-list">
                    <?php if (!empty($companies)): ?>
                        <?php foreach ($companies as $company): ?>
                            <div class="company-item" 
                                 data-name="<?php echo htmlspecialchars($company['nomsociete'] ?? ''); ?>" 
                                 data-email="<?php echo htmlspecialchars($company['email'] ?? ''); ?>" 
                                 data-hasoffers="<?php echo ($company['nb_offres'] ?? 0) > 0 ? '1' : '0'; ?>">
                                <div class="company-icon">
                                    <i class="fas fa-building"></i>
                                </div>
                                <div class="company-info">
                                    <h4><?php echo htmlspecialchars($company['nomsociete']); ?></h4>
                                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($company['email'] ?? 'N/A'); ?></p>
                                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($company['telephone'] ?? 'N/A'); ?></p>
                                </div>
                                <div class="company-offers <?php echo ($company['nb_offres'] ?? 0) > 0 ? 'has-offers' : 'no-offers'; ?>">
                                    <i class="fas fa-briefcase"></i> <?php echo $company['nb_offres'] ?? 0; ?> offre(s)
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: #e2e8f0; padding: 2rem;">Aucune entreprise trouvée</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../public/assets/js/main.js"></script>
</body>
</html>
