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

// Obtenir les informations de l'entreprise
$entreprise_info = get_entreprise_info($entreprise_id);

// Récupérer les messages de session
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : null;
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : null;

// Effacer les messages de session
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Traitement du formulaire de mise à jour des informations générales
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_info'])) {
    $nomsociete = sanitize_input($_POST['nomsociete']);
    $secteur = sanitize_input($_POST['secteur']);
    $email = sanitize_input($_POST['email']);
    $telephone = sanitize_input($_POST['telephone']);
    $siteweb = sanitize_input($_POST['siteweb']);
    $logo = null;

    // Gestion de l'upload du logo
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../../app/uploads/logos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $file_extension = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($file_extension, $allowed_extensions)) {
            $file_name = uniqid() . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $file_path)) {
                $logo = 'app/uploads/logos/' . $file_name;
            }
        }
    }

    if (update_entreprise_info($entreprise_id, $nomsociete, $secteur, $entreprise_info['description'], $siteweb, $logo)) {
        if (update_entreprise_user($entreprise_id, $email, $telephone)) {
            $_SESSION['success_message'] = "Informations mises à jour avec succès !";
            header("Location: profile.php");
            exit();
        }
    } else {
        $error_message = "Erreur lors de la mise à jour des informations.";
    }
}

// Traitement du formulaire de mise à jour de la description
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_description'])) {
    $description = sanitize_input($_POST['description']);

    if (update_entreprise_info($entreprise_id, $entreprise_info['nomsociete'], $entreprise_info['secteur'], $description, $entreprise_info['siteweb'])) {
        $_SESSION['success_message'] = "Description mise à jour avec succès !";
        header("Location: profile.php");
        exit();
    } else {
        $error_message = "Erreur lors de la mise à jour de la description.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon entreprise - ESEN NET</title>
    <meta name="description" content="Gérez votre profil entreprise">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../public/assets/css/main.css">
    <link rel="stylesheet" href="../../public/assets/css/entreprise-navbar.css">
    <link rel="stylesheet" href="../../public/assets/css/company-dashboard.css">
    <link rel="stylesheet" href="../../public/assets/css/entreprise-profile.css">
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
            const interactiveElements = document.querySelectorAll('a, button, .profile-card, .settings-form-btn');
            interactiveElements.forEach(el => {
                el.addEventListener('mouseenter', () => cursor.classList.add('hover'));
                el.addEventListener('mouseleave', () => cursor.classList.remove('hover'));
            });

            document.addEventListener('mousedown', () => cursor.classList.add('clicking'));
            document.addEventListener('mouseup', () => cursor.classList.remove('clicking'));

            // File upload preview handler for logo
            const logoFileInput = document.getElementById('logoFileInput');
            const logoFilePlaceholder = document.getElementById('logoFilePlaceholder');
            const logoFilePreview = document.getElementById('logoFilePreview');
            const logoPreviewImg = document.getElementById('logoPreviewImg');
            const logoFileRemove = document.getElementById('logoFileRemove');

            if (logoFileInput) {
                logoFileInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(ev) {
                            logoPreviewImg.src = ev.target.result;
                            logoFilePlaceholder.style.display = 'none';
                            logoFilePreview.style.display = 'flex';
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            if (logoFileRemove) {
                logoFileRemove.addEventListener('click', function() {
                    logoFileInput.value = '';
                    logoFilePlaceholder.style.display = 'flex';
                    logoFilePreview.style.display = 'none';
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
            <div class="profile-page-header">
                <div class="profile-header-left">
                    <h1>Mon entreprise</h1>
                    <p>Gérez les informations de votre entreprise</p>
                </div>
                <div class="profile-header-icon">
                    <i class="fas fa-building"></i>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="profile-card">
                    <div class="profile-card-header">
                        <div class="profile-card-icon">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <h3 class="profile-card-title">Informations générales</h3>
                    </div>
                    <?php if (isset($success_message)): ?>
                        <div class="profile-alert success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    <?php if (isset($error_message)): ?>
                        <div class="profile-alert error"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="update_info" value="1">
                        <div class="profile-form-group">
                            <label class="profile-form-label"><i class="fas fa-building"></i> Nom de l'entreprise</label>
                            <input type="text" name="nomsociete" class="profile-form-input" value="<?php echo htmlspecialchars($entreprise_info['nomsociete'] ?? ''); ?>" required>
                        </div>
                        <div class="profile-form-group">
                            <label class="profile-form-label"><i class="fas fa-briefcase"></i> Secteur d'activité</label>
                            <input type="text" name="secteur" class="profile-form-input" value="<?php echo htmlspecialchars($entreprise_info['secteur']); ?>" required>
                        </div>
                        <div class="profile-form-group">
                            <label class="profile-form-label"><i class="fas fa-envelope"></i> Email professionnel</label>
                            <input type="email" name="email" class="profile-form-input" value="<?php echo htmlspecialchars($entreprise_info['email']); ?>" required>
                        </div>
                        <div class="profile-form-group">
                            <label class="profile-form-label"><i class="fas fa-phone"></i> Téléphone</label>
                            <input type="tel" name="telephone" class="profile-form-input" value="<?php echo htmlspecialchars($entreprise_info['telephone']); ?>">
                        </div>
                        <div class="profile-form-group">
                            <label class="profile-form-label"><i class="fas fa-globe"></i> Site web</label>
                            <input type="url" name="siteweb" class="profile-form-input" value="<?php echo htmlspecialchars($entreprise_info['siteweb']); ?>">
                        </div>
                        <div class="profile-form-group">
                            <label class="profile-form-label"><i class="fas fa-image"></i> Logo de l'entreprise</label>
                            <div class="profile-file-upload" id="logoFileUpload">
                                <input type="file" name="logo" accept="image/*" class="profile-file-input" id="logoFileInput">
                                <div class="profile-file-placeholder" id="logoFilePlaceholder">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Cliquez ou glissez une image ici</span>
                                    <small>Formats acceptés : JPG, PNG, GIF, WEBP</small>
                                </div>
                                <div class="profile-file-preview" id="logoFilePreview" style="display:none;">
                                    <img id="logoPreviewImg" src="" alt="Logo preview">
                                    <button type="button" class="profile-file-remove" id="logoFileRemove">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <?php if (!empty($entreprise_info['logo'])): ?>
                                <div class="current-logo">
                                    <small>Logo actuel :</small>
                                    <img src="../../../<?php echo htmlspecialchars($entreprise_info['logo']); ?>" alt="Logo actuel" style="max-width: 100px; margin-top: 0.5rem; border-radius: 8px;">
                                </div>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="profile-form-btn primary">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                    </form>
                </div>

                <div class="profile-card">
                    <div class="profile-card-header">
                        <div class="profile-card-icon">
                            <i class="fas fa-align-left"></i>
                        </div>
                        <h3 class="profile-card-title">Description</h3>
                    </div>
                    <form method="POST" action="">
                        <input type="hidden" name="update_description" value="1">
                        <div class="profile-form-group">
                            <label class="profile-form-label"><i class="fas fa-file-alt"></i> Description de l'entreprise</label>
                            <textarea name="description" class="profile-form-textarea" rows="6"><?php echo htmlspecialchars($entreprise_info['description'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" class="profile-form-btn primary">
                            <i class="fas fa-edit"></i> Mettre à jour
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../public/assets/js/main.js"></script>
</body>
</html>
