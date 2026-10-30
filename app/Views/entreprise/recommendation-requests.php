<?php
require_once __DIR__ . '/../layouts/components.php';
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../layouts/components.php';


// Inclure EntrepriseFunctions.php avec vérification
$entreprise_functions_path = __DIR__ . '/../../Models/EntrepriseFunctions.php';
if (file_exists($entreprise_functions_path)) {
    require_once $entreprise_functions_path;
} else {
    die("Erreur : Fichier EntrepriseFunctions.php introuvable à : " . $entreprise_functions_path);
}

// Vérifier si la fonction existe
if (!function_exists('get_entreprise_candidatures')) {
    die("Erreur : La fonction get_entreprise_candidatures() n'existe pas dans EntrepriseFunctions.php");
}

// Initialiser les variables pour éviter les erreurs
$has_recommendation = false;

// Obtenir l'ID de l'entreprise connectée
$entreprise_id = get_entreprise_id();

   
if (!$entreprise_id) {
    header("Location: ../../login.php");
    exit();
}

// Traitement du formulaire de demande de recommandation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_recommendation_request'])) {
    $id_enseignant = $_POST['id_enseignant'];
    $id_etudiant = $_POST['id_etudiant'];
    $id_offre = $_POST['id_offre'];
    $message = $_POST['message'] ?? '';
    
    // Debug complet des données reçues
    error_log("=== DEBUG FORMULAIRE RECOMMANDATION ===");
    error_log("Entreprise ID: " . $entreprise_id . " (type: " . gettype($entreprise_id) . ")");
    error_log("Enseignant ID: " . $id_enseignant . " (type: " . gettype($id_enseignant) . ")");
    error_log("Étudiant ID: " . $id_etudiant . " (type: " . gettype($id_etudiant) . ")");
    error_log("Offre ID: " . $id_offre . " (type: " . gettype($id_offre) . ")");
    error_log("Message: " . $message);
    error_log("POST data: " . print_r($_POST, true));
    
    // Vérifier si les IDs sont valides
    if (!is_numeric($id_enseignant) || !is_numeric($id_etudiant) || !is_numeric($id_offre)) {
        error_log("❌ IDs non valides - certains ne sont pas numériques");
    }
    
    // Valider les données
    if (empty($id_enseignant) || empty($id_etudiant) || empty($id_offre)) {
        error_log("❌ Validation échouée - champs manquants");
        $_SESSION['error_message'] = "Veuillez remplir tous les champs obligatoires.";
        header("Location: recommendation-requests.php");
        exit();
    }
    
    error_log("✅ Validation OK - tentative de création de demande");
    
    // Vérification manuelle avant d'appeler la fonction
    error_log("=== VÉRIFICATION MANUELLE AVANT CRÉATION ===");
    $manual_check_sql = "SELECT COUNT(*) as count FROM demande_recommandation 
                         WHERE idEntreprise = ? AND idEnseignant = ? AND idEtudiant = ? AND idOffre = ?";
    $manual_stmt = $conn->prepare($manual_check_sql);
    
    if (!$manual_stmt) {
        error_log("❌ Erreur prepare manuel");
        $_SESSION['error_message'] = "Erreur de paramètres lors de la vérification.";
        header("Location: recommendation-requests.php");
        exit();
    }
    
    $manual_stmt->execute([$entreprise_id, $id_enseignant, $id_etudiant, $id_offre]);
    $manual_result = $manual_stmt->fetch();
    $manual_count = $manual_result['count'] ?? 0;
    error_log("Vérification manuelle - Nombre de demandes existantes: " . $manual_count);
    
    // Vérifier la structure de la table
    $structure_sql = "DESCRIBE demande_recommandation";
    $structure_result = $conn->query($structure_sql);
    error_log("Structure de la table demande_recommandation:");
    foreach ($structure_result->fetchAll() as $row) {
        error_log("  Colonne: " . $row['Field'] . " | Type: " . $row['Type'] . " | Null: " . $row['Null']);
    }
    
    // Créer la demande de recommandation avec la nouvelle structure de table
    if (create_demande_recommandation($entreprise_id, $id_enseignant, $id_etudiant, $id_offre, $message)) {
        error_log("✅ Demande créée avec succès");
        $_SESSION['success_message'] = "Demande de recommandation envoyée avec succès à l'enseignant sélectionné !";
    } else {
        error_log("❌ Échec de création de demande");
        $_SESSION['error_message'] = "Erreur lors de l'envoi de la demande ou demande déjà existante pour cette combinaison.";
    }
    
    header("Location: recommendation-requests.php");
    exit();
}

