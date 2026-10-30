<?php
require_once 'app/config/db_connect.php';
require_once 'app/Views/layouts/components.php';

// Récupérer les statistiques dynamiques
$stats = [
    'students' => 0,
    'companies' => 0,
    'offers' => 0
];

// Nombre d'étudiants
$student_query = "SELECT COUNT(*) as count FROM etudiant";
$student_result = $conn->query($student_query);
if ($student_result) {
    $stats['students'] = $student_result->fetch()['count'];
}

// Nombre d'entreprises
$company_query = "SELECT COUNT(*) as count FROM utilisateur WHERE statut = 'ENTREPRISE'";
$company_result = $conn->query($company_query);
if ($company_result) {
    $stats['companies'] = $company_result->fetch()['count'];
}

// Nombre d'offres
$offer_query = "SELECT COUNT(*) as count FROM offre";
$offer_result = $conn->query($offer_query);
if ($offer_result) {
    $stats['offers'] = $offer_result->fetch()['count'];
}

// Traitement de la soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capturer toutes les sorties pour éviter de corrompre le JSON
    ob_start();
    header('Content-Type: application/json');
    
    // Activer le rapport d'erreurs pour capturer les erreurs PHP
    error_reporting(E_ALL);
    ini_set('display_errors', 0); // Ne pas afficher les erreurs à l'écran
    
    try {
        $action = $_POST['action'] ?? '';
        $response = ['success' => false, 'message' => 'Action invalide'];
    
    // Gestion de l'upload de fichier
    $photo_path = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'app/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '.' . $file_ext;
        $photo_path = $upload_dir . $file_name;
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
            $response = ['success' => false, 'message' => 'Erreur lors de l\'upload de la photo'];
            echo json_encode($response);
            exit();
        }
    }
    
    if ($action === 'register_student') {
        $prenom = sanitize_input($_POST['prenom'] ?? '');
        $nom = sanitize_input($_POST['nom'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $mot_de_passe = $_POST['mot_de_passe'] ?? '';
        $telephone = sanitize_input($_POST['telephone'] ?? '');
        $cin = sanitize_input($_POST['cin'] ?? '');
        $promotion = sanitize_input($_POST['promotion'] ?? '');
        $specialite = sanitize_input($_POST['specialite'] ?? '');
        $experience = sanitize_input($_POST['experience'] ?? '');
        
        // Validation et formatage du numéro de téléphone
        if (!empty($telephone)) {
            // Supprimer tous les caractères non numériques
            $telephone_clean = preg_replace('/[^0-9]/', '', $telephone);
            
            // Vérifier que le numéro contient exactement 8 chiffres
            if (strlen($telephone_clean) !== 8) {
                $error_message = "Le numéro de téléphone doit contenir exactement 8 chiffres.";
            } else {
                // Formater le numéro sous la forme XX XXX XXX
                $telephone = substr($telephone_clean, 0, 2) . ' ' . substr($telephone_clean, 2, 3) . ' ' . substr($telephone_clean, 5, 3);
            }
        }
        
        // Gestion du CV
        $cv_path = null;
        if (isset($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'app/uploads/cv/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_ext = pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION);
            $file_name = 'cv_' . $prenom . '_' . $nom . '_' . uniqid() . '.' . $file_ext;
            $cv_path = $upload_dir . $file_name;
            if (!move_uploaded_file($_FILES['cv']['tmp_name'], $cv_path)) {
                $response = ['success' => false, 'message' => 'Erreur lors de l\'upload du CV'];
                echo json_encode($response);
                exit();
            }
        }
        
        if ($prenom && $nom && $email && $mot_de_passe) {
            $result = create_student($prenom, $nom, $email, $mot_de_passe, $telephone, $cin, $promotion, $specialite, $experience, $photo_path, $cv_path);
            if ($result['success']) {
                $_SESSION['user_id'] = $result['user_id'];
                $_SESSION['role'] = 'etudiant';
                $_SESSION['etudiant_id'] = $result['user_id'];
                $response = ['success' => true, 'message' => 'Inscription réussie', 'redirect' => 'app/Views/student/home.php'];
            } else {
                $response = ['success' => false, 'message' => $result['message']];
            }
        } else {
            $response = ['success' => false, 'message' => 'Veuillez remplir tous les champs'];
        }
    } elseif ($action === 'register_company') {
        $nom_entreprise = sanitize_input($_POST['nom_entreprise'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $mot_de_passe = $_POST['mot_de_passe'] ?? '';
        $telephone = sanitize_input($_POST['telephone'] ?? '');
        
        // Validation et formatage du numéro de téléphone
        if (!empty($telephone)) {
            // Supprimer tous les caractères non numériques
            $telephone_clean = preg_replace('/[^0-9]/', '', $telephone);
            
            // Vérifier que le numéro contient exactement 8 chiffres
            if (strlen($telephone_clean) !== 8) {
                $error_message = "Le numéro de téléphone doit contenir exactement 8 chiffres.";
            } else {
                // Formater le numéro sous la forme XX XXX XXX
                $telephone = substr($telephone_clean, 0, 2) . ' ' . substr($telephone_clean, 2, 3) . ' ' . substr($telephone_clean, 5, 3);
            }
        }
        
        if ($nom_entreprise && $email && $mot_de_passe) {
            $result = create_company($nom_entreprise, $email, $mot_de_passe, $telephone, null, $photo_path);
            if ($result['success']) {
                $_SESSION['user_id'] = $result['user_id'];
                $_SESSION['role'] = 'entreprise';
                $_SESSION['entreprise_id'] = $result['user_id'];
                $response = ['success' => true, 'message' => 'Inscription réussie', 'redirect' => 'app/Views/entreprise/home.php'];
            } else {
                $response = ['success' => false, 'message' => $result['message']];
            }
        } else {
            $response = ['success' => false, 'message' => 'Veuillez remplir tous les champs'];
        }
    } elseif ($action === 'register_teacher') {
        $prenom = sanitize_input($_POST['prenom'] ?? '');
        $nom = sanitize_input($_POST['nom'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $mot_de_passe = $_POST['mot_de_passe'] ?? '';
        $telephone = sanitize_input($_POST['telephone'] ?? '');
        
        // Validation et formatage du numéro de téléphone
        if (!empty($telephone)) {
            // Supprimer tous les caractères non numériques
            $telephone_clean = preg_replace('/[^0-9]/', '', $telephone);
            
            // Vérifier que le numéro contient exactement 8 chiffres
            if (strlen($telephone_clean) !== 8) {
                $error_message = "Le numéro de téléphone doit contenir exactement 8 chiffres.";
            } else {
                // Formater le numéro sous la forme XX XXX XXX
                $telephone = substr($telephone_clean, 0, 2) . ' ' . substr($telephone_clean, 2, 3) . ' ' . substr($telephone_clean, 5, 3);
            }
        }
        
        if ($prenom && $nom && $email && $mot_de_passe) {
            $result = create_teacher($prenom, $nom, $email, $mot_de_passe, $telephone, null, $photo_path);
            if ($result['success']) {
                $_SESSION['user_id'] = $result['user_id'];
                $_SESSION['role'] = 'enseignant';
                $_SESSION['teacher_id'] = $result['user_id'];
                $response = ['success' => true, 'message' => 'Inscription réussie', 'redirect' => 'app/Views/teacher/home.php'];
            } else {
                $response = ['success' => false, 'message' => $result['message']];
            }
        } else {
            $response = ['success' => false, 'message' => 'Veuillez remplir tous les champs'];
        }
    }
    
    } catch (Exception $e) {
        // Capturer toute exception et l'inclure dans la réponse JSON
        $response = ['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()];
    } catch (Error $e) {
        // Capturer les erreurs fatales PHP
        $response = ['success' => false, 'message' => 'Erreur système: ' . $e->getMessage()];
    }
    
    // Nettoyer le buffer de sortie pour éviter les erreurs HTML
    ob_end_clean();
    echo json_encode($response);
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - ESEN NET</title>
    <meta name="description" content="Créez votre compte ESEN NET">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="app/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="app/public/assets/css/main.css">
    <link rel="stylesheet" href="app/public/assets/css/navbar.css">
    <link rel="stylesheet" href="app/public/assets/css/login-pro.css">
    <link rel="icon" href="app/public/assets/images/WhatsApp Image 2026-04-30 at 19.20.19.jpeg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
    <script>
        // Theme Toggle Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.querySelector('.toggle-dark');
            
            // Check for saved theme preference or default to light
            const savedTheme = localStorage.getItem('theme') || 'light';
            if (savedTheme === 'dark') {
                document.body.classList.add('dark');
            }
            
            themeToggle.addEventListener('click', function() {
                document.body.classList.toggle('dark');
                const isDark = document.body.classList.contains('dark');
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
            });

            // Mobile menu toggle
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const verticalNavbar = document.getElementById('verticalNavbar');

            if (mobileMenuToggle && verticalNavbar) {
                mobileMenuToggle.addEventListener('click', function() {
                    verticalNavbar.classList.toggle('active');
                });

                // Close menu when clicking outside
                document.addEventListener('click', function(e) {
                    if (!verticalNavbar.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
                        verticalNavbar.classList.remove('active');
                    }
                });
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
            const interactiveElements = document.querySelectorAll('a, button, input, .role-tab');
            interactiveElements.forEach(el => {
                el.addEventListener('mouseenter', () => cursor.classList.add('hover'));
                el.addEventListener('mouseleave', () => cursor.classList.remove('hover'));
            });

            // Click effect
            document.addEventListener('mousedown', () => cursor.classList.add('clicking'));
            document.addEventListener('mouseup', () => cursor.classList.remove('clicking'));
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

    <!-- Grid Pattern -->
    <div class="grid-pattern"></div>

    <!-- Custom Cursor -->
    <div class="custom-cursor"></div>
    <div class="custom-cursor-dot"></div>

    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Navbar Verticale -->
    <header id="verticalNavbar">
        <div class="logo">
            <i class="fas fa-graduation-cap"></i> ESEN NET
        </div>
        <nav class="nav-links">
            <a href="index.php"><i class="fas fa-home"></i> Accueil</a>
            <a href="#offre"><i class="fas fa-briefcase"></i> Offres</a>
            <a href="#evenement"><i class="fas fa-calendar-alt"></i> Événements</a>
            <a href="login.php"><i class="fas fa-sign-in-alt"></i> Connexion</a>
            <a href="signup.php" class="active"><i class="fas fa-user-plus"></i> Inscription</a>
        </nav>
        <button class="toggle-dark">🌙 Dark</button>
    </header>

    <!-- Main Content Container -->
    <main class="main-content-wrapper">
        <!-- Signup Section -->
        <section class="login-section">
            <div class="login-container">
                <div class="login-card">
                    <!-- Left Side - Branding -->
                    <div class="login-branding">
                        <div class="branding-logo">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h2>Rejoignez ESEN NET</h2>
                        <p>La plateforme qui connecte les étudiants, enseignants et entreprises pour construire l'avenir professionnel.</p>

                        <div class="login-stats">
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $stats['students']; ?>+</div>
                                <div class="stat-label">Étudiants</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $stats['companies']; ?>+</div>
                                <div class="stat-label">Entreprises</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $stats['offers']; ?>+</div>
                                <div class="stat-label">Offres</div>
                            </div>
                        </div>

                        <div class="login-features">
                            <h4>Pourquoi nous rejoindre ?</h4>
                            <div class="feature-item">
                                <div class="feature-icon">
                                    <i class="fas fa-briefcase"></i>
                                </div>
                                <div class="feature-content">
                                    <h5>Offres de stage</h5>
                                    <p>Accès à des centaines d'opportunités</p>
                                </div>
                            </div>
                            <div class="feature-item">
                                <div class="feature-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="feature-content">
                                    <h5>Réseau pro</h5>
                                    <p>Connectez-vous avec des experts</p>
                                </div>
                            </div>
                            <div class="feature-item">
                                <div class="feature-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div class="feature-content">
                                    <h5>Événements</h5>
                                    <p>Formations et rencontres exclusives</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side - Signup Form -->
                    <div class="login-form">
                        <div class="login-header">
                            <div class="header-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <h3>Créer votre compte</h3>
                            <p>Rejoignez la communauté ESEN NET</p>
                        </div>

                        <!-- Role Tabs -->
                        <div class="role-tabs">
                            <button class="role-tab active" data-role="student">
                                <i class="fas fa-user-graduate"></i>
                                <span>Étudiant</span>
                            </button>
                            <button class="role-tab" data-role="company">
                                <i class="fas fa-building"></i>
                                <span>Entreprise</span>
                            </button>
                            <button class="role-tab" data-role="teacher">
                                <i class="fas fa-chalkboard-teacher"></i>
                                <span>Enseignant</span>
                            </button>
                        </div>

                        <!-- Tab Content -->
                        <div class="tab-content">
                            <!-- Student Signup -->
                            <div class="tab-pane active" id="student">
                                <form id="studentForm">
                                    <div class="form-group">
                                        <label for="studentFirstName">
                                            <i class="fas fa-user"></i> Prénom
                                        </label>
                                        <input type="text" id="studentFirstName" placeholder="Entrez votre prénom" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="studentLastName">
                                            <i class="fas fa-user"></i> Nom
                                        </label>
                                        <input type="text" id="studentLastName" placeholder="Entrez votre nom" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="studentEmail">
                                            <i class="fas fa-envelope"></i> Email
                                        </label>
                                        <input type="email" id="studentEmail" placeholder="email@exemple.com" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="studentPhone">
                                            <i class="fas fa-phone"></i> Téléphone
                                        </label>
                                        <input type="tel" id="studentPhone" placeholder="+216 XX XXX XXX" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="studentCin">
                                            <i class="fas fa-id-card"></i> CIN
                                        </label>
                                        <input type="text" id="studentCin" placeholder="Entrez votre numéro CIN" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="studentPromotion">
                                            <i class="fas fa-graduation-cap"></i> Promotion
                                        </label>
                                        <input type="text" id="studentPromotion" placeholder="L1 - L2 - L3 - M1 - M2" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="studentSpecialite">
                                            <i class="fas fa-book"></i> Spécialité
                                        </label>
                                        <input type="text" id="studentSpecialite" placeholder="BIS - BI - E_Bus" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="studentExperience" class="experience-label">
                                            <i class="fas fa-briefcase"></i> Expérience professionnelle (années)
                                        </label>
                                        <div class="experience-input-wrapper">
                                            <input type="number" id="studentExperience" class="experience-input" placeholder="Nombre d'années d'expérience" min="0" step="1" required>
                                            <div class="experience-indicator">
                                                <i class="fas fa-chart-line"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="studentCv">
                                            <i class="fas fa-file-pdf"></i> CV (PDF)
                                        </label>
                                        <input type="file" id="studentCv" accept=".pdf,.doc,.docx" placeholder="Choisissez votre CV">
                                    </div>
                                    <div class="form-group">
                                        <label for="studentPhoto">
                                            <i class="fas fa-camera"></i> Photo de profil
                                        </label>
                                        <input type="file" id="studentPhoto" accept="image/*" placeholder="Choisissez votre photo">
                                    </div>
                                    <div class="form-group">
                                        <label for="studentPassword">
                                            <i class="fas fa-lock"></i> Mot de passe
                                        </label>
                                        <div class="password-input">
                                            <input type="password" id="studentPassword" placeholder="Entrez votre mot de passe" required>
                                            <button type="button" id="toggleStudentPassword" class="password-toggle">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="studentConfirmPassword">
                                            <i class="fas fa-lock"></i> Confirmer le mot de passe
                                        </label>
                                        <input type="password" id="studentConfirmPassword" placeholder="Confirmez votre mot de passe" required>
                                    </div>
                                    <div class="form-group checkbox">
                                        <input type="checkbox" id="studentTerms" required>
                                        <label for="studentTerms">J'accepte les conditions d'utilisation</label>
                                    </div>
                                    <button type="submit" class="btn primary">
                                        <span>Créer mon compte</span>
                                        <i class="fas fa-arrow-right"></i>
                                    </button>
                                    <div class="signup-link">
                                        <span>Vous avez déjà un compte ?</span>
                                        <a href="login.php">Se connecter</a>
                                    </div>
                                </form>
                            </div>

                            <!-- Company Signup -->
                            <div class="tab-pane" id="company">
                                <form id="companyForm">
                                    <div class="form-group">
                                        <label for="companyName">
                                            <i class="fas fa-building"></i> Nom de l'entreprise
                                        </label>
                                        <input type="text" id="companyName" placeholder="Nom de votre entreprise" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="companyEmail">
                                            <i class="fas fa-envelope"></i> Email professionnel
                                        </label>
                                        <input type="email" id="companyEmail" placeholder="email@entreprise.com" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="companyPhone">
                                            <i class="fas fa-phone"></i> Téléphone
                                        </label>
                                        <input type="tel" id="companyPhone" placeholder="+216 XX XXX XXX" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="companyLogo">
                                            <i class="fas fa-image"></i> Logo de l'entreprise
                                        </label>
                                        <input type="file" id="companyLogo" accept="image/*" placeholder="Choisissez votre logo">
                                    </div>
                                    <div class="form-group">
                                        <label for="companyPassword">
                                            <i class="fas fa-lock"></i> Mot de passe
                                        </label>
                                        <div class="password-input">
                                            <input type="password" id="companyPassword" placeholder="Entrez votre mot de passe" required>
                                            <button type="button" id="toggleCompanyPassword" class="password-toggle">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="companyConfirmPassword">
                                            <i class="fas fa-lock"></i> Confirmer le mot de passe
                                        </label>
                                        <input type="password" id="companyConfirmPassword" placeholder="Confirmez votre mot de passe" required>
                                    </div>
                                    <div class="form-group checkbox">
                                        <input type="checkbox" id="companyTerms" required>
                                        <label for="companyTerms">J'accepte les conditions d'utilisation</label>
                                    </div>
                                    <button type="submit" class="btn primary">
                                        <span>Créer mon compte</span>
                                        <i class="fas fa-arrow-right"></i>
                                    </button>
                                    <div class="signup-link">
                                        <span>Vous avez déjà un compte ?</span>
                                        <a href="login.php">Se connecter</a>
                                    </div>
                                </form>
                            </div>

                            <!-- Teacher Signup -->
                            <div class="tab-pane" id="teacher">
                                <form id="teacherForm">
                                    <div class="form-group">
                                        <label for="teacherFirstName">
                                            <i class="fas fa-user"></i> Prénom
                                        </label>
                                        <input type="text" id="teacherFirstName" placeholder="Entrez votre prénom" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="teacherLastName">
                                            <i class="fas fa-user"></i> Nom
                                        </label>
                                        <input type="text" id="teacherLastName" placeholder="Entrez votre nom" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="teacherEmail">
                                            <i class="fas fa-envelope"></i> Email institutionnel
                                        </label>
                                        <input type="email" id="teacherEmail" placeholder="email@exemple.com" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="teacherPhone">
                                            <i class="fas fa-phone"></i> Téléphone
                                        </label>
                                        <input type="tel" id="teacherPhone" placeholder="+216 XX XXX XXX" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="teacherPhoto">
                                            <i class="fas fa-camera"></i> Photo de profil
                                        </label>
                                        <input type="file" id="teacherPhoto" accept="image/*" placeholder="Choisissez votre photo">
                                    </div>
                                    <div class="form-group">
                                        <label for="teacherPassword">
                                            <i class="fas fa-lock"></i> Mot de passe
                                        </label>
                                        <div class="password-input">
                                            <input type="password" id="teacherPassword" placeholder="Entrez votre mot de passe" required>
                                            <button type="button" id="toggleTeacherPassword" class="password-toggle">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="teacherConfirmPassword">
                                            <i class="fas fa-lock"></i> Confirmer le mot de passe
                                        </label>
                                        <input type="password" id="teacherConfirmPassword" placeholder="Confirmez votre mot de passe" required>
                                    </div>
                                    <div class="form-group checkbox">
                                        <input type="checkbox" id="teacherTerms" required>
                                        <label for="teacherTerms">J'accepte les conditions d'utilisation</label>
                                    </div>
                                    <button type="submit" class="btn primary">
                                        <span>Créer mon compte</span>
                                        <i class="fas fa-arrow-right"></i>
                                    </button>
                                    <div class="signup-link">
                                        <span>Vous avez déjà un compte ?</span>
                                        <a href="login.php">Se connecter</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="public/assets/js/main.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Formatage des numéros de téléphone (XX XXX XXX)
            function setupPhoneFormatting() {
                const phoneInputs = document.querySelectorAll('input[type="tel"]');
                phoneInputs.forEach(function(input) {
                    input.addEventListener('input', function(e) {
                        // N'autoriser que les chiffres et espaces
                        let value = this.value.replace(/[^0-9 ]/g, '');
                        
                        // Compter les chiffres (ignorer les espaces)
                        let digitsOnly = value.replace(/[^0-9]/g, '');
                        
                        // Limiter à 8 chiffres maximum mais permettre la correction
                        if (digitsOnly.length > 8) {
                            digitsOnly = digitsOnly.slice(0, 8);
                        }
                        
                        // Appliquer le formatage XX XXX XXX uniquement si on a assez de chiffres
                        if (digitsOnly.length >= 2) {
                            value = digitsOnly.slice(0, 2);
                            if (digitsOnly.length > 2) {
                                value += ' ' + digitsOnly.slice(2, 5);
                            }
                            if (digitsOnly.length > 5) {
                                value += ' ' + digitsOnly.slice(5, 8);
                            }
                        } else {
                            value = digitsOnly;
                        }
                        
                        this.value = value;
                    });
                    
                    // Validation au blur
                    input.addEventListener('blur', function() {
                        const digitsOnly = this.value.replace(/[^0-9]/g, '');
                        if (digitsOnly && digitsOnly.length !== 8) {
                            this.setCustomValidity('Le numéro de téléphone doit contenir exactement 8 chiffres');
                            this.reportValidity();
                        } else {
                            this.setCustomValidity('');
                        }
                    });
                });
            }
            
            // Password toggle functionality
            function setupPasswordToggle(toggleId, inputId) {
                const toggle = document.getElementById(toggleId);
                const input = document.getElementById(inputId);
                
                toggle.addEventListener('click', function() {
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    
                    const icon = toggle.querySelector('i');
                    if (type === 'text') {
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            }
            
            setupPhoneFormatting();
            setupPasswordToggle('toggleStudentPassword', 'studentPassword');
            setupPasswordToggle('toggleCompanyPassword', 'companyPassword');
            setupPasswordToggle('toggleTeacherPassword', 'teacherPassword');
            
            // Role tabs functionality
            const roleTabs = document.querySelectorAll('.role-tab');
            const tabPanes = document.querySelectorAll('.tab-pane');
            
            roleTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const role = this.getAttribute('data-role');
                    
                    // Remove active class from all tabs and panes
                    roleTabs.forEach(t => t.classList.remove('active'));
                    tabPanes.forEach(p => p.classList.remove('active'));
                    
                    // Add active class to clicked tab and corresponding pane
                    this.classList.add('active');
                    document.getElementById(role).classList.add('active');
                });
            });
            
            // Form submission handlers
            document.getElementById('studentForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const prenom = document.getElementById('studentFirstName').value;
                const nom = document.getElementById('studentLastName').value;
                const email = document.getElementById('studentEmail').value;
                const password = document.getElementById('studentPassword').value;
                const confirmPassword = document.getElementById('studentConfirmPassword').value;
                
                // Validation basique de format email (pas de restriction @esen.tn)
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    alert('Veuillez entrer un email valide');
                    return;
                }
                
                if (password !== confirmPassword) {
                    alert('Les mots de passe ne correspondent pas');
                    return;
                }
                
                if (password.length < 8) {
                    alert('Le mot de passe doit contenir au moins 8 caractères');
                    return;
                }
                
                // Envoyer les données via AJAX
                const formData = new FormData();
                formData.append('action', 'register_student');
                formData.append('prenom', document.getElementById('studentFirstName').value);
                formData.append('nom', document.getElementById('studentLastName').value);
                formData.append('email', document.getElementById('studentEmail').value);
                formData.append('telephone', document.getElementById('studentPhone').value);
                formData.append('cin', document.getElementById('studentCin').value);
                formData.append('promotion', document.getElementById('studentPromotion').value);
                formData.append('specialite', document.getElementById('studentSpecialite').value);
                formData.append('experience', document.getElementById('studentExperience').value);
                formData.append('mot_de_passe', password);
                
                // Ajouter la photo si elle est sélectionnée
                const photoInput = document.getElementById('studentPhoto');
                if (photoInput.files.length > 0) {
                    formData.append('photo', photoInput.files[0]);
                }
                
                // Ajouter le CV s'il est sélectionné
                const cvInput = document.getElementById('studentCv');
                if (cvInput.files.length > 0) {
                    formData.append('cv', cvInput.files[0]);
                }
                
                fetch('signup.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erreur serveur: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert('Inscription réussie! Redirection vers le tableau de bord étudiant...');
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1000);
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors de l\'inscription: ' + error.message);
                });
            });
            
            document.getElementById('companyForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const nomEntreprise = document.getElementById('companyName').value;
                const email = document.getElementById('companyEmail').value;
                const password = document.getElementById('companyPassword').value;
                const confirmPassword = document.getElementById('companyConfirmPassword').value;
                
                // Pour les entreprises, autoriser n'importe quel email professionnel (pas de validation restrictive)
                // Les entreprises peuvent utiliser n'importe quel email valide
                
                if (password !== confirmPassword) {
                    alert('Les mots de passe ne correspondent pas');
                    return;
                }
                
                if (password.length < 8) {
                    alert('Le mot de passe doit contenir au moins 8 caractères');
                    return;
                }
                
                // Envoyer les données via AJAX
                const formData = new FormData();
                formData.append('action', 'register_company');
                formData.append('nom_entreprise', document.getElementById('companyName').value);
                formData.append('email', document.getElementById('companyEmail').value);
                formData.append('telephone', document.getElementById('companyPhone').value);
                formData.append('mot_de_passe', password);
                
                // Ajouter le logo si il est sélectionné
                const logoInput = document.getElementById('companyLogo');
                if (logoInput.files.length > 0) {
                    formData.append('photo', logoInput.files[0]);
                }
                
                fetch('signup.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erreur serveur: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert('Inscription réussie! Redirection vers le tableau de bord entreprise...');
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1000);
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors de l\'inscription: ' + error.message);
                });
            });
            
            document.getElementById('teacherForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const prenom = document.getElementById('teacherFirstName').value;
                const nom = document.getElementById('teacherLastName').value;
                const email = document.getElementById('teacherEmail').value;
                const password = document.getElementById('teacherPassword').value;
                const confirmPassword = document.getElementById('teacherConfirmPassword').value;
                
                // Validation basique de format email (pas de restriction @esen.tn)
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    alert('Veuillez entrer un email valide');
                    return;
                }
                
                if (password !== confirmPassword) {
                    alert('Les mots de passe ne correspondent pas');
                    return;
                }
                
                if (password.length < 8) {
                    alert('Le mot de passe doit contenir au moins 8 caractères');
                    return;
                }
                
                // Envoyer les données via AJAX
                const formData = new FormData();
                formData.append('action', 'register_teacher');
                formData.append('prenom', document.getElementById('teacherFirstName').value);
                formData.append('nom', document.getElementById('teacherLastName').value);
                formData.append('email', document.getElementById('teacherEmail').value);
                formData.append('telephone', document.getElementById('teacherPhone').value);
                formData.append('mot_de_passe', password);
                
                // Ajouter la photo si elle est sélectionnée
                const photoInput = document.getElementById('teacherPhoto');
                if (photoInput.files.length > 0) {
                    formData.append('photo', photoInput.files[0]);
                }
                
                fetch('signup.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erreur serveur: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert('Inscription réussie! Redirection vers le tableau de bord enseignant...');
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1000);
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors de l\'inscription: ' + error.message);
                });
            });
        });
    </script>
</body>
</html>
