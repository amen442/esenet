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

// Obtenir les informations de l'enseignant
$teacher_info = get_teacher_info($teacher_id);

// Obtenir les statistiques de l'enseignant
$stats = get_teacher_stats($teacher_id);

// Obtenir les étudiants de l'enseignant
$students = get_teacher_students($teacher_id);

// Obtenir les recommandations de l'enseignant
$recommendations = get_teacher_recommendations($teacher_id);

// Obtenir les statistiques de participation aux événements
$event_stats = get_teacher_event_participation_stats($teacher_id);

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
    <link rel="stylesheet" href="../../public/assets/css/teacher-navbar.css">
    <link rel="stylesheet" href="../../public/assets/css/teacher-dashboard.css">
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

            // Navbar toggle functionality
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

            const interactiveElements = document.querySelectorAll('a, button, .stat-card, .quick-action-card, .recommendation-item');
            interactiveElements.forEach(el => {
                el.addEventListener('mouseenter', () => cursor.classList.add('hover'));
                el.addEventListener('mouseleave', () => cursor.classList.remove('hover'));
            });

            document.addEventListener('mousedown', () => cursor.classList.add('clicking'));
            document.addEventListener('mouseup', () => cursor.classList.remove('clicking'));

            // Initialize Charts
            const studentsCtx = document.getElementById('studentsChart').getContext('2d');
            const recommendationsCtx = document.getElementById('recommendationsChart').getContext('2d');

            // Students Chart (Doughnut)
            const studentsData = {
                labels: ['Actifs', 'Inactifs'],
                datasets: [{
                    data: [
                        <?php echo $stats['students']; ?>,
                        0
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

            new Chart(studentsCtx, {
                type: 'doughnut',
                data: studentsData,
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

            // Recommendations Chart (Bar)
            const recommendationsData = {
                labels: ['Faites', 'En attente'],
                datasets: [{
                    label: 'Recommandations',
                    data: [
                        <?php echo $stats['recommendations']; ?>,
                        0
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

            new Chart(recommendationsCtx, {
                type: 'bar',
                data: recommendationsData,
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
            <div class="dashboard-header">
                <h1>Tableau de bord</h1>
                <p>Bienvenue sur votre espace enseignant</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon green"><i class="fas fa-star"></i></div>
                    <div class="value"><?php echo $stats['recommendations']; ?></div>
                    <div class="label">Recommandations</div>
                    <small class="text-success">Recommandations faites</small>
                </div>
                <div class="stat-card">
                    <div class="icon blue"><i class="fas fa-calendar-check"></i></div>
                    <div class="value"><?php echo $event_stats['total_participations']; ?></div>
                    <div class="label">Participations</div>
                    <small class="text-info">Total des participations</small>
                </div>
                <div class="stat-card">
                    <div class="icon orange"><i class="fas fa-clock"></i></div>
                    <div class="value"><?php echo $event_stats['upcoming_participations']; ?></div>
                    <div class="label">À venir</div>
                    <small class="text-warning">Événements à venir</small>
                </div>
                <div class="stat-card">
                    <div class="icon purple"><i class="fas fa-percentage"></i></div>
                    <div class="value"><?php echo $event_stats['participation_rate']; ?>%</div>
                    <div class="label">Taux de participation</div>
                    <small class="text-primary">Pourcentage des événements</small>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <h3>Dernières recommandations</h3>
                    <div class="recommendations-list">
                        <?php if (!empty($recommendations)): ?>
                            <?php foreach (array_slice($recommendations, 0, 5) as $rec): ?>
                                <div class="recommendation-item">
                                    <h4><?php echo htmlspecialchars($rec['etudiant_prenom'] . ' ' . $rec['etudiant_nom']); ?></h4>
                                    <p><?php echo htmlspecialchars($rec['contenu'] ?? $rec['profil'] ?? 'Recommandation'); ?> - <?php echo htmlspecialchars($rec['nomsociete'] ?? 'Entreprise'); ?></p>
                                    <div class="company"><?php echo date('d/m/Y', strtotime($rec['dateRecommandation'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>Aucune recommandation faite</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="dashboard-card actions-section">
                    <h3>Actions rapides</h3>
                    <div class="quick-actions-grid">
                        <a href="recommendations.php" class="quick-action-card green">
                            <div class="quick-action-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="quick-action-content">
                                <h4>Recommander un étudiant</h4>
                                <p>Faire une nouvelle recommandation</p>
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
                                <i class="fas fa-user-edit"></i>
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

<?php include 'notification-box.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../public/assets/js/main.js"></script>
</body>
</html>
