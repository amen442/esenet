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

// Récupérer les messages de session
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : null;
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : null;

// Effacer les messages de session
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Traitement du formulaire de création d'offre
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_offre'])) {
    $titre = sanitize_input($_POST['titre']);
    $type = sanitize_input($_POST['type']);
    $date_exp = sanitize_input($_POST['date_exp']);
    $image = null;

    // Gestion de l'upload d'image
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../../app/uploads/offres/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($file_extension, $allowed_extensions)) {
            $file_name = uniqid() . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                $image = 'app/uploads/offres/' . $file_name;
            } else {
                $error_message = "Erreur lors de l'upload de l'image.";
            }
        } else {
            $error_message = "Format d'image non autorisé.";
        }
    }

    if (create_offre($entreprise_id, $titre, $type, $date_exp, 'OUVERT', $image)) {
        $_SESSION['success_message'] = "Offre créée avec succès !";
        header("Location: offers.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Erreur lors de la création de l'offre.";
        header("Location: offers.php");
        exit();
    }
}

// Traitement de la modification d'offre
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_offre'])) {
    $offre_id = sanitize_input($_POST['offre_id']);
    $titre = sanitize_input($_POST['titre']);
    $type = sanitize_input($_POST['type']);
    $date_exp = sanitize_input($_POST['date_exp']);
    $statut = sanitize_input($_POST['statut']);
    $image = null;

    // Gestion de l'upload d'image
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../../app/uploads/offres/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
            $image = 'app/uploads/offres/' . $file_name;
        }
    }

    if (update_offre($offre_id, $titre, $type, $date_exp, $statut, $image)) {
        $success_message = "Offre mise à jour avec succès !";
        header("Location: offers.php");
        exit();
    } else {
        $error_message = "Erreur lors de la mise à jour de l'offre.";
        header("Location: offers.php");
        exit();
    }
}

// Traitement de la suppression d'offre
if (isset($_GET['delete_offre'])) {
    $offre_id = sanitize_input($_GET['delete_offre']);
    if (delete_offre($offre_id)) {
        $success_message = "Offre supprimée avec succès !";
        header("Location: offers.php");
        exit();
    } else {
        $error_message = "Erreur lors de la suppression de l'offre.";
        header("Location: offers.php");
        exit();
    }
}

