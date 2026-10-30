<?php
require_once __DIR__ . '/../layouts/components.php';
require_once __DIR__ . '/../../config/db_connect.php';

// Obtenir l'ID de l'étudiant connecté
$etudiant_id = get_etudiant_id();

// Si aucun étudiant n'est connecté, rediriger vers la page de connexion
if (!$etudiant_id) {
    header("Location: ../../login.php");
    exit();
}

// Obtenir les informations de l'étudiant
$etudiant_info = get_etudiant_info($etudiant_id);

// Obtenir les statistiques de l'étudiant
$stats = get_etudiant_stats($etudiant_id);

// Obtenir le score de profil depuis la table badge
$badge_score = 0;
try {
    $stmt = $conn->prepare("SELECT score FROM badge WHERE idEtudiant = ?");
    $stmt->execute([$etudiant_id]);
    $result = $stmt->fetch();
    if ($result) {
        $badge_score = $result['score'];
    }
} catch (Exception $e) {
    $badge_score = 0;
}

// Obtenir les candidatures de l'étudiant
$candidatures = get_etudiant_candidatures($etudiant_id);

// Obtenir les offres disponibles
$offres = get_offres_disponibles(3);

// Obtenir les événements à venir
$events = get_evenements_avenir(3);

// Obtenir les statistiques de candidatures par mois
$candidatures_data = [];
try {
    $stmt = $conn->prepare("SELECT DATE_FORMAT(c.datePost, '%Y-%m') as mois, COUNT(*) as total, SUM(CASE WHEN UPPER(c.statut) = 'ACCEPTE' THEN 1 ELSE 0 END) as acceptees_count 
                            FROM candidature c 
                            WHERE c.idEtudiant = ? 
                            GROUP BY DATE_FORMAT(c.datePost, '%Y-%m') 
                            ORDER BY mois DESC 
                            LIMIT 12");
    $stmt->execute([$etudiant_id]);
    foreach ($stmt->fetchAll() as $row) {
        $candidatures_data[$row['mois']] = [
            'total' => (int)$row['total'],
            'acceptees_count' => (int)$row['acceptees_count']
        ];
    }
    
    // Debug: Afficher les données récupérées
    error_log("Student ID: $etudiant_id");
    error_log("Candidatures data retrieved: " . print_r($candidatures_data, true));
    error_log("Total acceptées found: " . count($candidatures_data));
    
    // Vérifier s'il y a des candidatures acceptées dans la base
    $debug_acceptees = [];
    try {
        $debug_stmt = $conn->prepare("SELECT COUNT(*) as total FROM candidature WHERE idEtudiant = ? AND UPPER(statut) = 'ACCEPTE'");
        $debug_stmt->execute([$etudiant_id]);
        $debug_result = $debug_stmt->fetch();
        error_log("Total acceptées in database: " . $debug_result['total']);
    } catch (Exception $e) {
        error_log("Debug acceptees query error: " . $e->getMessage());
    }
    
    // Vérifier les différents statuts possibles
    $debug_statuts = [];
    try {
        $debug_stmt2 = $conn->prepare("SELECT statut, COUNT(*) as count FROM candidature WHERE idEtudiant = ? GROUP BY statut");
        $debug_stmt2->execute([$etudiant_id]);
        foreach ($debug_stmt2->fetchAll() as $row2) {
            $debug_statuts[$row2['statut']] = $row2['count'];
        }
        error_log("All statuts for student: " . print_r($debug_statuts, true));
    } catch (Exception $e) {
        error_log("Debug statuts query error: " . $e->getMessage());
    }
    
} catch (Exception $e) {
    error_log("Error getting candidatures data: " . $e->getMessage());
    $candidatures_data = [];
}

// Récupérer les contacts du réseau
$network_contacts = [];
try {
    $stmt = $conn->prepare("SELECT p.id, p.nom, p.poste, p.nomsociete, MAX(r.dateAjout) as dateAjout, GROUP_CONCAT(DISTINCT r.statut SEPARATOR ', ') as statuts FROM personne p JOIN relation r ON p.id = r.idPersonne WHERE r.idEtudiant = ? GROUP BY COALESCE(p.cin, p.id) ORDER BY MAX(r.dateAjout) DESC LIMIT 5");
    $stmt->execute([$etudiant_id]);
    foreach ($stmt->fetchAll() as $row) {
        $network_contacts[] = $row;
    }
} catch (Exception $e) {
    $network_contacts = [];
}

// Récupérer les statistiques détaillées pour les graphiques
$chart_stats = [];

// Statistiques des candidatures
$chart_stats['candidatures'] = [
    'acceptees' => 0,
    'en_attente' => 0,
    'rejetees' => 0,
    'total' => 0
];

try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN statut = 'ACCEPTE' THEN 1 ELSE 0 END) as acceptees, SUM(CASE WHEN statut = 'EN_ATTENTE' THEN 1 ELSE 0 END) as en_attente, SUM(CASE WHEN statut = 'REJETE' THEN 1 ELSE 0 END) as rejetees FROM candidature WHERE idEtudiant = ?");
    $stmt->execute([$etudiant_id]);
    $result = $stmt->fetch();
    $chart_stats['candidatures'] = [
        'acceptees' => (int)$result['acceptees'],
        'en_attente' => (int)$result['en_attente'],
        'rejetees' => (int)$result['rejetees'],
        'total' => (int)$result['total']
    ];
} catch (Exception $e) {
    // Valeurs par défaut en cas d'erreur
}

