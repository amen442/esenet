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

// Obtenir les informations de l'admin
$admin_info = get_admin_info($admin_id);

// Obtenir les statistiques globales
$stats = [
    'total_users' => 0,
    'total_students' => 0,
    'total_teachers' => 0,
    'total_admins' => 0,
    'total_events' => 0,
    'total_offers' => 0,
    'total_companies' => 0
];

// Compter les utilisateurs par rôle en utilisant la colonne 'statut'
$sql = "SELECT COUNT(*) as count FROM utilisateur WHERE statut = ?";
$stmt = $conn->prepare($sql);

// Compter les étudiants
$student_role = "ETUDIANT";
$stmt->execute([$student_role]);
$result = $stmt->fetch();
if ($result) {
    $stats['total_students'] = $result['count'];
} else {
    $stats['total_students'] = 0;
}

// Compter les enseignants
$teacher_role = "ENSEIGNANT";
$stmt->execute([$teacher_role]);
$result = $stmt->fetch();
if ($result) {
    $stats['total_teachers'] = $result['count'];
} else {
    $stats['total_teachers'] = 0;
}

// Compter les entreprises
$company_role = "ENTREPRISE";
$stmt->execute([$company_role]);
$result = $stmt->fetch();
if ($result) {
    $stats['total_companies_users'] = $result['count'];
} else {
    $stats['total_companies_users'] = 0;
}

// Compter les admins
$admin_role = "ADMIN";
$stmt->execute([$admin_role]);
$result = $stmt->fetch();
if ($result) {
    $stats['total_admins'] = $result['count'];
} else {
    $stats['total_admins'] = 0;
}

// Compter les utilisateurs au total (tous les rôles)
$stats['total_users'] = $stats['total_students'] + $stats['total_teachers'] + $stats['total_companies_users'] + $stats['total_admins'];

// Compter les événements
$result = $conn->query("SELECT COUNT(*) as count FROM evenement");
if ($result) {
    $row = $result->fetch();
    $stats['total_events'] = $row['count'] ?? 0;
} else {
    $stats['total_events'] = 0;
}

// Compter les offres
$result = $conn->query("SELECT COUNT(*) as count FROM offre");
if ($result) {
    $row = $result->fetch();
    $stats['total_offers'] = $row['count'] ?? 0;
} else {
    $stats['total_offers'] = 0;
}

// Compter les entreprises
$result = $conn->query("SELECT COUNT(*) as count FROM entreprise");
if ($result) {
    $row = $result->fetch();
    $stats['total_companies'] = $row['count'] ?? 0;
} else {
    $stats['total_companies'] = 0;
}

// Obtenir la répartition des rôles pour les graphiques
$role_distribution = [
    ['role' => 'Étudiants', 'count' => $stats['total_students'], 'color' => '#00d4c4'],
    ['role' => 'Enseignants', 'count' => $stats['total_teachers'], 'color' => '#2f6fed'],
    ['role' => 'Entreprises', 'count' => $stats['total_companies_users'], 'color' => '#f59e0b'],
    ['role' => 'Admins', 'count' => $stats['total_admins'], 'color' => '#8b5cf6']
];

// Obtenir les statistiques d'activité en utilisant les données déjà récupérées
$activity_stats = [
    ['type' => 'Événements', 'count' => $stats['total_events'], 'color' => '#00d4c4'],
    ['type' => 'Offres', 'count' => $stats['total_offers'], 'color' => '#2f6fed']
];



