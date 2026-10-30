<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../Models/EntrepriseFunctions.php';
require_once __DIR__ . '/../layouts/components.php';


// Obtenir l'ID de l'entreprise connectée
$entreprise_id = get_entreprise_id();

// Si aucune entreprise n'est connectée, rediriger vers la page de connexion
if (!$entreprise_id) {
    header("Location: ../login.php");
    exit();
}

// Obtenir les participants aux événements de l'entreprise
$participants = get_event_participants($entreprise_id);

// Obtenir les informations de l'entreprise
$entreprise_info = get_entreprise_info($entreprise_id);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participations - ESEN NET</title>
    <meta name="description" content="Gérez les participants à vos événements">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../public/assets/css/main.css">
    <link rel="stylesheet" href="../../public/assets/css/entreprise-navbar.css">
    <link rel="stylesheet" href="../../public/assets/css/company-dashboard.css">
    <link rel="stylesheet" href="../../public/assets/css/entreprise-participations.css">
    <link rel="icon" href="../../public/assets/images/WhatsApp Image 2026-04-30 at 19.20.19.jpeg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.querySelector('.toggle-dark');
            const savedTheme = localStorage.getItem('theme') || 'light';
            if (savedTheme === 'dark') {
                document.body.classList.add('dark');
            }
            themeToggle.addEventListener('click', function() {
                document.body.classList.toggle('dark');
                const isDark = document.body.classList.contains('dark');
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
            });
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

            // Bouton pour réafficher la navbar
            if (navbarShowBtn && verticalNavbar) {
                navbarShowBtn.addEventListener('click', function() {
                    verticalNavbar.classList.remove('collapsed');
                    updateNavbarShowBtn();
                });
            }

            // Fonction pour mettre à jour l'affichage du bouton
            function updateNavbarShowBtn() {
                if (verticalNavbar.classList.contains('collapsed')) {
                    navbarShowBtn.classList.add('visible');
                } else {
                    navbarShowBtn.classList.remove('visible');
                }
            }

            // Initialisation de l'état du bouton
            if (verticalNavbar && navbarShowBtn) {
                updateNavbarShowBtn();
            }

            // Custom Cursor Animation
            const cursor = document.querySelector('.custom-cursor');
            const cursorDot = document.querySelector('.custom-cursor-dot');
            let mouseX = 0, mouseY = 0;
            let cursorX = 0, cursorY = 0;

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

            // Hover effect on interactive elements
            const interactiveElements = document.querySelectorAll('a, button, .part-card');
            interactiveElements.forEach(el => {
                el.addEventListener('mouseenter', () => cursor.classList.add('hover'));
                el.addEventListener('mouseleave', () => cursor.classList.remove('hover'));
            });

            document.addEventListener('mousedown', () => cursor.classList.add('clicking'));
            document.addEventListener('mouseup', () => cursor.classList.remove('clicking'));

            // Filter tabs
            const filterTabs = document.querySelectorAll('.part-filter-tab');
            const partCards = document.querySelectorAll('.part-card');
            filterTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    filterTabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    const filter = this.dataset.filter;
                    partCards.forEach(card => {
                        if (filter === 'all' || card.dataset.status === filter) {
                            card.style.display = '';
                            card.style.animation = 'partCardIn 0.5s ease forwards';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            });

            // Search
            const searchInput = document.querySelector('.part-search-input');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const query = this.value.toLowerCase();
                    partCards.forEach(card => {
                        const name = card.querySelector('.part-name').textContent.toLowerCase();
                        const event = card.querySelector('.part-event-name').textContent.toLowerCase();
                        const spec = card.querySelector('.part-spec').textContent.toLowerCase();
                        if (name.includes(query) || event.includes(query) || spec.includes(query)) {
                            card.style.display = '';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            }
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

    <?php include 'entreprise-navbar.php'; ?>

    <main class="main-content-wrapper">
        <div class="dashboard-container">
            <!-- Header -->
            <div class="part-page-header">
                <div class="part-header-left">
                    <h1><i class="fas fa-handshake"></i> Participations</h1>
                    <p>Participants à vos événements</p>
                </div>
            </div>

            <?php
            $total_part = count($participants);
            $confirmed = 0; $pending = 0; $events_count = 0;
            $unique_events = [];
            foreach ($participants as $p) {
                if ($p['statut'] === 'ok') $confirmed++;
                else $pending++;
                $unique_events[$p['event_titre']] = true;
            }
            $events_count = count($unique_events);
            ?>

            <!-- Stats Row -->
            <div class="part-stats-row">
                <div class="part-stat-chip">
                    <div class="part-stat-icon blue"><i class="fas fa-users"></i></div>
                    <div class="part-stat-data">
                        <span class="part-stat-number"><?php echo $total_part; ?></span>
                        <span class="part-stat-label">Participants</span>
                    </div>
                </div>
                <div class="part-stat-chip">
                    <div class="part-stat-icon green"><i class="fas fa-check-circle"></i></div>
                    <div class="part-stat-data">
                        <span class="part-stat-number"><?php echo $confirmed; ?></span>
                        <span class="part-stat-label">Confirmés</span>
                    </div>
                </div>
                <div class="part-stat-chip">
                    <div class="part-stat-icon amber"><i class="fas fa-hourglass-half"></i></div>
                    <div class="part-stat-data">
                        <span class="part-stat-number"><?php echo $pending; ?></span>
                        <span class="part-stat-label">En attente</span>
                    </div>
                </div>
                <div class="part-stat-chip">
                    <div class="part-stat-icon purple"><i class="fas fa-calendar-check"></i></div>
                    <div class="part-stat-data">
                        <span class="part-stat-number"><?php echo $events_count; ?></span>
                        <span class="part-stat-label">Événements</span>
                    </div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="part-toolbar">
                <div class="part-search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" class="part-search-input" placeholder="Rechercher un participant...">
                </div>
                <div class="part-filter-tabs">
                    <button class="part-filter-tab active" data-filter="all">
                        <i class="fas fa-th-large"></i> Tous
                    </button>
                    <button class="part-filter-tab" data-filter="ok">
                        <i class="fas fa-check-circle"></i> Confirmés
                    </button>
                    <button class="part-filter-tab" data-filter="pending">
                        <i class="fas fa-hourglass-half"></i> En attente
                    </button>
                </div>
            </div>

            <!-- Participants Grid -->
            <?php if (!empty($participants)): ?>
                <div class="part-grid">
                    <?php foreach ($participants as $participant): ?>
                        <?php
                        $is_confirmed = $participant['statut'] === 'ok';
                        $status_class = $is_confirmed ? 'confirmed' : 'pending';
                        $initials = strtoupper(substr($participant['prenom'], 0, 1) . substr($participant['nom'], 0, 1));
                        $status_key = $is_confirmed ? 'ok' : 'pending';
                        ?>
                        <div class="part-card" data-status="<?php echo $status_key; ?>">
                            <div class="part-card-top">
                                <div class="part-avatar <?php echo $status_class; ?>">
                                    <span><?php echo $initials; ?></span>
                                </div>
                                <div class="part-card-top-right">
                                    <span class="part-status-badge <?php echo $status_class; ?>">
                                        <span class="part-status-dot"></span>
                                        <?php echo $is_confirmed ? 'Confirmé' : 'En attente'; ?>
                                    </span>
                                    <?php if (!empty($participant['badge'])): ?>
                                        <span class="part-badge-tag">
                                            <i class="fas fa-award"></i> <?php echo htmlspecialchars($participant['badge']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="part-card-body">
                                <h3 class="part-name"><?php echo htmlspecialchars($participant['prenom'] . ' ' . $participant['nom']); ?></h3>
                                <div class="part-event">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span class="part-event-name"><?php echo htmlspecialchars($participant['event_titre']); ?></span>
                                </div>
                                <div class="part-event-date">
                                    <i class="fas fa-clock"></i>
                                    <span><?php echo date('d M Y', strtotime($participant['dateEvent'])); ?></span>
                                </div>
                                <div class="part-spec">
                                    <i class="fas fa-graduation-cap"></i>
                                    <span><?php echo htmlspecialchars($participant['specialite']); ?></span>
                                </div>
                                <div class="part-role">
                                    <i class="fas fa-user-tag"></i>
                                    <span><?php echo htmlspecialchars($participant['role']); ?></span>
                                </div>
                            </div>

                            <div class="part-card-footer">
                                <div class="part-inscri-date">
                                    <i class="fas fa-calendar-plus"></i>
                                    Inscrit le <?php echo date('d M Y', strtotime($participant['dateInscri'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="part-empty-state">
                    <div class="part-empty-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3>Aucun participant</h3>
                    <p>Les participants apparaîtront ici lorsque des étudiants s'inscriront à vos événements</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="../../public/assets/js/main.js"></script>
</body>
</html>
