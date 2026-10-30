<?php
require_once __DIR__ . '/../layouts/components.php';
require_once __DIR__ . '/../../config/db_connect.php';

$teacher_id = get_teacher_id();
if (!$teacher_id) { header("Location: ../../login.php"); exit(); }

$teacher_info = get_teacher_info($teacher_id);
$success_message = '';
$error_message = '';

// Debug pour vérifier les données récupérées
if (!$teacher_info || empty($teacher_info)) {
    // Si get_teacher_info() ne fonctionne pas, essayer directement
    $stmt = $conn->prepare("SELECT u.email, u.telephone, u.nom, u.prenom FROM utilisateur u WHERE u.id = ?");
    $stmt->execute([$teacher_id]);
    $teacher_info = $stmt->fetch();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $specialite = trim($_POST['specialite'] ?? '');
    $departement = trim($_POST['departement'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Traitement de l'upload de photo
    $photo_path = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $photo = $_FILES['photo'];
        
        // Vérifications
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($photo['type'], $allowed_types)) {
            $errors[] = "Format d'image non autorisé. Utilisez JPG, PNG ou GIF.";
        } elseif ($photo['size'] > $max_size) {
            $errors[] = "L'image ne doit pas dépasser 2MB.";
        } else {
            // Créer le dossier uploads s'il n'existe pas
            $upload_dir = '../../../app/uploads/teacher_profiles/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Générer un nom de fichier unique
            $file_extension = pathinfo($photo['name'], PATHINFO_EXTENSION);
            $file_name = 'teacher_' . $teacher_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $file_name;
            
            // Déplacer le fichier
            if (move_uploaded_file($photo['tmp_name'], $upload_path)) {
                $photo_path = 'app/uploads/teacher_profiles/' . $file_name;
            } else {
                $errors[] = "Erreur lors du téléchargement de la photo.";
            }
        }
    }

    // Validation des champs requis
    $errors = [];
    if (empty($prenom)) $errors[] = "Le prénom est requis.";
    if (empty($nom)) $errors[] = "Le nom est requis.";
    if (empty($email)) $errors[] = "L'email est requis.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "L'email n'est pas valide.";
    
    // Validation du mot de passe si changement demandé
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = "Le mot de passe actuel est requis pour le changement.";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "Les mots de passe ne correspondent pas.";
        }
    }
    
    if (empty($errors)) {
        try {
            // Mise à jour des informations de l'enseignant
            $stmt_teacher = $conn->prepare("UPDATE enseignant SET prenom = ?, nom = ?, specialite = ?, departement = ? WHERE id = ?");
            if (!$stmt_teacher->execute([$prenom, $nom, $specialite, $departement, $teacher_id])) {
                throw new Exception("Erreur lors de la mise à jour du profil enseignant.");
            }
            
            // Mise à jour des informations utilisateur
            if (!empty($photo_path)) {
                // Avec photo
                $stmt_user = $conn->prepare("UPDATE utilisateur SET email = ?, telephone = ?, photo = ? WHERE id = ?");
            } else {
                // Sans photo
                $stmt_user = $conn->prepare("UPDATE utilisateur SET email = ?, telephone = ? WHERE id = ?");
            }
            
            $user_result = !empty($photo_path) 
                ? $stmt_user->execute([$email, $telephone, $photo_path, $teacher_id])
                : $stmt_user->execute([$email, $telephone, $teacher_id]);
            
            if (!$user_result) {
                throw new Exception("Erreur lors de la mise à jour des coordonnées.");
            }
            
            // Si changement de mot de passe demandé
            if (!empty($current_password) && !empty($new_password)) {
                $password_result = change_user_password($teacher_id, $current_password, $new_password);
                if (!$password_result['success']) {
                    throw new Exception($password_result['message']);
                }
            }
            
            // Rafraîchir les informations de l'enseignant
            $teacher_info = get_teacher_info($teacher_id);
            $success_message = "Informations mises à jour avec succès!";
            
        } catch (Exception $e) {
            $error_message = $e->getMessage();
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
    <title>Paramètres - ESEN NET</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../public/assets/css/main.css">
    <link rel="stylesheet" href="../../public/assets/css/student-navbar.css">
    <link rel="stylesheet" href="../../public/assets/css/student-dashboard.css">
    <link rel="stylesheet" href="../../public/assets/css/teacher-dashboard.css">
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

    <?php include 'teacher-navbar.php'; ?>

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
                
                <form class="admin-form" method="POST" action="" enctype="multipart/form-data">
                    <!-- Informations personnelles -->
                    <h4><i class="fas fa-user"></i> Informations personnelles</h4>
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Prénom</label>
                        <input type="text" name="prenom" value="<?php echo htmlspecialchars($teacher_info['prenom'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Nom</label>
                        <input type="text" name="nom" value="<?php echo htmlspecialchars($teacher_info['nom'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-graduation-cap"></i> Spécialité</label>
                        <input type="text" name="specialite" value="<?php echo htmlspecialchars($teacher_info['specialite'] ?? ''); ?>" placeholder="Ex: Informatique, Management...">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-building"></i> Département</label>
                        <input type="text" name="departement" value="<?php echo htmlspecialchars($teacher_info['departement'] ?? ''); ?>" placeholder="Ex: Département informatique">
                    </div>
                    
                    <!-- Coordonnées -->
                    <h4><i class="fas fa-address-card"></i> Coordonnées</h4>
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email institutionnel</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($teacher_info['email'] ?? ''); ?>" required>
                        <small>Format: prenom.nom@esen.tn</small>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Téléphone</label>
                        <input type="tel" name="telephone" value="<?php echo htmlspecialchars($teacher_info['telephone'] ?? ''); ?>" placeholder="+216 XX XXX XXX">
                    </div>
                    
                    <!-- Photo de profil -->
                    <h4><i class="fas fa-camera"></i> Photo de profil</h4>
                    <div class="form-group">
                        <label><i class="fas fa-image"></i> Changer la photo</label>
                        <input type="file" name="photo" id="photoInput" accept="image/jpeg,image/jpg,image/png,image/gif">
                        <small>Formats acceptés: JPG, PNG, GIF (max 2MB)</small>
                        <div id="photoPreview" style="margin-top: 10px; text-align: center;">
                            <?php if (!empty($teacher_info['photo'])): ?>
                                <img src="../../../<?php echo htmlspecialchars($teacher_info['photo']); ?>" alt="Photo actuelle" style="max-width: 150px; max-height: 150px; border-radius: 50%; border: 3px solid #3b82f6;">
                                <p style="margin-top: 5px; font-size: 0.9em;">Photo actuelle</p>
                            <?php else: ?>
                                <div style="width: 150px; height: 150px; border-radius: 50%; background: #f0f0f0; display: flex; align-items: center; justify-content: center; margin: 0 auto; border: 3px solid #ddd;">
                                    <i class="fas fa-user" style="font-size: 48px; color: #999;"></i>
                                </div>
                                <p style="margin-top: 5px; font-size: 0.9em;">Aucune photo</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Sécurité -->
                    <h4><i class="fas fa-shield-alt"></i> Sécurité</h4>
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Mot de passe actuel</label>
                        <input type="password" name="current_password" placeholder="Entrez votre mot de passe actuel (requis pour le changement)">
                        <small>Laissez vide si vous ne voulez pas changer de mot de passe</small>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-key"></i> Nouveau mot de passe</label>
                        <input type="password" name="new_password" placeholder="Entrez votre nouveau mot de passe (minimum 6 caractères)">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-key"></i> Confirmer le nouveau mot de passe</label>
                        <input type="password" name="confirm_password" placeholder="Confirmez votre nouveau mot de passe">
                    </div>
                    
                    <!-- Informations système -->
                    <h4><i class="fas fa-info-circle"></i> Informations système</h4>
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Date d'inscription</label>
                        <input type="text" value="<?php echo htmlspecialchars(date('d/m/Y', strtotime($teacher_info['dateInscription'] ?? 'now'))); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-id-card"></i> Statut</label>
                        <input type="text" value="<?php echo htmlspecialchars(ucfirst($teacher_info['statut'] ?? 'ENSEIGNANT')); ?>" readonly>
                    </div>
                    
                    <button type="submit" class="btn primary">
                        <i class="fas fa-save"></i> Enregistrer les modifications
                    </button>
                </form>
            </div>
        </div>
    </main>

<?php include 'notification-box.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../public/assets/js/main.js"></script>
    
    <script>
        // Prévisualisation de la photo
        document.getElementById('photoInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('photoPreview');
            
            if (file) {
                // Vérifier le type de fichier
                if (!file.type.match('image.*')) {
                    alert('Veuillez sélectionner une image valide.');
                    return;
                }
                
                // Vérifier la taille (max 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('L\'image ne doit pas dépasser 2MB.');
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `
                        <img src="${e.target.result}" alt="Nouvelle photo" style="max-width: 150px; max-height: 150px; border-radius: 50%; border: 3px solid #10b981;">
                        <p style="margin-top: 5px; font-size: 0.9em; color: #10b981;">Nouvelle photo</p>
                    `;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
