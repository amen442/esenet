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

// Traitement du formulaire de mise à jour du mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $current_password = sanitize_input($_POST['current_password']);
    $new_password = sanitize_input($_POST['new_password']);
    $confirm_password = sanitize_input($_POST['confirm_password']);
    
    if (!verify_password($entreprise_id, $current_password)) {
        $error_message = "Le mot de passe actuel est incorrect.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "Les nouveaux mots de passe ne correspondent pas.";
    } elseif (strlen($new_password) < 6) {
        $error_message = "Le mot de passe doit contenir au moins 6 caractères.";
    } else {
        if (update_password($entreprise_id, $new_password)) {
            $success_message = "Mot de passe mis à jour avec succès !";
        } else {
            $error_message = "Erreur lors de la mise à jour du mot de passe.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - ESEN NET</title>
    <meta name="description" content="Gérez vos paramètres de compte">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../public/assets/css/main.css">
    <link rel="stylesheet" href="../../public/assets/css/entreprise-navbar.css">
    <link rel="stylesheet" href="../../public/assets/css/company-dashboard.css">
    <link rel="stylesheet" href="../../public/assets/css/entreprise-settings.css">
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
            const interactiveElements = document.querySelectorAll('a, button, .settings-card, .settings-form-btn');
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

    <?php include 'entreprise-navbar.php'; ?>

    <main class="main-content-wrapper">
        <div class="dashboard-container">
            <div class="settings-page-header">
                <div class="settings-header-left">
                    <h1>Paramètres</h1>
                    <p>Gérez vos paramètres de compte</p>
                </div>
                <div class="settings-header-icon">
                    <i class="fas fa-cog"></i>
                </div>
            </div>

            <div class="settings-card-centered">
                <div class="settings-card">
                    <div class="settings-card-header">
                        <div class="settings-card-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <h3 class="settings-card-title">Mot de passe</h3>
                    </div>
                    <?php if (isset($success_message)): ?>
                        <div class="settings-alert success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    <?php if (isset($error_message)): ?>
                        <div class="settings-alert error"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    <form method="POST" action="">
                        <input type="hidden" name="update_password" value="1">
                        <div class="settings-form-group">
                            <label class="settings-form-label"><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" class="settings-form-input" value="<?php echo htmlspecialchars($entreprise_info['email']); ?>" readonly>
                        </div>
                        <div class="settings-form-group">
                            <label class="settings-form-label"><i class="fas fa-key"></i> Mot de passe actuel</label>
                            <input type="password" name="current_password" class="settings-form-input" placeholder="Entrez votre mot de passe actuel" required>
                        </div>
                        <div class="settings-form-group">
                            <label class="settings-form-label"><i class="fas fa-plus-circle"></i> Nouveau mot de passe</label>
                            <input type="password" name="new_password" class="settings-form-input" placeholder="Entrez votre nouveau mot de passe" required>
                        </div>
                        <div class="settings-form-group">
                            <label class="settings-form-label"><i class="fas fa-check-circle"></i> Confirmer le mot de passe</label>
                            <input type="password" name="confirm_password" class="settings-form-input" placeholder="Confirmez votre nouveau mot de passe" required>
                        </div>
                        <button type="submit" class="settings-form-btn primary">
                            <i class="fas fa-save"></i> Mettre à jour
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
