<?php
require_once __DIR__ . '/../layouts/components.php';
require_once __DIR__ . '/../../config/db_connect.php';

// Obtenir l'ID de l'enseignant connecté
$teacher_id = get_teacher_id();

// Si aucun enseignant n'est connecté, rediriger vers la page de connexion
if (!$teacher_id) {
    header("Location: ../../login.php");
    exit();
}

// Obtenir tous les événements (passés et à venir)
$events = get_all_evenements();

// Obtenir les statistiques des événements
$stats = get_evenements_stats();

// Traitement des actions de participation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['participer_event']) && isset($_POST['event_id'])) {
        $event_id = sanitize_input($_POST['event_id']);
        $result = teacher_participer_evenement($teacher_id, $event_id);
        
        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
        } else {
            $_SESSION['error_message'] = $result['message'];
        }
        
        header("Location: events.php");
        exit();
    }
    
    if (isset($_POST['annuler_participation']) && isset($_POST['event_id'])) {
        $event_id = sanitize_input($_POST['event_id']);
        $result = teacher_annuler_participation($teacher_id, $event_id);
        
        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
        } else {
            $_SESSION['error_message'] = $result['message'];
        }
        
        header("Location: events.php");
        exit();
    }
}

// Obtenir les participations de l'enseignant
$teacher_participations = get_teacher_participations($teacher_id);

