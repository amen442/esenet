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

// Obtenir les statistiques du dashboard
$stats = get_dashboard_stats($entreprise_id);

// Obtenir les offres récentes
$recent_offres = get_recent_offres($entreprise_id, 3);

// Obtenir les événements à venir
$upcoming_events = get_upcoming_evenements(100, $entreprise_id);

// Calculer les statistiques d'événements
$events_upcoming = 0;
$events_expired = 0;
foreach ($upcoming_events as $event) {
    $event_date = new DateTime($event['dateEvent']);
    $today = new DateTime();
    if ($event_date >= $today) {
        $events_upcoming++;
    } else {
        $events_expired++;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - ESEN NET</title>
    <meta name="description" content="Votre espace entreprise ESEN NET">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../public/assets/css/main.css">
    <link rel="stylesheet" href="../../public/assets/css/entreprise-navbar.css">
    <link rel="stylesheet" href="../../public/assets/css/company-dashboard.css">
    <link rel="icon" href="../../public/assets/images/1.jpeg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.querySelector('.toggle-dark');
            if (themeToggle) {
                const savedTheme = localStorage.getItem('theme') || 'light';
                if (savedTheme === 'dark') {
                    document.body.classList.add('dark');
                }
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
            const interactiveElements = document.querySelectorAll('a, button, .stat-card, .quick-action-card, .recommendation-item');
            interactiveElements.forEach(el => {
                el.addEventListener('mouseenter', () => cursor.classList.add('hover'));
                el.addEventListener('mouseleave', () => cursor.classList.remove('hover'));
            });

            document.addEventListener('mousedown', () => cursor.classList.add('clicking'));
            document.addEventListener('mouseup', () => cursor.classList.remove('clicking'));

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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
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

            const interactiveElements = document.querySelectorAll('a, button, .stat-card, .quick-action-card, .recommendation-item');
            interactiveElements.forEach(el => {
                el.addEventListener('mouseenter', () => cursor.classList.add('hover'));
                el.addEventListener('mouseleave', () => cursor.classList.remove('hover'));
            });

            document.addEventListener('mousedown', () => cursor.classList.add('clicking'));
            document.addEventListener('mouseup', () => cursor.classList.remove('clicking'));

            // Initialize Charts
            const candidaturesCtx = document.getElementById('candidaturesChart').getContext('2d');
            const eventsCtx = document.getElementById('eventsChart').getContext('2d');
            const offresCtx = document.getElementById('offresChart').getContext('2d');

            // Candidatures Chart (Doughnut)
            const candidaturesData = {
                labels: ['Acceptées', 'En attente', 'Refusées'],
                datasets: [{
                    data: [
                        <?php echo $stats['acceptees']; ?>,
                        <?php echo $stats['candidatures'] - $stats['acceptees']; ?>,
                        0
                    ],
                    backgroundColor: [
                        'rgba(0, 212, 196, 0.8)',
                        'rgba(47, 111, 237, 0.8)',
                        'rgba(239, 68, 68, 0.8)'
                    ],
                    borderColor: [
                        'rgba(0, 212, 196, 1)',
                        'rgba(47, 111, 237, 1)',
                        'rgba(239, 68, 68, 1)'
                    ],
                    borderWidth: 2
                }]
            };

            new Chart(candidaturesCtx, {
                type: 'doughnut',
                data: candidaturesData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: '#e2e8f0',
                                font: {
                                    family: 'Inter',
                                    size: 12
                                }
                            }
                        }
                    }
                }
            });

            // Events Chart (Bar)
            const eventsData = {
                labels: ['À venir', 'Expirés'],
                datasets: [{
                    label: 'Événements',
                    data: [
                        <?php echo $events_upcoming; ?>,
                        <?php echo $events_expired; ?>
                    ],
                    backgroundColor: [
                        'rgba(0, 212, 196, 0.8)',
                        'rgba(47, 111, 237, 0.8)'
                    ],
                    borderColor: [
                        'rgba(0, 212, 196, 1)',
                        'rgba(47, 111, 237, 1)'
                    ],
                    borderWidth: 2,
                    borderRadius: 8
                }]
            };

            new Chart(eventsCtx, {
                type: 'bar',
                data: eventsData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: '#94a3b8',
                                font: {
                                    family: 'Inter'
                                }
                            },
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            }
                        },
                        x: {
                            ticks: {
                                color: '#94a3b8',
                                font: {
                                    family: 'Inter'
                                }
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // Offres Chart (Doughnut)
            const offresData = {
                labels: ['Actives', 'Expirées'],
                datasets: [{
                    data: [
                        <?php echo $stats['actives']; ?>,
                        <?php echo $stats['offres'] - $stats['actives']; ?>
                    ],
                    backgroundColor: [
                        'rgba(0, 212, 196, 0.8)',
                        'rgba(47, 111, 237, 0.8)'
                    ],
                    borderColor: [
                        'rgba(0, 212, 196, 1)',
                        'rgba(47, 111, 237, 1)'
                    ],
                    borderWidth: 2
                }]
            };

            new Chart(offresCtx, {
                type: 'doughnut',
                data: offresData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: '#e2e8f0',
                                font: {
                                    family: 'Inter',
                                    size: 12
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>

    <?php include 'entreprise-navbar.php'; ?>

    <main class="main-content-wrapper">
        <div class="dashboard-container">
            <div class="dashboard-header">
                <h1>Tableau de bord</h1>
                <p>Bienvenue sur votre espace entreprise</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon blue"><i class="fas fa-briefcase"></i></div>
                    <div class="value"><?php echo $stats['offres']; ?></div>
                    <div class="label">Offres publiées</div>
                    <small class="text-success"><?php echo $stats['actives']; ?> actives</small>
                </div>
                <div class="stat-card">
                    <div class="icon green"><i class="fas fa-users"></i></div>
                    <div class="value"><?php echo $stats['candidatures']; ?></div>
                    <div class="label">Candidatures reçues</div>
                    <small class="text-success"><?php echo $stats['acceptees']; ?> acceptées</small>
                </div>
                <div class="stat-card">
                    <div class="icon orange"><i class="fas fa-check-circle"></i></div>
                    <div class="value"><?php echo $stats['acceptees']; ?></div>
                    <div class="label">Candidats acceptés</div>
                    <small class="text-success">Total</small>
                </div>
                <div class="stat-card">
                    <div class="icon purple"><i class="fas fa-calendar-alt"></i></div>
                    <div class="value"><?php echo count($upcoming_events); ?></div>
                    <div class="label">Événements à venir</div>
                    <small class="text-success">Prochainement</small>
                </div>
            </div>

            <div class="charts-grid">
                <div class="dashboard-card chart-section">
                    <h3>Répartition des candidatures</h3>
                    <div class="chart-container">
                        <canvas id="candidaturesChart"></canvas>
                    </div>
                </div>

                <div class="dashboard-card chart-section">
                    <h3>Répartition des événements</h3>
                    <div class="chart-container">
                        <canvas id="eventsChart"></canvas>
                    </div>
                </div>

                <div class="dashboard-card chart-section">
                    <h3>Répartition des offres</h3>
                    <div class="chart-container">
                        <canvas id="offresChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="dashboard-card offers-section">
                    <h3>Offres récentes</h3>
                    <div class="recommendations-list">
                        <?php if (!empty($recent_offres)): ?>
                            <?php foreach ($recent_offres as $offre): ?>
                                <div class="recommendation-item">
                                    <h4><?php echo htmlspecialchars($offre['titre']); ?></h4>
                                    <p>Publié le <?php echo date('d/m/Y', strtotime($offre['datePub'])); ?></p>
                                    <div class="company"><?php echo $offre['nb_candidatures']; ?> candidature(s)</div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>Aucune offre récente</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="dashboard-card actions-section">
                    <h3>Actions rapides</h3>
                    <div class="quick-actions-grid">
                        <a href="offers.php" class="quick-action-card blue">
                            <div class="quick-action-icon">
                                <i class="fas fa-plus-circle"></i>
                            </div>
                            <div class="quick-action-content">
                                <h4>Publier une offre</h4>
                                <p>Créer une nouvelle offre de stage</p>
                            </div>
                            <div class="quick-action-arrow">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                        </a>
                        <a href="candidates.php" class="quick-action-card green">
                            <div class="quick-action-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="quick-action-content">
                                <h4>Voir les candidats</h4>
                                <p>Consulter les candidatures</p>
                            </div>
                            <div class="quick-action-arrow">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                        </a>
                        <a href="events.php" class="quick-action-card orange">
                            <div class="quick-action-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="quick-action-content">
                                <h4>Événements à venir</h4>
                                <p>Participer aux événements</p>
                            </div>
                            <div class="quick-action-arrow">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                        </a>
                        <a href="profile.php" class="quick-action-card purple">
                            <div class="quick-action-icon">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="quick-action-content">
                                <h4>Mettre à jour mon profil</h4>
                                <p>Gérer vos informations</p>
                            </div>
                            <div class="quick-action-arrow">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../public/assets/js/main.js"></script>
</body>
</html>
