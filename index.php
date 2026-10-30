<?php
require_once 'app/Views/layouts/components.php';
require_once 'app/config/db_connect.php';

// Vérifier la connexion
if (!$conn) {
    die("Erreur de connexion à la base de données");
}

// Statistiques dynamiques avec gestion d'erreurs
try {
    $stats = [
        'total_offres' => get_total_offres(),
        'total_events' => get_total_events(),
        'total_students' => get_total_students(),
        'total_companies' => get_total_companies(),
        'active_offres' => get_active_offres_count(),
        'upcoming_events' => get_upcoming_events_count()
    ];
    
    // Debug: afficher les statistiques
    error_log("Statistiques: " . print_r($stats, true));
    
} catch (Exception $e) {
    error_log("Erreur statistiques: " . $e->getMessage());
    $stats = [
        'total_offres' => 0,
        'total_events' => 0,
        'total_students' => 0,
        'total_companies' => 0,
        'active_offres' => 0,
        'upcoming_events' => 0
    ];
}

try {
    // Récupérer les offres récentes avec les informations des entreprises
    $offres = get_recent_offres_with_companies(6);
    
    // Récupérer les événements à venir
    $events = get_upcoming_events(6);
    
    // Récupérer les étudiants avec badges
    $students = get_students_with_badges(9);
    
    // Récupérer les témoignages avec notes depuis la table temoignage
    $testimonials = get_testimonials_with_ratings(3);
    
    // Debug: afficher les comptes
    error_log("Offres: " . count($offres ?? []));
    error_log("Events: " . count($events ?? []));
    error_log("Students: " . count($students ?? []));
    error_log("Testimonials: " . count($testimonials ?? []));
    
} catch (Exception $e) {
    error_log("Erreur données: " . $e->getMessage());
    $offres = [];
    $events = [];
    $students = [];
    $testimonials = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ESEN NET - Connecte, Grandis, Réussis</title>
    <meta name="description" content="La plateforme de référence pour connecter les étudiants, enseignants et entreprises de l'ESEN">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="app/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="app/public/assets/css/main.css">
    <link rel="stylesheet" href="app/public/assets/css/navbar.css">
    <link rel="stylesheet" href="app/public/assets/css/index-pro.css">
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
            
            // Back to top button
            const backToTop = document.querySelector('.back-to-top');
            if (backToTop) {
                window.addEventListener('scroll', function() {
                    if (window.pageYOffset > 300) {
                        backToTop.classList.add('show');
                    } else {
                        backToTop.classList.remove('show');
                    }
                });
                
                backToTop.addEventListener('click', function() {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            }

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

            // Students rotation every 5 seconds
            const studentsData = <?php echo json_encode($students); ?>;
            const studentsGrid = document.getElementById('studentsGrid');
            let currentIndex = 0;
            const studentsPerRow = 3;

            function rotateStudents() {
                const studentCards = studentsGrid.querySelectorAll('.student-card');
                
                // Fade out current cards
                studentCards.forEach(card => {
                    card.classList.add('fade-out');
                });

                setTimeout(() => {
                    // Update cards with new students
                    studentCards.forEach((card, index) => {
                        const newIndex = (currentIndex + index) % studentsData.length;
                        const student = studentsData[newIndex];
                        
                        card.innerHTML = `
                            <div class="team-member">
                                <img src="https://via.placeholder.com/120/2f6fed/ffffff?text=${student.nom.charAt(0) + student.prenom.charAt(0)}" alt="${student.prenom} ${student.nom}">
                                <div class="team-overlay">
                                    <a href="#" class="team-icon"><i class="fab fa-linkedin-in"></i></a>
                                    <a href="#" class="team-icon"><i class="fab fa-github"></i></a>
                                </div>
                            </div>
                            <h4>${student.prenom} ${student.nom}</h4>
                            <span>${student.specialite} • Score: ${student.score || 0}</span>
                        `;
                        card.setAttribute('data-index', newIndex);
                    });

                    // Fade in new cards
                    studentCards.forEach(card => {
                        card.classList.remove('fade-out');
                        card.classList.add('fade-in');
                    });

                    currentIndex = (currentIndex + studentsPerRow) % studentsData.length;
                }, 500);
            }

            // Start rotation every 5 seconds
            if (studentsGrid && studentsData.length > studentsPerRow) {
                setInterval(rotateStudents, 5000);
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
            const interactiveElements = document.querySelectorAll('a, button, .card, .carousel-card, .team-card');
            interactiveElements.forEach(el => {
                el.addEventListener('mouseenter', () => cursor.classList.add('hover'));
                el.addEventListener('mouseleave', () => cursor.classList.remove('hover'));
            });

            // Click effect
            document.addEventListener('mousedown', () => cursor.classList.add('clicking'));
            document.addEventListener('mouseup', () => cursor.classList.remove('clicking'));

            // Cursor trail
            let trailTimeout;
            document.addEventListener('mousemove', (e) => {
                clearTimeout(trailTimeout);
                trailTimeout = setTimeout(() => {
                    const trail = document.createElement('div');
                    trail.className = 'cursor-trail';
                    trail.style.left = e.clientX + 'px';
                    trail.style.top = e.clientY + 'px';
                    document.body.appendChild(trail);
                    setTimeout(() => trail.remove(), 500);
                }, 50);
            });
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
            <a href="index.php" class="active"><i class="fas fa-home"></i> Accueil</a>
            <a href="#offre"><i class="fas fa-briefcase"></i> Offres</a>
            <a href="#evenement"><i class="fas fa-calendar-alt"></i> Événements</a>
            <a href="login.php"><i class="fas fa-sign-in-alt"></i> Connexion</a>
            <a href="signup.php"><i class="fas fa-user-plus"></i> Inscription</a>
        </nav>
        <button class="toggle-dark">🌙 Dark</button>
    </header>

    <!-- Main Content Container -->
    <main class="main-content-wrapper">
        <!-- Hero Section -->
        <section class="hero">
            <div>
                <div class="eyebrow">Bienvenue sur ESEN NET</div>
                <h1>Connecte, Grandis, Réussis</h1>
                <p>Rejoignez la plateforme qui transforme votre avenir professionnel. Connectez-vous avec les meilleures entreprises et développez votre carrière.</p>
                <div class="hero-cta">
                    <a href="signup.php" class="btn primary">Rejoindre maintenant</a>
                    <a href="#offers" class="btn ghost">Explorer les offres</a>
                </div>
            </div>
            <div>
                <img src="assets/images/hero-placeholder.jpg" alt="ESEN NET" class="hero-visual" onerror="this.src='https://via.placeholder.com/600x400/2563eb/ffffff?text=ESEN+Connect'">
            </div>
        </section>

        <!-- Statistics Section -->
        <section class="stats-section">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?= number_format($stats['total_offres']) ?></h3>
                        <p class="stat-label">Offres d'emploi</p>
                        <span class="stat-sub"><?= number_format($stats['active_offres']) ?> actives</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?= number_format($stats['total_events']) ?></h3>
                        <p class="stat-label">Événements</p>
                        <span class="stat-sub"><?= number_format($stats['upcoming_events']) ?> à venir</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?= number_format($stats['total_students']) ?></h3>
                        <p class="stat-label">Étudiants</p>
                        <span class="stat-sub">Talentueux et motivés</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?= number_format($stats['total_companies']) ?></h3>
                        <p class="stat-label">Entreprises</p>
                        <span class="stat-sub">Partenaires de confiance</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Latest Offers Section -->
        <div class="section-title" id="offre">
            <h2>Dernières Offres</h2>
            <span>Voir toutes les offres →</span>
        </div>
        
        <div class="offers-carousel-container">
            <div class="offers-carousel-track" id="offersCarousel">
                <?php foreach($offres as $offre): ?>
                <div class="card carousel-card">
                    <h3><?= htmlspecialchars($offre['titre']) ?></h3>
                    <p><?= htmlspecialchars($offre['type']) ?> chez <?= htmlspecialchars($offre['nomsociete']) ?></p>
                    <div style="margin-top: 1rem; color: #2f6fed; font-weight: 600;">
                        <i class="fas fa-building"></i> <?= htmlspecialchars($offre['nomsociete']) ?> • <?= htmlspecialchars($offre['type']) ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <!-- Duplicate cards for infinite scroll effect -->
                <?php foreach($offres as $offre): ?>
                <div class="card carousel-card">
                    <h3><?= htmlspecialchars($offre['titre']) ?></h3>
                    <p><?= htmlspecialchars($offre['type']) ?> chez <?= htmlspecialchars($offre['nomsociete']) ?></p>
                    <div style="margin-top: 1rem; color: #2f6fed; font-weight: 600;">
                        <i class="fas fa-building"></i> <?= htmlspecialchars($offre['nomsociete']) ?> • <?= htmlspecialchars($offre['type']) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Événements Section -->
        <div class="section-title" id="evenement">
            <h2>Événements à venir</h2>
            <span>Voir tous les événements →</span>
        </div>
        
        <div class="events-carousel-container">
            <div class="events-carousel-track" id="eventsCarousel">
                <?php foreach($events as $event): ?>
                <div class="card carousel-card">
                    <h3>🎓 <?= htmlspecialchars($event['titre']) ?></h3>
                    <p><?= htmlspecialchars($event['description']) ?></p>
                    <div style="margin-top: 1rem; color: #2f6fed; font-weight: 600;">
                        <i class="fas fa-calendar"></i> <?= date('d F Y', strtotime($event['dateEvent'])) ?> • <?= htmlspecialchars($event['lieu']) ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <!-- Duplicate cards for infinite scroll effect -->
                <?php foreach($events as $event): ?>
                <div class="card carousel-card">
                    <h3>🎓 <?= htmlspecialchars($event['titre']) ?></h3>
                    <p><?= htmlspecialchars($event['description']) ?></p>
                    <div style="margin-top: 1rem; color: #2f6fed; font-weight: 600;">
                        <i class="fas fa-calendar"></i> <?= date('d F Y', strtotime($event['dateEvent'])) ?> • <?= htmlspecialchars($event['lieu']) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Étudiants Talentueux Section -->
        <div class="section-title">
            <h2>Étudiants Talentueux</h2>
            <span>Voir tous les profils →</span>
        </div>
        
        <div class="team-grid">
                <?php if (!empty($students)): ?>
                    <?php
                    foreach($students as $student):
                    // Récupérer les badges de cet étudiant
                    $student_badges = get_student_badges($student['id']);
                    ?>
                    <div class="team-card student-card">
                        <div class="team-member">
                            <?php 
                            // Essayer avec le chemin direct depuis la base, puis avec uploads/
                            $photo_path = $student['photo'] ?? '';
                            $photo_exists = false;
                            
                            if (!empty($photo_path)) {
                                // Vérifier si le chemin dans la base fonctionne
                                if (file_exists($photo_path)) {
                                    $photo_exists = true;
                                    $final_path = $photo_path;
                                } 
                                // Sinon essayer avec app/uploads/ si le chemin ne commence pas par app/uploads/
                                elseif (strpos($photo_path, 'app/') !== 0 && file_exists('app/' . basename($photo_path))) {
                                    $photo_exists = true;
                                    $final_path = 'app/' . basename($photo_path);
                                }
                                // Sinon essayer directement avec app/uploads/ + nom de fichier
                                elseif (file_exists('app/' . $photo_path)) {
                                    $photo_exists = true;
                                    $final_path = 'app/' . $photo_path;
                                }
                            }
                            
                            if ($photo_exists): ?>
                                <img src="<?= htmlspecialchars($final_path) ?>" alt="<?= htmlspecialchars($student['prenom']) . ' ' . htmlspecialchars($student['nom']) ?>" style="width: 120px; height: 120px; object-fit: cover; border-radius: 50%;">
                            <?php else: ?>
                                <div style="width: 120px; height: 120px; background: linear-gradient(135deg, #2f6fed, #00d4c4); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem; font-weight: bold; margin: 0 auto;"><?= substr(htmlspecialchars($student['nom']), 0, 1) . substr(htmlspecialchars($student['prenom']), 0, 1) ?></div>
                            <?php endif; ?>
                            
                            <!-- Badge indicator -->
                            <?php if (!empty($student_badges)): ?>
                                <div style="position: absolute; top: 5px; right: 5px; background: #f59e0b; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: bold; border: 2px solid white;">
                                    <?= count($student_badges) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h4><?= htmlspecialchars($student['prenom']) . ' ' . htmlspecialchars($student['nom']) ?></h4>
                        <span><?= htmlspecialchars($student['specialite']) ?> • <?= $student['badge_count'] ?> badges</span>
                        
                        <!-- Affichage des badges -->
                        <?php if (!empty($student_badges)): ?>
                            <div style="margin-top: 10px; display: flex; flex-wrap: wrap; gap: 5px;">
                                <?php foreach(array_slice($student_badges, 0, 3) as $badge): ?>
                                    <div style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 3px 8px; border-radius: 12px; font-size: 0.7rem; font-weight: 600;">
                                        Badge (+<?= $badge['score'] ?? 0 ?>)
                                    </div>
                                <?php endforeach; ?>
                                <?php if (count($student_badges) > 3): ?>
                                    <div style="background: #e5e7eb; color: #6b7280; padding: 3px 8px; border-radius: 12px; font-size: 0.7rem;">
                                        +<?= count($student_badges) - 3 ?> de plus
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="team-card student-card" style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                        <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #2f6fed, #00d4c4); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; font-weight: bold; margin: 0 auto 20px;">
                            <i class="fas fa-award"></i>
                        </div>
                        <h4 style="color: #2f6fed; margin-bottom: 10px;">Aucun étudiant avec badges</h4>
                        <p style="color: #64748b;">Les badges seront attribués aux étudiants méritants</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Témoignages Section -->
        <div class="section-title">
            <h2>Témoignages Étudiants</h2>
            <span>Partager votre expérience →</span>
        </div>
        
        <div class="cards">
            <?php if (!empty($testimonials)): ?>
                <?php foreach($testimonials as $testimonial): ?>
                <div class="card">
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                        <?php 
                        // Prioriser la photo du témoignage, sinon utiliser la photo de l'utilisateur
                        $testimonial_photo = '';
                        if (!empty($testimonial['photo'])) {
                            // Utiliser la photo spécifique au témoignage
                            $photo_path = $testimonial['photo'];
                            if (file_exists($photo_path)) {
                                $testimonial_photo = $photo_path;
                            } elseif (file_exists('app/' . basename($photo_path))) {
                                $testimonial_photo = 'app/' . basename($photo_path);
                            } elseif (file_exists('app/' . $photo_path)) {
                                $testimonial_photo = 'app/' . $photo_path;
                            }
                        } elseif (!empty($testimonial['user_photo'])) {
                            // Utiliser la photo de l'utilisateur comme fallback
                            $photo_path = $testimonial['user_photo'];
                            if (file_exists($photo_path)) {
                                $testimonial_photo = $photo_path;
                            } elseif (file_exists('app/' . basename($photo_path))) {
                                $testimonial_photo = 'app/' . basename($photo_path);
                            } elseif (file_exists('app/' . $photo_path)) {
                                $testimonial_photo = 'app/' . $photo_path;
                            }
                        }
                        
                        if (!empty($testimonial_photo)): ?>
                            <img src="<?= htmlspecialchars($testimonial_photo) ?>" alt="<?= htmlspecialchars($testimonial['prenom']) . ' ' . htmlspecialchars($testimonial['nom']) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 50%;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #2f6fed, #00d4c4); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; display: none;"><?= substr(htmlspecialchars($testimonial['nom']), 0, 1) . substr(htmlspecialchars($testimonial['prenom']), 0, 1) ?></div>
                        <?php else: ?>
                            <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #2f6fed, #00d4c4); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700;"><?= substr(htmlspecialchars($testimonial['nom']), 0, 1) . substr(htmlspecialchars($testimonial['prenom']), 0, 1) ?></div>
                        <?php endif; ?>
                        <div>
                            <h4 style="margin: 0; color: #2f6fed;"><?= htmlspecialchars($testimonial['prenom']) . ' ' . htmlspecialchars($testimonial['nom']) ?></h4>
                            <span style="font-size: 0.85rem; color: #64748b;"><?= htmlspecialchars($testimonial['specialite']) ?></span>
                        </div>
                    </div>
                    <?php if (!empty($testimonial['titre'])): ?>
                    <h5 style="margin: 0 0 10px 0; color: #1e293b; font-size: 1.1rem;"><?= htmlspecialchars($testimonial['titre']) ?></h5>
                <?php endif; ?>
                    <p>"<?= htmlspecialchars($testimonial['contenu']) ?>"</p>
                    <div style="margin-top: 15px;">
                        <?= generate_star_rating($testimonial['note'] ?? 5) ?>
                        <span style="margin-left: 10px; font-size: 0.85rem; color: #64748b;"><?= $testimonial['note'] ?? 5 ?>/5</span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="card" style="text-align: center; padding: 40px;">
                    <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #2f6fed, #00d4c4); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; font-weight: bold; margin: 0 auto 20px;">
                        <i class="fas fa-quote-left"></i>
                    </div>
                    <h4 style="color: #2f6fed; margin-bottom: 10px;">Aucun témoignage pour le moment</h4>
                    <p style="color: #64748b;">Soyez le premier à partager votre expérience !</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 ESEN NET. Tous droits réservés.</p>
    </footer>

    <!-- Back to Top Button -->
    <button class="back-to-top">↑</button>
</body>
</html>