// Obtenir les événements récents dynamiques
$recent_events = [];
try {
    $sql = "SELECT titre, dateEvent, lieu 
            FROM evenement 
            ORDER BY dateEvent DESC 
            LIMIT 5";
    $result = $conn->query($sql);
    
    if (!$result) {
                $recent_events = [];
    } else {
        foreach ($result->fetchAll() as $row) {
            $recent_events[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Exception recent events: " . $e->getMessage());
    $recent_events = [];
}

// Obtenir les offres récentes dynamiques
$recent_offers = [];
try {
    $sql = "SELECT o.titre, o.datePub, e.nomsociete as entreprise_nom 
            FROM offre o 
            LEFT JOIN entreprise e ON o.idEntreprise = e.id 
            ORDER BY o.datePub DESC 
            LIMIT 5";
    $result = $conn->query($sql);
    
    if (!$result) {
                $recent_offers = [];
    } else {
        foreach ($result->fetchAll() as $row) {
            $recent_offers[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Exception recent offers: " . $e->getMessage());
    $recent_offers = [];
}

// Obtenir les statistiques des candidatures
$candidature_stats = [];
try {
    $sql = "SELECT COUNT(*) as total, 
                   SUM(CASE WHEN statut = 'EN_ATTENTE' THEN 1 ELSE 0 END) as en_attente,
                   SUM(CASE WHEN statut = 'ACCEPTE' THEN 1 ELSE 0 END) as accepte,
                   SUM(CASE WHEN statut = 'REFUSE' THEN 1 ELSE 0 END) as refuse
            FROM candidature";
    $result = $conn->query($sql);
    
    if ($result) {
        $candidature_data = $result->fetch();
        $candidature_stats = [
            ['type' => 'En attente', 'count' => $candidature_data['en_attente'], 'color' => '#f59e0b'],
            ['type' => 'Acceptées', 'count' => $candidature_data['accepte'], 'color' => '#10b981'],
            ['type' => 'Refusées', 'count' => $candidature_data['refuse'], 'color' => '#ef4444']
        ];
    } else {
        $candidature_stats = [
            ['type' => 'En attente', 'count' => 0, 'color' => '#f59e0b'],
            ['type' => 'Acceptées', 'count' => 0, 'color' => '#10b981'],
            ['type' => 'Refusées', 'count' => 0, 'color' => '#ef4444']
        ];
    }
} catch (Exception $e) {
    error_log("Exception candidature stats: " . $e->getMessage());
    $candidature_stats = [
        ['type' => 'En attente', 'count' => 0, 'color' => '#f59e0b'],
        ['type' => 'Acceptées', 'count' => 0, 'color' => '#10b981'],
        ['type' => 'Refusées', 'count' => 0, 'color' => '#ef4444']
    ];
}

// Obtenir les entreprises les plus actives
$top_companies = [];
try {
    $sql = "SELECT e.nomsociete, COUNT(o.id) as nb_offres 
            FROM entreprise e 
            LEFT JOIN offre o ON e.id = o.idEntreprise 
            GROUP BY e.id, e.nomsociete 
            ORDER BY nb_offres DESC 
            LIMIT 5";
    $result = $conn->query($sql);
    
    if (!$result) {
                $top_companies = [];
    } else {
        foreach ($result->fetchAll() as $row) {
            $top_companies[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Exception top companies: " . $e->getMessage());
    $top_companies = [];
}

// Obtenir les tendances des inscriptions par mois
$inscription_trends = [];
try {
    $sql = "SELECT DATE_FORMAT(dateInscription, '%Y-%m') as month_year, 
                   COUNT(*) as count,
                   SUM(CASE WHEN statut = 'ETUDIANT' THEN 1 ELSE 0 END) as students,
                   SUM(CASE WHEN statut = 'ENSEIGNANT' THEN 1 ELSE 0 END) as teachers,
                   SUM(CASE WHEN statut = 'ENTREPRISE' THEN 1 ELSE 0 END) as companies
            FROM utilisateur 
            WHERE dateInscription IS NOT NULL 
            GROUP BY DATE_FORMAT(dateInscription, '%Y-%m') 
            ORDER BY month_year DESC 
            LIMIT 6";
    $result = $conn->query($sql);
    
    if (!$result) {
                $inscription_trends = [];
    } else {
        foreach ($result->fetchAll() as $row) {
            $inscription_trends[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Exception inscription trends: " . $e->getMessage());
    $inscription_trends = [];
}

// Statistiques supplémentaires pour un dashboard complet
$additional_stats = [];
try {
    // Statistiques des événements par mois
    $result = $conn->query("SELECT DATE_FORMAT(dateEvent, '%Y-%m') as month_year, COUNT(*) as count
                            FROM evenement 
                            WHERE dateEvent IS NOT NULL 
                            GROUP BY DATE_FORMAT(dateEvent, '%Y-%m') 
                            ORDER BY month_year DESC 
                            LIMIT 6");
    if ($result) {
        $additional_stats['monthly_events'] = [];
        foreach ($result->fetchAll() as $row) {
            $additional_stats['monthly_events'][] = $row;
        }
    } else {
        $additional_stats['monthly_events'] = [];
    }
    
    // Statistiques des offres par mois
    $result = $conn->query("SELECT DATE_FORMAT(datePub, '%Y-%m') as month_year, COUNT(*) as count
                            FROM offre 
                            WHERE datePub IS NOT NULL 
                            GROUP BY DATE_FORMAT(datePub, '%Y-%m') 
                            ORDER BY month_year DESC 
                            LIMIT 6");
    if ($result) {
        $additional_stats['monthly_offers'] = [];
        foreach ($result->fetchAll() as $row) {
            $additional_stats['monthly_offers'][] = $row;
        }
    } else {
        $additional_stats['monthly_offers'] = [];
    }
    
    // Top 5 des étudiants les plus actifs (nombre de candidatures)
    $result = $conn->query("SELECT u.nom, u.email, COUNT(c.id) as nb_candidatures
                            FROM utilisateur u 
                            LEFT JOIN candidature c ON u.id = c.idEtudiant 
                            WHERE u.statut = 'ETUDIANT' 
                            GROUP BY u.id, u.nom, u.email 
                            ORDER BY nb_candidatures DESC 
                            LIMIT 5");
    if ($result) {
        $additional_stats['top_students'] = [];
        foreach ($result->fetchAll() as $row) {
            $additional_stats['top_students'][] = $row;
        }
    } else {
        $additional_stats['top_students'] = [];
    }
    
    // Statistiques des participations aux événements
    $result = $conn->query("SELECT COUNT(*) as total_participations FROM participation");
    if ($result) {
        $row = $result->fetch();
        $additional_stats['total_participations'] = $row['total_participations'] ?? 0;
    } else {
        $additional_stats['total_participations'] = 0;
    }
    
    // Répartition des offres par secteur (si disponible)
    $result = $conn->query("SELECT secteur, COUNT(*) as count 
                            FROM offre 
                            WHERE secteur IS NOT NULL AND secteur != '' 
                            GROUP BY secteur 
                            ORDER BY count DESC 
                            LIMIT 5");
    if ($result) {
        $additional_stats['offers_by_sector'] = [];
        foreach ($result->fetchAll() as $row) {
            $additional_stats['offers_by_sector'][] = $row;
        }
    } else {
        $additional_stats['offers_by_sector'] = [];
    }
    
    // Moyenne des candidatures par offre
    $result = $conn->query("SELECT AVG(candidature_count) as avg_candidatures
                            FROM (
                                SELECT COUNT(*) as candidature_count 
                                FROM offre 
                                LEFT JOIN candidature ON offre.id = candidature.idOffre 
                                GROUP BY offre.id
                            ) as offre_stats");
    if ($result) {
        $avg_data = $result->fetch();
        $additional_stats['avg_candidatures_per_offer'] = round($avg_data['avg_candidatures'], 1);
    } else {
        $additional_stats['avg_candidatures_per_offer'] = 0;
    }
    
} catch (Exception $e) {
    error_log("Exception additional stats: " . $e->getMessage());
    $additional_stats = [
        'monthly_events' => [],
        'monthly_offers' => [],
        'top_students' => [],
        'total_participations' => 0,
        'offers_by_sector' => [],
        'avg_candidatures_per_offer' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Administrateur - ESEN Connect</title>
    <meta name="description" content="Votre espace administrateur ESEN Connect">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../public/assets/css/main.css">
    <link rel="stylesheet" href="../../public/assets/css/admin-navbar.css">
    <link rel="stylesheet" href="../../public/assets/css/admin-dashboard.css">
    <link rel="icon" href="../../public/assets/images/1.jpeg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .dashboard-grid.centered-actions {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 400px;
        }
        
        .dashboard-card.actions-section.centered {
            max-width: 800px;
            width: 100%;
            text-align: center;
        }
        
        .dashboard-card.actions-section.centered h3 {
            font-size: 2rem;
            margin-bottom: 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .dashboard-card.actions-section.centered .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        body.dark .dashboard-card.actions-section.centered h3 {
            background: linear-gradient(135deg, #818cf8 0%, #c084fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Données dynamiques du dashboard
        const dashboardData = {
            stats: <?php echo json_encode($stats); ?>,
            roleDistribution: <?php echo json_encode($role_distribution); ?>,
            activityStats: <?php echo json_encode($activity_stats); ?>,
            recentEvents: <?php echo json_encode($recent_events); ?>,
            recentOffers: <?php echo json_encode($recent_offers); ?>,
            candidatureStats: <?php echo json_encode($candidature_stats); ?>,
            topCompanies: <?php echo json_encode($top_companies); ?>,
            inscriptionTrends: <?php echo json_encode($inscription_trends); ?>,
            additionalStats: <?php echo json_encode($additional_stats); ?>
        };

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

            // Custom Cursor (identique au student/teacher/entreprise)
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

                // Hover effect on interactive elements
                const interactiveElements = document.querySelectorAll('a, button, input, .btn, .stat-card, .dashboard-card, .quick-action-card, .user-item, .chart-card, .nav-item');
                interactiveElements.forEach(el => {
                    el.addEventListener('mouseenter', () => cursor.classList.add('hover'));
                    el.addEventListener('mouseleave', () => cursor.classList.remove('hover'));
                });

                // Click effect
                document.addEventListener('mousedown', () => cursor.classList.add('clicking'));
                document.addEventListener('mouseup', () => cursor.classList.remove('clicking'));
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

    <?php include 'admin-navbar.php'; ?>

    <main class="main-content-wrapper">
        <div class="dashboard-container">
            <div class="dashboard-header">
                <h1>Tableau de bord</h1>
                <p>Bienvenue sur votre espace administrateur</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon blue"><i class="fas fa-users"></i></div>
                    <div class="value"><?php echo $stats['total_users']; ?></div>
                    <div class="label">Total utilisateurs</div>
                    <small class="text-success"><?php echo $stats['total_students']; ?> étudiants</small>
                </div>
                <div class="stat-card">
                    <div class="icon green"><i class="fas fa-building"></i></div>
                    <div class="value"><?php echo $stats['total_companies']; ?></div>
                    <div class="label">Entreprises</div>
                    <small class="text-success">Total entreprises</small>
                </div>
                <div class="stat-card">
                    <div class="icon orange"><i class="fas fa-briefcase"></i></div>
                    <div class="value"><?php echo $stats['total_offers']; ?></div>
                    <div class="label">Offres d'emploi</div>
                    <small class="text-success">Total offres</small>
                </div>
                <div class="stat-card">
                    <div class="icon purple"><i class="fas fa-calendar-alt"></i></div>
                    <div class="value"><?php echo $stats['total_events']; ?></div>
                    <div class="label">Événements</div>
                    <small class="text-success">Total événements</small>
                </div>
            </div>

            <div class="charts-grid">
                <div class="chart-card">
                    <h4><i class="fas fa-chart-pie"></i> Répartition des utilisateurs</h4>
                    <div class="chart-wrapper">
                        <canvas id="roleChart"></canvas>
                    </div>
                    <div class="chart-legend">
                        <div class="legend-item">
                            <div class="legend-color" style="background: #00d4c4;"></div>
                            <span>Étudiants</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #2f6fed;"></div>
                            <span>Enseignants</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #8b5cf6;"></div>
                            <span>Admins</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #f59e0b"></div>
                            <span>Entreprises</span>
                        </div>
                    </div>
                </div>

                <div class="chart-card">
                    <h4><i class="fas fa-chart-bar"></i> Activité de la plateforme</h4>
                    <div class="chart-wrapper">
                        <canvas id="activityChart"></canvas>
                    </div>
                    <div class="chart-legend">
                        <div class="legend-item">
                            <div class="legend-color" style="background: #00d4c4;"></div>
                            <span>Événements</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #2f6fed;"></div>
                            <span>Offres</span>
                        </div>
                    </div>
                </div>

                            </div>

            <div class="dashboard-grid centered-actions">
                
                <div class="dashboard-card actions-section centered">
                    <h3>Actions rapides</h3>
                    <div class="quick-actions-grid">
                        <a href="users.php" class="quick-action-card blue">
                            <div class="quick-action-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="quick-action-content">
                                <h4>Gérer les utilisateurs</h4>
                                <p>Ajouter, modifier ou supprimer des utilisateurs</p>
                            </div>
                            <div class="quick-action-arrow">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                        </a>
                        <a href="companies.php" class="quick-action-card green">
                            <div class="quick-action-icon">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="quick-action-content">
                                <h4>Gérer les entreprises</h4>
                                <p>Administrer les comptes entreprises</p>
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
                                <h4>Gérer les événements</h4>
                                <p>Créer et modifier les événements</p>
                            </div>
                            <div class="quick-action-arrow">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                        </a>
                        <a href="offers.php" class="quick-action-card purple">
                            <div class="quick-action-icon">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            <div class="quick-action-content">
                                <h4>Gérer les offres</h4>
                                <p>Superviser les offres d'emploi</p>
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

    <script>
        // Chart.js configuration (identique au student/teacher/entreprise)
        Chart.defaults.font.family = 'Inter';
        Chart.defaults.color = '#e2e8f0';
        Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.1)';

        // Role Distribution Chart (Doughnut)
        const roleCtx = document.getElementById('roleChart').getContext('2d');
        new Chart(roleCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($role_distribution, 'role')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($role_distribution, 'count')); ?>,
                    backgroundColor: <?php echo json_encode(array_column($role_distribution, 'color')); ?>,
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        borderColor: 'rgba(255, 255, 255, 0.1)',
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? Math.round((context.parsed / total) * 100) : 0;
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });

        // Activity Chart (Bar)
        const activityCtx = document.getElementById('activityChart').getContext('2d');
        new Chart(activityCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($activity_stats, 'type')); ?>,
                datasets: [{
                    label: 'Nombre total',
                    data: <?php echo json_encode(array_column($activity_stats, 'count')); ?>,
                    backgroundColor: <?php echo json_encode(array_column($activity_stats, 'color')); ?>,
                    borderWidth: 0,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        borderColor: 'rgba(255, 255, 255, 0.1)',
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#e2e8f0',
                            stepSize: 1
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#e2e8f0'
                        }
                    }
                }
            }
        });

            </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../public/assets/js/main.js"></script>
</body>
</html>
