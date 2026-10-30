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

// Traitement du formulaire de mise à jour de statut
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_statut'])) {
    $candidature_id = sanitize_input($_POST['candidature_id']);
    $statut = sanitize_input($_POST['statut']);
    
    if (update_candidature_statut($candidature_id, $statut)) {
        $success_message = "Statut mis à jour avec succès !";
    } else {
        $error_message = "Erreur lors de la mise à jour du statut.";
    }
}

// Traitement de la demande de recommandation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_recommendation_request'])) {
    $id_etudiant = sanitize_input($_POST['id_etudiant']);
    $id_offre = sanitize_input($_POST['id_offre']);
    $id_enseignant = sanitize_input($_POST['id_enseignant']);
    $message = sanitize_input($_POST['message'] ?? '');
    
    // Inclure les fonctions de recommandation
    require_once __DIR__ . '/../layouts/components.php';
    
    // Créer la demande de recommandation
    if (create_demande_recommandation($entreprise_id, $id_enseignant, $id_etudiant, $id_offre, $message)) {
        $success_message = "Demande de recommandation envoyée avec succès à l'enseignant !";
    } else {
        $error_message = "Erreur lors de l'envoi de la demande de recommandation.";
    }
}

// Obtenir toutes les candidatures de l'entreprise
$candidatures = get_entreprise_candidatures($entreprise_id);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidats - ESEN Connect</title>
    <meta name="description" content="Gérez les candidatures reçues">
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
            <div class="cand-page-header">
                <div class="cand-header-left">
                    <h1><i class="fas fa-users"></i> Candidats</h1>
                    <p>Gérez les candidatures reçues</p>
                </div>
            </div>

            <?php
            $total_cand = count($candidatures);
            $en_cours = 0;
            $accepte = 0;
            $refuse = 0;
            foreach ($candidatures as $c) {
                if ($c['statut'] === 'EN_COURS') $en_cours++;
                elseif ($c['statut'] === 'ACCEPTE') $accepte++;
                elseif ($c['statut'] === 'REFUSE') $refuse++;
            }
            ?>

            <!-- Stats Row -->
            <div class="cand-stats-row">
                <div class="cand-stat-chip">
                    <div class="cand-stat-icon blue"><i class="fas fa-users"></i></div>
                    <div class="cand-stat-data">
                        <span class="cand-stat-number"><?php echo $total_cand; ?></span>
                        <span class="cand-stat-label">Total</span>
                    </div>
                </div>
                <div class="cand-stat-chip">
                    <div class="cand-stat-icon amber"><i class="fas fa-hourglass-half"></i></div>
                    <div class="cand-stat-data">
                        <span class="cand-stat-number"><?php echo $en_cours; ?></span>
                        <span class="cand-stat-label">En attente</span>
                    </div>
                </div>
                <div class="cand-stat-chip">
                    <div class="cand-stat-icon green"><i class="fas fa-check-circle"></i></div>
                    <div class="cand-stat-data">
                        <span class="cand-stat-number"><?php echo $accepte; ?></span>
                        <span class="cand-stat-label">Acceptés</span>
                    </div>
                </div>
                <div class="cand-stat-chip">
                    <div class="cand-stat-icon red"><i class="fas fa-times-circle"></i></div>
                    <div class="cand-stat-data">
                        <span class="cand-stat-number"><?php echo $refuse; ?></span>
                        <span class="cand-stat-label">Refusés</span>
                    </div>
                </div>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="cand-alert success"><i class="fas fa-check-circle"></i> <?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if (isset($error_message)): ?>
                <div class="cand-alert error"><i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?></div>
            <?php endif; ?>

            <!-- Toolbar -->
            <div class="cand-toolbar">
                <div class="cand-search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" class="cand-search-input" placeholder="Rechercher un candidat...">
                </div>
                <div class="cand-filter-tabs">
                    <button class="cand-filter-tab active" data-filter="all">
                        <i class="fas fa-th-large"></i> Tous
                    </button>
                    <button class="cand-filter-tab" data-filter="EN_COURS">
                        <i class="fas fa-hourglass-half"></i> En attente
                    </button>
                    <button class="cand-filter-tab" data-filter="ACCEPTE">
                        <i class="fas fa-check-circle"></i> Acceptés
                    </button>
                    <button class="cand-filter-tab" data-filter="REFUSE">
                        <i class="fas fa-times-circle"></i> Refusés
                    </button>
                </div>
            </div>

            <!-- Candidates Grid -->
            <?php if (!empty($candidatures)): ?>
                <div class="cand-grid">
                    <?php foreach ($candidatures as $candidature): ?>
                        <?php
                        $status_class = $candidature['statut'] === 'EN_COURS' ? 'pending' :
                            ($candidature['statut'] === 'ACCEPTE' ? 'accepted' : 'rejected');
                        $initials = strtoupper(substr($candidature['prenom'], 0, 1) . substr($candidature['nom'], 0, 1));
                        ?>
                        <div class="cand-card" data-status="<?php echo $candidature['statut']; ?>">
                            <div class="cand-card-top">
                                <div class="cand-avatar <?php echo $status_class; ?>">
                                    <span><?php echo $initials; ?></span>
                                </div>
                                <span class="cand-status-badge <?php echo $status_class; ?>">
                                    <span class="cand-status-dot"></span>
                                    <?php
                                    echo $candidature['statut'] === 'EN_COURS' ? 'En attente' :
                                        ($candidature['statut'] === 'ACCEPTE' ? 'Accepté' : 'Refusé');
                                    ?>
                                </span>
                            </div>

                            <div class="cand-card-body">
                                <h3 class="cand-name"><?php echo htmlspecialchars($candidature['prenom'] . ' ' . $candidature['nom']); ?></h3>
                                <div class="cand-offre">
                                    <i class="fas fa-briefcase"></i>
                                    <span><?php echo htmlspecialchars($candidature['offre_titre']); ?></span>
                                </div>
                                <div class="cand-spec">
                                    <i class="fas fa-graduation-cap"></i>
                                    <span><?php echo htmlspecialchars($candidature['specialite']); ?></span>
                                </div>
                                <div class="cand-date">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Postulé le <?php echo date('d M Y', strtotime($candidature['datePost'])); ?></span>
                                </div>
                            </div>

                            <div class="cand-card-actions">
                                <form method="POST" action="" class="cand-status-form">
                                    <input type="hidden" name="update_statut" value="1">
                                    <input type="hidden" name="candidature_id" value="<?php echo $candidature['id']; ?>">
                                    <select name="statut" class="cand-status-select" onchange="this.form.submit()">
                                        <option value="EN_COURS" <?php echo $candidature['statut'] === 'EN_COURS' ? 'selected' : ''; ?>>⏳ En attente</option>
                                        <option value="ACCEPTE" <?php echo $candidature['statut'] === 'ACCEPTE' ? 'selected' : ''; ?>>✅ Accepter</option>
                                        <option value="REFUSE" <?php echo $candidature['statut'] === 'REFUSE' ? 'selected' : ''; ?>>❌ Refuser</option>
                                    </select>
                                </form>
                                
                                <?php 
