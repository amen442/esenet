<?php
require_once __DIR__ . '/../layouts/components.php';
require_once __DIR__ . '/../../config/db_connect.php';

// Obtenir l'ID de l'étudiant connecté
$etudiant_id = get_etudiant_id();

// Si aucun étudiant n'est connecté, rediriger vers la page de connexion
if (!$etudiant_id) {
    header("Location: ../../login.php");
    exit();
}


// Traitement de l'envoi de témoignage
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_testimonial'])) {
    $titre = sanitize_input($_POST['titre'] ?? '');
    $contenu = sanitize_input($_POST['contenu'] ?? '');
    $note = $_POST['note'] ?? null;
    
    // Gestion de l'upload de fichier
    $photo_path = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../../app/uploads/testimonials/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '.' . $file_ext;
        $photo_path = $upload_dir . $file_name;
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
            $error_testimonial = "Erreur lors de l'upload de la photo";
        }
    }
    
    if ($titre && $contenu) {
        // Insertion dans la table temoignage
        $sql = "INSERT INTO temoignage (idEtudiant, titre, contenu, dateTemoignage, statut, photo, note) 
                VALUES (?, ?, ?, CURRENT_DATE, 'en_attente', ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt->execute([$etudiant_id, $titre, $contenu, $photo_path, $note])) {
            $_SESSION['success_testimonial'] = "Témoignage envoyé avec succès ! Il sera visible après validation.";
            header("Location: messages.php");
            exit();
        } else {
            $error_testimonial = "Erreur lors de l'envoi du témoignage.";
        }
    } else {
        $error_testimonial = "Veuillez remplir tous les champs obligatoires.";
    }
}

// Vérifier le message de succès dans la session
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Vérifier le message de succès de témoignage dans la session
if (isset($_SESSION['success_testimonial'])) {
    $success_testimonial = $_SESSION['success_testimonial'];
    unset($_SESSION['success_testimonial']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Témoignages - ESEN NET</title>
    <meta name="description" content="Partagez votre témoignage">
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

        .custom-cursor.clicking {
            transform: translate(-50%, -50%) scale(0.8);
            background: rgba(0, 212, 196, 0.3);
        }
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
                const interactiveElements = document.querySelectorAll('a, button, input, .btn, .form-group input, .form-group textarea, .message-item, .testimonial-item, .chatbot-bubble');
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
                <h1>Témoignages</h1>
                <p>Partagez votre expérience avec la communauté</p>
            </div>

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

            <?php if (isset($success_testimonial)): ?>
                <div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.4); color: #10b981; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem;">
                    <i class="fas fa-check-circle"></i> <?php echo $success_testimonial; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_testimonial)): ?>
                <div class="alert alert-danger" style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.4); color: #ef4444; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_testimonial; ?>
                </div>
            <?php endif; ?>

            
            <!-- Section Témoignages -->
            <div class="dashboard-card" style="margin-top: 2rem;">
                <div class="dashboard-header" style="margin-bottom: 2rem;">
                    <h3><i class="fas fa-quote-left"></i> Partager votre témoignage</h3>
                    <p>Faites part de votre expérience et aidez les autres étudiants</p>
                </div>

                <form method="POST" action="" enctype="multipart/form-data" autocomplete="off">
                    <input type="hidden" name="send_testimonial" value="1">
                    <div class="dashboard-grid">
                        <div class="form-group">
                            <label>Titre du témoignage *</label>
                            <input type="text" name="titre" placeholder="Donnez un titre à votre témoignage" required style="width: 100%; padding: 0.75rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #ffffff;">
                        </div>
                        <div class="form-group">
                            <label>Note (sur 5)</label>
                            <input type="number" name="note" min="0" max="5" step="0.5" placeholder="Notez votre expérience" style="width: 100%; padding: 0.75rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #ffffff;">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Votre témoignage *</label>
                        <textarea name="contenu" rows="6" placeholder="Partagez votre expérience, vos apprentissages, vos conseils..." required style="width: 100%; padding: 0.75rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #ffffff;"></textarea>
                    </div>
                    <button type="submit" class="btn primary" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                        <i class="fas fa-paper-plane"></i> Envoyer mon témoignage
                    </button>
                </form>
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
    </script>

    
<!-- Chatbot JavaScript -->
    <script src="../../public/assets/js/chatbot.js"></script>
</body>
</html>
