<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../layouts/components.php';


// Obtenir l'ID de l'enseignant connecté
$teacher_id = get_teacher_id();

// Si aucun enseignant n'est connecté, rediriger vers la page de connexion
if (!$teacher_id) {
    header("Location: ../../login.php");
    exit();
}

// Obtenir les informations de l'enseignant
$teacher_info = get_teacher_info($teacher_id);

// Debug: Afficher les informations de l'enseignant
error_log("Teacher ID: " . $teacher_id);
error_log("Teacher info: " . ($teacher_info ? 'found' : 'null'));
if ($teacher_info) {
    error_log("Teacher photo: " . ($teacher_info['photo'] ?? 'null'));
    error_log("Teacher name: " . ($teacher_info['nom'] ?? 'null'));
    error_log("Teacher first name: " . ($teacher_info['prenom'] ?? 'null'));
}

// Obtenir les statistiques de l'enseignant
$stats = get_teacher_stats($teacher_id);

// Obtenir les recommandations de l'enseignant
$recommendations = get_teacher_recommendations($teacher_id);

// Obtenir les étudiants de l'enseignant
$students = get_teacher_students($teacher_id);

// Obtenir les offres disponibles
$offres = get_offres_disponibles(10);

// Obtenir les événements à venir (limité pour le feed)
$events = get_evenements_avenir(10);

// Obtenir le nombre total d'événements pour les statistiques
$all_events = get_evenements_avenir();

// Fusionner et trier par date chronologique
$feed_items = [];

// Ajouter les recommandations
foreach ($recommendations as $recommendation) {
    $feed_items[] = [
        'type' => 'recommendation',
        'date' => $recommendation['dateRecommandation'] ?? date('Y-m-d'),
        'data' => $recommendation
    ];
}

// Ajouter les offres
foreach ($offres as $offre) {
    $feed_items[] = [
        'type' => 'offer',
        'date' => $offre['datePub'],
        'data' => $offre
    ];
}

// Ajouter les événements
foreach ($events as $event) {
    $feed_items[] = [
        'type' => 'event',
        'date' => $event['dateEvent'],
        'data' => $event
    ];
}