// Récupérer les candidatures pour cette entreprise
$candidatures = get_entreprise_candidatures($entreprise_id);

// Récupérer les enseignants disponibles
$enseignants = get_all_teachers();

// Débogage - afficher les données des candidatures
error_log("Nombre de candidatures: " . count($candidatures ?? []));
error_log("Nombre d'enseignants: " . count($enseignants ?? []));
if (!empty($candidatures)) {
    error_log("Première candidature: " . print_r($candidatures[0], true));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demandes de recommandation - ESEN NET</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../public/assets/css/main.css">
    <link rel="stylesheet" href="../../public/assets/css/entreprise-navbar.css">
    <link rel="stylesheet" href="../../public/assets/css/company-dashboard.css">
    <link rel="stylesheet" href="../../public/assets/css/entreprise-candidates.css">
    <link rel="icon" href="../../public/assets/images/1.jpeg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Styles modernes pour recommendation-requests */
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 3rem;
            margin-bottom: 2rem;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .page-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 500px;
            height: 500px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }

        .hero-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }

        .hero-stats {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .hero-stat {
            background: rgba(255, 255, 255, 0.15);
            padding: 1rem 1.5rem;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .hero-stat-number {
            font-size: 2rem;
            font-weight: 700;
            display: block;
        }

        .hero-stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .action-section {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: 1px solid rgba(0,0,0,0.05);
        }

        .action-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .search-container {
            position: relative;
            flex: 1;
            max-width: 400px;
        }

        .search-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e5e7eb;
            border-radius: 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f9fafb;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
        }

        .filter-pills {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .filter-pill {
            padding: 0.75rem 1.5rem;
            border: 2px solid #e5e7eb;
            background: white;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-pill:hover {
            border-color: #667eea;
            background: #f3f4f6;
        }

        .filter-pill.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }

        .create-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .create-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .requests-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .request-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .request-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .request-card.pending::before {
            background: linear-gradient(90deg, #f59e0b, #f97316);
        }

        .request-card.accepted::before {
            background: linear-gradient(90deg, #10b981, #059669);
        }

        .request-card.rejected::before {
            background: linear-gradient(90deg, #ef4444, #dc2626);
        }

        .request-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .request-meta {
            flex: 1;
        }

        .request-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .request-date {
            font-size: 0.9rem;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-badge.accepted {
            background: #d1fae5;
            color: #065f46;
        }

        .status-badge.rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .request-details {
            margin-bottom: 1rem;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            color: #4b5563;
        }

        .detail-icon {
            width: 20px;
            text-align: center;
            color: #6b7280;
        }

        .request-message {
            background: #f9fafb;
            padding: 1rem;
            border-radius: 12px;
            border-left: 4px solid #667eea;
            margin-bottom: 1rem;
            font-size: 0.95rem;
            color: #4b5563;
            line-height: 1.5;
        }

        .request-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #e5e7eb;
            background: white;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .action-btn:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
        }

        .action-btn.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }

        .action-btn.primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .empty-icon {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }

        .empty-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .empty-text {
            color: #6b7280;
            margin-bottom: 2rem;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-10px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .alert.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Mode sombre */
        body.dark .page-hero {
            background: linear-gradient(135deg, #4c1d95 0%, #5b21b6 100%);
        }

        body.dark .action-section,
        body.dark .request-card,
        body.dark .empty-state {
            background: #1f2937;
            border-color: #374151;
        }

        body.dark .request-title {
            color: #f9fafb;
        }

        body.dark .request-message {
            background: #374151;
            color: #d1d5db;
        }

        body.dark .action-btn {
            background: #374151;
            border-color: #4b5563;
            color: #f9fafb;
        }

        body.dark .action-btn:hover {
            background: #4b5563;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1rem;
            }
            
            .hero-title {
                font-size: 2rem;
            }
            
            .hero-stats {
                gap: 1rem;
            }
            
            .action-header {
                flex-direction: column;
            }
            
            .search-container {
                max-width: 100%;
            }
            
            .requests-grid {
                grid-template-columns: 1fr;
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

    <?php include 'entreprise-navbar.php'; ?>

    <main class="main-content-wrapper">
        <div class="dashboard-container">
            <!-- Header -->
            <div class="cand-page-header">
                <div class="cand-header-left">
                    <h1><i class="fas fa-star"></i> Demandes de recommandation</h1>
                    <p>Gérez vos demandes de recommandation pour les candidats</p>
                </div>
                <button class="cand-header-btn" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i> Nouvelle demande
                </button>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="cand-alert success"><i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="cand-alert error"><i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
            <?php endif; ?>

            <!-- Stats Row -->
            <div class="cand-stats-row">
                <div class="cand-stat-chip">
                    <div class="cand-stat-icon blue"><i class="fas fa-star"></i></div>
                    <div class="cand-stat-data">
                        <span class="cand-stat-number"><?php echo count($enseignants ?? []); ?></span>
                        <span class="cand-stat-label">Enseignants</span>
                    </div>
                </div>
                <div class="cand-stat-chip">
                    <div class="cand-stat-icon green"><i class="fas fa-users"></i></div>
                    <div class="cand-stat-data">
                        <span class="cand-stat-number">
                            <?php 
                            echo count($candidatures ?? []);
                            ?>
                        </span>
                        <span class="cand-stat-label">Candidatures reçues</span>
                    </div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="cand-toolbar">
                <div class="cand-search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" class="cand-search-input" placeholder="Rechercher une demande...">
                </div>
                <div class="cand-filter-tabs">
                    <button class="cand-filter-tab active" data-filter="all">
                        <i class="fas fa-th-large"></i> Toutes
                    </button>
                    <button class="cand-filter-tab" data-filter="EN_ATTENTE">
                        <i class="fas fa-hourglass-half"></i> En attente
                    </button>
                    <button class="cand-filter-tab" data-filter="ACCEPTEE">
                        <i class="fas fa-check-circle"></i> Acceptées
                    </button>
                    <button class="cand-filter-tab" data-filter="REFUSE">
                        <i class="fas fa-times-circle"></i> Refusées
                    </button>
                </div>
            </div>

            <!-- Demands Grid -->
            <?php if (!empty($demandes)): ?>
                <div class="cand-grid">
                    <?php foreach ($demandes as $demande): ?>
                        <?php
                        $status_class = $demande['statut'] === 'EN_ATTENTE' ? 'pending' :
                            ($demande['statut'] === 'ACCEPTEE' ? 'accepted' : 'rejected');
                        $initials = 'DR';
                        ?>
                        <div class="cand-card" data-status="<?php echo $demande['statut']; ?>">
                            <div class="cand-card-top">
                                <div class="cand-avatar <?php echo $status_class; ?>">
                                    <span><?php echo $initials; ?></span>
                                </div>
                                <span class="cand-status-badge <?php echo $status_class; ?>">
                                    <span class="cand-status-dot"></span>
                                    <?php
                                    echo $demande['statut'] === 'EN_ATTENTE' ? 'En attente' :
                                        ($demande['statut'] === 'ACCEPTEE' ? 'Acceptée' : 'Refusée');
                                    ?>
                                </span>
                            </div>

                            <div class="cand-card-body">
                                <h3 class="cand-name"><?php echo htmlspecialchars($demande['offre_titre'] ?? 'Offre non spécifiée'); ?></h3>
                                <div class="cand-offre">
                                    <i class="fas fa-briefcase"></i>
                                    <span>Offre #<?php echo $demande['idOffre'] ?? 'N/A'; ?></span>
                                </div>
                                <div class="cand-spec">
                                    <i class="fas fa-user-graduate"></i>
                                    <span>Étudiant: <?php echo get_student_name($demande['idEtudiant']) ?? 'ID: ' . $demande['idEtudiant']; ?></span>
                                </div>
                                <div class="cand-spec">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                    <span>Enseignant: <?php echo get_teacher_name($demande['idEnseignant']) ?? 'ID: ' . $demande['idEnseignant']; ?></span>
                                </div>
                                <div class="cand-date">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Demande le <?php echo date('d M Y', strtotime($demande['date_demande'])); ?></span>
                                </div>
                                <?php if (!empty($demande['contenu'])): ?>
                                <div class="cand-message">
                                    <i class="fas fa-comment"></i>
                                    <span><?php echo htmlspecialchars(substr($demande['contenu'], 0, 100)) . '...'; ?></span>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="cand-card-actions">
                                <button class="cand-action-btn view" onclick="viewRequest(<?php echo $demande['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="cand-action-btn recommend" onclick="openRecommendationModal(<?php echo $demande['idEtudiant']; ?>, <?php echo $demande['idOffre']; ?>, '<?php echo htmlspecialchars($demande['offre_titre'] ?? 'Offre non spécifiée'); ?>', '<?php echo htmlspecialchars(get_student_name($demande['idEtudiant']) ?? 'Étudiant #' . $demande['idEtudiant']); ?>')">
                                    <i class="fas fa-star"></i>
                                </button>
                                <?php if ($demande['statut'] === 'EN_ATTENTE'): ?>
                                    <button class="cand-action-btn edit" onclick="editRequest(<?php echo $demande['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="cand-empty-state">
                    <div class="cand-empty-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3>Aucune demande de recommandation</h3>
                    <p>Vous n'avez pas encore envoyé de demandes de recommandation</p>
                    <button class="cand-header-btn" onclick="openCreateModal()">
                        <i class="fas fa-plus"></i> Créer votre première demande
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal pour créer une demande de recommandation -->
    <div class="modal fade" id="createRecommendationModal" tabindex="-1" aria-labelledby="createRecommendationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createRecommendationModalLabel">
                        <i class="fas fa-star"></i> Nouvelle demande de recommandation
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <!-- Sélection du couple étudiant-offre -->
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Sélectionnez une candidature :</strong> Choisissez parmi les couples étudiant-offre existants.
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="candidature_select" class="form-label">
                                    <i class="fas fa-users"></i> Candidature (Étudiant + Offre) *
                                </label>
                                <select class="form-select" id="candidature_select" name="candidature_select" required onchange="updateCandidatureInfo()">
                                    <option value="">Sélectionner une candidature</option>
                                    <?php 
                                    // Débogage - afficher le nombre de candidatures
                                    echo "<!-- DEBUG: Nombre de candidatures: " . count($candidatures) . " -->";
                                    echo "<!-- DEBUG: Contenu des candidatures: " . print_r($candidatures, true) . " -->";
                                    
                                    foreach ($candidatures as $candidature): 
                                        // Débogage - afficher les détails de chaque candidature
                                        echo "<!-- DEBUG Candidature ID: " . $candidature['id'] . " -->";
                                        echo "<!-- DEBUG Statut: " . $candidature['statut'] . " -->";
                                        echo "<!-- DEBUG Étudiant: " . ($candidature['nom'] ?? 'N/A') . " " . ($candidature['prenom'] ?? 'N/A') . " -->";
                                        echo "<!-- DEBUG Offre: " . ($candidature['offre_titre'] ?? 'N/A') . " -->";
                                        
                                        $status_text = $candidature['statut'] === 'ACCEPTE' ? 'Acceptée' : 
                                                     ($candidature['statut'] === 'EN_COURS' ? 'En cours' : 
                                                     ($candidature['statut'] === 'EN_ATTENTE' ? 'En attente' : 'Refusée'));
                                        $status_class = $candidature['statut'] === 'ACCEPTE' ? 'text-success' : 
                                                         ($candidature['statut'] === 'EN_COURS' ? 'text-warning' : 
                                                         ($candidature['statut'] === 'EN_ATTENTE' ? 'text-info' : 'text-danger'));
                                        ?>
                                        <?php if ($candidature['statut'] === 'ACCEPTE' || $candidature['statut'] === 'EN_COURS' || $candidature['statut'] === 'EN_ATTENTE'): ?>
                                            <option value="<?php echo $candidature['id']; ?>" 
                                                    data-student-id="<?php echo $candidature['idEtudiant']; ?>"
                                                    data-offre-id="<?php echo $candidature['idOffre']; ?>"
                                                    data-student-name="<?php echo htmlspecialchars(($candidature['nom'] ?? 'Étudiant') . ' ' . ($candidature['prenom'] ?? '')); ?>"
                                                    data-offre-titre="<?php echo htmlspecialchars($candidature['offre_titre'] ?? 'Offre non spécifiée'); ?>">
                                                <?php echo htmlspecialchars(($candidature['nom'] ?? 'Étudiant') . ' ' . ($candidature['prenom'] ?? '')); ?> - 
                                                <?php echo htmlspecialchars($candidature['offre_titre'] ?? 'Offre non spécifiée'); ?>
                                                <span class="<?php echo $status_class; ?>">(<?php echo $status_text; ?>)</span>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Candidatures acceptées, en cours ou en attente sont affichées</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="id_enseignant" class="form-label">
                                    <i class="fas fa-chalkboard-teacher"></i> Enseignant *
                                </label>
                                <select class="form-select" id="id_enseignant" name="id_enseignant" required>
                                    <option value="">Sélectionner un enseignant</option>
                                    <?php foreach ($enseignants as $enseignant): ?>
                                        <option value="<?php echo $enseignant['id']; ?>">
                                            <?php echo htmlspecialchars($enseignant['nom'] . ' ' . $enseignant['prenom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Informations de la candidature sélectionnée -->
                        <div class="row" id="candidature_info" style="display: none;">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-user-graduate"></i> Étudiant sélectionné
                                </label>
                                <input type="text" class="form-control bg-light" id="student_display" readonly>
                                <input type="hidden" id="id_etudiant" name="id_etudiant">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-briefcase"></i> Offre sélectionnée
                                </label>
                                <input type="text" class="form-control bg-light" id="offre_display" readonly>
                                <input type="hidden" id="id_offre" name="id_offre">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-hourglass-half"></i> Statut de la demande
                                </label>
                                <input type="text" class="form-control bg-light" value="En attente" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="message" class="form-label">
                                    <i class="fas fa-comment"></i> Message pour l'enseignant
                                </label>
                                <textarea class="form-control" id="message" name="message" rows="4" placeholder="Expliquez pourquoi vous recommandez cet étudiant..."></textarea>
                            </div>
                        </div>
                        
                        <!-- Résumé de la demande -->
                        <div class="card bg-light" id="demand_summary" style="display: none;">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-clipboard-check"></i> Résumé de la demande
                                </h6>
                                <p class="card-text small">
                                    Vous demandez une recommandation pour <strong id="summary_student">l'étudiant</strong> 
                                    concernant l'offre <strong id="summary_offre">sélectionnée</strong>.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" name="create_recommendation_request" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Envoyer la demande
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../public/assets/js/main.js"></script>
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
                    navbarShowBtn.style.display = 'flex';
                } else {
                    navbarShowBtn.style.display = 'none';
                }
            }

            // Custom Cursor
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
            const interactiveElements = document.querySelectorAll('a, button, .cand-card');
            interactiveElements.forEach(el => {
                el.addEventListener('mouseenter', () => cursor.classList.add('hover'));
                el.addEventListener('mouseleave', () => cursor.classList.remove('hover'));
            });

            document.addEventListener('mousedown', () => cursor.classList.add('clicking'));
            document.addEventListener('mouseup', () => cursor.classList.remove('clicking'));

            // Filter tabs
            const filterTabs = document.querySelectorAll('.cand-filter-tab');
            const candCards = document.querySelectorAll('.cand-card');

            filterTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    filterTabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');

                    const filter = this.dataset.filter;
                    candCards.forEach(card => {
                        if (filter === 'all' || card.dataset.status === filter) {
                            card.style.display = '';
                            card.style.animation = 'candCardIn 0.5s ease forwards';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            });

            // Search
            const searchInput = document.querySelector('.cand-search-input');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const query = this.value.toLowerCase();
                    candCards.forEach(card => {
                        const name = card.querySelector('.cand-name').textContent.toLowerCase();
                        const offre = card.querySelector('.cand-offre').textContent.toLowerCase();
                        const spec = card.querySelector('.cand-spec').textContent.toLowerCase();
                        if (name.includes(query) || offre.includes(query) || spec.includes(query)) {
                            card.style.display = '';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            }
        });

        // Fonctions pour les demandes
        function openCreateModal() {
            // Réinitialiser le formulaire
            document.getElementById('candidature_select').value = '';
            document.getElementById('id_enseignant').value = '';
            document.getElementById('id_etudiant').value = '';
            document.getElementById('id_offre').value = '';
            document.getElementById('student_display').value = '';
            document.getElementById('offre_display').value = '';
            document.getElementById('message').value = '';
            document.getElementById('candidature_info').style.display = 'none';
            document.getElementById('demand_summary').style.display = 'none';
            
            const modal = new bootstrap.Modal(document.getElementById('createRecommendationModal'));
            modal.show();
        }
        
        function updateCandidatureInfo() {
            const select = document.getElementById('candidature_select');
            const selectedOption = select.options[select.selectedIndex];
            
            if (select.value) {
                // Récupérer les données de l'option sélectionnée
                const studentId = selectedOption.dataset.studentId;
                const offreId = selectedOption.dataset.offreId;
                const studentName = selectedOption.dataset.studentName;
                const offreTitre = selectedOption.dataset.offreTitre;
                
                // Mettre à jour les champs cachés
                document.getElementById('id_etudiant').value = studentId;
                document.getElementById('id_offre').value = offreId;
                
                // Mettre à jour les champs d'affichage
                document.getElementById('student_display').value = studentName;
                document.getElementById('offre_display').value = offreTitre;
                
                // Mettre à jour le résumé
                document.getElementById('summary_student').textContent = studentName;
                document.getElementById('summary_offre').textContent = offreTitre;
                
                // Afficher les sections d'information
                document.getElementById('candidature_info').style.display = 'flex';
                document.getElementById('demand_summary').style.display = 'block';
            } else {
                // Masquer les sections si aucune sélection
                document.getElementById('candidature_info').style.display = 'none';
                document.getElementById('demand_summary').style.display = 'none';
                
                // Vider les champs
                document.getElementById('id_etudiant').value = '';
                document.getElementById('id_offre').value = '';
                document.getElementById('student_display').value = '';
                document.getElementById('offre_display').value = '';
            }
        }
        
        function openRecommendationModal(studentId, offreId, offreTitre, studentName) {
            openCreateModal();
            // Préremplir avec les données si fournies
            if (studentId && offreId && offreTitre && studentName) {
                // Trouver l'option correspondante dans le select
                const select = document.getElementById('candidature_select');
                for (let option of select.options) {
                    if (option.dataset.studentId == studentId && option.dataset.offreId == offreId) {
                        select.value = option.value;
                        updateCandidatureInfo();
                        break;
                    }
                }
            }
        }
        
        function viewRequest(id) {
            console.log('Voir la demande:', id);
            // Implémenter la vue de demande
        }
        
        function editRequest(id) {
            console.log('Modifier la demande:', id);
            // Implémenter la modification de demande
        }
    </script>
</body>
</html>
