<?php

require_once __DIR__ . '/../layouts/components.php';

require_once __DIR__ . '/../../config/db_connect.php';



// Obtenir l'ID de l'admin connecté

$admin_id = get_admin_id();



if (!$admin_id) {

    header("Location: ../../login.php");

    exit();

}



// Récupérer les messages de succès/erreur depuis la session

if (session_status() === PHP_SESSION_NONE) {

    session_start();

}

$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';

$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';



// Ne pas effacer les messages immédiatement pour qu'ils persistent après rafraîchissement

// Les messages seront effacés lors de la prochaine soumission de formulaire



// Obtenir les statistiques des événements avec des requêtes directes

$stats = [

    'total' => 0,

    'a_venir' => 0,

    'ouverts' => 0,

    'capacite_total' => 0

];



// Total des événements

$result = $conn->query("SELECT COUNT(*) as total FROM evenement");

if ($result) {

    $row = $result->fetch();

    $stats['total'] = $row['total'] ?? 0;

}



// Événements à venir

$result = $conn->query("SELECT COUNT(*) as count FROM evenement WHERE dateEvent >= CURDATE()");

if ($result) {

    $row = $result->fetch();

    $stats['a_venir'] = $row['count'] ?? 0;

}



// Événements ouverts

$result = $conn->query("SELECT COUNT(*) as count FROM evenement WHERE statut = 'OUVERT'");

if ($result) {

    $row = $result->fetch();

    $stats['ouverts'] = $row['count'] ?? 0;

}



// Capacité totale

$result = $conn->query("SELECT SUM(capacite) as total FROM evenement WHERE capacite IS NOT NULL");

if ($result) {

    $row = $result->fetch();

    $stats['capacite_total'] = (int)($row['total'] ?? 0);

}



// Obtenir tous les événements pour l'affichage

$events = get_all_evenements();



// Traitement de la création d'événement

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_event'])) {

    $titre = sanitize_input($_POST['titre']);

    $description = sanitize_input($_POST['description']);

    $date_event = sanitize_input($_POST['date_event']);

    $lieu = sanitize_input($_POST['lieu']);

    $capacite = sanitize_input($_POST['capacite']);

    $image = null;



    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {

        $upload_dir = '../../../app/uploads/evenements/';

        if (!is_dir($upload_dir)) {

            mkdir($upload_dir, 0777, true);

        }

        $file_name = time() . '_' . basename($_FILES['image']['name']);

        $file_path = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {

            $image = 'app/uploads/evenements/' . $file_name;

        }

    }



    if (create_evenement($titre, $description, $date_event, $lieu, $capacite, 'OUVERT', $image, null)) {

        // Envoyer notification à tous les enseignants

        $notification_message = "Un nouvel événement \"$titre\" a été créé pour le " . date('d/m/Y', strtotime($date_event)) . " à $lieu.";

        notify_all_teachers("Nouvel événement créé", $notification_message, 'EVENEMENT');

        // Stocker le message de succès dans la session

        $_SESSION['success_message'] = "Événement créé avec succès !";

        // Redirection PRG (Post-Redirect-Get) pour éviter le renvoi du formulaire

        header("Location: events.php");

        exit();

    } else {

        // Stocker le message d'erreur dans la session

        $_SESSION['error_message'] = "Erreur lors de la création de l'événement.";

        // Redirection PRG en cas d'erreur

        header("Location: events.php");

        exit();

    }

}

?>

<!DOCTYPE html>