// Statistiques des événements
$chart_stats['events'] = [
    'participes' => 0,
    'avenir' => 0,
    'manques' => 0,
    'total' => 0
];

try {
    // Événements participés (passés - déjà effectués)
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM participation p JOIN evenement e ON p.idEvenement = e.id WHERE p.idEtudiant = ? AND e.dateEvent < NOW()");
    $stmt->execute([$etudiant_id]);
    $result = $stmt->fetch();
    $chart_stats['events']['participes'] = (int)$result['count'];
    
    // Événements à venir (futurs - pas encore effectués)
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM participation p JOIN evenement e ON p.idEvenement = e.id WHERE p.idEtudiant = ? AND e.dateEvent > NOW()");
    $stmt->execute([$etudiant_id]);
    $result = $stmt->fetch();
    $chart_stats['events']['avenir'] = (int)$result['count'];
    
    // Événements manqués (ceux auxquels l'étudiant ne s'est pas inscrit mais qui sont passés)
    // Ou simplement mettre 0 si on ne veut pas compter ça
    $chart_stats['events']['manques'] = 0;
    
    $chart_stats['events']['total'] = $chart_stats['events']['participes'] + $chart_stats['events']['avenir'];
    
} catch (Exception $e) {
    // Valeurs par défaut en cas d'erreur
}

// Statistiques du réseau
$chart_stats['network'] = [
    'contacts' => count($network_contacts),
    'collaborateurs' => 0,
    'mentors' => 0
];