// Créer un tableau des IDs d'événements auxquels l'enseignant participe
$participated_events = [];
foreach ($teacher_participations as $participation) {
    $participated_events[] = $participation['idEvenement'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Événements - ESEN NET</title>
    <meta name="description" content="Participez aux événements ESEN">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../public/assets/css/main.css">
    <link rel="stylesheet" href="../../public/assets/css/teacher-navbar.css">
    <link rel="stylesheet" href="../../public/assets/css/teacher-dashboard.css">
    <link rel="stylesheet" href="../../public/assets/css/entreprise-events.css">
    <link rel="stylesheet" href="../../public/assets/css/teacher-dashboard.css">
    <link rel="icon" href="../../public/assets/images/1.jpeg" type="image/x-icon">
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

            // Toggle navbar avec la flèche
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
            const interactiveElements = document.querySelectorAll('a, button, .evt-card');
            interactiveElements.forEach(el => {
                el.addEventListener('mouseenter', () => cursor.classList.add('hover'));
                el.addEventListener('mouseleave', () => cursor.classList.remove('hover'));
            });

            document.addEventListener('mousedown', () => cursor.classList.add('clicking'));
            document.addEventListener('mouseup', () => cursor.classList.remove('clicking'));

            // Filter tabs
            const filterTabs = document.querySelectorAll('.evt-filter-tab');
            const evtCards = document.querySelectorAll('.evt-card');
            
            // Afficher automatiquement les événements "À venir" au chargement
            const upcomingTab = document.querySelector('[data-filter="upcoming"]');
            
            if (upcomingTab && evtCards.length > 0) {
                // Activer le filtre "À venir" par défaut
                upcomingTab.classList.add('active');
                
                // Masquer les événements passés
                evtCards.forEach(card => {
                    if (card.dataset.status === 'past') {
                        card.style.display = 'none';
                    } else {
                        card.style.display = '';
                        card.style.animation = 'evtCardIn 0.5s ease forwards';
                    }
                });
            }

            filterTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    filterTabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    const filter = this.dataset.filter;
                    evtCards.forEach(card => {
                        if (filter === 'all' || card.dataset.status === filter) {
                            card.style.display = '';
                            card.style.animation = 'evtCardIn 0.5s ease forwards';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            });

            // Search
            const searchInput = document.querySelector('.evt-search-input');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const query = this.value.toLowerCase();
                    evtCards.forEach(card => {
                        const title = card.querySelector('.evt-card-title').textContent.toLowerCase();
                        const lieu = card.querySelector('.evt-card-lieu').textContent.toLowerCase();
                        if (title.includes(query) || lieu.includes(query)) {
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

    <?php include 'teacher-navbar.php'; ?>

    <main class="main-content-wrapper">
        <div class="dashboard-container">
            <!-- Header -->
            <div class="evt-page-header">
                <div class="evt-header-left">
                    <h1><i class="fas fa-calendar-alt"></i> Événements</h1>
                    <p>Choisissez les événements auxquels vous souhaitez participer</p>
                </div>
            </div>

            <!-- Messages de succès/erreur -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert-card alert-success-custom" style="margin-bottom: 20px;">
                    <i class="fas fa-check-circle"></i>
                    <?php 
                    echo htmlspecialchars($_SESSION['success_message']);
                    unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert-card alert-error-custom" style="margin-bottom: 20px;">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php 
                    echo htmlspecialchars($_SESSION['error_message']);
                    unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>

            <?php
            // Utiliser les statistiques dynamiques de la base de données
            $total_evt = $stats['total'];
            $upcoming = $stats['upcoming'];
            $past = $stats['past'];
            $total_cap = $stats['total_capacity'];
            ?>

            <!-- Stats Row -->
            <div class="evt-stats-row">
                <div class="evt-stat-chip">
                    <div class="evt-stat-icon blue"><i class="fas fa-calendar-alt"></i></div>
                    <div class="evt-stat-data">
                        <span class="evt-stat-number"><?php echo $total_evt; ?></span>
                        <span class="evt-stat-label">Total</span>
                    </div>
                </div>
                <div class="evt-stat-chip">
                    <div class="evt-stat-icon green"><i class="fas fa-clock"></i></div>
                    <div class="evt-stat-data">
                        <span class="evt-stat-number"><?php echo $upcoming; ?></span>
                        <span class="evt-stat-label">À venir</span>
                    </div>
                </div>
                <div class="evt-stat-chip">
                    <div class="evt-stat-icon red"><i class="fas fa-calendar-check"></i></div>
                    <div class="evt-stat-data">
                        <span class="evt-stat-number"><?php echo $past; ?></span>
                        <span class="evt-stat-label">Terminés</span>
                    </div>
                </div>
                <div class="evt-stat-chip">
                    <div class="evt-stat-icon purple"><i class="fas fa-users"></i></div>
                    <div class="evt-stat-data">
                        <span class="evt-stat-number"><?php echo $total_cap; ?></span>
                        <span class="evt-stat-label">Places totales</span>
                    </div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="evt-toolbar">
                <div class="evt-search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" class="evt-search-input" placeholder="Rechercher un événement...">
                </div>
                <div class="evt-filter-tabs">
                    <button class="evt-filter-tab active" data-filter="all">
                        <i class="fas fa-th-large"></i> Tous
                    </button>
                    <button class="evt-filter-tab" data-filter="upcoming">
                        <i class="fas fa-clock"></i> À venir
                    </button>
                    <button class="evt-filter-tab" data-filter="past">
                        <i class="fas fa-calendar-check"></i> Terminés
                    </button>
                </div>
            </div>

            <!-- Events Grid -->
            <?php if (!empty($events)): ?>
                <div class="evt-grid">
                    <?php foreach ($events as $event): ?>
                        <?php
                        $event_date = new DateTime($event['dateEvent']);
                        $today = new DateTime();
                        $is_expired = $event_date < $today;
                        $status_key = $event['status'] ?? ($is_expired ? 'past' : 'upcoming');
                        $day = date('d', strtotime($event['dateEvent']));
                        $month = date('M', strtotime($event['dateEvent']));
                        
                        // Formatage de la date en français
                        $formatted_date = date('d/m/Y', strtotime($event['dateEvent']));
                        $formatted_time = date('H:i', strtotime($event['dateEvent']));
                        ?>
                        <div class="evt-card" data-status="<?php echo $status_key; ?>">
                            <?php if (!empty($event['image'])): ?>
                                <div class="evt-card-banner">
                                    <img src="../../../<?php echo htmlspecialchars($event['image']); ?>" alt="<?php echo htmlspecialchars($event['titre']); ?>">
                                    <div class="evt-card-banner-overlay"></div>
                                </div>
                            <?php else: ?>
                                <div class="evt-card-banner evt-card-banner-placeholder">
                                    <div class="evt-card-banner-icon">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="evt-card-date-badge">
                                <span class="evt-date-day"><?php echo $day; ?></span>
                                <span class="evt-date-month"><?php echo $month; ?></span>
                            </div>

                            <div class="evt-card-body">
                                <div class="evt-card-top">
                                    <span class="evt-card-status <?php echo $status_key; ?>">
                                        <span class="evt-status-dot"></span>
                                        <?php echo $is_expired ? 'Terminé' : 'À venir'; ?>
                                    </span>
                                    <?php if (!empty($event['capacite'])): ?>
                                    <span class="evt-card-capacity">
                                        <i class="fas fa-users"></i> <?php echo $event['capacite']; ?> places
                                    </span>
                                    <?php endif; ?>
                                </div>

                                <h3 class="evt-card-title"><?php echo htmlspecialchars($event['titre']); ?></h3>

                                <div class="evt-card-lieu">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($event['lieu'] ?? 'ESEN'); ?></span>
                                </div>

                                <div class="evt-card-date">
                                    <i class="fas fa-calendar"></i>
                                    <span><?php echo $formatted_date; ?> à <?php echo $formatted_time; ?></span>
                                </div>

                                <?php if (!empty($event['capacite'])): ?>
                                    <div class="evt-card-capacity-info">
                                        <i class="fas fa-users"></i>
                                        <span><?php echo $event['capacite']; ?> places disponibles</span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($event['description'])): ?>
                                    <p class="evt-card-desc"><?php echo htmlspecialchars(mb_strimwidth($event['description'], 0, 120, '...')); ?></p>
                                <?php endif; ?>

                                <?php if (!empty($event['statut'])): ?>
                                    <div class="evt-card-statut">
                                        <span class="evt-statut-badge <?php echo strtolower($event['statut']); ?>">
                                            <?php echo htmlspecialchars(ucfirst($event['statut'])); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="evt-card-actions">
                                <?php 
                                $is_participating = in_array($event['id'], $participated_events);
                                if (!$is_expired): ?>
                                    <?php if ($is_participating): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                            <button type="submit" name="annuler_participation" class="evt-card-btn cancel">
                                                <i class="fas fa-times-circle"></i> Annuler
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                            <button type="submit" name="participer_event" class="evt-card-btn participate">
                                                <i class="fas fa-check-circle"></i> Participer
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button class="evt-card-btn disabled" disabled>
                                        <i class="fas fa-calendar-check"></i> 
                                        <?php echo $is_participating ? 'Participé' : 'Terminé'; ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="evt-empty-state">
                    <div class="evt-empty-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3>Aucun événement disponible</h3>
                    <p>Revenez plus tard pour voir les nouveaux événements</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

<?php include 'notification-box.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../public/assets/js/main.js"></script>
</body>
</html>
