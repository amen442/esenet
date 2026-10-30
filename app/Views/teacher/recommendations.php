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

// Obtenir les étudiants de l'enseignant
$students = get_teacher_students($teacher_id);

// Obtenir les demandes de recommandation reçues
$demandes_recommandation = get_demandes_recommandation_enseignant($teacher_id);

// Obtenir les statistiques dynamiques des recommandations
$stats = get_recommendations_stats($teacher_id);

// Obtenir les recommandations détaillées
$recommendations_details = get_teacher_recommendations_details($teacher_id, 10);

// Traitement du formulaire de recommandation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_recommendation'])) {
    $student_id = $_POST['student_id'] ?? null;
    $message = $_POST['message'] ?? '';
    $score = $_POST['score'] ?? 0;
    
    if ($student_id && !empty($message)) {
        try {
            // Insérer la recommandation (permettre plusieurs recommandations)
            $sql = "INSERT INTO recommandation (profil, contenu, dateRec, idEnseignant, idEtudiant) 
                    VALUES (?, ?, CURDATE(), ?, ?)";
            $stmt = $conn->prepare($sql);
            $profil = "Recommandation";
            $stmt->execute([$profil, $message, $teacher_id, $student_id]);
            
            // Mettre à jour directement le score dans la table badge (faire la somme)
            try {
                // Vérifier si l'étudiant a déjà un badge
                $sql_check = "SELECT id, score FROM badge WHERE idEtudiant = ?";
                $stmt_check = $conn->prepare($sql_check);
                $stmt_check->execute([$student_id]);
                $result = $stmt_check->fetch();
                
                if ($result) {
                    // Mettre à jour le score existant (faire la somme)
                    $row = $result;
                    $old_score = $row['score'];
                    $new_total_score = $old_score + $score;
                    
                    $sql_update = "UPDATE badge SET score = ? WHERE idEtudiant = ?";
                    $stmt_update = $conn->prepare($sql_update);
                    $stmt_update->execute([$new_total_score, $student_id]);
                    
                    error_log("Badge mis à jour (somme): étudiant $student_id, ancien: $old_score, ajout: $score, total: $new_total_score");
                } else {
                    // Créer un nouveau badge
                    $sql_insert = "INSERT INTO badge (score, idEtudiant) VALUES (?, ?)";
                    $stmt_insert = $conn->prepare($sql_insert);
                    $stmt_insert->execute([$score, $student_id]);
                    
                    error_log("Nouveau badge créé: étudiant $student_id, score: $score");
                }
                
            } catch (Exception $e) {
                error_log("Erreur directe badge: " . $e->getMessage());
            }
            
            // Rediriger avec un message de succès
            header("Location: recommendations.php?success=1");
            exit();
        } catch (Exception $e) {
            $error = "Erreur lors de la création de la recommandation : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recommandations - ESEN NET</title>
    <meta name="description" content="Recommandez vos étudiants pour des offres">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../public/assets/css/main.css">
    <link rel="stylesheet" href="../../public/assets/css/teacher-navbar.css">
    <link rel="stylesheet" href="../../public/assets/css/teacher-dashboard.css">
    <link rel="stylesheet" href="../../public/assets/css/entreprise-events.css">
    <link rel="stylesheet" href="../../public/assets/css/student-navbar.css">
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
                        const company = card.querySelector('.evt-card-company').textContent.toLowerCase();
                        if (title.includes(query) || company.includes(query)) {
                            card.style.display = '';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            }
        });
    </script>
    
    <style>
        /* Styles pour la nouvelle structure des recommandations */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .section-header h3 {
            margin: 0;
            color: #2c3e50;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .section-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .badge-count {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .recommendations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }
        
        .recommendation-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .recommendation-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
            border-color: #667eea;
        }
        
        .recommendation-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .student-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .student-avatar {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }
        
        .student-details h4 {
            margin: 0 0 0.25rem 0;
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .student-promotion {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #6c757d;
            font-size: 0.875rem;
        }
        
        .recommendation-meta {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.5rem;
        }
        
        .recommendation-date {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6c757d;
            font-size: 0.875rem;
        }
        
        .recent-badge {
            background: #28a745;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .recommendation-content {
            margin-bottom: 1.5rem;
        }
        
        .content-text {
            color: #495057;
            line-height: 1.6;
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }
        
        .entreprise-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6c757d;
            font-size: 0.875rem;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .recommendation-actions {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        
        .action-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            border: none;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        
        .action-btn.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .action-btn.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .action-btn.secondary {
            background: #f8f9fa;
            color: #6c757d;
            border: 1px solid #e9ecef;
        }
        
        .action-btn.secondary:hover {
            background: #e9ecef;
            color: #495057;
        }
        
        .recommendation-status {
            position: absolute;
            top: 1rem;
            right: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #28a745;
            animation: pulse 2s infinite;
        }
        
        .status-text {
            font-size: 0.75rem;
            color: #28a745;
            font-weight: 500;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(40, 167, 69, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
            }
        }
        
        /* Dark Mode Styles */
        body.dark .section-header {
            border-bottom-color: #495057;
        }
        
        body.dark .section-header h3 {
            color: #e9ecef;
        }
        
        body.dark .recommendation-card {
            background: #2c3e50;
            border-color: #495057;
        }
        
        body.dark .recommendation-card:hover {
            border-color: #667eea;
        }
        
        body.dark .student-details h4 {
            color: #e9ecef;
        }
        
        body.dark .student-promotion {
            color: #adb5bd;
        }
        
        body.dark .recommendation-date {
            color: #adb5bd;
        }
        
        body.dark .content-text {
            color: #e9ecef;
        }
        
        body.dark .entreprise-info {
            background: #34495e;
            color: #adb5bd;
        }
        
        body.dark .action-btn.secondary {
            background: #34495e;
            color: #adb5bd;
            border-color: #495057;
        }
        
        body.dark .action-btn.secondary:hover {
            background: #495057;
            color: #e9ecef;
        }
        
        body.dark .status-text {
            color: #28a745;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .recommendations-grid {
                grid-template-columns: 1fr;
            }
            
            .recommendation-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .recommendation-meta {
                align-items: flex-start;
            }
            
            .recommendation-actions {
                flex-direction: column;
            }
            
            .action-btn {
                justify-content: center;
            }
        }
    </style>
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
                    <h1><i class="fas fa-user-graduate"></i> Recommandations</h1>
                    <p>Recommandez vos étudiants pour des offres</p>
                </div>
            </div>

            <?php
            // Utiliser les statistiques dynamiques de la base de données
            $total_students = $stats['total_students'];
            $total_demandes = $stats['requests_received'];
            $total_candidatures = $stats['total_applications'];
            $recommendations_made = $stats['recommendations_made'];
            $recent_recommendations = $stats['recent_recommendations'];
            ?>

            <!-- Stats Row -->
            <div class="evt-stats-row">
                <div class="evt-stat-chip">
                    <div class="evt-stat-icon blue"><i class="fas fa-user-graduate"></i></div>
                    <div class="evt-stat-data">
                        <span class="evt-stat-number"><?php echo $total_students; ?></span>
                        <span class="evt-stat-label">Étudiants</span>
                    </div>
                </div>
                <div class="evt-stat-chip">
                    <div class="evt-stat-icon green"><i class="fas fa-star"></i></div>
                    <div class="evt-stat-data">
                        <span class="evt-stat-number"><?php echo $recommendations_made; ?></span>
                        <span class="evt-stat-label">Recommandations faites</span>
                    </div>
                </div>
                <div class="evt-stat-chip">
                    <div class="evt-stat-icon orange"><i class="fas fa-briefcase"></i></div>
                    <div class="evt-stat-data">
                        <span class="evt-stat-number"><?php echo $total_candidatures; ?></span>
                        <span class="evt-stat-label">Candidatures totales</span>
                    </div>
                </div>
                <div class="evt-stat-chip">
                    <div class="evt-stat-icon purple"><i class="fas fa-clock"></i></div>
                    <div class="evt-stat-data">
                        <span class="evt-stat-number"><?php echo $recent_recommendations; ?></span>
                        <span class="evt-stat-label">Récentes (30j)</span>
                    </div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="evt-toolbar">
                <div class="evt-search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" class="evt-search-input" placeholder="Rechercher un étudiant...">
                </div>
                <div class="evt-filter-tabs">
                    <button class="evt-filter-tab active" data-filter="all">
                        <i class="fas fa-th-large"></i> Tous
                    </button>
                    <button class="evt-filter-tab" data-filter="recommended">
                        <i class="fas fa-star"></i> Recommandés
                    </button>
                    <button class="evt-filter-tab" data-filter="not-recommended">
                        <i class="fas fa-star-o"></i> Non recommandés
                    </button>
                </div>
            </div>

            
            <!-- Students Grid -->
            <?php if (!empty($students)): ?>
                <div class="evt-grid">
                    <?php foreach ($students as $student): ?>
                        <?php
                        // Vérifier si l'étudiant a des demandes de recommandation
                        $has_demandes = false;
                        foreach ($demandes_recommandation as $demande) {
                            if ($demande['idEtudiant'] == $student['id']) {
                                $has_demandes = true;
                                break;
                            }
                        }
                        $status_key = $has_demandes ? 'has-demandes' : 'no-demandes';
                        ?>
                        <div class="evt-card" data-status="<?php echo $status_key; ?>">
                            <div class="evt-card-banner evt-card-banner-placeholder">
                                <div class="evt-card-banner-icon">
                                    <i class="fas fa-user-graduate"></i>
                                </div>
                            </div>

                            <div class="evt-card-body">
                                <div class="evt-card-top">
                                    <span class="evt-card-status <?php echo $status_key; ?>">
                                        <span class="evt-status-dot"></span>
                                        <?php echo $has_demandes ? 'Demandes reçues' : 'Aucune demande'; ?>
                                    </span>
                                </div>
                                <span class="evt-card-capacity">
                                    <i class="fas fa-briefcase"></i> <?php echo $student['nb_candidatures'] ?? 0; ?> candidature(s)
                                </span>

                                <div class="evt-card-lieu evt-card-specialty">
                                    <i class="fas fa-graduation-cap"></i>
                                    <span><?php echo htmlspecialchars($student['specialite'] ?? 'Non spécifié'); ?></span>
                                </div>

                                <p class="evt-card-desc"><?php echo htmlspecialchars($student['niveauEtude'] ?? 'Non spécifié'); ?></p>
                                
                                <div class="evt-card-badge">
                                    <span class="badge-score">
                                        <i class="fas fa-star"></i> Score: <?php echo number_format($student['badge_score'] ?? 0, 1); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="evt-card-actions">
                                <?php 
                                // Vérifier si cet étudiant a déjà une recommandation
                                $student_recommendations = get_student_recommendations($student['id'], $teacher_id);
                                $has_student_recommendation = !empty($student_recommendations);
                                ?>
                                <?php if (!$has_student_recommendation): ?>
                                    <button class="evt-card-btn recommend" onclick="openRecommendModal(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['prenom'] . ' ' . $student['nom']); ?>')">
                                        <i class="fas fa-star"></i> Recommander
                                    </button>
                                <?php else: ?>
                                    <button class="evt-card-btn view" onclick="viewRecommendation(<?php echo $student['id']; ?>)">
                                        <i class="fas fa-eye"></i> Voir recommandation
                                    </button>
                                    <button class="evt-card-btn recommend" onclick="openRecommendModal(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['prenom'] . ' ' . $student['nom']); ?>')">
                                        <i class="fas fa-plus"></i> Ajouter recommandation
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="evt-empty-state">
                    <div class="evt-empty-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h3>Aucun étudiant assigné</h3>
                    <p>Vous n'avez pas encore d'étudiants assignés</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

<?php include 'notification-box.php'; ?>

    <!-- Modal de recommandation -->
    <div class="modal fade evt-modal" id="recommendModal" tabindex="-1" aria-labelledby="recommendModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="evt-modal-content">
                <div class="evt-modal-header">
                    <div class="evt-modal-header-icon"><i class="fas fa-star"></i></div>
                    <div>
                        <h5 class="evt-modal-title" id="recommendModalLabel">Recommander un étudiant</h5>
                        <p class="evt-modal-subtitle">Écrivez une recommandation pour cet étudiant</p>
                    </div>
                    <button type="button" class="evt-modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="fas fa-times"></i></button>
                </div>
                <div class="evt-modal-body">
                    <form method="POST" action="">
                        <input type="hidden" name="create_recommendation" value="1">
                        <input type="hidden" name="student_id" id="studentId">
                        <div class="evt-form-group">
                            <label class="evt-form-label"><i class="fas fa-user"></i> Étudiant</label>
                            <input type="text" id="studentName" class="evt-form-input" readonly>
                        </div>
                        <div class="evt-form-group">
                            <label class="evt-form-label"><i class="fas fa-align-left"></i> Message de recommandation</label>
                            <textarea name="message" class="evt-form-textarea" rows="4" placeholder="Décrivez pourquoi vous recommandez cet étudiant..." required></textarea>
                        </div>
                        <div class="evt-form-group">
                            <label class="evt-form-label"><i class="fas fa-star"></i> Note (0-20)</label>
                            <input type="number" name="score" class="evt-form-input" min="0" max="20" step="0.5" placeholder="Note de 0 à 20" required>
                        </div>
                        <div class="evt-form-actions">
                            <button type="button" class="evt-form-btn cancel" data-bs-dismiss="modal"><i class="fas fa-arrow-left"></i> Annuler</button>
                            <button type="submit" class="evt-form-btn submit"><i class="fas fa-paper-plane"></i> Envoyer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour voir les recommandations -->
    <div class="modal fade evt-modal" id="viewRecommendationsModal" tabindex="-1" aria-labelledby="viewRecommendationsModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="evt-modal-content">
                <div class="evt-modal-header">
                    <div class="evt-modal-header-icon"><i class="fas fa-eye"></i></div>
                    <div>
                        <h5 class="evt-modal-title" id="viewRecommendationsModalLabel">Historique des recommandations</h5>
                        <p class="evt-modal-subtitle">Toutes les recommandations pour cet étudiant</p>
                    </div>
                    <button type="button" class="evt-modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="fas fa-times"></i></button>
                </div>
                <div class="evt-modal-body">
                    <div id="recommendationsList" class="recommendations-list">
                        <!-- Les recommandations seront insérées ici par JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../public/assets/js/main.js"></script>
    <style>
        .recommendations-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .recommendation-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #4f46e5;
            transition: transform 0.2s ease;
        }
        
        .recommendation-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .recommendation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .recommendation-number {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }
        
        .recommendation-date {
            color: #6c757d;
            font-size: 14px;
            font-weight: 500;
        }
        
        .recommendation-content h6 {
            color: #2c3e50;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .recommendation-content p {
            color: #495057;
            font-size: 15px;
            line-height: 1.6;
            margin: 0;
        }
        
        .recommendation-content p i {
            color: #4f46e5;
            margin-right: 8px;
        }
    </style>
    <script>
        // Stocker les recommandations pour la fonction viewRecommendation
        const recommendations = <?php echo json_encode([]); ?>;
        
        function openRecommendModal(studentId, studentName) {
            document.getElementById('studentId').value = studentId;
            document.getElementById('studentName').value = studentName;
            const modal = new bootstrap.Modal(document.getElementById('recommendModal'));
            modal.show();
        }

        function viewRecommendation(studentId) {
            // Récupérer les recommandations via AJAX
            fetch(`get_recommendations.php?student_id=${studentId}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Data received:', data); // Débogage
                    
                    if (data.success && data.recommendations && data.recommendations.length > 0) {
                        // Afficher les recommandations dans le modal
                        const modal = new bootstrap.Modal(document.getElementById('viewRecommendationsModal'));
                        const recommendationsList = document.getElementById('recommendationsList');
                        
                        // Vider la liste
                        recommendationsList.innerHTML = '';
                        
                        // Ajouter chaque recommandation
                        data.recommendations.forEach((rec, index) => {
                            console.log('Recommendation:', rec); // Débogage
                            
                            const recommendationCard = document.createElement('div');
                            recommendationCard.className = 'recommendation-card';
                            
                            // Utiliser les bons noms de colonnes
                            const date = rec.dateRec || rec.date_creation || new Date().toLocaleDateString('fr-FR');
                            const contenu = rec.contenu || rec.message || 'Aucun contenu';
                            
                            recommendationCard.innerHTML = `
                                <div class="recommendation-header">
                                    <span class="recommendation-number">Recommandation ${index + 1}</span>
                                    <span class="recommendation-date">${new Date(date).toLocaleDateString('fr-FR')}</span>
                                </div>
                                <div class="recommendation-content">
                                    <p><i class="fas fa-align-left"></i> ${contenu}</p>
                                </div>
                            `;
                            recommendationsList.appendChild(recommendationCard);
                        });
                        
                        modal.show();
                    } else {
                        // Afficher un message plus informatif
                        const modal = new bootstrap.Modal(document.getElementById('viewRecommendationsModal'));
                        const recommendationsList = document.getElementById('recommendationsList');
                        recommendationsList.innerHTML = `
                            <div class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Aucune recommandation trouvée</h5>
                                <p class="text-muted">Cet étudiant n'a pas encore de recommandations.</p>
                            </div>
                        `;
                        modal.show();
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors du chargement des recommandations.');
                });
        }
    </script>
</body>
</html>
