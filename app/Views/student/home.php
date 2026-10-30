<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../layouts/components.php';


// Obtenir l'ID de l'étudiant connecté
$etudiant_id = get_etudiant_id();

// Si aucun étudiant n'est connecté, rediriger vers la page de connexion
if (!$etudiant_id) {
    header("Location: ../../login.php");
    exit();
}

// Obtenir les informations de l'étudiant
$student_info = get_etudiant_info($etudiant_id);

// Obtenir les statistiques de l'étudiant
$stats = get_etudiant_stats($etudiant_id);

// Obtenir le score de profil depuis la table badge
$badge_score = 0;
try {
    $stmt = $conn->prepare("SELECT score FROM badge WHERE idEtudiant = ?");
    $stmt->execute([$etudiant_id]);
    $result = $stmt->fetch();
    if ($result) {
        $badge_score = $result['score'];
    }
} catch (Exception $e) {
    $badge_score = 0;
}

// Obtenir les candidatures de l'étudiant
$candidatures = get_etudiant_candidatures($etudiant_id);

// Obtenir les offres disponibles (toutes)
$offres = get_offres_disponibles();

// Obtenir les événements à venir (tous)
$events = get_evenements_avenir();

// Obtenir tous les événements avec images pour le feed
$all_events_with_images = [];
try {
    $stmt = $conn->prepare("SELECT * FROM evenement WHERE (image IS NOT NULL AND image != '') OR (photos_evenement IS NOT NULL AND photos_evenement != '') ORDER BY dateEvent DESC LIMIT 10");
    $stmt->execute();
    foreach ($stmt->fetchAll() as $row) {
        $all_events_with_images[] = $row;
    }
    
    // Debug: Si aucun événement avec image, récupérer tous les événements
    if (empty($all_events_with_images)) {
        $stmt = $conn->prepare("SELECT * FROM evenement ORDER BY dateEvent DESC LIMIT 10");
        $stmt->execute();
        foreach ($stmt->fetchAll() as $row) {
            $all_events_with_images[] = $row;
        }
    }
} catch (Exception $e) {
    $all_events_with_images = [];
}

// Obtenir les affiches uploadées (photos d'événements et d'offres)
$affiches = [];
try {
    // Récupérer les événements avec photos
    $stmt = $conn->prepare("SELECT e.*, 'event' as type FROM evenement e WHERE (image IS NOT NULL AND image != '') OR (photos_evenement IS NOT NULL AND photos_evenement != '') ORDER BY dateEvent DESC LIMIT 10");
    $stmt->execute();
    foreach ($stmt->fetchAll() as $row) {
        $affiches[] = $row;
    }
    
    // Récupérer les offres avec images
    $stmt = $conn->prepare("SELECT o.*, 'offer' as type FROM offre o WHERE image IS NOT NULL AND image != '' ORDER BY datePub DESC LIMIT 10");
    $stmt->execute();
    foreach ($stmt->fetchAll() as $row) {
        $affiches[] = $row;
    }
} catch (Exception $e) {
    $affiches = [];
}

// Fusionner et trier par date chronologique
$feed_items = [];

// Ajouter les candidatures
foreach ($candidatures as $candidature) {
    $feed_items[] = [
        'type' => 'application',
        'date' => $candidature['datePost'],
        'data' => $candidature
    ];
    
    // Debug: Afficher les informations de la candidature
    error_log("Candidature ID: " . $candidature['id']);
    error_log("Candidature statut: " . ($candidature['statut'] ?? 'null'));
    error_log("Candidature datePost: " . ($candidature['datePost'] ?? 'null'));
    
    // Si la candidature est acceptée, mettre à jour et notifier l'entreprise
    if (strcasecmp($candidature['statut'], 'ACCEPTE') === 0) {
        error_log("Tentative de mise à jour du statut pour candidature ID: " . $candidature['id']);
        error_log("Statut actuel: '" . $candidature['statut'] . "'");
        $update_result = update_candidature_status($candidature['id'], 'ACCEPTE');
        error_log("Résultat mise à jour: " . ($update_result ? 'succès' : 'échec'));
    } else {
        error_log("Statut non-ACCEPTE, pas de mise à jour. Statut actuel: '" . $candidature['statut'] . "'");
    }
}

// Ajouter les offres
foreach ($offres as $offre) {
    $feed_items[] = [
        'type' => 'offer',
        'date' => $offre['datePub'],
        'data' => $offre
    ];
}

