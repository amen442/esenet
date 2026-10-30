<?php
require_once __DIR__ . '/../layouts/components.php';
require_once __DIR__ . '/../../config/db_connect.php';

$admin_id = get_admin_id();
if (!$admin_id) { header("Location: ../../login.php"); exit(); }

$admin_info = get_admin_info($admin_id);
$success_message = '';
$error_message = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    // Validation email
    if (empty($email)) {
        $error_message = "L'email est requis.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "L'email n'est pas valide.";
    } else {
        // Mise à jour email et téléphone
        $stmt = $conn->prepare("UPDATE utilisateur SET email = ?, telephone = ? WHERE id = ?");
                
        if ($stmt->execute([$email, $telephone, $admin_id])) {
            // Si changement de mot de passe demandé
            if (!empty($current_password) && !empty($new_password)) {
                // Vérifier le mot de passe actuel (comparaison directe car stocké en clair)
                $stmt_check = $conn->prepare("SELECT motDePasse FROM utilisateur WHERE id = ?");
                $stmt_check->execute([$admin_id]);
                $user = $stmt_check->fetch();
                
                if ($user && $current_password === $user['motDePasse']) {
                    // Nouveau mot de passe (minimum 6 caractères)
                    if (strlen($new_password) < 6) {
                        $error_message = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
                    } else {
                        // Stocker le nouveau mot de passe en clair (comme dans la base existante)
                        $stmt_pass = $conn->prepare("UPDATE utilisateur SET motDePasse = ? WHERE id = ?");
                        if ($stmt_pass->execute([$new_password, $admin_id])) {
                            $success_message = "Paramètres mis à jour avec succès !";
                        } else {
                            $error_message = "Erreur lors de la mise à jour du mot de passe.";
                        }
                    }
                } else {
                    $error_message = "Le mot de passe actuel est incorrect.";
                }
            } else {
                $success_message = "Paramètres mis à jour avec succès !";
            }
            
            // Rafraîchir les infos admin
            $admin_info = get_admin_info($admin_id);
            
            // Redirection pour éviter le renvoi du formulaire (PRG pattern)
            header("Location: settings.php?success=1");
            exit();
        } else {
            $error_message = "Erreur lors de la mise à jour des paramètres.";
        }
    }
}

// Vérifier le paramètre success après redirection
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_message = "Paramètres mis à jour avec succès !";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - ESEN Connect</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../public/assets/css/main.css">
    <link rel="stylesheet" href="../../public/assets/css/admin-navbar.css">
    <link rel="stylesheet" href="../../public/assets/css/admin-dashboard.css">
    <link rel="icon" href="../../public/assets/images/1.jpeg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Theme toggle
        const themeToggle = document.querySelector('.toggle-dark');
        const savedTheme = localStorage.getItem('theme') || 'light';
        if (savedTheme === 'dark') document.body.classList.add('dark');
        if (themeToggle) themeToggle.addEventListener('click', function() {
            document.body.classList.toggle('dark');
            localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
        });

        // Mobile menu
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const verticalNavbar = document.getElementById('verticalNavbar');
        if (mobileMenuToggle && verticalNavbar) {
            mobileMenuToggle.addEventListener('click', () => verticalNavbar.classList.toggle('active'));
            document.addEventListener('click', e => {
                if (!verticalNavbar.contains(e.target) && !mobileMenuToggle.contains(e.target))
                    verticalNavbar.classList.remove('active');
            });
        }

        // Navbar toggle
        const navbarToggle = document.getElementById('navbarToggle');
        const navbarShowBtn = document.getElementById('navbarShowBtn');
        if (navbarToggle && verticalNavbar) navbarToggle.addEventListener('click', () => { verticalNavbar.classList.toggle('collapsed'); updateBtn(); });
        if (navbarShowBtn && verticalNavbar) navbarShowBtn.addEventListener('click', () => { verticalNavbar.classList.remove('collapsed'); updateBtn(); });
        function updateBtn() { navbarShowBtn.classList.toggle('visible', verticalNavbar.classList.contains('collapsed')); }
        if (verticalNavbar && navbarShowBtn) updateBtn();

        // Custom Cursor
        const cursor = document.querySelector('.custom-cursor');
        const cursorDot = document.querySelector('.custom-cursor-dot');
        let mx=0,my=0,cx=0,cy=0;
        if (cursor && cursorDot) {
            document.addEventListener('mousemove', e => { mx=e.clientX; my=e.clientY; });
            (function anim() { cx+=(mx-cx)*0.15; cy+=(my-cy)*0.15; cursor.style.left=cx+'px'; cursor.style.top=cy+'px'; cursorDot.style.left=mx+'px'; cursorDot.style.top=my+'px'; requestAnimationFrame(anim); })();
            document.querySelectorAll('a,button,input,.btn,.dashboard-card,.nav-item').forEach(el => {
                el.addEventListener('mouseenter', () => cursor.classList.add('hover'));
                el.addEventListener('mouseleave', () => cursor.classList.remove('hover'));
            });
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
                <h1>Paramètres</h1>
                <p>Gérez vos paramètres de compte</p>
            </div>

            <div class="dashboard-card">
                <h3><i class="fas fa-user-cog"></i> Paramètres du compte</h3>
                
                <?php if ($success_message): ?>
                    <div class="alert-card alert-success-custom">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert-card alert-error-custom">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <form class="admin-form" method="POST" action="">
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($admin_info['email'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Téléphone</label>
                        <input type="tel" name="telephone" value="<?php echo htmlspecialchars($admin_info['telephone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Mot de passe actuel</label>
                        <input type="password" name="current_password" placeholder="Entrez votre mot de passe actuel">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-key"></i> Nouveau mot de passe</label>
                        <input type="password" name="new_password" placeholder="Entrez votre nouveau mot de passe (minimum 6 caractères)">
                    </div>
                    <button type="submit" class="btn primary">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../public/assets/js/main.js"></script>
</body>
</html>
