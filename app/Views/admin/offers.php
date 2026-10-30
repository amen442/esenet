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

// Obtenir toutes les offres
$offers = get_all_offers();

// Statistiques offres
$stats = [
    'total' => count($offers),
    'total_candidatures' => 0,
    'stages' => 0,
    'alternances' => 0
];

foreach ($offers as $offer) {
    $stats['total_candidatures'] += (int)($offer['nb_candidatures'] ?? 0);
    $type = strtolower($offer['type'] ?? '');
    if (strpos($type, 'stage') !== false || strpos($type, 'Stage') !== false) {
        $stats['stages']++;
    } elseif (strpos($type, 'alternance') !== false || strpos($type, 'Alternance') !== false) {
        $stats['alternances']++;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offres - ESEN Connect</title>
    <meta name="description" content="Gérez les offres">
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
            if (verticalNavbar && navbarShowBtn) updateNavbarShowBtn();

            // Custom Cursor
            const cursor = document.querySelector('.custom-cursor');
            const cursorDot = document.querySelector('.custom-cursor-dot');
            let mouseX = 0, mouseY = 0, cursorX = 0, cursorY = 0;
            if (cursor && cursorDot) {
                document.addEventListener('mousemove', (e) => { mouseX = e.clientX; mouseY = e.clientY; });
                function animateCursor() {
                    cursorX += (mouseX - cursorX) * 0.15;
                    cursorY += (mouseY - cursorY) * 0.15;
                    cursor.style.left = cursorX + 'px';
                    cursor.style.top = cursorY + 'px';
                    cursorDot.style.left = mouseX + 'px';
                    cursorDot.style.top = mouseY + 'px';
                    requestAnimationFrame(animateCursor);
                }
                animateCursor();
                document.querySelectorAll('a, button, input, .btn, .stat-card, .dashboard-card, .offer-item, .nav-item, .search-input, .filter-btn').forEach(el => {
                    el.addEventListener('mouseenter', () => cursor.classList.add('hover'));
                    el.addEventListener('mouseleave', () => cursor.classList.remove('hover'));
                });
                document.addEventListener('mousedown', () => cursor.classList.add('clicking'));
                document.addEventListener('mouseup', () => cursor.classList.remove('clicking'));
            }

            // Search & Filter
            const searchInput = document.getElementById('searchOffers');
            const filterBtns = document.querySelectorAll('.filter-btn');
            const offerItems = document.querySelectorAll('.offer-item');

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const query = this.value.toLowerCase();
                    const activeFilter = document.querySelector('.filter-btn.active');
                    const filterType = activeFilter ? activeFilter.dataset.filter : 'all';
                    offerItems.forEach(item => {
                        const title = item.dataset.title.toLowerCase();
                        const company = item.dataset.company.toLowerCase();
                        const type = item.dataset.type.toLowerCase();
                        const matchesSearch = title.includes(query) || company.includes(query);
                        const matchesFilter = filterType === 'all' || type.includes(filterType);
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
                    offerItems.forEach(item => {
                        const title = item.dataset.title.toLowerCase();
                        const company = item.dataset.company.toLowerCase();
                        const type = item.dataset.type.toLowerCase();
                        const matchesSearch = title.includes(query) || company.includes(query);
                        const matchesFilter = filterType === 'all' || type.includes(filterType);
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
                <h1>Offres</h1>
                <p>Gérez les offres de la plateforme</p>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon blue"><i class="fas fa-briefcase"></i></div>
                    <div class="value"><?php echo $stats['total']; ?></div>
                    <div class="label">Total offres</div>
                </div>
                <div class="stat-card">
                    <div class="icon green"><i class="fas fa-file-alt"></i></div>
                    <div class="value"><?php echo $stats['total_candidatures']; ?></div>
                    <div class="label">Candidatures</div>
                </div>
                <div class="stat-card">
                    <div class="icon orange"><i class="fas fa-internship"></i></div>
                    <div class="value"><?php echo $stats['stages']; ?></div>
                    <div class="label">Stages</div>
                </div>
                <div class="stat-card">
                    <div class="icon purple"><i class="fas fa-handshake"></i></div>
                    <div class="value"><?php echo $stats['alternances']; ?></div>
                    <div class="label">Alternances</div>
                </div>
            </div>

            <!-- Search & Filter Bar -->
            <div class="dashboard-card" style="margin-bottom: 2rem;">
                <div class="search-filter-bar">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchOffers" class="search-input" placeholder="Rechercher une offre...">
                    </div>
                    <div class="filter-group">
                        <button class="filter-btn active" data-filter="all">
                            <i class="fas fa-globe"></i> Toutes
                        </button>
                        <button class="filter-btn" data-filter="stage">
                            <i class="fas fa-flask"></i> Stages
                        </button>
                        <button class="filter-btn" data-filter="alternance">
                            <i class="fas fa-handshake"></i> Alternances
                        </button>
                    </div>
                </div>
            </div>

            <!-- Offers List -->
            <div class="dashboard-card">
                <h3>Liste des offres</h3>
                <div class="offers-list">
                    <?php if (!empty($offers)): ?>
                        <?php foreach ($offers as $offer): ?>
                            <div class="offer-item" 
                                 data-title="<?php echo htmlspecialchars($offer['titre'] ?? ''); ?>" 
                                 data-company="<?php echo htmlspecialchars($offer['nomsociete'] ?? ''); ?>" 
                                 data-type="<?php echo htmlspecialchars($offer['type'] ?? ''); ?>">
                                <div class="offer-icon">
                                    <i class="fas fa-briefcase"></i>
                                </div>
                                <div class="offer-info">
                                    <h4><?php echo htmlspecialchars($offer['titre']); ?></h4>
                                    <p><i class="fas fa-building"></i> <?php echo htmlspecialchars($offer['nomsociete']); ?></p>
                                    <p><i class="fas fa-tag"></i> <?php echo htmlspecialchars($offer['type']); ?></p>
                                </div>
                                <div class="offer-candidatures">
                                    <i class="fas fa-users"></i> <?php echo $offer['nb_candidatures'] ?? 0; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: #e2e8f0; padding: 2rem;">Aucune offre trouvée</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../public/assets/js/main.js"></script>
</body>
</html>
