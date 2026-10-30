<?php
require_once 'app/config/db_connect.php';

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

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email']);
    $motDePasse = $_POST['password']; // Ne pas sanitizer le mot de passe
    $role = sanitize_input($_POST['role']);

    // Pour les entreprises, autoriser n'importe quel email professionnel
    if ($role !== 'ENTREPRISE' && !str_ends_with($email, '@esen.tn')) {
        $error_message = "L'email doit être au format @esen.tn";
    } else {
        // Vérifier les identifiants dans la table utilisateur
        $sql = "SELECT * FROM utilisateur WHERE email = ? AND statut = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$email, $role]);
        $result = $stmt->fetchAll();

        if (count($result) > 0) {
            $user = $result[0];

            // Vérifier le mot de passe (supporte les mots de passe hachés et non hachés)
            if (password_verify($motDePasse, $user['motDePasse']) || $motDePasse === $user['motDePasse']) {
                // Créer la session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['statut'] = $user['statut'];

                // Rediriger selon le rôle
                if ($role === 'ENTREPRISE') {
                    $_SESSION['entreprise_id'] = $user['id'];
                    header("Location: app/Views/entreprise/home.php");
                    exit();
                } elseif ($role === 'ETUDIANT') {
                    $_SESSION['etudiant_id'] = $user['id'];
                    header("Location: app/Views/student/home.php");
                    exit();
                } elseif ($role === 'ENSEIGNANT') {
                    $_SESSION['teacher_id'] = $user['id'];
                    header("Location: app/Views/teacher/home.php");
                    exit();
                } elseif ($role === 'ADMIN') {
                    $_SESSION['admin_id'] = $user['id'];
                    header("Location: app/Views/admin/dashboard.php");
                    exit();
                }
            } else {
                $error_message = "Mot de passe incorrect";
            }
        } else {
            $error_message = "Email ou rôle incorrect";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - ESEN NET</title>
    <meta name="description" content="Connectez-vous à votre compte ESEN NET">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="app/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="app/public/assets/css/main.css">
    <link rel="stylesheet" href="app/public/assets/css/navbar.css">
    <link rel="stylesheet" href="app/public/assets/css/login-pro.css">
    <link rel="icon" href="app/public/assets/images/WhatsApp Image 2026-04-30 at 19.20.19.jpeg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            <a href="login.php" class="active"><i class="fas fa-sign-in-alt"></i> Connexion</a>
            <a href="signup.php"><i class="fas fa-user-plus"></i> Inscription</a>
        </nav>
        <button class="toggle-dark">🌙 Dark</button>
    </header>

    <!-- Main Content Container -->
    <main class="main-content-wrapper">
        <!-- Login Section -->
        <section class="login-section">
            <div class="login-container">
                <div class="login-card">
                    <!-- Left Side - Branding -->
                    <div class="login-branding">
                        <div class="branding-logo">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h2>Bienvenue sur ESEN NET</h2>
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

                    <!-- Right Side - Login Form -->
                    <div class="login-form">
                        <div class="login-header">
                            <div class="header-icon">
                                <i class="fas fa-sign-in-alt"></i>
                            </div>
                            <h3>Connexion</h3>
                            <p>Accédez à votre espace personnel</p>
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
                            <button class="role-tab" data-role="admin">
                                <i class="fas fa-shield-alt"></i>
                                <span>Admin</span>
                            </button>
                        </div>

                        <!-- Tab Content -->
                        <div class="tab-content">
                            <!-- Student Login -->
                            <div class="tab-pane active" id="student">
                                <?php if (isset($error_message)): ?>
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <?php echo $error_message; ?>
                                    </div>
                                <?php endif; ?>
                                <form method="POST" action="">
                                    <input type="hidden" name="role" value="ETUDIANT">
                                    <div class="form-group">
                                        <label for="studentEmail">
                                            <i class="fas fa-envelope"></i> Email
                                        </label>
                                        <input type="email" id="studentEmail" name="email" placeholder="exemple@esen.tn" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="studentPassword">
                                            <i class="fas fa-lock"></i> Mot de passe
                                        </label>
                                        <div class="password-input">
                                            <input type="password" id="studentPassword" name="password" placeholder="Entrez votre mot de passe" required>
                                            <button type="button" id="toggleStudentPassword" class="password-toggle">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="form-options">
                                        <div class="form-group checkbox">
                                            <input type="checkbox" id="studentRemember">
                                            <label for="studentRemember">Se souvenir de moi</label>
                                        </div>

                                        <div class="forgot-password">
                                            <a href="#">Mot de passe oublié ?</a>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn primary">
                                        <span>Se connecter</span>
                                        <i class="fas fa-arrow-right"></i>
                                    </button>

                                    <div class="signup-link">
                                        <span>Pas encore de compte ?</span>
                                        <a href="signup.php">S'inscrire</a>
                                    </div>
                                </form>
                            </div>

                            <!-- Company Login -->
                            <div class="tab-pane" id="company">
                                <?php if (isset($error_message)): ?>
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <?php echo $error_message; ?>
                                    </div>
                                <?php endif; ?>
                                <form method="POST" action="">
                                    <input type="hidden" name="role" value="ENTREPRISE">
                                    <div class="form-group">
                                        <label for="companyEmail">
                                            <i class="fas fa-envelope"></i> Email professionnel
                                        </label>
                                        <input type="email" id="companyEmail" name="email" placeholder="contact@entreprise.com" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="companyPassword">
                                            <i class="fas fa-lock"></i> Mot de passe
                                        </label>
                                        <div class="password-input">
                                            <input type="password" id="companyPassword" name="password" placeholder="Entrez votre mot de passe" required>
                                            <button type="button" id="toggleCompanyPassword" class="password-toggle">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="form-options">
                                        <div class="form-group checkbox">
                                            <input type="checkbox" id="companyRemember">
                                            <label for="companyRemember">Se souvenir de moi</label>
                                        </div>

                                        <div class="forgot-password">
                                            <a href="#">Mot de passe oublié ?</a>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn primary">
                                        <span>Se connecter</span>
                                        <i class="fas fa-arrow-right"></i>
                                    </button>

                                    <div class="signup-link">
                                        <span>Pas encore de compte ?</span>
                                        <a href="signup.php">S'inscrire</a>
                                    </div>
                                </form>
                            </div>

                            <!-- Teacher Login -->
                            <div class="tab-pane" id="teacher">
                                <?php if (isset($error_message)): ?>
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <?php echo $error_message; ?>
                                    </div>
                                <?php endif; ?>
                                <form method="POST" action="">
                                    <input type="hidden" name="role" value="ENSEIGNANT">
                                    <div class="form-group">
                                        <label for="teacherEmail">
                                            <i class="fas fa-envelope"></i> Email institutionnel
                                        </label>
                                        <input type="email" id="teacherEmail" name="email" placeholder="nom@esen.tn" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="teacherPassword">
                                            <i class="fas fa-lock"></i> Mot de passe
                                        </label>
                                        <div class="password-input">
                                            <input type="password" id="teacherPassword" name="password" placeholder="Entrez votre mot de passe" required>
                                            <button type="button" id="toggleTeacherPassword" class="password-toggle">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="form-options">
                                        <div class="form-group checkbox">
                                            <input type="checkbox" id="teacherRemember">
                                            <label for="teacherRemember">Se souvenir de moi</label>
                                        </div>

                                        <div class="forgot-password">
                                            <a href="#">Mot de passe oublié ?</a>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn primary">
                                        <span>Se connecter</span>
                                        <i class="fas fa-arrow-right"></i>
                                    </button>

                                    <div class="signup-link">
                                        <span>Pas encore de compte ?</span>
                                        <a href="signup.php">S'inscrire</a>
                                    </div>
                                </form>
                            </div>

                            <!-- Admin Login -->
                            <div class="tab-pane" id="admin">
                                <?php if (isset($error_message)): ?>
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <?php echo $error_message; ?>
                                    </div>
                                <?php endif; ?>
                                <form method="POST" action="">
                                    <input type="hidden" name="role" value="ADMIN">
                                    <div class="form-group">
                                        <label for="adminEmail">
                                            <i class="fas fa-envelope"></i> Email admin
                                        </label>
                                        <input type="email" id="adminEmail" name="email" placeholder="admin@esen.tn" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="adminPassword">
                                            <i class="fas fa-lock"></i> Mot de passe
                                        </label>
                                        <div class="password-input">
                                            <input type="password" id="adminPassword" name="password" placeholder="Entrez votre mot de passe" required>
                                            <button type="button" id="toggleAdminPassword" class="password-toggle">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="form-options">
                                        <div class="form-group checkbox">
                                            <input type="checkbox" id="adminRemember">
                                            <label for="adminRemember">Se souvenir de moi</label>
                                        </div>

                                        <div class="forgot-password">
                                            <a href="#">Mot de passe oublié ?</a>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn primary">
                                        <span>Se connecter</span>
                                        <i class="fas fa-arrow-right"></i>
                                    </button>
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
    <script src="app/public/assets/js/main.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
            
            setupPasswordToggle('toggleStudentPassword', 'studentPassword');
            setupPasswordToggle('toggleCompanyPassword', 'companyPassword');
            setupPasswordToggle('toggleTeacherPassword', 'teacherPassword');
            setupPasswordToggle('toggleAdminPassword', 'adminPassword');
            
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
            
            // Les formulaires sont maintenant traités par PHP avec la base de données
        });
    </script>
</body>
</html>
