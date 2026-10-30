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

// Traitement du formulaire de création d'événement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_event'])) {
    $titre = sanitize_input($_POST['titre']);
    $description = sanitize_input($_POST['description']);
    $date_event = sanitize_input($_POST['date_event']);
    $lieu = sanitize_input($_POST['lieu']);
    $capacite = sanitize_input($_POST['capacite']);
    $image = null;

    // Gestion de l'upload d'image
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../../app/uploads/evenements/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
            $image = 'app/uploads/evenements/' . $file_name;
        }
    }

    if (create_evenement($titre, $description, $date_event, $lieu, $capacite, 'OUVERT', $image, $entreprise_id)) {
        // Envoyer notification à tous les enseignants
        $notification_message = "Un nouvel événement \"$titre\" a été créé par une entreprise pour le " . date('d/m/Y', strtotime($date_event)) . " à $lieu.";
        notify_all_teachers("Nouvel événement créé", $notification_message, 'EVENEMENT');
        $_SESSION['success_message'] = "Événement créé avec succès !";
        header("Location: events.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Erreur lors de la création de l'événement.";
        header("Location: events.php");
        exit();
    }
}

// Traitement de la modification d'événement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_event'])) {
    $event_id = sanitize_input($_POST['event_id']);
    $titre = sanitize_input($_POST['titre']);
    $description = sanitize_input($_POST['description']);
    $date_event = sanitize_input($_POST['date_event']);
    $lieu = sanitize_input($_POST['lieu']);
    $capacite = sanitize_input($_POST['capacite']);
    $statut = sanitize_input($_POST['statut']);
    $image = null;

    // Gestion de l'upload d'image
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../../app/uploads/evenements/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
            $image = 'app/uploads/evenements/' . $file_name;
        }
    }

    if (update_evenement($event_id, $titre, $description, $date_event, $lieu, $capacite, $statut, $image)) {
        $success_message = "Événement mis à jour avec succès !";
    } else {
        $error_message = "Erreur lors de la mise à jour de l'événement.";
    }
}

// Traitement de la suppression d'événement
if (isset($_GET['delete_event'])) {
    $event_id = sanitize_input($_GET['delete_event']);
    if (delete_evenement($event_id)) {
        $success_message = "Événement supprimé avec succès !";
        header("Location: events.php");
        exit();
    } else {
        $error_message = "Erreur lors de la suppression de l'événement.";
        header("Location: events.php");
        exit();
    }
}