$offres = get_entreprise_offres($entreprise_id);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offres - ESEN NET</title>
    <meta name="description" content="Gérez vos offres de stage">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../public/assets/css/main.css">
    <link rel="stylesheet" href="../../public/assets/css/entreprise-navbar.css">
    <link rel="stylesheet" href="../../public/assets/css/company-dashboard.css">
    <link rel="stylesheet" href="../../public/assets/css/entreprise-offers.css">
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
            const interactiveElements = document.querySelectorAll('a, button, .offer-card-advanced, .offers-btn-create, .offer-form-btn');
            interactiveElements.forEach(el => {
                el.addEventListener('mouseenter', () => cursor.classList.add('hover'));
                el.addEventListener('mouseleave', () => cursor.classList.remove('hover'));
            });

            document.addEventListener('mousedown', () => cursor.classList.add('clicking'));
            document.addEventListener('mouseup', () => cursor.classList.remove('clicking'));

            // Filter tabs
            const filterTabs = document.querySelectorAll('.offers-filter-tab');
            const offerCards = document.querySelectorAll('.offer-card-advanced');
            filterTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    filterTabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    const filter = this.dataset.filter;
                    offerCards.forEach(card => {
                        if (filter === 'all' || card.dataset.status === filter) {
                            card.style.display = '';
                            card.style.animation = 'offerCardIn 0.5s ease forwards';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            });

            // Search
            const searchInput = document.querySelector('.offers-search-input');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const query = this.value.toLowerCase();
                    offerCards.forEach(card => {
                        const title = card.querySelector('.offer-card-title').textContent.toLowerCase();
                        const type = card.querySelector('.offer-card-type').textContent.toLowerCase();
                        if (title.includes(query) || type.includes(query)) {
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
            <div class="offers-page-header">
                <div class="offers-header-left">
                    <h1><i class="fas fa-briefcase"></i> Mes Offres</h1>
                    <p>Gérez et suivez vos offres de stage</p>
                </div>
                <div class="offers-header-right">
                    <button class="offers-btn-create" data-bs-toggle="modal" data-bs-target="#createOfferModal">
                        <i class="fas fa-plus"></i>
                        <span>Nouvelle offre</span>
                    </button>
                </div>
            </div>

            <?php
            $total_offres = count($offres);
            $active_offres = 0;
            $expired_offres = 0;
            $total_candidatures = 0;
            foreach ($offres as $o) {
                $d = new DateTime($o['dateExp']);
                if ($d < new DateTime()) { $expired_offres++; } else { $active_offres++; }
                $total_candidatures += $o['nb_candidatures'];
            }
            ?>

            <!-- Stats Row -->
            <div class="offers-stats-row">
                <div class="offers-stat-chip">
                    <div class="offers-stat-icon blue"><i class="fas fa-briefcase"></i></div>
                    <div class="offers-stat-data">
                        <span class="offers-stat-number"><?php echo $total_offres; ?></span>
                        <span class="offers-stat-label">Total</span>
                    </div>
                </div>
                <div class="offers-stat-chip">
                    <div class="offers-stat-icon green"><i class="fas fa-check-circle"></i></div>
                    <div class="offers-stat-data">
                        <span class="offers-stat-number"><?php echo $active_offres; ?></span>
                        <span class="offers-stat-label">Actives</span>
                    </div>
                </div>
                <div class="offers-stat-chip">
                    <div class="offers-stat-icon red"><i class="fas fa-clock"></i></div>
                    <div class="offers-stat-data">
                        <span class="offers-stat-number"><?php echo $expired_offres; ?></span>
                        <span class="offers-stat-label">Expirées</span>
                    </div>
                </div>
                <div class="offers-stat-chip">
                    <div class="offers-stat-icon purple"><i class="fas fa-users"></i></div>
                    <div class="offers-stat-data">
                        <span class="offers-stat-number"><?php echo $total_candidatures; ?></span>
                        <span class="offers-stat-label">Candidatures</span>
                    </div>
                </div>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="offers-alert success"><i class="fas fa-check-circle"></i> <?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if (isset($error_message)): ?>
                <div class="offers-alert error"><i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?></div>
            <?php endif; ?>

            <!-- Toolbar: Search + Filters -->
            <div class="offers-toolbar">
                <div class="offers-search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" class="offers-search-input" placeholder="Rechercher une offre...">
                </div>
                <div class="offers-filter-tabs">
                    <button class="offers-filter-tab active" data-filter="all">
                        <i class="fas fa-th-large"></i> Toutes
                    </button>
                    <button class="offers-filter-tab" data-filter="active">
                        <i class="fas fa-check-circle"></i> Actives
                    </button>
                    <button class="offers-filter-tab" data-filter="expired">
                        <i class="fas fa-clock"></i> Expirées
                    </button>
                </div>
            </div>

            <!-- Offers Grid -->
            <?php if (!empty($offres)): ?>
                <div class="offers-grid">
                    <?php foreach ($offres as $offre): ?>
                        <?php 
                        $date_exp = new DateTime($offre['dateExp']);
                        $date_pub = new DateTime($offre['datePub']);
                        $today = new DateTime();
                        $is_expired = $date_exp < $today;
                        $days_left = $is_expired ? 0 : $today->diff($date_exp)->days;
                        $total_days = $date_pub->diff($date_exp)->days;
                        $elapsed_days = $date_pub->diff($today)->days;
                        $progress = $total_days > 0 ? min(100, ($elapsed_days / $total_days) * 100) : 100;
                        $status_key = $is_expired ? 'expired' : 'active';
                        ?>
                        <div class="offer-card-advanced" data-status="<?php echo $status_key; ?>">
                            <?php if (!empty($offre['image'])): ?>
                                <div class="offer-card-banner">
                                    <img src="../../../<?php echo htmlspecialchars($offre['image']); ?>" alt="<?php echo htmlspecialchars($offre['titre']); ?>">
                                    <div class="offer-card-banner-overlay"></div>
                                </div>
                            <?php else: ?>
                                <div class="offer-card-banner offer-card-banner-placeholder">
                                    <div class="offer-card-banner-icon">
                                        <i class="fas fa-briefcase"></i>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="offer-card-body">
                                <div class="offer-card-top">
                                    <span class="offer-card-type <?php echo strtolower($offre['type']); ?>">
                                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($offre['type']); ?>
                                    </span>
                                    <span class="offer-card-status <?php echo $status_key; ?>">
                                        <span class="status-dot"></span>
                                        <?php echo $is_expired ? 'Expirée' : 'Active'; ?>
                                    </span>
                                </div>

                                <h3 class="offer-card-title"><?php echo htmlspecialchars($offre['titre']); ?></h3>

                                <div class="offer-card-dates">
                                    <div class="offer-card-date-item">
                                        <i class="fas fa-calendar-plus"></i>
                                        <span><?php echo date('d M Y', strtotime($offre['datePub'])); ?></span>
                                    </div>
                                    <div class="offer-card-date-item">
                                        <i class="fas fa-calendar-times"></i>
                                        <span><?php echo date('d M Y', strtotime($offre['dateExp'])); ?></span>
                                    </div>
                                </div>

                                <!-- Time progress bar -->
                                <div class="offer-card-progress-wrapper">
                                    <div class="offer-card-progress-label">
                                        <span>Temps écoulé</span>
                                        <span><?php echo $is_expired ? 'Expirée' : $days_left . 'j restants'; ?></span>
                                    </div>
                                    <div class="offer-card-progress-bar">
                                        <div class="offer-card-progress-fill <?php echo $progress > 80 ? 'danger' : ($progress > 50 ? 'warning' : 'safe'); ?>" style="width: <?php echo min(100, $progress); ?>%"></div>
                                    </div>
                                </div>

                                <div class="offer-card-candidatures">
                                    <i class="fas fa-users"></i>
                                    <span><strong><?php echo $offre['nb_candidatures']; ?></strong> candidature(s)</span>
                                </div>
                            </div>

                            <div class="offer-card-actions">
                                <?php if (!$is_expired): ?>
                                    <button class="offer-card-btn edit" onclick="openEditOfferModal(<?php echo $offre['id']; ?>, '<?php echo htmlspecialchars($offre['titre']); ?>', '<?php echo htmlspecialchars($offre['type']); ?>', '<?php echo htmlspecialchars($offre['dateExp']); ?>', '<?php echo htmlspecialchars($offre['statut']); ?>', '<?php echo htmlspecialchars($offre['image'] ?? ''); ?>')">
                                        <i class="fas fa-pen"></i> Modifier
                                    </button>
                                <?php endif; ?>
                                <a href="?delete_offre=<?php echo $offre['id']; ?>" class="offer-card-btn delete" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette offre ?');">
                                    <i class="fas fa-trash-alt"></i> Supprimer
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="offers-empty-state">
                    <div class="offers-empty-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <h3>Aucune offre publiée</h3>
                    <p>Commencez par créer votre première offre de stage</p>
                    <button class="offers-btn-create" data-bs-toggle="modal" data-bs-target="#createOfferModal">
                        <i class="fas fa-plus"></i>
                        <span>Créer une offre</span>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal de création d'offre -->
    <div class="modal fade offer-modal" id="createOfferModal" tabindex="-1" aria-labelledby="createOfferModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="offer-modal-content">
                <div class="offer-modal-header">
                    <div class="offer-modal-header-icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <div>
                        <h5 class="offer-modal-title" id="createOfferModalLabel">Nouvelle offre</h5>
                        <p class="offer-modal-subtitle">Créez une nouvelle offre de stage</p>
                    </div>
                    <button type="button" class="offer-modal-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="offer-modal-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="create_offre" value="1">
                        <div class="offer-form-group">
                            <label class="offer-form-label">
                                <i class="fas fa-heading"></i> Titre du poste
                            </label>
                            <input type="text" name="titre" class="offer-form-input" placeholder="Ex: Développeur Full Stack" required>
                        </div>
                        <div class="offer-form-row">
                            <div class="offer-form-group">
                                <label class="offer-form-label">
                                    <i class="fas fa-tag"></i> Type d'offre
                                </label>
                                <select name="type" class="offer-form-select" required>
                                    <option value="">Sélectionner...</option>
                                    <option value="Stage">Stage</option>
                                    <option value="Alternance">Alternance</option>
                                    <option value="Emploi">Emploi</option>
                                </select>
                            </div>
                            <div class="offer-form-group">
                                <label class="offer-form-label">
                                    <i class="fas fa-calendar-alt"></i> Date d'expiration
                                </label>
                                <input type="date" name="date_exp" class="offer-form-input" required>
                            </div>
                        </div>
                        <div class="offer-form-group">
                            <label class="offer-form-label">
                                <i class="fas fa-image"></i> Photo de l'offre
                            </label>
                            <div class="offer-file-upload" id="createFileUpload">
                                <input type="file" name="image" accept="image/*" class="offer-file-input" id="createFileInput">
                                <div class="offer-file-placeholder" id="createFilePlaceholder">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Cliquez ou glissez une image ici</span>
                                    <small>Formats acceptés : JPG, PNG, GIF</small>
                                </div>
                                <div class="offer-file-preview" id="createFilePreview" style="display:none;">
                                    <img id="createPreviewImg" src="" alt="Aperçu">
                                    <button type="button" class="offer-file-remove" id="createFileRemove">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="offer-form-actions">
                            <button type="button" class="offer-form-btn cancel" data-bs-dismiss="modal">
                                <i class="fas fa-arrow-left"></i> Annuler
                            </button>
                            <button type="submit" class="offer-form-btn submit">
                                <i class="fas fa-rocket"></i> Publier l'offre
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de modification d'offre -->
    <div class="modal fade offer-modal" id="editOfferModal" tabindex="-1" aria-labelledby="editOfferModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="offer-modal-content">
                <div class="offer-modal-header edit">
                    <div class="offer-modal-header-icon">
                        <i class="fas fa-pen"></i>
                    </div>
                    <div>
                        <h5 class="offer-modal-title" id="editOfferModalLabel">Modifier l'offre</h5>
                        <p class="offer-modal-subtitle">Modifiez les informations de l'offre</p>
                    </div>
                    <button type="button" class="offer-modal-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="offer-modal-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="update_offre" value="1">
                        <input type="hidden" name="offre_id" id="editOffreId">
                        <div class="offer-form-group">
                            <label class="offer-form-label">
                                <i class="fas fa-heading"></i> Titre du poste
                            </label>
                            <input type="text" name="titre" id="editTitre" class="offer-form-input" placeholder="Titre du poste" required>
                        </div>
                        <div class="offer-form-row">
                            <div class="offer-form-group">
                                <label class="offer-form-label">
                                    <i class="fas fa-tag"></i> Type d'offre
                                </label>
                                <select name="type" id="editType" class="offer-form-select" required>
                                    <option value="">Sélectionner...</option>
                                    <option value="Stage">Stage</option>
                                    <option value="Alternance">Alternance</option>
                                    <option value="Emploi">Emploi</option>
                                </select>
                            </div>
                            <div class="offer-form-group">
                                <label class="offer-form-label">
                                    <i class="fas fa-calendar-alt"></i> Date d'expiration
                                </label>
                                <input type="date" name="date_exp" id="editDateExp" class="offer-form-input" required>
                            </div>
                        </div>
                        <div class="offer-form-row">
                            <div class="offer-form-group">
                                <label class="offer-form-label">
                                    <i class="fas fa-toggle-on"></i> Statut
                                </label>
                                <select name="statut" id="editStatut" class="offer-form-select" required>
                                    <option value="OUVERT">Ouvert</option>
                                    <option value="FERME">Fermé</option>
                                </select>
                            </div>
                            <div class="offer-form-group">
                                <label class="offer-form-label">
                                    <i class="fas fa-image"></i> Photo de l'offre
                                </label>
                                <div class="offer-file-upload" id="editFileUpload">
                                    <input type="file" name="image" accept="image/*" class="offer-file-input" id="editFileInput">
                                    <div class="offer-file-placeholder" id="editFilePlaceholder">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <span>Cliquez ou glissez une image</span>
                                        <small>JPG, PNG, GIF</small>
                                    </div>
                                    <div class="offer-file-preview" id="editFilePreview" style="display:none;">
                                        <img id="editPreviewImg" src="" alt="Aperçu">
                                        <button type="button" class="offer-file-remove" id="editFileRemove">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="offer-form-actions">
                            <button type="button" class="offer-form-btn cancel" data-bs-dismiss="modal">
                                <i class="fas fa-arrow-left"></i> Annuler
                            </button>
                            <button type="submit" class="offer-form-btn submit edit">
                                <i class="fas fa-save"></i> Mettre à jour
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../public/assets/js/main.js"></script>
    <script>
        function openEditOfferModal(offreId, titre, type, dateExp, statut, image) {
            document.getElementById('editOffreId').value = offreId;
            document.getElementById('editTitre').value = titre;
            document.getElementById('editType').value = type;
            document.getElementById('editDateExp').value = dateExp;
            document.getElementById('editStatut').value = statut;

            // Afficher l'image existante si elle existe
            const editFilePlaceholder = document.getElementById('editFilePlaceholder');
            const editFilePreview = document.getElementById('editFilePreview');
            const editPreviewImg = document.getElementById('editPreviewImg');
            if (image && editPreviewImg) {
                editPreviewImg.src = '../../../' + image;
                editFilePlaceholder.style.display = 'none';
                editFilePreview.style.display = 'flex';
            } else {
                editFilePlaceholder.style.display = 'flex';
                editFilePreview.style.display = 'none';
            }

            const modal = new bootstrap.Modal(document.getElementById('editOfferModal'));
            modal.show();
        }

        // File upload preview handlers
        function setupFileUpload(inputId, placeholderId, previewId, previewImgId, removeId) {
            const fileInput = document.getElementById(inputId);
            const placeholder = document.getElementById(placeholderId);
            const preview = document.getElementById(previewId);
            const previewImg = document.getElementById(previewImgId);
            const removeBtn = document.getElementById(removeId);

            if (fileInput) {
                fileInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(ev) {
                            previewImg.src = ev.target.result;
                            placeholder.style.display = 'none';
                            preview.style.display = 'flex';
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            if (removeBtn) {
                removeBtn.addEventListener('click', function() {
                    fileInput.value = '';
                    placeholder.style.display = 'flex';
                    preview.style.display = 'none';
                });
            }
        }

        setupFileUpload('createFileInput', 'createFilePlaceholder', 'createFilePreview', 'createPreviewImg', 'createFileRemove');
        setupFileUpload('editFileInput', 'editFilePlaceholder', 'editFilePreview', 'editPreviewImg', 'editFileRemove');
    </script>
</body>
</html>
