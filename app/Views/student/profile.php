<?php
require_once __DIR__ . '/../layouts/components.php';
require_once __DIR__ . '/../../config/db_connect.php';
?>
<style>
    /* Experience field custom design */
    .experience-label {
        color: #ffffff;
        font-weight: 600;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        position: relative;
    }
    
    .experience-label i {
        color: #64748b;
        font-size: 1.1rem;
    }
    
    .experience-input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }
    
    .experience-input {
        width: 100%;
        padding: 0.75rem 3rem 0.75rem 1rem;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        color: #ffffff;
        font-size: 1rem;
        transition: all 0.3s ease;
    }
    
    .experience-input::placeholder {
        color: rgba(255, 255, 255, 0.6);
    }
    
    .experience-input:focus {
        outline: none;
        border-color: #cbd5e1;
        background: rgba(255, 255, 255, 0.08);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .experience-indicator {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #64748b;
        font-size: 1.2rem;
        opacity: 0.7;
        transition: all 0.3s ease;
    }
    
    .experience-input:focus + .experience-indicator {
        opacity: 1;
        transform: translateY(-50%) scale(1.1);
    }
    
    .experience-input-wrapper:hover .experience-indicator {
        opacity: 1;
    }
</style>
<?php

// Obtenir l'ID de l'étudiant connecté
$etudiant_id = get_etudiant_id();

// Si aucun étudiant n'est connecté, rediriger vers la page de connexion
if (!$etudiant_id) {
    header("Location: ../../login.php");
    exit();
}

// Récupérer tous les badges de l'étudiant avec leur score total
$badges_data = get_etudiant_badges_complets($etudiant_id);
$student_badges = $badges_data['badges'];
$badge_score_total = $badges_data['score_total'];

// Obtenir les informations de l'étudiant
$etudiant_info = get_etudiant_info($etudiant_id);

// Obtenir les statistiques de l'étudiant
$etudiant_stats = get_etudiant_stats($etudiant_id);

// Obtenir les candidatures de l'étudiant
$mes_candidatures = get_etudiant_candidatures($etudiant_id);

// Obtenir les participations aux événements
$mes_participations = get_etudiant_participations($etudiant_id);

// Ajouter les statistiques aux informations de l'étudiant
$etudiant_info['stats'] = $etudiant_stats;
$etudiant_info['candidatures_count'] = count($mes_candidatures);
$etudiant_info['participations_count'] = count($mes_participations);
$etudiant_info['badges'] = $student_badges;

// Traitement de la mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $prenom = $_POST['prenom'] ?? '';
    $nom = $_POST['nom'] ?? '';
    $email = $_POST['email'] ?? '';
    $telephone = $_POST['telephone'] ?? '';
    $specialite = $_POST['specialite'] ?? '';
    $experience = $_POST['experience'] ?? 0;
    
    // Traitement de l'upload de photo
    $photo_path = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $photo = $_FILES['photo'];
        
        // Vérifications
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($photo['type'], $allowed_types)) {
            $error_message = "Format d'image non autorisé. Utilisez JPG, PNG ou GIF.";
        } elseif ($photo['size'] > $max_size) {
            $error_message = "L'image ne doit pas dépasser 2MB.";
        } else {
            // Créer le dossier uploads s'il n'existe pas
            $upload_dir = '../../../app/uploads/profiles/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Générer un nom de fichier unique
            $file_extension = pathinfo($photo['name'], PATHINFO_EXTENSION);
            $file_name = 'student_' . $etudiant_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $file_name;
            
            // Déplacer le fichier
            if (move_uploaded_file($photo['tmp_name'], $upload_path)) {
                $photo_path = 'app/uploads/profiles/' . $file_name;
            } else {
                $error_message = "Erreur lors du téléchargement de la photo.";
            }
        }
    }

    // Si pas d'erreur d'upload, procéder à la mise à jour
    if (!isset($error_message)) {
        // Démarrer une transaction pour assurer la cohérence
        $conn->beginTransaction();
        
        try {
            // 1. Mettre à jour la table utilisateur (email, téléphone, photo si fournie)
            if (!empty($photo_path)) {
                $sql_utilisateur = "UPDATE utilisateur SET email = ?, telephone = ?, photo = ? WHERE id = ?";
                $stmt_utilisateur = $conn->prepare($sql_utilisateur);
                $user_result = $stmt_utilisateur->execute([$email, $telephone, $photo_path, $etudiant_id]);
            } else {
                $sql_utilisateur = "UPDATE utilisateur SET email = ?, telephone = ? WHERE id = ?";
                $stmt_utilisateur = $conn->prepare($sql_utilisateur);
                $user_result = $stmt_utilisateur->execute([$email, $telephone, $etudiant_id]);
            }
            
            if (!$user_result) {
                throw new Exception("Erreur mise à jour utilisateur");
            }
            
            // 2. Mettre à jour la table etudiant (nom, prénom, spécialité, experience)
            $sql_etudiant = "UPDATE etudiant SET prenom = ?, nom = ?, specialite = ?, experience = ? WHERE id = ?";
            $stmt_etudiant = $conn->prepare($sql_etudiant);
            $etudiant_result = $stmt_etudiant->execute([$prenom, $nom, $specialite, $experience, $etudiant_id]);
            if (!$etudiant_result) {
                throw new Exception("Erreur mise à jour étudiant");
            }
            
            // Si tout s'est bien passé, valider la transaction
            $conn->commit();
            $success_message = "Profil mis à jour avec succès !" . (!empty($photo_path) ? " Photo mise à jour." : "");
            $etudiant_info = get_etudiant_info($etudiant_id); // Rafraîchir les données
            
        } catch (Exception $e) {
            // En cas d'erreur, annuler la transaction
            $conn->rollBack();
            $error_message = "Erreur lors de la mise à jour du profil: " . $e->getMessage();
        }
    }
}