// Trier par date décroissante
usort($feed_items, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - ESEN NET</title>
    <meta name="description" content="Votre feed d'actualités ESEN NET">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../public/assets/css/main.css">
    <link rel="stylesheet" href="../../public/assets/css/student-navbar.css">
    <link rel="stylesheet" href="../../public/assets/css/student-pro.css">
    <link rel="stylesheet" href="../../public/assets/css/teacher-dashboard.css">
    <link rel="icon" href="../../public/assets/images/1.jpeg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Dark mode toggle
            const themeToggle = document.querySelector('.toggle-dark');
            if (themeToggle) {
                const savedTheme = localStorage.getItem('theme') || 'light';
                if (savedTheme === 'dark') {
                    document.body.classList.add('dark');
                }
                themeToggle.addEventListener('click', function() {
                    document.body.classList.toggle('dark');
                    const isDark = document.body.classList.contains('dark');
                    localStorage.setItem('theme', isDark ? 'dark' : 'light');
                });
            }

            // Mobile menu toggle
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

            // Navbar toggle functionality
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
                    navbarShowBtn.classList.add('visible');
                } else {
                    navbarShowBtn.classList.remove('visible');
                }
            }

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

            const interactiveElements = document.querySelectorAll('a, button, .feed-card, .feed-sidebar-card');
            interactiveElements.forEach(el => {
                el.addEventListener('mouseenter', () => cursor.classList.add('hover'));
                el.addEventListener('mouseleave', () => cursor.classList.remove('hover'));
            });

            document.addEventListener('mousedown', () => cursor.classList.add('clicking'));
            document.addEventListener('mouseup', () => cursor.classList.remove('clicking'));

            // Feed filtering
            const filterButtons = document.querySelectorAll('.feed-filter-btn');
            const feedItems = document.querySelectorAll('.feed-item');

            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    // Add active class to clicked button
                    this.classList.add('active');

                    const filter = this.dataset.filter;

                    feedItems.forEach(item => {
                        if (filter === 'all' || item.dataset.type === filter) {
                            item.style.display = 'block';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            });
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

    <main class="feed-layout">
        <!-- Sidebar Gauche - Profil Enseignant -->
        <aside class="feed-sidebar-left">
            <div class="profile-card">
                <div class="profile-cover"></div>
                <div class="profile-avatar">
                    <?php 
                    $photo_path = $teacher_info['photo'] ?? '../../../assets/images/default-teacher-avatar.png';
                    // Vérifier si la photo existe et est accessible
                    if (!empty($teacher_info['photo']) && file_exists('../../../' . $teacher_info['photo'])) {
                        $photo_path = '../../../' . $teacher_info['photo'];
                    }
                    ?>
                    <img src="<?php echo htmlspecialchars($photo_path); ?>" alt="Photo de profil" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                </div>
                <div class="profile-info">
                    <h3><?php echo htmlspecialchars($teacher_info['prenom'] . ' ' . $teacher_info['nom']); ?></h3>
                    <p><?php echo htmlspecialchars($teacher_info['specialite'] ?? 'Enseignant'); ?></p>
                </div>
                <div class="profile-stats">
                    <div class="stat-item">
                        <span class="stat-value"><?php echo count($students); ?></span>
                        <span class="stat-label">Étudiants</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo count($recommendations); ?></span>
                        <span class="stat-label">Recommandations</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo count($all_events); ?></span>
                        <span class="stat-label">Événements</span>
                    </div>
                </div>
            </div>

            <div class="sidebar-card">
                <h3>Liens rapides</h3>
                <div class="quick-links">
                    <a href="recommendations.php" class="quick-link">
                        <i class="fas fa-star"></i> Recommandations
                    </a>
                    <a href="events.php" class="quick-link">
                        <i class="fas fa-calendar-alt"></i> Événements
                    </a>
                    <a href="profile.php" class="quick-link">
                        <i class="fas fa-user"></i> Mon profil
                    </a>
                    <a href="settings.php" class="quick-link">
                        <i class="fas fa-cog"></i> Paramètres
                    </a>
                </div>
            </div>
        </aside>

        <!-- Feed Central -->
        <div class="feed-main">
            <!-- Section Publication -->
            <div class="feed-card create-post">
                <div class="post-header">
                    <div class="user-avatar">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <input type="text" placeholder="Partagez une actualité..." class="post-input">
                </div>
                <div class="post-actions">
                    <button class="action-btn">
                        <i class="fas fa-image"></i> Photo
                    </button>
                    <button class="action-btn">
                        <i class="fas fa-video"></i> Vidéo
                    </button>
                    <button class="action-btn">
                        <i class="fas fa-calendar"></i> Événement
                    </button>
                    <button class="action-btn">
                        <i class="fas fa-newspaper"></i> Article
                    </button>
                </div>
            </div>

            <!-- Filtres -->
            <div class="feed-filters">
                <button class="filter-btn active" data-filter="all">
                    <i class="fas fa-th-large"></i> Tout
                </button>
                <button class="filter-btn" data-filter="recommendation">
                    <i class="fas fa-star"></i> Recommandations
                </button>
                <button class="filter-btn" data-filter="offer">
                    <i class="fas fa-briefcase"></i> Offres
                </button>
                <button class="filter-btn" data-filter="event">
                    <i class="fas fa-calendar-alt"></i> Événements
                </button>
            </div>

            <!-- Feed Items -->
            <?php if (!empty($feed_items)): ?>
                <?php foreach ($feed_items as $item): ?>
                    <?php if ($item['type'] === 'recommendation'): ?>
                        <?php $recommendation = $item['data']; ?>
                        <div class="feed-card content-item" data-type="recommendation">
                            <div class="post-header">
                                <div class="user-avatar">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                                <div class="user-info">
                                    <h4><?php echo htmlspecialchars($teacher_info['prenom'] . ' ' . $teacher_info['nom']); ?></h4>
                                    <span class="post-time">
                                        <i class="fas fa-star"></i> Recommandation • 
                                        <?php echo date('d/m/Y', strtotime($recommendation['dateRecommandation'] ?? 'now')); ?>
                                    </span>
                                </div>
                                <div class="post-menu">
                                    <button class="menu-btn"><i class="fas fa-ellipsis-h"></i></button>
                                </div>
                            </div>
                            <div class="post-content">
                                <h3>Nouvelle recommandation</h3>
                                <p><?php echo htmlspecialchars($recommendation['contenu'] ?? 'Commentaire de recommandation'); ?></p>
                            </div>
                            <div class="post-footer">
                                <button class="like-btn">
                                    <i class="far fa-thumbs-up"></i> J'aime
                                </button>
                                <button class="comment-btn">
                                    <i class="far fa-comment"></i> Commenter
                                </button>
                                <button class="share-btn">
                                    <i class="far fa-share-square"></i> Partager
                                </button>
                            </div>
                        </div>
                    <?php elseif ($item['type'] === 'offer'): ?>
                        <?php $offre = $item['data']; ?>
                        <div class="feed-card content-item" data-type="offer">
                            <div class="post-header">
                                <div class="user-avatar company">
                                    <i class="fas fa-building"></i>
                                </div>
                                <div class="user-info">
                                    <h4><?php echo htmlspecialchars($offre['nomsociete']); ?></h4>
                                    <span class="post-time">
                                        <i class="fas fa-briefcase"></i> Offre • 
                                        <?php echo date('d/m/Y', strtotime($offre['datePub'])); ?>
                                    </span>
                                </div>
                                <div class="post-menu">
                                    <button class="menu-btn"><i class="fas fa-ellipsis-h"></i></button>
                                </div>
                            </div>
                            <div class="post-content">
                                <h3><?php echo htmlspecialchars($offre['titre']); ?></h3>
                                <?php if (!empty($offre['image'])): ?>
                                    <div class="post-image">
                                        <img src="../../../<?php echo htmlspecialchars($offre['image']); ?>" alt="<?php echo htmlspecialchars($offre['titre']); ?>">
                                    </div>
                                <?php endif; ?>
                                <div class="offer-meta">
                                    <span class="meta-badge type-badge"><?php echo htmlspecialchars($offre['type']); ?></span>
                                    <span class="meta-badge candidates-badge">
                                        <i class="fas fa-users"></i> <?php echo $offre['nb_candidatures'] ?? 0; ?> candidature(s)
                                    </span>
                                    <span class="meta-badge status-badge <?php echo $offre['statut'] === 'OUVERT' ? 'open' : 'closed'; ?>">
                                        <?php echo $offre['statut'] === 'OUVERT' ? 'Ouverte' : 'Fermée'; ?>
                                    </span>
                                </div>
                                <div class="offer-expiry">
                                    <i class="fas fa-clock"></i> Expire le <?php echo date('d/m/Y', strtotime($offre['dateExp'])); ?>
                                </div>
                            </div>
                            <div class="post-footer">
                                <button class="like-btn">
                                    <i class="far fa-thumbs-up"></i> J'aime
                                </button>
                                <button class="comment-btn">
                                    <i class="far fa-comment"></i> Commenter
                                </button>
                                <button class="share-btn">
                                    <i class="far fa-share-square"></i> Partager
                                </button>
                            </div>
                        </div>
                    <?php elseif ($item['type'] === 'event'): ?>
                        <?php $event = $item['data']; ?>
                        <div class="feed-card content-item" data-type="event">
                            <div class="post-header">
                                <div class="user-avatar">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div class="user-info">
                                    <h4>Événement ESEN</h4>
                                    <span class="post-time">
                                        <i class="fas fa-calendar-alt"></i> Événement • 
                                        <?php echo date('d/m/Y', strtotime($event['dateEvent'])); ?>
                                    </span>
                                </div>
                                <div class="post-menu">
                                    <button class="menu-btn"><i class="fas fa-ellipsis-h"></i></button>
                                </div>
                            </div>
                            <div class="post-content">
                                <h3><?php echo htmlspecialchars($event['titre']); ?></h3>
                                <?php if (!empty($event['image'])): ?>
                                    <div class="post-image">
                                        <img src="../../../<?php echo htmlspecialchars($event['image']); ?>" alt="<?php echo htmlspecialchars($event['titre']); ?>">
                                    </div>
                                <?php elseif (!empty($event['photos_evenement'])): ?>
                                    <div class="post-image">
                                        <img src="../../../<?php echo htmlspecialchars($event['photos_evenement']); ?>" alt="Photos de l'événement">
                                    </div>
                                <?php endif; ?>
                                <p><?php echo htmlspecialchars($event['description']); ?></p>
                                <div class="event-details">
                                    <span class="detail-badge">
                                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['lieu']); ?>
                                    </span>
                                    <span class="detail-badge">
                                        <i class="fas fa-users"></i> <?php echo htmlspecialchars($event['capacite']); ?> places
                                    </span>
                                    <span class="detail-badge">
                                        <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($event['statut']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="post-footer">
                                <button class="like-btn">
                                    <i class="far fa-thumbs-up"></i> J'aime
                                </button>
                                <button class="comment-btn">
                                    <i class="far fa-comment"></i> Commenter
                                </button>
                                <button class="share-btn">
                                    <i class="far fa-share-square"></i> Partager
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>Aucune activité</h3>
                    <p>Commencez par recommander des étudiants ou participer à des événements</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar Droite - Suggestions -->
        <aside class="feed-sidebar-right">
            <div class="sidebar-card">
                <h3>Événements à venir</h3>
                <?php if (!empty($events)): ?>
                    <?php foreach (array_slice($events, 0, 3) as $event): ?>
                        <div class="sidebar-item">
                            <div class="item-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="item-content">
                                <h4><?php echo htmlspecialchars($event['titre']); ?></h4>
                                <p><?php echo date('d/m/Y', strtotime($event['dateEvent'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="empty-text">Aucun événement à venir</p>
                <?php endif; ?>
            </div>

            <div class="sidebar-card">
                <h3>Offres récentes</h3>
                <?php if (!empty($offres)): ?>
                    <?php foreach (array_slice($offres, 0, 3) as $offre): ?>
                        <div class="sidebar-item">
                            <div class="item-icon">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            <div class="item-content">
                                <h4><?php echo htmlspecialchars($offre['titre']); ?></h4>
                                <p><?php echo htmlspecialchars($offre['nomsociete']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="empty-text">Aucune offre récente</p>
                <?php endif; ?>
            </div>
        </aside>
    </main>

<?php include 'notification-box.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../public/assets/js/main.js"></script>
</body>
</html>