// Débogage - afficher le statut de chaque candidature
error_log('Candidature ID: ' . $candidature['id'] . ', Statut: ' . $candidature['statut']);
?>

<?php if ($candidature['statut'] === 'ACCEPTE' || $candidature['statut'] === 'EN_COURS'): ?>
                                    <a href="recommendation-requests.php" class="recommendation-btn">
                                        <i class="fas fa-star"></i>
                                        Demander recommandation
                                    </a>
                                <?php else: ?>
                                    <small class="text-muted">Statut: <?php echo $candidature['statut']; ?> (Acceptez la candidature pour demander une recommandation)</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="cand-empty-state">
                    <div class="cand-empty-icon">
                        <i class="fas fa-user-friends"></i>
                    </div>
                    <h3>Aucune candidature reçue</h3>
                    <p>Les candidatures apparaîtront ici lorsque des étudiants postuleront à vos offres</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal pour demander une recommandation -->
    <div class="modal fade" id="recommendationModal" tabindex="-1" aria-labelledby="recommendationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="recommendationModalLabel">
                        <i class="fas fa-star"></i> Demander une recommandation
                    </h5>
                    <button type="button" class="btn-close" onclick="closeRecommendationModal()" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Étudiant</label>
                            <input type="text" class="form-control" id="studentInfo" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Offre</label>
                            <input type="text" class="form-control" id="offreInfo" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="id_enseignant" class="form-label">Enseignant *</label>
                            <select class="form-select" id="id_enseignant" name="id_enseignant" required>
                                <option value="">Sélectionner un enseignant</option>
                                <?php 
                                $enseignants = get_all_teachers();
                                foreach ($enseignants as $enseignant): ?>
                                    <option value="<?php echo $enseignant['id']; ?>">
                                        <?php echo htmlspecialchars($enseignant['nom'] . ' ' . $enseignant['prenom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message pour l'enseignant</label>
                            <textarea class="form-control" id="message" name="message" rows="4" placeholder="Expliquez pourquoi vous recommandez cet étudiant..."></textarea>
                        </div>
                        <input type="hidden" name="id_etudiant" id="id_etudiant">
                        <input type="hidden" name="id_offre" id="id_offre">
                        <input type="hidden" name="create_recommendation_request" value="1">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeRecommendationModal()">Annuler</button>
                        <button type="submit" class="btn btn-primary">
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
        function openRecommendationModal(studentId, offreId) {
            console.log('Opening recommendation modal for student:', studentId, 'offre:', offreId);
            
            // Remplir les informations dans le modal
            document.getElementById('id_etudiant').value = studentId;
            document.getElementById('id_offre').value = offreId;
            document.getElementById('studentInfo').value = 'Étudiant #' + studentId;
            document.getElementById('offreInfo').value = 'Offre #' + offreId;
            
            // Ouvrir le modal avec plusieurs méthodes
            const modalElement = document.getElementById('recommendationModal');
            console.log('Modal element found:', modalElement);
            
            if (modalElement) {
                // Méthode 1: Bootstrap Modal
                try {
                    if (typeof bootstrap !== 'undefined') {
                        const modal = new bootstrap.Modal(modalElement);
                        modal.show();
                        console.log('Bootstrap modal opened successfully');
                        return;
                    }
                } catch (error) {
                    console.log('Bootstrap modal failed, trying manual method:', error);
                }
                
                // Méthode 2: Manuel avec classes Bootstrap
                try {
                    modalElement.classList.add('show');
                    modalElement.style.display = 'block';
                    document.body.classList.add('modal-open');
                    
                    // Créer le backdrop
                    const backdrop = document.createElement('div');
                    backdrop.className = 'modal-backdrop fade show';
                    backdrop.id = 'modal-backdrop';
                    document.body.appendChild(backdrop);
                    
                    console.log('Manual modal opened successfully');
                    return;
                } catch (error) {
                    console.error('Manual modal failed:', error);
                }
                
                // Méthode 3: Simple display
                modalElement.style.display = 'block';
                alert('Modal affiché en mode simple. Veuillez utiliser la croix pour fermer.');
            } else {
                console.error('Modal element not found!');
                alert('Erreur: Modal introuvable');
            }
        }
        
        // Fonction pour fermer le modal manuellement
        function closeRecommendationModal() {
            const modalElement = document.getElementById('recommendationModal');
            const backdrop = document.getElementById('modal-backdrop');
            
            if (modalElement) {
                modalElement.classList.remove('show');
                modalElement.style.display = 'none';
            }
            
            if (backdrop) {
                backdrop.remove();
            }
            
            document.body.classList.remove('modal-open');
        }
        
        // Fonctions de test pour débogage
        function testClick() {
            console.log('TEST CLIQUE FONCTIONNE!');
            alert('Le clique fonctionne! Test modal maintenant...');
            testModal(1, 1);
        }
        
        function testModal(studentId, offreId) {
            console.log('TEST MODAL - studentId:', studentId, 'offreId:', offreId);
            
            // Vérifier si l'élément modal existe
            const modalElement = document.getElementById('recommendationModal');
            console.log('Modal element:', modalElement);
            
            if (!modalElement) {
                console.error('ERREUR: Modal element non trouvé!');
                alert('ERREUR: Modal non trouvé!');
                return;
            }
            
            // Vérifier si les champs existent
            const studentField = document.getElementById('id_etudiant');
            const offreField = document.getElementById('id_offre');
            console.log('Champs trouvés:', studentField, offreField);
            
            if (!studentField || !offreField) {
                console.error('ERREUR: Champs du formulaire non trouvés!');
                alert('ERREUR: Champs non trouvés!');
                return;
            }
            
            // Remplir les champs
            studentField.value = studentId;
            offreField.value = offreId;
            console.log('Champs remplis');
            
            // Afficher le modal en mode simple
            modalElement.style.display = 'block';
            modalElement.style.zIndex = '9999';
            modalElement.style.position = 'fixed';
            modalElement.style.top = '50%';
            modalElement.style.left = '50%';
            modalElement.style.transform = 'translate(-50%, -50%)';
            modalElement.style.backgroundColor = 'white';
            modalElement.style.padding = '20px';
            modalElement.style.border = '2px solid red';
            modalElement.style.borderRadius = '10px';
            
            console.log('Modal affiché en mode test');
            alert('Modal affiché en mode test! Regardez la page.');
        }
        
        // Test au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('=== DÉMARRAGE DU DÉBOGAGE ===');
            console.log('Page loaded, checking modal element:', document.getElementById('recommendationModal'));
            console.log('Bootstrap available:', typeof bootstrap !== 'undefined');
            console.log('openRecommendationModal function exists:', typeof openRecommendationModal);
            
            // Test automatique après 2 secondes
            setTimeout(function() {
                console.log('Test automatique...');
                const modalElement = document.getElementById('recommendationModal');
                if (modalElement) {
                    console.log('Modal trouvé:', modalElement);
                } else {
                    console.error('Modal NON TROUVÉ!');
                }
            }, 2000);
        });
    </script>
</body>
</html>