// Ajouter les événements (ceux avec images pour afficher les affiches)
foreach ($all_events_with_images as $event) {
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
    <link rel="icon" href="../../public/assets/images/1.jpeg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }
    </style>
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
                const interactiveElements = document.querySelectorAll('a, button, input, .feed-card, .sidebar-item, .quick-link');
                interactiveElements.forEach(el => {
                    el.addEventListener('mouseenter', () => cursor.classList.add('hover'));
                    el.addEventListener('mouseleave', () => cursor.classList.remove('hover'));
                });

                // Click effect
                document.addEventListener('mousedown', () => cursor.classList.add('clicking'));
                document.addEventListener('mouseup', () => cursor.classList.remove('clicking'));
            }

            // Filtrage du feed
            const filterButtons = document.querySelectorAll('.filter-btn');
            const feedItems = document.querySelectorAll('.feed-card.content-item');

            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const filter = this.dataset.filter;

                    // Active class
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');

                    // Filter items
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

    <?php include 'student-navbar.php'; ?>

    <main class="feed-layout">
        <!-- Sidebar Gauche - Profil Étudiant -->
        <aside class="feed-sidebar-left">
            <div class="profile-card">
                <div class="profile-cover"></div>
                <div class="profile-avatar">
                    <?php if (!empty($student_info['photo']) && file_exists('../../../' . $student_info['photo'])): ?>
                        <img src="../../../<?php echo htmlspecialchars($student_info['photo']); ?>" alt="Photo de profil" class="profile-img">
                    <?php else: ?>
                        <i class="fas fa-user-graduate"></i>
                    <?php endif; ?>
                </div>
                <div class="profile-info">
                    <h3><?php echo htmlspecialchars($student_info['prenom'] . ' ' . $student_info['nom']); ?></h3>
                    <p><?php echo htmlspecialchars($student_info['specialite'] ?? 'Étudiant'); ?></p>
                </div>
                <div class="profile-stats">
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $stats['candidatures'] ?? 0; ?></span>
                        <span class="stat-label">Candidatures</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $badge_score; ?></span>
                        <span class="stat-label">Score de profil</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $stats['participations'] ?? 0; ?></span>
                        <span class="stat-label">Participations</span>
                    </div>
                </div>
            </div>

            <div class="sidebar-card">
                <h3>Liens rapides</h3>
                <div class="quick-links">
                    <a href="offers.php" class="quick-link">
                        <i class="fas fa-briefcase"></i> Voir les offres
                    </a>
                    <a href="profile.php#mescondidateur" class="quick-link">
                        <i class="fas fa-file-alt"></i> Mes candidatures
                    </a>
                    <a href="events.php" class="quick-link">
                        <i class="fas fa-calendar-alt"></i> Événements
                    </a>
                    <a href="profile.php" class="quick-link">
                        <i class="fas fa-user"></i> Mon profil
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
                        <i class="fas fa-user-graduate"></i>
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
                <button class="filter-btn" data-filter="application">
                    <i class="fas fa-file-alt"></i> Candidatures
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
                    <?php if ($item['type'] === 'application'): ?>
                        <?php $candidature = $item['data']; ?>
                        <div class="feed-card content-item" data-type="application">
                            <div class="post-header">
                                <div class="user-avatar">
                                    <i class="fas fa-user-graduate"></i>
                                </div>
                                <div class="user-info">
                                    <h4><?php echo htmlspecialchars($student_info['prenom'] . ' ' . $student_info['nom']); ?></h4>
                                    <span class="post-time">
                                        <i class="fas fa-file-alt"></i> Candidature • 
                                        <?php echo date('d/m/Y', strtotime($candidature['datePost'])); ?>
                                    </span>
                                </div>
                                <div class="post-menu">
                                    <button class="menu-btn"><i class="fas fa-ellipsis-h"></i></button>
                                </div>
                            </div>
                            <div class="post-content">
                                <div class="application-header">
                                    <h3>Candidature envoyée</h3>
                                    <p>Poste : <strong><?php echo htmlspecialchars($candidature['offre_titre']); ?></strong></p>
                                </div>
                                <div class="application-status">
                                    <span class="status-badge <?php echo $candidature['statut'] === 'ACCEPTE' ? 'accepted' : ($candidature['statut'] === 'REFUSE' ? 'rejected' : 'pending'); ?>">
                                        <?php echo htmlspecialchars($candidature['statut']); ?>
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
                                        <img src="<?php echo htmlspecialchars($offre['image']); ?>" alt="<?php echo htmlspecialchars($offre['titre']); ?>">
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
                                <?php 
                                // Afficher l'image de l'événement (affiche)
                                $event_image = !empty($event['image']) ? $event['image'] : $event['photos_evenement'];
                                if (!empty($event_image)): 
                                ?>
                                    <div class="post-image">
                                        <img src="../../../<?php echo htmlspecialchars($event_image); ?>" alt="<?php echo htmlspecialchars($event['titre']); ?>">
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
                    <p>Commencez par postuler à des offres ou participer à des événements</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar Droite - Suggestions -->
        <aside class="feed-sidebar-right">
            <div class="sidebar-card">
                <h3>Statistiques</h3>
                <div class="stats-grid">
                    <div class="stat-box">
                        <div class="stat-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-number"><?php echo $stats['candidatures'] ?? 0; ?></span>
                            <span class="stat-text">Candidatures</span>
                        </div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-number"><?php echo $badge_score; ?></span>
                            <span class="stat-text">Score de profil</span>
                        </div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-number"><?php echo $stats['participations'] ?? 0; ?></span>
                            <span class="stat-text">Participations</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sidebar-card">
                <h3>Événements à venir</h3>
                <?php if (!empty($events)): ?>
                    <?php foreach ($events as $event): ?>
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
                    <?php foreach ($offres as $offre): ?>
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

            <div class="sidebar-card">
                <h3>Affiches</h3>
                <div class="affiches-grid">
                    <?php if (!empty($affiches)): ?>
                        <?php foreach (array_slice($affiches, 0, 6) as $affiche): ?>
                            <div class="affiche-item">
                                <?php if ($affiche['type'] === 'event'): ?>
                                    <?php $image = !empty($affiche['image']) ? $affiche['image'] : $affiche['photos_evenement']; ?>
                                    <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($affiche['titre'] ?? 'Événement'); ?>" class="affiche-image">
                                    <div class="affiche-overlay">
                                        <span class="affiche-type"><i class="fas fa-calendar-alt"></i> Événement</span>
                                    </div>
                                <?php else: ?>
                                    <img src="<?php echo htmlspecialchars($affiche['image']); ?>" alt="<?php echo htmlspecialchars($affiche['titre'] ?? 'Offre'); ?>" class="affiche-image">
                                    <div class="affiche-overlay">
                                        <span class="affiche-type"><i class="fas fa-briefcase"></i> Offre</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="empty-text">Aucune affiche disponible</p>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
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