<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Événements - ESEN Connect</title>

    <meta name="description" content="Gérez les événements">

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

            const themeToggle = document.querySelector('.toggle-dark');

            const savedTheme = localStorage.getItem('theme') || 'light';

            if (savedTheme === 'dark') {

                document.body.classList.add('dark');

            }

            if (themeToggle) {

                themeToggle.addEventListener('click', function() {

                    document.body.classList.toggle('dark');

                    const isDark = document.body.classList.contains('dark');

                    localStorage.setItem('theme', isDark ? 'dark' : 'light');

                });

            }

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

            if (verticalNavbar && navbarShowBtn) updateNavbarShowBtn();



            // Custom Cursor

            const cursor = document.querySelector('.custom-cursor');

            const cursorDot = document.querySelector('.custom-cursor-dot');

            let mouseX = 0, mouseY = 0, cursorX = 0, cursorY = 0;

            if (cursor && cursorDot) {

                document.addEventListener('mousemove', (e) => { mouseX = e.clientX; mouseY = e.clientY; });

                function animateCursor() {

                    cursorX += (mouseX - cursorX) * 0.15;

                    cursorY += (mouseY - cursorY) * 0.15;

                    cursor.style.left = cursorX + 'px';

                    cursor.style.top = cursorY + 'px';

                    cursorDot.style.left = mouseX + 'px';

                    cursorDot.style.top = mouseY + 'px';

                    requestAnimationFrame(animateCursor);

                }

                animateCursor();

                document.querySelectorAll('a, button, input, textarea, .btn, .stat-card, .dashboard-card, .event-item, .nav-item, .search-input, .filter-btn').forEach(el => {

                    el.addEventListener('mouseenter', () => cursor.classList.add('hover'));

                    el.addEventListener('mouseleave', () => cursor.classList.remove('hover'));

                });

                document.addEventListener('mousedown', () => cursor.classList.add('clicking'));

                document.addEventListener('mouseup', () => cursor.classList.remove('clicking'));

            }



            // Search

            const searchInput = document.getElementById('searchEvents');

            const eventItems = document.querySelectorAll('.event-item');

            if (searchInput) {

                searchInput.addEventListener('input', function() {

                    const query = this.value.toLowerCase();

                    eventItems.forEach(item => {

                        const title = item.dataset.title.toLowerCase();

                        const lieu = item.dataset.lieu.toLowerCase();

                        const matches = title.includes(query) || lieu.includes(query);

                        item.style.display = matches ? '' : 'none';

                    });

                });

            }



            // Toggle create form

            const toggleFormBtn = document.getElementById('toggleCreateForm');

            const createFormSection = document.getElementById('createFormSection');

            if (toggleFormBtn && createFormSection) {

                toggleFormBtn.addEventListener('click', function() {

                    createFormSection.classList.toggle('form-hidden');

                    this.classList.toggle('active');

                });

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

                <h1>Événements</h1>

                <p>Gérez les événements de la plateforme</p>

            </div>



            <!-- Stats Grid -->

            <div class="stats-grid">

                <div class="stat-card">

                    <div class="icon blue"><i class="fas fa-calendar-alt"></i></div>

                    <div class="value"><?php echo $stats['total']; ?></div>

                    <div class="label">Total événements</div>

                </div>

                <div class="stat-card">

                    <div class="icon green"><i class="fas fa-calendar-check"></i></div>

                    <div class="value"><?php echo $stats['a_venir']; ?></div>

                    <div class="label">À venir</div>

                </div>

                <div class="stat-card">

                    <div class="icon orange"><i class="fas fa-door-open"></i></div>

                    <div class="value"><?php echo $stats['ouverts']; ?></div>

                    <div class="label">Ouverts</div>

                </div>

                <div class="stat-card">

                    <div class="icon purple"><i class="fas fa-users"></i></div>

                    <div class="value"><?php echo $stats['capacite_total']; ?></div>

                    <div class="label">Capacité totale</div>

                </div>

            </div>



            <!-- Formulaire de création d'événement -->

            <button id="toggleCreateForm" class="filter-btn" style="margin-bottom: 1.5rem; font-size: 1rem; padding: 0.875rem 1.5rem;">

                <i class="fas fa-plus"></i> Créer un événement

            </button>



            <!-- Create Event Form -->

            <div id="createFormSection" class="dashboard-card form-hidden" style="margin-bottom: 2rem;">

                <h3>Créer un événement</h3>

                <form method="POST" action="" enctype="multipart/form-data" class="admin-form">

                    <input type="hidden" name="create_event" value="1">

                    <div class="form-row">

                        <div class="form-group">

                            <label>Titre</label>

                            <input type="text" name="titre" placeholder="Titre de l'événement" required>

                        </div>

                        <div class="form-group">

                            <label>Date</label>

                            <input type="date" name="date_event" required>

                        </div>

                    </div>

                    <div class="form-row">

                        <div class="form-group">

                            <label>Lieu</label>

                            <input type="text" name="lieu" placeholder="Lieu de l'événement" required>

                        </div>

                        <div class="form-group">

                            <label>Capacité</label>

                            <input type="number" name="capacite" placeholder="Nombre de places" required>

                        </div>

                    </div>

                    <div class="form-group">

                        <label>Description</label>

                        <textarea name="description" rows="3" placeholder="Description de l'événement" required></textarea>

                    </div>

                    <div class="form-group">

                        <label>Image (Affiche)</label>

                        <input type="file" name="image" accept="image/*">

                    </div>

                    <button type="submit" class="filter-btn active" style="font-size: 1rem; padding: 0.875rem 2rem;">

                        <i class="fas fa-plus"></i> Créer l'événement

                    </button>

                </form>

            </div>



            <!-- Search Bar -->

            <div class="dashboard-card" style="margin-bottom: 2rem;">

                <div class="search-filter-bar">

                    <div class="search-box">

                        <i class="fas fa-search"></i>

                        <input type="text" id="searchEvents" class="search-input" placeholder="Rechercher un événement...">

                    </div>

                </div>

            </div>



            <!-- Events List -->

            <div class="dashboard-card">

                <h3>Liste des événements</h3>

                <div class="events-list">

                    <?php if (!empty($events)): ?>

                        <?php foreach ($events as $event): ?>

                            <div class="event-item" 

                                 data-title="<?php echo htmlspecialchars($event['titre'] ?? ''); ?>" 

                                 data-lieu="<?php echo htmlspecialchars($event['lieu'] ?? ''); ?>">

                                <?php if (!empty($event['image'])): ?>

                                    <div class="event-image">

                                        <img src="../../../<?php echo htmlspecialchars($event['image']); ?>" alt="<?php echo htmlspecialchars($event['titre']); ?>">

                                    </div>

                                <?php else: ?>

                                    <div class="event-icon">

                                        <i class="fas fa-calendar-alt"></i>

                                    </div>

                                <?php endif; ?>

                                <div class="event-info">

                                    <h4><?php echo htmlspecialchars($event['titre']); ?></h4>

                                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['lieu'] ?? 'ESEN'); ?></p>

                                    <p><i class="fas fa-clock"></i> <?php echo date('d M Y', strtotime($event['dateEvent'])); ?></p>

                                </div>

                                <div class="event-status <?php echo ($event['status'] ?? '') === 'past' ? 'status-past' : 'status-upcoming'; ?>">

                                    <?php if (($event['status'] ?? '') === 'past'): ?>

                                        <i class="fas fa-history"></i> Passé

                                    <?php else: ?>

                                        <i class="fas fa-calendar-check"></i> À venir

                                    <?php endif; ?>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <p style="text-align: center; color: #e2e8f0; padding: 2rem;">Aucun événement trouvé</p>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </main>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script src="../../public/assets/js/main.js"></script>

</body>

</html>