try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM relation r JOIN personne p ON r.idPersonne = p.id WHERE r.idEtudiant = ? AND r.statut = 'contact'");
    $stmt->execute([$etudiant_id]);
    $result = $stmt->fetch();
    $chart_stats['network']['contacts'] = (int)$result['count'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM relation r JOIN personne p ON r.idPersonne = p.id WHERE r.idEtudiant = ? AND r.statut = 'collaborateur'");
    $stmt->execute([$etudiant_id]);
    $result = $stmt->fetch();
    $chart_stats['network']['collaborateurs'] = (int)$result['count'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM relation r JOIN personne p ON r.idPersonne = p.id WHERE r.idEtudiant = ? AND r.statut = 'mentor'");
    $stmt->execute([$etudiant_id]);
    $result = $stmt->fetch();
    $chart_stats['network']['mentors'] = (int)$result['count'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM relation r JOIN personne p ON r.idPersonne = p.id WHERE r.idEtudiant = ? AND r.statut = 'sponsor'");
    $stmt->execute([$etudiant_id]);
    $result = $stmt->fetch();
    $chart_stats['network']['sponsors'] = (int)$result['count'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM relation r JOIN personne p ON r.idPersonne = p.id WHERE r.idEtudiant = ? AND r.statut = 'partenaire'");
    $stmt->execute([$etudiant_id]);
    $result = $stmt->fetch();
    $chart_stats['network']['partenaires'] = (int)$result['count'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM relation r JOIN personne p ON r.idPersonne = p.id WHERE r.idEtudiant = ? AND r.statut = 'speaker'");
    $stmt->execute([$etudiant_id]);
    $result = $stmt->fetch();
    $chart_stats['network']['speakers'] = (int)$result['count'];
    
} catch (Exception $e) {
    // Valeurs par défaut en cas d'erreur
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - ESEN NET</title>
    <meta name="description" content="Votre espace personnel ESEN NET">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../public/assets/css/main.css">
    <link rel="stylesheet" href="../../public/assets/css/student-navbar.css">
    <link rel="stylesheet" href="../../public/assets/css/student-dashboard.css">
    <link rel="icon" href="../../public/assets/images/1.jpeg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* ===== CUSTOM CURSOR ===== */
        * {
            cursor: none;
        }

        .custom-cursor {
            position: fixed;
            top: 0;
            left: 0;
            width: 20px;
            height: 20px;
            border: 2px solid #3b82f6;
            border-radius: 50%;
            pointer-events: none;
            z-index: 9999;
            transition: transform 0.15s ease, background 0.15s ease, border-color 0.15s ease;
            transform: translate(-50%, -50%);
        }

        .custom-cursor-dot {
            position: fixed;
            top: 0;
            left: 0;
            width: 6px;
            height: 6px;
            background: #3b82f6;
            border-radius: 50%;
            pointer-events: none;
            z-index: 10000;
            transform: translate(-50%, -50%);
        }

        .custom-cursor.hover {
            transform: translate(-50%, -50%) scale(1.5);
            background: rgba(59, 130, 246, 0.1);
            border-color: #00d4c4;
        }

        .custom-cursor.clicking {
            transform: translate(-50%, -50%) scale(0.8);
            background: rgba(0, 212, 196, 0.3);
        }

        /* ===== CHARTS STYLES ===== */
        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
        }

        .chart-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 1.5rem;
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .chart-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .chart-card h4 {
            color: #ffffff;
            margin-bottom: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .chart-wrapper {
            position: relative;
            height: 300px;
            margin-top: 1rem;
        }

        .chart-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1rem;
            justify-content: center;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: #e2e8f0;
        }

        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 2px;
        }

        body.dark .chart-card {
            background: rgba(0, 0, 0, 0.3);
            border-color: rgba(255, 255, 255, 0.1);
        }

        body.dark .chart-card h4 {
            color: #ffffff;
        }

        body.dark .legend-item {
            color: #e2e8f0;
        }
    </style>
    <script>
        // Theme Toggle Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.querySelector('.toggle-dark');
            
            // Check for saved theme preference or default to light
            const savedTheme = localStorage.getItem('theme') || 'light';
            if (savedTheme === 'dark') {
                document.body.classList.add('dark');
            }
            
            themeToggle.addEventListener('click', function() {
                document.body.classList.toggle('dark');
                const isDark = document.body.classList.contains('dark');
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
            });

            // Mobile menu toggle
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const verticalNavbar = document.getElementById('verticalNavbar');
            
            if (mobileMenuToggle && verticalNavbar) {
                mobileMenuToggle.addEventListener('click', function() {
                    verticalNavbar.classList.toggle('active');
                });

                // Close menu when clicking outside
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
                    navbarShowBtn.style.display = 'flex';
                } else {
                    navbarShowBtn.style.display = 'none';
                }
            }
            
            // Initialiser l'état du bouton show
            updateNavbarShowBtn();

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

                // Hover effect on interactive elements
                const interactiveElements = document.querySelectorAll('a, button, input, .btn, .stat-card, .dashboard-card, .quick-link, .nav-item');
                interactiveElements.forEach(el => {
                    el.addEventListener('mouseenter', () => cursor.classList.add('hover'));
                    el.addEventListener('mouseleave', () => cursor.classList.remove('hover'));
                });

                // Click effect
                document.addEventListener('mousedown', () => cursor.classList.add('clicking'));
                document.addEventListener('mouseup', () => cursor.classList.remove('clicking'));
            }

            // Initialize Charts
            initializeCharts();
        });

        function initializeCharts() {
            // Chart.js default configuration
            Chart.defaults.color = '#e2e8f0';
            Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.1)';
            
            // Candidatures Simple Counter
            const candidaturesCtx = document.getElementById('candidaturesChart').getContext('2d');
            const totalAcceptees = <?php 
                // Calculer le total des candidatures acceptées
                $total_acceptees = 0;
                foreach ($candidatures_data as $month_data) {
                    $total_acceptees += $month_data['acceptees_count'] ?? 0;
                }
                echo $total_acceptees;
            ?>;
            
            // Créer un affichage simple avec le compteur
            const counterData = {
                labels: ['Total acceptées'],
                datasets: [{
                    label: 'Candidatures acceptées',
                    data: [totalAcceptees],
                    backgroundColor: ['#10b981'],
                    borderColor: ['#10b981'],
                    borderWidth: 2,
                    borderRadius: 8,
                    barThickness: 60
                }]
            };
            
            new Chart(candidaturesCtx, {
                type: 'bar',
                data: counterData,
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
                                    return 'Total acceptées: ' + context.parsed;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: Math.ceil(totalAcceptees * 1.2), // 20% de marge
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

            // Events Chart (Doughnut Chart)
            const eventsCtx = document.getElementById('eventsChart').getContext('2d');
            const eventsData = {
                labels: ['Participés', 'À venir', 'Manqués'],
                datasets: [{
                    data: [
                        <?php echo $chart_stats['events']['participes']; ?>,
                        <?php echo $chart_stats['events']['avenir']; ?>,
                        <?php echo $chart_stats['events']['manques']; ?>
                    ],
                    backgroundColor: ['#3b82f6', '#8b5cf6', '#6b7280'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            };
            
            new Chart(eventsCtx, {
                type: 'doughnut',
                data: eventsData,
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

            // Network Chart (Bar Chart)
            const networkCtx = document.getElementById('networkChart').getContext('2d');
            const networkData = {
                labels: ['Contacts', 'Collaborateurs', 'Mentors', 'Sponsors', 'Partenaires', 'Speakers'],
                datasets: [{
                    label: 'Nombre de contacts',
                    data: [
                        <?php echo $chart_stats['network']['contacts']; ?>,
                        <?php echo $chart_stats['network']['collaborateurs']; ?>,
                        <?php echo $chart_stats['network']['mentors']; ?>,
                        <?php echo $chart_stats['network']['sponsors']; ?>,
                        <?php echo $chart_stats['network']['partenaires']; ?>,
                        <?php echo $chart_stats['network']['speakers']; ?>
                    ],
                    backgroundColor: ['#06b6d4', '#84cc16', '#f97316', '#ec4899', '#8b5cf6', '#f59e0b'],
                    borderWidth: 0,
                    borderRadius: 8
                }]
            };
            
            new Chart(networkCtx, {
                type: 'bar',
                data: networkData,
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

                    }
    </script>
</head>
<body>
    <!-- Animated Background Shapes -->
    <div class="bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-4"></div>
    </div>

    <!-- Custom Cursor -->
    <div class="custom-cursor"></div>
    <div class="custom-cursor-dot"></div>

    <div class="grid-pattern"></div>

    <?php include 'student-navbar.php'; ?>

    <!-- Main Content Container -->
    <main class="main-content-wrapper">
        <!-- Dashboard Section -->
        <div class="dashboard-container">
            <div class="dashboard-header">
                <h1>Tableau de bord</h1>
                <p>Bienvenue sur votre espace personnel</p>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon blue"><i class="fas fa-paper-plane"></i></div>
                    <div class="value"><?php echo $chart_stats['candidatures']['total']; ?></div>
                    <div class="label">Candidatures envoyées</div>
                    <small class="text-success"><?php echo $chart_stats['candidatures']['acceptees']; ?> acceptées</small>
                </div>
                <div class="stat-card">
                    <div class="icon green"><i class="fas fa-calendar-check"></i></div>
                    <div class="value"><?php echo $chart_stats['events']['total']; ?></div>
                    <div class="label">Événements participés</div>
                    <small class="text-success"><?php echo $chart_stats['events']['avenir']; ?> à venir</small>
                </div>
                <div class="stat-card">
                    <div class="icon orange"><i class="fas fa-star"></i></div>
                    <div class="value"><?php echo $badge_score; ?></div>
                    <div class="label">Score de profil</div>
                    <small class="text-success">Points accumulés</small>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="charts-container">
                <!-- Candidatures Simple Counter -->
                <div class="chart-card">
                    <h4><i class="fas fa-chart-bar"></i> Total acceptées</h4>
                    <div class="chart-wrapper">
                        <canvas id="candidaturesChart"></canvas>
                    </div>
                </div>

                <!-- Events Participation Chart -->
                <div class="chart-card">
                    <h4><i class="fas fa-chart-doughnut"></i> Participation aux événements</h4>
                    <div class="chart-wrapper">
                        <canvas id="eventsChart"></canvas>
                    </div>
                    <div class="chart-legend">
                        <div class="legend-item">
                            <div class="legend-color" style="background: #3b82f6;"></div>
                            <span>Participés</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #8b5cf6;"></div>
                            <span>À venir</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #6b7280;"></div>
                            <span>Manqués</span>
                        </div>
                    </div>
                </div>

                <!-- Network Distribution Chart -->
                <div class="chart-card">
                    <h4><i class="fas fa-chart-bar"></i> Répartition du réseau</h4>
                    <div class="chart-wrapper">
                        <canvas id="networkChart"></canvas>
                    </div>
                    <div class="chart-legend">
                        <div class="legend-item">
                            <div class="legend-color" style="background: #06b6d4;"></div>
                            <span>Contacts</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #84cc16;"></div>
                            <span>Collaborateurs</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #f97316;"></div>
                            <span>Mentors</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #ec4899;"></div>
                            <span>Sponsors</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #8b5cf6;"></div>
                            <span>Partenaires</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #f59e0b;"></div>
                            <span>Speakers</span>
                        </div>
                    </div>
                </div>

                            </div>

            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Main Content -->
                <div class="dashboard-card">
                    <h3>Mon réseau</h3>
                    <div class="applications-list">
                        <?php if (!empty($network_contacts)): ?>
                            <?php foreach ($network_contacts as $contact): ?>
                                <div class="application-item" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 12px; padding: 1rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
                                    <div class="application-info" style="flex: 1;">
                                        <h4 style="color: #ffffff; margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($contact['nom']); ?></h4>
                                        <p style="color: #e2e8f0; margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($contact['poste']); ?> - <?php echo htmlspecialchars($contact['nomsociete'] ?? 'Indépendant'); ?></p>
                                        <p style="font-size: 0.8rem; color: #e2e8f0; margin: 0;">
                                            <i class="fas fa-calendar-alt"></i> Ajouté le <?php echo date('d/m/Y', strtotime($contact['dateAjout'])); ?>
                                        </p>
                                    </div>
                                    <span class="application-status accepted" style="text-transform: capitalize; padding: 0.5rem 1rem; background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.3); color: #10b981; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($contact['statuts']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="text-align: center; color: #e2e8f0; padding: 2rem;">Aucun contact dans votre réseau</p>
                        <?php endif; ?>
                    </div>
                    <a href="network.php" class="btn primary" style="margin-top: 1rem;">
                        <i class="fas fa-user-plus"></i> Gérer mon réseau
                    </a>
                </div>

                <!-- Sidebar Content -->
                <div class="dashboard-card">
                    <h3>Mes candidatures</h3>
                    <div class="applications-list">
                        <?php if (!empty($candidatures)): ?>
                            <?php foreach (array_slice($candidatures, 0, 5) as $candidature): ?>
                                <div class="application-item">
                                    <div class="application-info">
                                        <h4><?php echo htmlspecialchars($candidature['offre_titre']); ?></h4>
                                        <p><?php echo htmlspecialchars($candidature['nomsociete']); ?> - <?php echo date('d/m/Y', strtotime($candidature['datePost'])); ?></p>
                                    </div>
                                    <span class="application-status <?php echo strtolower($candidature['statut']); ?>"><?php echo htmlspecialchars($candidature['statut']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>Aucune candidature envoyée</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- Recommendations -->
                <div class="dashboard-card">
                    <h3>Offres recommandées</h3>
                    <div class="recommendations-list">
                        <?php if (!empty($offres)): ?>
                            <?php foreach ($offres as $offre): ?>
                                <div class="recommendation-item">
                                    <h4><?php echo htmlspecialchars($offre['titre']); ?></h4>
                                    <p><?php echo htmlspecialchars($offre['description'] ?? ''); ?></p>
                                    <div class="company"><?php echo htmlspecialchars($offre['nomsociete']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>Aucune offre disponible pour le moment</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="dashboard-card">
                    <h3>Actions rapides</h3>
                    <div class="quick-actions">
                        <a href="offers.php" class="quick-action">
                            <i class="fas fa-briefcase"></i>
                            <span>Consulter les offres</span>
                        </a>
                        <a href="events.php" class="quick-action">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Voir les événements</span>
                        </a>
                        <a href="network.php" class="quick-action">
                            <i class="fas fa-network-wired"></i>
                            <span>Étendre mon réseau</span>
                        </a>
                        <a href="profile.php" class="quick-action primary">
                            <i class="fas fa-user-edit"></i>
                            <span>Mettre à jour mon profil</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="../../public/assets/js/chatbot.js"></script>

    <!-- Chatbot Widget -->
    <div class="chatbot-widget" id="chatbotWidget">
        <div class="chatbot-toggle" id="chatbotToggle">
            <i class="fas fa-robot"></i>
        </div>
        <div class="chatbot-container" id="chatbotContainer">
            <div class="chatbot-header">
                <div class="chatbot-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="chatbot-title">
                    <h4>Assistant ESEN</h4>
                    <span class="status">En ligne</span>
                </div>
                <button class="chatbot-close" id="chatbotClose">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="chatbot-messages" id="chatbotMessages">
                <div class="message bot">
                    <div class="message-avatar">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="message-content">
                        <p>Bonjour ! Je suis votre assistant ESEN. Je peux analyser votre profil et vous recommander les meilleures offres et événements selon vos compétences et badges. Voulez-vous que je fasse une analyse ?</p>
                    </div>
                </div>
            </div>
            <div class="chatbot-input">
                <input type="text" id="chatbotInput" placeholder="Tapez votre message...">
                <button id="chatbotSend">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Chatbot Data -->
    <script>
        // Données de l'étudiant (à remplacer par les vraies données PHP)
        const studentData = {
            badges: <?php echo json_encode($etudiant_info['badges'] ?? ['Débutant']); ?>,
            skills: <?php echo json_encode($etudiant_info['specialite'] ?? 'Informatique'); ?>,
            offers: <?php echo json_encode($offres ?? []); ?>,
            events: <?php echo json_encode($events ?? []); ?>
        };
    </script>

    <!-- Chatbot JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatbotWidget = document.getElementById('chatbotWidget');
            const chatbotToggle = document.getElementById('chatbotToggle');
            const chatbotContainer = document.getElementById('chatbotContainer');
            const chatbotClose = document.getElementById('chatbotClose');
            const chatbotMessages = document.getElementById('chatbotMessages');
            const chatbotInput = document.getElementById('chatbotInput');
            const chatbotSend = document.getElementById('chatbotSend');

            // Toggle chatbot
            chatbotToggle.addEventListener('click', function() {
                chatbotContainer.classList.toggle('active');
                chatbotToggle.classList.toggle('active');
            });

            chatbotClose.addEventListener('click', function() {
                chatbotContainer.classList.remove('active');
                chatbotToggle.classList.remove('active');
            });

            // Send message
            function sendMessage() {
                const message = chatbotInput.value.trim();
                if (!message) return;

                // Add user message
                addMessage(message, 'user');
                chatbotInput.value = '';

                // Process message
                setTimeout(() => {
                    processMessage(message);
                }, 500);
            }

            chatbotSend.addEventListener('click', sendMessage);
            chatbotInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') sendMessage();
            });

            function addMessage(text, sender) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${sender}`;
                
                const avatar = document.createElement('div');
                avatar.className = 'message-avatar';
                avatar.innerHTML = sender === 'bot' ? '<i class="fas fa-robot"></i>' : '<i class="fas fa-user"></i>';
                
                const content = document.createElement('div');
                content.className = 'message-content';
                content.innerHTML = `<p>${text}</p>`;
                
                messageDiv.appendChild(avatar);
                messageDiv.appendChild(content);
                chatbotMessages.appendChild(messageDiv);
                chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
            }

            function processMessage(message) {
                const lowerMessage = message.toLowerCase();
                
                if (lowerMessage.includes('analyse') || lowerMessage.includes('analyse') || lowerMessage.includes('recommandation')) {
                    analyzeProfile();
                } else if (lowerMessage.includes('offre') || lowerMessage.includes('stage')) {
                    recommendOffers();
                } else if (lowerMessage.includes('événement') || lowerMessage.includes('event')) {
                    recommendEvents();
                } else if (lowerMessage.includes('badge') || lowerMessage.includes('compétence')) {
                    showSkills();
                } else if (lowerMessage.includes('oui') || lowerMessage.includes('ok') || lowerMessage.includes('daccord')) {
                    analyzeProfile();
                } else {
                    addMessage("Je peux vous aider avec :<br>• Analyse de votre profil<br>• Recommandations d'offres<br>• Recommandations d'événements<br>• Vos badges et compétences<br><br>Que souhaitez-vous faire ?", 'bot');
                }
            }

            function analyzeProfile() {
                const badges = studentData.badges;
                const skills = studentData.skills;
                
                let response = `<strong>Analyse de votre profil :</strong><br><br>`;
                response += `🏆 <strong>Badges :</strong> ${Array.isArray(badges) ? badges.join(', ') : badges}<br>`;
                response += `💡 <strong>Compétences :</strong> ${skills}<br><br>`;
                response += `Sur cette base, je peux vous recommander les meilleures offres et événements. Voulez-vous voir les recommandations ?`;
                
                addMessage(response, 'bot');
            }

            function recommendOffers() {
                const offers = studentData.offers;
                
                if (offers.length === 0) {
                    addMessage("Aucune offre disponible pour le moment.", 'bot');
                    return;
                }
                
                let response = `<strong>Meilleures offres pour vous :</strong><br><br>`;
                offers.slice(0, 3).forEach((offer, index) => {
                    response += `${index + 1}. <strong>${offer.titre}</strong><br>`;
                    response += `   📍 ${offer.nomsociete} - ${offer.lieu || 'Tunis'}<br>`;
                    response += `   📅 ${offer.type || 'Stage'}<br><br>`;
                });
                
                addMessage(response, 'bot');
            }

            function recommendEvents() {
                const events = studentData.events;
                
                if (events.length === 0) {
                    addMessage("Aucun événement disponible pour le moment.", 'bot');
                    return;
                }
                
                let response = `<strong>Événements recommandés :</strong><br><br>`;
                events.slice(0, 3).forEach((event, index) => {
                    response += `${index + 1}. <strong>${event.titre}</strong><br>`;
                    response += `   📅 ${new Date(event.dateEvent).toLocaleDateString('fr-FR')}<br>`;
                    response += `   📍 ${event.lieu || 'ESEN'}<br><br>`;
                });
                
                addMessage(response, 'bot');
            }

            function showSkills() {
                const badges = studentData.badges;
                const skills = studentData.skills;
                
                let response = `<strong>Vos badges et compétences :</strong><br><br>`;
                response += `🏆 <strong>Badges obtenus :</strong><br>`;
                if (Array.isArray(badges)) {
                    badges.forEach(badge => {
                        response += `   • ${badge}<br>`;
                    });
                } else {
                    response += `   • ${badges}<br>`;
                }
                response += `<br>💡 <strong>Compétence principale :</strong> ${skills}<br><br>`;
                response += `Continuez à développer vos compétences pour débloquer plus de badges !`;
                
                addMessage(response, 'bot');
            }
        });
    </script>

    </body>
</html>