// Obtenir les événements à venir
$upcoming_events = get_upcoming_evenements(10, $entreprise_id);
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
    <link rel="stylesheet" href="../../public/assets/css/entreprise-navbar.css">
    <link rel="stylesheet" href="../../public/assets/css/company-dashboard.css">
    <link rel="stylesheet" href="../../public/assets/css/entreprise-events.css">
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
            const interactiveElements = document.querySelectorAll('a, button, .evt-card, .evt-btn-create, .evt-form-btn');
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
                        const lieu = card.querySelector('.evt-card-lieu').textContent.toLowerCase();
                        if (title.includes(query) || lieu.includes(query)) {
                            card.style.display = '';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            }

            // File upload preview
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
            setupFileUpload('createEvtFileInput', 'createEvtFilePlaceholder', 'createEvtFilePreview', 'createEvtPreviewImg', 'createEvtFileRemove');
            setupFileUpload('editEvtFileInput', 'editEvtFilePlaceholder', 'editEvtFilePreview', 'editEvtPreviewImg', 'editEvtFileRemove');
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
            <div class="evt-page-header">
                <div class="evt-header-left">
                    <h1><i class="fas fa-calendar-alt"></i> Événements</h1>
                    <p>Participez aux événements ESEN</p>
                </div>
                <div class="evt-header-right">
                    <button class="evt-btn-create" data-bs-toggle="modal" data-bs-target="#createEventModal">
                        <i class="fas fa-plus"></i>
                        <span>Nouvel événement</span>
                    </button>
                </div>
            </div>

            <?php
            $total_evt = count($upcoming_events);
            $upcoming = 0; $past = 0; $total_cap = 0;
            foreach ($upcoming_events as $ev) {
                $d = new DateTime($ev['dateEvent']);
                if ($d < new DateTime()) { $past++; } else { $upcoming++; }
                $total_cap += (int)$ev['capacite'];
            }
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

            <?php if (isset($success_message)): ?>
                <div class="evt-alert success"><i class="fas fa-check-circle"></i> <?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if (isset($error_message)): ?>
                <div class="evt-alert error"><i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?></div>
            <?php endif; ?>

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
            <?php if (!empty($upcoming_events)): ?>
                <div class="evt-grid">
                    <?php foreach ($upcoming_events as $event): ?>
                        <?php
                        $event_date = new DateTime($event['dateEvent']);
                        $today = new DateTime();
                        $is_expired = $event_date < $today;
                        $status_key = $is_expired ? 'past' : 'upcoming';
                        $day = date('d', strtotime($event['dateEvent']));
                        $month = date('M', strtotime($event['dateEvent']));
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
                                    <span class="evt-card-capacity">
                                        <i class="fas fa-users"></i> <?php echo $event['capacite']; ?> places
                                    </span>
                                </div>

                                <h3 class="evt-card-title"><?php echo htmlspecialchars($event['titre']); ?></h3>

                                <div class="evt-card-lieu">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($event['lieu']); ?></span>
                                </div>

                                <?php if (!empty($event['description'])): ?>
                                    <p class="evt-card-desc"><?php echo htmlspecialchars(mb_strimwidth($event['description'], 0, 100, '...')); ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="evt-card-actions">
                                <?php if (!$is_expired): ?>
                                    <button class="evt-card-btn edit" onclick="openEditModal(<?php echo $event['id']; ?>, '<?php echo htmlspecialchars($event['titre']); ?>', '<?php echo htmlspecialchars($event['dateEvent']); ?>', '<?php echo htmlspecialchars($event['lieu']); ?>', '<?php echo htmlspecialchars($event['capacite']); ?>', '<?php echo htmlspecialchars($event['statut']); ?>', '<?php echo htmlspecialchars($event['description']); ?>', '<?php echo htmlspecialchars($event['image'] ?? ''); ?>')">
                                        <i class="fas fa-pen"></i> Modifier
                                    </button>
                                <?php endif; ?>
                                <a href="?delete_event=<?php echo $event['id']; ?>" class="evt-card-btn delete" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet événement ?');">
                                    <i class="fas fa-trash-alt"></i> Supprimer
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="evt-empty-state">
                    <div class="evt-empty-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3>Aucun événement à venir</h3>
                    <p>Créez votre premier événement pour commencer</p>
                    <button class="evt-btn-create" data-bs-toggle="modal" data-bs-target="#createEventModal">
                        <i class="fas fa-plus"></i>
                        <span>Créer un événement</span>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal de création d'événement -->
    <div class="modal fade evt-modal" id="createEventModal" tabindex="-1" aria-labelledby="createEventModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="evt-modal-content">
                <div class="evt-modal-header">
                    <div class="evt-modal-header-icon"><i class="fas fa-plus-circle"></i></div>
                    <div>
                        <h5 class="evt-modal-title" id="createEventModalLabel">Nouvel événement</h5>
                        <p class="evt-modal-subtitle">Créez un nouvel événement ESEN</p>
                    </div>
                    <button type="button" class="evt-modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="fas fa-times"></i></button>
                </div>
                <div class="evt-modal-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="create_event" value="1">
                        <div class="evt-form-group">
                            <label class="evt-form-label"><i class="fas fa-heading"></i> Titre</label>
                            <input type="text" name="titre" class="evt-form-input" placeholder="Titre de l'événement" required>
                        </div>
                        <div class="evt-form-row">
                            <div class="evt-form-group">
                                <label class="evt-form-label"><i class="fas fa-calendar-alt"></i> Date</label>
                                <input type="date" name="date_event" class="evt-form-input" required>
                            </div>
                            <div class="evt-form-group">
                                <label class="evt-form-label"><i class="fas fa-map-marker-alt"></i> Lieu</label>
                                <input type="text" name="lieu" class="evt-form-input" placeholder="Lieu de l'événement" required>
                            </div>
                        </div>
                        <div class="evt-form-row">
                            <div class="evt-form-group">
                                <label class="evt-form-label"><i class="fas fa-users"></i> Capacité</label>
                                <input type="number" name="capacite" class="evt-form-input" placeholder="Max participants" required>
                            </div>
                            <div class="evt-form-group">
                                <label class="evt-form-label"><i class="fas fa-toggle-on"></i> Statut</label>
                                <select name="statut" class="evt-form-select" required>
                                    <option value="OUVERT">Ouvert</option>
                                    <option value="FERME">Fermé</option>
                                </select>
                            </div>
                        </div>
                        <div class="evt-form-group">
                            <label class="evt-form-label"><i class="fas fa-align-left"></i> Description</label>
                            <textarea name="description" class="evt-form-textarea" rows="3" placeholder="Description de l'événement"></textarea>
                        </div>
                        <div class="evt-form-group">
                            <label class="evt-form-label"><i class="fas fa-image"></i> Photo</label>
                            <div class="evt-file-upload" id="createEvtFileUpload">
                                <input type="file" name="image" accept="image/*" class="evt-file-input" id="createEvtFileInput">
                                <div class="evt-file-placeholder" id="createEvtFilePlaceholder">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Cliquez ou glissez une image ici</span>
                                    <small>JPG, PNG, GIF</small>
                                </div>
                                <div class="evt-file-preview" id="createEvtFilePreview" style="display:none;">
                                    <img id="createEvtPreviewImg" src="" alt="Aperçu">
                                    <button type="button" class="evt-file-remove" id="createEvtFileRemove"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="evt-form-actions">
                            <button type="button" class="evt-form-btn cancel" data-bs-dismiss="modal"><i class="fas fa-arrow-left"></i> Annuler</button>
                            <button type="submit" class="evt-form-btn submit"><i class="fas fa-rocket"></i> Créer l'événement</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de modification d'événement -->
    <div class="modal fade evt-modal" id="editEventModal" tabindex="-1" aria-labelledby="editEventModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="evt-modal-content">
                <div class="evt-modal-header edit">
                    <div class="evt-modal-header-icon"><i class="fas fa-pen"></i></div>
                    <div>
                        <h5 class="evt-modal-title" id="editEventModalLabel">Modifier l'événement</h5>
                        <p class="evt-modal-subtitle">Modifiez les informations de l'événement</p>
                    </div>
                    <button type="button" class="evt-modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="fas fa-times"></i></button>
                </div>
                <div class="evt-modal-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="update_event" value="1">
                        <input type="hidden" name="event_id" id="editEventId">
                        <div class="evt-form-group">
                            <label class="evt-form-label"><i class="fas fa-heading"></i> Titre</label>
                            <input type="text" name="titre" id="editTitre" class="evt-form-input" placeholder="Titre de l'événement" required>
                        </div>
                        <div class="evt-form-row">
                            <div class="evt-form-group">
                                <label class="evt-form-label"><i class="fas fa-calendar-alt"></i> Date</label>
                                <input type="date" name="date_event" id="editDateEvent" class="evt-form-input" required>
                            </div>
                            <div class="evt-form-group">
                                <label class="evt-form-label"><i class="fas fa-map-marker-alt"></i> Lieu</label>
                                <input type="text" name="lieu" id="editLieu" class="evt-form-input" placeholder="Lieu de l'événement" required>
                            </div>
                        </div>
                        <div class="evt-form-row">
                            <div class="evt-form-group">
                                <label class="evt-form-label"><i class="fas fa-users"></i> Capacité</label>
                                <input type="number" name="capacite" id="editCapacite" class="evt-form-input" placeholder="Max participants" required>
                            </div>
                            <div class="evt-form-group">
                                <label class="evt-form-label"><i class="fas fa-toggle-on"></i> Statut</label>
                                <select name="statut" id="editStatut" class="evt-form-select" required>
                                    <option value="OUVERT">Ouvert</option>
                                    <option value="FERME">Fermé</option>
                                </select>
                            </div>
                        </div>
                        <div class="evt-form-group">
                            <label class="evt-form-label"><i class="fas fa-align-left"></i> Description</label>
                            <textarea name="description" id="editDescription" class="evt-form-textarea" rows="3" placeholder="Description de l'événement"></textarea>
                        </div>
                        <div class="evt-form-group">
                            <label class="evt-form-label"><i class="fas fa-image"></i> Photo</label>
                            <div class="evt-file-upload" id="editEvtFileUpload">
                                <input type="file" name="image" accept="image/*" class="evt-file-input" id="editEvtFileInput">
                                <div class="evt-file-placeholder" id="editEvtFilePlaceholder">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Cliquez ou glissez une image</span>
                                    <small>JPG, PNG, GIF</small>
                                </div>
                                <div class="evt-file-preview" id="editEvtFilePreview" style="display:none;">
                                    <img id="editEvtPreviewImg" src="" alt="Aperçu">
                                    <button type="button" class="evt-file-remove" id="editEvtFileRemove"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="evt-form-actions">
                            <button type="button" class="evt-form-btn cancel" data-bs-dismiss="modal"><i class="fas fa-arrow-left"></i> Annuler</button>
                            <button type="submit" class="evt-form-btn submit edit"><i class="fas fa-save"></i> Mettre à jour</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../public/assets/js/main.js"></script>
    <script>
        function openEditModal(eventId, titre, dateEvent, lieu, capacite, statut, description, image) {
            document.getElementById('editEventId').value = eventId;
            document.getElementById('editTitre').value = titre;
            document.getElementById('editDateEvent').value = dateEvent;
            document.getElementById('editLieu').value = lieu;
            document.getElementById('editCapacite').value = capacite;
            document.getElementById('editStatut').value = statut;
            document.getElementById('editDescription').value = description;

            const editFilePlaceholder = document.getElementById('editEvtFilePlaceholder');
            const editFilePreview = document.getElementById('editEvtFilePreview');
            const editPreviewImg = document.getElementById('editEvtPreviewImg');
            if (image && editPreviewImg) {
                editPreviewImg.src = '../../../' + image;
                editFilePlaceholder.style.display = 'none';
                editFilePreview.style.display = 'flex';
            } else {
                editFilePlaceholder.style.display = 'flex';
                editFilePreview.style.display = 'none';
            }

            const modal = new bootstrap.Modal(document.getElementById('editEventModal'));
            modal.show();
        }
    </script>
</body>
</html>