// Récupérer les badges avec leurs scores depuis la table badge
$student_badges = [];
try {
    $stmt = $conn->prepare("SELECT * FROM badge WHERE idEtudiant = ?");
    $stmt->execute([$etudiant_id]);
    foreach ($stmt->fetchAll() as $row) {
        $student_badges[] = $row;
    }
} catch (Exception $e) {
    $student_badges = [];
}

// Calculer le score total
$badge_score = 0;
foreach ($student_badges as $badge) {
    $badge_score += $badge['score'] ?? 0;
}

// Récupérer l'historique des participations aux événements
$participations = [];
try {
    $stmt = $conn->prepare("SELECT p.*, e.titre, e.dateEvent, e.lieu FROM participation p JOIN evenement e ON p.idEvenement = e.id WHERE p.idEtudiant = ? ORDER BY p.dateInscri DESC");
    $stmt->execute([$etudiant_id]);
    foreach ($stmt->fetchAll() as $row) {
        $participations[] = $row;
    }
} catch (Exception $e) {
    $participations = [];
}

// Récupérer l'historique des candidatures aux offres
$candidatures = [];
try {
    $stmt = $conn->prepare("SELECT c.*, o.titre, o.type, e.nomsociete FROM candidature c JOIN offre o ON c.idOffre = o.id JOIN entreprise e ON o.idEntreprise = e.id WHERE c.idEtudiant = ? ORDER BY c.datePost DESC");
    $stmt->execute([$etudiant_id]);
    foreach ($stmt->fetchAll() as $row) {
        $candidatures[] = $row;
    }
} catch (Exception $e) {
    $candidatures = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - ESEN NET</title>
    <meta name="description" content="Gérez votre profil ESEN NET">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../public/assets/css/main.css">
    <link rel="stylesheet" href="../../public/assets/css/student-navbar.css">
    <link rel="stylesheet" href="../../public/assets/css/student-dashboard.css">
    <link rel="icon" href="../../public/assets/images/1.jpeg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        /* Photo upload styles */
        .photo-upload-container {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-top: 0.5rem;
        }

        .current-photo {
            position: relative;
        }

        .profile-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .photo-placeholder {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .photo-placeholder i {
            color: white;
            font-size: 2.5rem;
        }

        .photo-upload {
            flex: 1;
        }

        .photo-upload input[type="file"] {
            display: none;
        }

        .photo-upload-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            border: none;
            font-size: 0.9rem;
        }

        .photo-upload-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }

        .photo-help {
            display: block;
            margin-top: 0.5rem;
            color: #6b7280;
            font-size: 0.85rem;
        }

        .custom-cursor.clicking {
            transform: translate(-50%, -50%) scale(0.8);
            background: rgba(0, 212, 196, 0.3);
        }

        /* ===== PROFILE PAGE STYLES ===== */
    </style>
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
                const interactiveElements = document.querySelectorAll('a, button, input, .btn, .form-group input, .form-group textarea, .dashboard-card');
                interactiveElements.forEach(el => {
                    el.addEventListener('mouseenter', () => cursor.classList.add('hover'));
                    el.addEventListener('mouseleave', () => cursor.classList.remove('hover'));
                });

                // Click effect
                document.addEventListener('mousedown', () => cursor.classList.add('clicking'));
                document.addEventListener('mouseup', () => cursor.classList.remove('clicking'));
            }
        });
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

    <main class="main-content-wrapper">
        <div class="dashboard-container">
            <div class="dashboard-header">
                <h1>Mon profil</h1>
                <p>Gérez vos informations personnelles</p>
            </div>

            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <h3>Informations personnelles</h3>
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success" style="background: rgba(0, 212, 196, 0.2); border: 1px solid rgba(0, 212, 196, 0.4); color: #00d4c4; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem;">
                            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger" style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.4); color: #ef4444; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem;">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="update_profile" value="1">
                        
                        <!-- Photo de profil -->
                        <div class="form-group">
                            <label>Photo de profil</label>
                            <div class="photo-upload-container">
                                <div class="current-photo">
                                    <?php if (!empty($etudiant_info['photo'])): ?>
                                        <img src="../../../<?php echo htmlspecialchars($etudiant_info['photo']); ?>" alt="Photo de profil" class="profile-photo">
                                    <?php else: ?>
                                        <div class="photo-placeholder">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="photo-upload">
                                    <input type="file" name="photo" id="photo" accept="image/*" onchange="previewPhoto(event)">
                                    <label for="photo" class="photo-upload-btn">
                                        <i class="fas fa-camera"></i> Changer la photo
                                    </label>
                                    <small class="photo-help">Formats: JPG, PNG, GIF (Max 2MB)</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Prénom</label>
                            <input type="text" name="prenom" value="<?php echo htmlspecialchars($etudiant_info['prenom'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Nom</label>
                            <input type="text" name="nom" value="<?php echo htmlspecialchars($etudiant_info['nom'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($etudiant_info['email'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Téléphone</label>
                            <input type="tel" name="telephone" value="<?php echo htmlspecialchars($etudiant_info['telephone'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Spécialité</label>
                            <input type="text" name="specialite" value="<?php echo htmlspecialchars($etudiant_info['specialite'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="experience-label">
                                <i class="fas fa-briefcase"></i> Expérience professionnelle (années)
                            </label>
                            <div class="experience-input-wrapper">
                                <input type="number" name="experience" class="experience-input" value="<?php echo htmlspecialchars($etudiant_info['experience'] ?? 0); ?>" min="0" step="0.5" required>
                                <div class="experience-indicator">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn primary">Enregistrer</button>
                    </form>
                </div>

                <div class="dashboard-card">
                    <h3>Badges</h3>
                    <div class="badge-score-container">
                        <div class="badge-score">
                            <div class="score-icon">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <div class="score-info">
                                <span class="score-label">Score total</span>
                                <span class="score-value"><?php echo $badge_score; ?></span>
                            </div>
                        </div>
                        <div class="badge-stats">
                            <div class="stat-badge">
                                <span class="stat-number"><?php echo count($etudiant_info['badges'] ?? []); ?></span>
                                <span class="stat-text">Badges</span>
                            </div>
                            <div class="stat-badge">
                                <span class="stat-number"><?php echo $badge_score; ?></span>
                                <span class="stat-text">Points</span>
                            </div>
                        </div>
                    </div>
                    <div class="badges-list">
                        <?php
                        // Définir les types de badges selon le score
                        function getBadgeInfo($score) {
                            if ($score >= 90) {
                                return ['name' => 'Expert', 'icon' => 'fa-crown', 'desc' => 'Niveau expert atteint', 'color' => '#ffd700'];
                            } elseif ($score >= 70) {
                                return ['name' => 'Avancé', 'icon' => 'fa-star', 'desc' => 'Niveau avancé atteint', 'color' => '#00d4c4'];
                            } elseif ($score >= 50) {
                                return ['name' => 'Intermédiaire', 'icon' => 'fa-medal', 'desc' => 'Niveau intermédiaire atteint', 'color' => '#6366f1'];
                            } elseif ($score >= 30) {
                                return ['name' => 'Débutant', 'icon' => 'fa-seedling', 'desc' => 'Niveau débutant atteint', 'color' => '#10b981'];
                            } else {
                                return ['name' => 'Novice', 'icon' => 'fa-award', 'desc' => 'Niveau novice', 'color' => '#94a3b8'];
                            }
                        }
                        
                        if (!empty($student_badges)):
                            foreach ($student_badges as $badge):
                                $badge_score = $badge['score'] ?? 0;
                                $badge_info = getBadgeInfo($badge_score);
                                $badge_level = min(3, max(1, ceil($badge_score / 30))); // Convertir score en niveau 1-3
                        ?>
                        <div class="badge-item">
                            <div class="badge-icon" style="color: <?php echo $badge_info['color']; ?>;">
                                <i class="fas <?php echo $badge_info['icon']; ?>"></i>
                            </div>
                            <div class="badge-info">
                                <h4><?php echo htmlspecialchars($badge_info['name']); ?></h4>
                                <p><?php echo htmlspecialchars($badge_info['desc']); ?></p>
                            </div>
                            <div class="badge-score-display">
                                <span class="score-number"><?php echo $badge_score; ?></span>
                                <span class="score-label">pts</span>
                            </div>
                            <div class="badge-level">
                                <?php for($i = 1; $i <= 3; $i++): ?>
                                    <span class="badge-star <?php echo $i > $badge_level ? 'empty' : ''; ?>" style="color: <?php echo $badge_info['color']; ?>;">
                                        <i class="fas fa-star"></i>
                                    </span>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php endforeach; else: ?>
                        <p style="text-align: center; color: #e2e8f0; padding: 2rem;">Aucun badge obtenu pour le moment</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Historique Section -->
            <div class="dashboard-card" style="margin-top: 2rem;">
                <h3>Historique des activités</h3>
                
                <!-- Participations aux événements -->
                <div style="margin-bottom: 2rem;">
                    <h4 style="color: #00d4c4; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-calendar-alt"></i> Participations aux événements
                    </h4>
                    <?php if (!empty($mes_participations)): ?>
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <?php foreach ($mes_participations as $participation): ?>
                                <div style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 12px; padding: 1rem; display: flex; align-items: center; gap: 1rem;">
                                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #00d4c4, #00b4d8); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white;">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <div style="flex: 1;">
                                        <h5 style="margin: 0; color: #ffffff; font-size: 0.95rem;"><?php echo htmlspecialchars($participation['event_titre']); ?></h5>
                                        <p style="margin: 0.25rem 0 0 0; color: #e2e8f0; font-size: 0.85rem;">
                                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($participation['lieu']); ?> • 
                                            <i class="fas fa-clock"></i> <?php echo date('d/m/Y', strtotime($participation['dateEvent'])); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="text-align: center; color: #6c757d; padding: 2rem;">Aucune participation enregistrée</p>
                    <?php endif; ?>
                </div>
                
                <!-- Candidatures aux offres -->
                <div>
                    <h4 style="color: #00d4c4; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-briefcase" id="mescondidateur"></i> Candidatures aux offres
                    </h4>
                    <?php if (!empty($mes_candidatures)): ?>
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <?php foreach ($mes_candidatures as $candidature): ?>
                                <div style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 12px; padding: 1rem; display: flex; align-items: center; gap: 1rem;">
                                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white;">
                                        <i class="fas fa-briefcase"></i>
                                    </div>
                                    <div style="flex: 1;">
                                        <h5 style="margin: 0; color: #ffffff; font-size: 0.95rem;"><?php echo htmlspecialchars($candidature['offre_titre']); ?></h5>
                                        <p style="margin: 0.25rem 0 0 0; color: #e2e8f0; font-size: 0.85rem;">
                                            <i class="fas fa-building"></i> <?php echo htmlspecialchars($candidature['nomsociete']); ?> • 
                                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($candidature['offre_type']); ?>
                                        </p>
                                    </div>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="color: #e2e8f0; font-size: 0.9rem;">Aucune candidature aux offres</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../public/assets/js/main.js"></script>

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
                    <h4>Assistant ESEN NET</h4>
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
                        <p>Bonjour ! Je suis votre assistant ESEN NET. Je peux analyser votre profil et vous recommander les meilleures offres et événements selon vos compétences et badges. Voulez-vous que je fasse une analyse ?</p>
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
        window.studentData = {
            badges: <?php echo json_encode($etudiant_info['badges'] ?? ['Débutant']); ?>,
            skills: <?php echo json_encode($etudiant_info['specialite'] ?? 'Informatique'); ?>,
            offers: <?php echo json_encode($offres ?? []); ?>,
            events: <?php echo json_encode($events ?? []); ?>
        };

        // Fonction pour prévisualiser la photo
        function previewPhoto(event) {
            const file = event.target.files[0];
            if (file) {
                // Vérifier le type de fichier
                if (!file.type.startsWith('image/')) {
                    alert('Veuillez sélectionner une image valide (JPG, PNG, GIF)');
                    event.target.value = '';
                    return;
                }
                
                // Vérifier la taille (max 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('L\'image ne doit pas dépasser 2MB');
                    event.target.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const currentPhoto = document.querySelector('.current-photo');
                    currentPhoto.innerHTML = `<img src="${e.target.result}" alt="Aperçu" class="profile-photo">`;
                };
                reader.readAsDataURL(file);
            }
        }
    </script>

    
<!-- Chatbot JavaScript -->
    <script src="../../public/assets/js/chatbot.js"></script>
</body>
</html>
