<?php
// ESEN NET - Shared Components

// Navigation Bar Component
function renderNavbar($currentPage = 'home') {
    $navItems = [
        'home' => ['Accueil', '#'],
        'offers' => ['Offres', 'entreprise/offers.php'],
        'events' => ['Événements', 'events.php'],
        'about' => ['À propos', '#'],
        'team' => ['Équipe', '#'],
        'network' => ['Réseau', 'network.php']
    ];
    
    echo '<nav class="navbar navbar-expand-lg navbar-light fixed-top">';
    echo '<div class="container">';
    echo '<a class="navbar-brand" href="index.php">';
    echo '<svg width="32" height="32" viewBox="0 0 32 32" fill="currentColor">';
    echo '<rect x="4" y="4" width="24" height="24" rx="6" fill="currentColor"/>';
    echo '<text x="16" y="20" text-anchor="middle" fill="white" font-size="12" font-weight="bold">E</text>';
    echo '</svg>';
    echo 'ESEN NET';
    echo '</a>';
    
    echo '<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">';
    echo '<span class="navbar-toggler-icon"></span>';
    echo '</button>';
    
    echo '<div class="collapse navbar-collapse" id="navbarNav">';
    echo '<ul class="navbar-nav mx-auto">';
    
    foreach ($navItems as $key => $item) {
        $activeClass = ($currentPage === $key) ? 'active' : '';
        echo '<li class="nav-item">';
        echo '<a class="nav-link ' . $activeClass . '" href="' . $item[1] . '">' . $item[0] . '</a>';
        echo '</li>';
    }
    
    echo '</ul>';
    
    echo '<div class="d-flex align-items-center gap-3">';
    echo '<div class="dropdown">';
    echo '<button class="btn btn-link position-relative" type="button" data-bs-toggle="dropdown">';
    echo '<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">';
    echo '<path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6z"/>';
    echo '</svg>';
    echo '<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">3</span>';
    echo '</button>';
    echo '<ul class="dropdown-menu dropdown-menu-end">';
    echo '<li><h6 class="dropdown-header">Notifications</h6></li>';
    // Récupérer le nombre de notifications non lues
    $notification_count = 0;
    if (function_exists('get_unread_notifications_count')) {
        $notification_count = get_unread_notifications_count($user_id ?? null);
    }
    
    // Afficher le compteur uniquement s'il y a des notifications
    if ($notification_count > 0) {
        echo '<li><a class="dropdown-item position-relative" href="#">Notifications <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">' . $notification_count . '</span></a></li>';
    } else {
        echo '<li><a class="dropdown-item" href="#">Notifications</a></li>';
    }
    
    echo '<li><hr class="dropdown-divider"></li>';
    echo '<li><a class="dropdown-item" href="teacher/notifications.php">Voir toutes</a></li>';
    echo '</ul>';
    echo '</div>';
    
    echo '<a href="login.php" class="btn btn-outline-primary btn-sm">Connexion</a>';
    echo '<a href="signup.php" class="btn btn-primary btn-sm">Inscription</a>';
    echo '</div>';
    
    echo '</div>';
    echo '</div>';
    echo '</nav>';
}

// Fonction pour compter les notifications non lues
function get_unread_notifications_count($user_id) {
    global $conn;
    
    if (!$user_id) {
        return 0;
    }
    
    $sql = "SELECT COUNT(*) as count FROM notifications WHERE id_destinataire = ? AND lu = 0";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    
    return $result['count'] ?? 0;
}

// Footer Component
function renderFooter() {
    echo '<footer class="footer">';
    echo '<div class="container">';
    echo '<div class="row">';
    
    echo '<div class="col-lg-4 mb-4">';
    echo '<div class="footer-brand">ESEN NET</div>';
    echo '<p class="footer-description">La plateforme de référence pour connecter les étudiants, enseignants et entreprises de l\'ESEN.</p>';
    echo '<div class="social-links">';
    echo '<a href="#" class="social-link">';
    echo '<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">';
    echo '<path d="M18.9 0H1.1C.5 0 0 .5 0 1.1v17.8c0 .6.5 1.1 1.1 1.1h9.6v-7.8h-2.6V9.4h2.6V7.2c0-2.6 1.6-4 3.9-4 1.1 0 2.1.1 2.4.1V5.7h-1.6c-1.3 0-1.5.6-1.5 1.5v1.9h3l-.4 2.8h-2.6V20h5.1c.6 0 1.1-.5 1.1-1.1V1.1C20 .5 19.5 0 18.9 0z"/>';
    echo '</svg>';
    echo '</a>';
    echo '<a href="#" class="social-link">';
    echo '<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">';
    echo '<path d="M20 3.8c-.7.3-1.5.5-2.4.6.9-.5 1.5-1.3 1.8-2.3-.8.5-1.7.8-2.7 1-.7-.8-1.8-1.3-3-1.3-2.3 0-4.1 1.8-4.1 4.1 0 .3 0 .6.1.9-3.4-.2-6.4-1.8-8.5-4.3-.4.6-.6 1.3-.6 2.1 0 1.4.7 2.7 1.8 3.4-.7 0-1.3-.2-1.9-.5v.1c0 2 1.4 3.6 3.3 4-.3.1-.7.1-1.1.1-.3 0-.5 0-.8-.1.5 1.6 2 2.8 3.8 2.8-1.4 1.1-3.2 1.8-5.1 1.8-.3 0-.7 0-1-.1 1.8 1.2 4 1.8 6.3 1.8 7.5 0 11.6-6.3 11.6-11.6v-.5c.8-.6 1.5-1.3 2.1-2.1z"/>';
    echo '</svg>';
    echo '</a>';
    echo '<a href="#" class="social-link">';
    echo '<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">';
    echo '<path d="M10 0C4.5 0 0 4.5 0 10s4.5 10 10 10 10-4.5 10-10S15.5 0 10 0zm3.5 6.1c.8.1 1.5.4 2.1 1 .1.1.1.2.1.3 0 .1-.1.2-.2.3l-.7.7c-.1.1-.2.1-.3.1s-.2-.1-.3-.2c-.3-.3-.7-.5-1.1-.5-.4 0-.8.2-1.1.5-.1.1-.2.2-.3.2s-.2 0-.3-.1l-.7-.7c-.1-.1-.2-.2-.2-.3 0-.1 0-.2.1-.3.6-.6 1.3-.9 2.1-1zm-7 0c.8.1 1.5.4 2.1 1 .1.1.1.2.1.3 0 .1-.1.2-.2.3l-.7.7c-.1.1-.2.1-.3.1s-.2-.1-.3-.2c-.3-.3-.7-.5-1.1-.5-.4 0-.8.2-1.1.5-.1.1-.2.2-.3.2s-.2 0-.3-.1l-.7-.7c-.1-.1-.2-.2-.2-.3 0-.1 0-.2.1-.3.6-.6 1.3-.9 2.1-1zM10 15.5c-2.8 0-5.1-2.3-5.1-5.1h10.2c0 2.8-2.3 5.1-5.1 5.1z"/>';
    echo '</svg>';
    echo '</a>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="col-lg-2 mb-4">';
    echo '<h6 class="text-white mb-3">Navigation</h6>';
    echo '<ul class="footer-links">';
    echo '<li><a href="index.php">Accueil</a></li>';
    echo '<li><a href="entreprise/offers.php">Offres</a></li>';
    echo '<li><a href="events.php">Événements</a></li>';
    echo '<li><a href="network.php">Réseau</a></li>';
    echo '</ul>';
    echo '</div>';
    
    echo '<div class="col-lg-2 mb-4">';
    echo '<h6 class="text-white mb-3">Services</h6>';
    echo '<ul class="footer-links">';
    echo '<li><a href="#">Pour les étudiants</a></li>';
    echo '<li><a href="#">Pour les entreprises</a></li>';
    echo '<li><a href="#">Pour les enseignants</a></li>';
    echo '<li><a href="#">Support</a></li>';
    echo '</ul>';
    echo '</div>';
    
    echo '<div class="col-lg-2 mb-4">';
    echo '<h6 class="text-white mb-3">Ressources</h6>';
    echo '<ul class="footer-links">';
    echo '<li><a href="#">Documentation</a></li>';
    echo '<li><a href="#">API</a></li>';
    echo '<li><a href="#">Blog</a></li>';
    echo '<li><a href="#">Carrières</a></li>';
    echo '</ul>';
    echo '</div>';
    
    echo '<div class="col-lg-2 mb-4">';
    echo '<h6 class="text-white mb-3">Légal</h6>';
    echo '<ul class="footer-links">';
    echo '<li><a href="#">Mentions légales</a></li>';
    echo '<li><a href="#">Confidentialité</a></li>';
    echo '<li><a href="#">CGU</a></li>';
    echo '<li><a href="#">Cookies</a></li>';
    echo '</ul>';
    echo '</div>';
    
    echo '</div>';
    
    echo '<div class="footer-bottom">';
    echo '<p>&copy; 2026 ESEN NET. Tous droits réservés.</p>';
    echo '</div>';
    
    echo '</div>';
    echo '</footer>';
}

// Modal Component
function renderModal($id, $title, $content, $size = 'md') {
    echo '<div class="modal fade" id="' . $id . '" tabindex="-1">';
    echo '<div class="modal-dialog modal-' . $size . '">';
    echo '<div class="modal-content">';
    echo '<div class="modal-header">';
    echo '<h5 class="modal-title">' . $title . '</h5>';
    echo '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>';
    echo '</div>';
    echo '<div class="modal-body">';
    echo $content;
    echo '</div>';
    echo '<div class="modal-footer">';
    echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>';
    echo '<button type="button" class="btn btn-primary">Enregistrer</button>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

// Toast Notification Component
function renderToast($type, $title, $message, $id = null) {
    $toastId = $id ?? 'toast-' . uniqid();
    $bgClass = '';
    
    switch($type) {
        case 'success':
            $bgClass = 'bg-success';
            break;
        case 'error':
            $bgClass = 'bg-danger';
            break;
        case 'warning':
            $bgClass = 'bg-warning';
            break;
        case 'info':
            $bgClass = 'bg-info';
            break;
        default:
            $bgClass = 'bg-primary';
    }
    
    echo '<div id="' . $toastId . '" class="toast align-items-center text-white ' . $bgClass . ' border-0" role="alert">';
    echo '<div class="d-flex">';
    echo '<div class="toast-body">';
    echo '<strong>' . $title . '</strong><br>' . $message;
    echo '</div>';
    echo '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>';
    echo '</div>';
    echo '</div>';
}

// Loading Spinner Component
function renderSpinner($size = 'md') {
    $sizeClass = '';
    switch($size) {
        case 'sm':
            $sizeClass = 'spinner-border-sm';
            break;
        case 'lg':
            $sizeClass = 'spinner-border-lg';
            break;
        default:
            $sizeClass = '';
    }
    
    echo '<div class="spinner-border ' . $sizeClass . ' text-primary" role="status">';
    echo '<span class="visually-hidden">Chargement...</span>';
    echo '</div>';
}

// Card Component for Dashboard
function renderDashboardCard($title, $content, $icon = null) {
    echo '<div class="dashboard-card">';
    echo '<div class="d-flex align-items-center mb-3">';
    if ($icon) {
        echo '<div class="me-3">' . $icon . '</div>';
    }
    echo '<h5 class="mb-0">' . $title . '</h5>';
    echo '</div>';
    echo '<div>' . $content . '</div>';
    echo '</div>';
}

// KPI Card Component
function renderKPICard($number, $label, $change = null, $color = 'primary') {
    $colorClass = 'bg-' . $color;
    if ($color === 'primary') {
        $colorClass = 'kpi-card';
    }
    
    echo '<div class="kpi-card ' . $colorClass . '">';
    echo '<div class="kpi-number">' . $number . '</div>';
    echo '<div class="kpi-label">' . $label . '</div>';
    if ($change) {
        echo '<div class="kpi-change">';
        if (strpos($change, '+') !== false) {
            echo '<svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor">';
            echo '<path d="M6 0l6 6-6 6-6-6z"/>';
            echo '</svg>';
        } else {
            echo '<svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor">';
            echo '<path d="M6 12L0 6l6-6 6 6z"/>';
            echo '</svg>';
        }
        echo ' ' . $change;
        echo '</div>';
    }
    echo '</div>';
}

// Sidebar Navigation Component
function renderSidebar($currentPage = 'home') {
    $menuItems = [
        'home' => ['Accueil', 'home.svg', 'student-home.php'],
        'profile' => ['Mon profil', 'profile.svg', 'profile.php'],
        'offers' => ['Offres', 'offers.svg', 'entreprise/offers.php'],
        'events' => ['Événements', 'events.svg', 'events.php'],
        'network' => ['Réseau', 'network.svg', 'network.php'],
        'messages' => ['Messages', 'messages.svg', '#'],
        'settings' => ['Paramètres', 'settings.svg', '#']
    ];
    
    echo '<div class="sidebar">';
    echo '<div class="p-3 border-bottom">';
    echo '<h5 class="mb-0">ESEN NET</h5>';
    echo '</div>';
    
    echo '<ul class="sidebar-nav">';
    foreach ($menuItems as $key => $item) {
        $activeClass = ($currentPage === $key) ? 'active' : '';
        echo '<li class="sidebar-nav-item">';
        echo '<a href="' . $item[2] . '" class="sidebar-nav-link ' . $activeClass . '">';
        echo '<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">';
        echo '<rect x="2" y="2" width="16" height="16" rx="4"/>';
        echo '</svg>';
        echo $item[0];
        echo '</a>';
        echo '</li>';
    }
    echo '</ul>';
    
    echo '<div class="p-3 mt-auto border-top">';
    echo '<a href="login.php" class="btn btn-outline-danger btn-sm w-100">Déconnexion</a>';
    echo '</div>';
    
    echo '</div>';
}

// Search Bar Component
function renderSearchBar($placeholder = 'Rechercher...', $id = 'search') {
    echo '<div class="search-container">';
    echo '<form class="search-form" id="' . $id . '-form">';
    echo '<input type="text" class="search-input" id="' . $id . '-input" placeholder="' . $placeholder . '">';
    echo '<button type="submit" class="search-button">';
    echo '<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">';
    echo '<path d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"/>';
    echo '</svg>';
    echo '</button>';
    echo '</form>';
    echo '</div>';
}

// Breadcrumb Component
function renderBreadcrumb($items) {
    echo '<nav aria-label="breadcrumb">';
    echo '<ol class="breadcrumb">';
    foreach ($items as $index => $item) {
        if ($index === count($items) - 1) {
            echo '<li class="breadcrumb-item active" aria-current="page">' . $item[0] . '</li>';
        } else {
            echo '<li class="breadcrumb-item"><a href="' . $item[1] . '">' . $item[0] . '</a>';
        }
    }
    echo '</ol>';
    echo '</nav>';
}

// ===== NOTIFICATIONS FUNCTIONS =====

// Créer une notification
function create_notification($id_destinataire, $type_destinataire, $titre, $message, $type_notification, $id_source = null) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO notifications (id_destinataire, type_destinataire, titre, message, type_notification, id_source) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$id_destinataire, $type_destinataire, $titre, $message, $type_notification, $id_source]);
}

// Récupérer les notifications d'un utilisateur
function get_notifications($id_utilisateur, $type_utilisateur, $limit = 10) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE id_destinataire = ? AND type_destinataire = ? ORDER BY date_creation DESC LIMIT ?");
    $stmt->execute([$id_utilisateur, $type_utilisateur, $limit]);
    $result = $stmt->fetchAll();
    $notifications = [];
    foreach ($result as $row) {
        $notifications[] = $row;
    }
    return $notifications;
}


// Marquer une notification comme lue
function mark_notification_as_read($notification_id) {
    global $conn;
    $stmt = $conn->prepare("UPDATE notifications SET lu = TRUE WHERE id = ?");
    return $stmt->execute([$notification_id]);
}

// Marquer toutes les notifications comme lues
function mark_all_notifications_as_read($id_utilisateur, $type_utilisateur) {
    global $conn;
    $stmt = $conn->prepare("UPDATE notifications SET lu = TRUE WHERE id_destinataire = ? AND type_destinataire = ?");
    return $stmt->execute([$id_utilisateur, $type_utilisateur]);
}

// Envoyer notification à tous les enseignants
function notify_all_teachers($titre, $message, $type_notification, $id_source = null) {
    global $conn;
    // Récupérer tous les enseignants
    $result = $conn->query("SELECT id FROM utilisateur WHERE statut = 'ENSEIGNANT'");
    $teachers = [];
    foreach ($result->fetchAll() as $row) {
        $teachers[] = $row['id'];
    }
    
    // Créer une notification pour chaque enseignant
    foreach ($teachers as $teacher_id) {
        create_notification($teacher_id, 'ENSEIGNANT', $titre, $message, $type_notification, $id_source);
    }
}

// ===== DEMANDE DE RECOMMANDATION FUNCTIONS =====

// Créer une demande de recommandation
function create_demande_recommandation($id_entreprise, $id_enseignant, $id_etudiant, $id_offre, $message = '') {
    global $conn;
    
    // Vérifier si une demande existe déjà pour cette combinaison
    $check_sql = "SELECT id, statut FROM demande_recommandation 
                  WHERE idEntreprise = ? AND idEnseignant = ? AND idEtudiant = ? AND idOffre = ?";
    $check_stmt = $conn->prepare($check_sql);
    
    if (!$check_stmt) {
        error_log("Erreur prepare check_stmt: " . $conn->error);
        return false;
    }
    
    $check_stmt->execute([$id_entreprise, $id_enseignant, $id_etudiant, $id_offre]);
    $result = $check_stmt->fetchAll();
    $existing_requests = $result;
    
    error_log("=== DEBUG DEMANDE RECOMMANDATION ===");
    error_log("Vérification pour: Entreprise=$id_entreprise, Enseignant=$id_enseignant, Étudiant=$id_etudiant, Offre=$id_offre");
    error_log("Nombre de demandes existantes: " . count($existing_requests));
    
    foreach ($existing_requests as $existing) {
        error_log("Demande existante - ID: " . $existing['id'] . ", Statut: " . $existing['statut']);
    }
    
    if (count($existing_requests) > 0) {
        error_log("❌ Demande déjà existante pour cette combinaison");
        return false; // Demande déjà existante
    }
    
    error_log("✅ Aucune demande existante, création autorisée");
    
    // Insérer avec la structure complète de la nouvelle table
    $sql = "INSERT INTO demande_recommandation (idEntreprise, idEnseignant, idEtudiant, idOffre, contenu, date_demande, statut) 
            VALUES (?, ?, ?, ?, ?, NOW(), 'EN_ATTENTE')";
    $stmt = $conn->prepare($sql);
    
    try {
        $stmt->execute([$id_entreprise, $id_enseignant, $id_etudiant, $id_offre, $message]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            error_log("Demande de recommandation déjà existante");
            return false;
        }
        throw $e;
    }
    
    if (true) {
        // Envoyer une notification à l'enseignant
        $entreprise_info = get_user_info($id_entreprise);
        $etudiant_info = get_user_info($id_etudiant);
        $offre_info = get_offre_info($id_offre);
        
        $titre = "Demande de recommandation";
        
        // Construire le message de notification avec gestion des clés manquantes
        $entreprise_nom = $entreprise_info['nom'] ?? $entreprise_info['username'] ?? 'Entreprise inconnue';
        $etudiant_nom = ($etudiant_info['nom'] ?? '') . ' ' . ($etudiant_info['prenom'] ?? '');
        $etudiant_nom = trim($etudiant_nom) ?: 'Étudiant inconnu';
        $offre_titre = $offre_info['titre'] ?? 'Offre inconnue';
        
        $message_notif = "L'entreprise \"{$entreprise_nom}\" demande une recommandation pour l'étudiant \"{$etudiant_nom}\" concernant l'offre \"{$offre_titre}\"";
        
        // Utiliser l'ID de l'entreprise comme source (doit exister dans la table utilisateur)
        create_notification($id_enseignant, 'ENSEIGNANT', $titre, $message_notif, 'RECOMMANDATION', $id_entreprise);
        
        return true;
    }
    
    return false;
}

// Récupérer les demandes de recommandation pour un enseignant
function get_demandes_recommandation_enseignant($id_enseignant, $statut = 'EN_ATTENTE') {
    global $conn;
    
    // D'abord vérifier si la table existe
    $table_check = "SHOW TABLES LIKE 'demande_recommandation'";
    $result = $conn->query($table_check);
    
    if (count($result->fetchAll()) === 0) {
        return []; // La table n'existe pas encore
    }
    
    // Essayer avec les noms de colonnes corrects
    try {
        $sql = "SELECT dr.*, o.titre as offre_titre
                FROM demande_recommandation dr
                LEFT JOIN offre o ON dr.idOffre = o.id
                WHERE dr.idEnseignant = ? AND dr.statut = ?
                ORDER BY dr.date_demande DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id_enseignant, $statut]);
        $result = $stmt->fetchAll();
        
        $demandes = [];
        foreach ($result as $row) {
            $demandes[] = $row;
        }
        
        return $demandes;
    } catch (Exception $e) {
        // Si ça échoue, retourner un tableau vide
        return [];
    }
}

// Répondre à une demande de recommandation
function repondre_demande_recommandation($id_demande, $reponse, $statut) {
    global $conn;
    
    $sql = "UPDATE demande_recommandation 
            SET statut = ?, reponse = ?, date_reponse = CURRENT_TIMESTAMP 
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt->execute([$statut, $reponse, $id_demande])) {
        // Récupérer les infos pour notifier l'entreprise
        $demande_info = get_demande_info($id_demande);
        
        if ($demande_info) {
            $titre = "Réponse à votre demande de recommandation";
            $message_notif = "L'enseignant a " . ($statut === 'ACCEPTEE' ? 'accepté' : 'refusé') . " votre demande de recommandation pour l'étudiant \"{$demande_info['etudiant_nom']} {$demande_info['etudiant_prenom']}\"";
            
            create_notification($demande_info['id_entreprise'], 'ENTREPRISE', $titre, $message_notif, 'RECOMMANDATION', $id_demande);
        }
        
        return true;
    }
    
    return false;
}

// Récupérer les informations d'une demande
function get_demande_info($id_demande) {
    global $conn;
    
    $sql = "SELECT dr.*, u_entreprise.nom as entreprise_nom, u_etudiant.nom as etudiant_nom, 
                   u_etudiant.prenom as etudiant_prenom, o.titre as offre_titre
            FROM demande_recommandation dr
            JOIN utilisateur u_entreprise ON dr.id_entreprise = u_entreprise.id
            JOIN utilisateur u_etudiant ON dr.id_etudiant = u_etudiant.id
            JOIN offre o ON dr.id_offre = o.id
            WHERE dr.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_demande]);
    
    return $stmt->fetch();
}

// Obtenir les informations d'un utilisateur
function get_user_info($user_id) {
    global $conn;
    
    $sql = "SELECT * FROM utilisateur WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    
    return $stmt->fetch();
}

// Obtenir les informations d'une offre
function get_offre_info($offre_id) {
    global $conn;
    
    $sql = "SELECT * FROM offre WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$offre_id]);
    
    return $stmt->fetch();
}

// Obtenir l'enseignant associé à un étudiant
function get_etudiant_enseignant($etudiant_id) {
    global $conn;
    
    // Méthode 1: Vérifier si la table utilisateur a un champ id_enseignant
    $check_column = $conn->query("SHOW COLUMNS FROM utilisateur LIKE 'id_enseignant'");
    if (count($check_column->fetchAll()) > 0) {
        $sql = "SELECT id_enseignant FROM utilisateur WHERE id = ? AND id_enseignant IS NOT NULL";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$etudiant_id]);
        $result = $stmt->fetch();
        return $result ? $result['id_enseignant'] : null;
    }
    
    // Méthode 2: Chercher dans une table de liaison étudiant-enseignant si elle existe
    $check_table = $conn->query("SHOW TABLES LIKE 'etudiant_enseignant'");
    if (count($check_table->fetchAll()) > 0) {
        $sql = "SELECT id_enseignant FROM etudiant_enseignant WHERE id_etudiant = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$etudiant_id]);
        $result = $stmt->fetch();
        return $result ? $result['id_enseignant'] : null;
    }
    
    // Méthode 3: Prendre le premier enseignant disponible (fallback)
    $sql = "SELECT id FROM utilisateur WHERE statut = 'ENSEIGNANT' LIMIT 1";
    $result = $conn->query($sql);
    if ($result && count($result->fetchAll()) > 0) {
        return $result->fetch()['id'];
    }
    
    return null;
}


// Récupérer tous les enseignants
function get_all_teachers() {
    global $conn;
    
    $sql = "SELECT * FROM utilisateur u 
            JOIN enseignant e ON u.id = e.id 
            WHERE u.statut = 'ENSEIGNANT' 
            ORDER BY u.id ASC";
    
    $result = $conn->query($sql);
    
    $teachers = [];
    foreach ($result->fetchAll() as $row) {
        $teachers[] = $row;
    }
    
    return $teachers;
}

// Récupérer les demandes d'une entreprise
function get_entreprise_demandes($entreprise_id) {
    global $conn;
    
    // D'abord vérifier si la table existe
    $table_check = "SHOW TABLES LIKE 'demande_recommandation'";
    $result = $conn->query($table_check);
    
    if (count($result->fetchAll()) === 0) {
        return []; // La table n'existe pas encore
    }
    
    // Essayer avec les noms de colonnes camelCase
    try {
        $sql = "SELECT dr.*, o.titre as offre_titre
                FROM demande_recommandation dr
                JOIN offre o ON dr.idOffre = o.id
                WHERE dr.idEntreprise = ?
                ORDER BY dr.date_demande DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$entreprise_id]);
        
        $demandes = [];
        $result = $stmt->fetchAll();
        foreach ($result as $row) {
            $demandes[] = $row;
        }
        
        return $demandes;
    } catch (Exception $e) {
        // Si ça échoue, retourner un tableau vide
        return [];
    }
}

// Récupérer les recommandations d'un étudiant par un enseignant
function get_student_recommendations($student_id, $teacher_id) {
    global $conn;
    
    $sql = "SELECT * FROM recommandation 
            WHERE idEtudiant = ? AND idEnseignant = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$student_id, $teacher_id]);
    
    $recommendations = [];
    $result = $stmt->fetchAll();
    foreach ($result as $row) {
        $recommendations[] = $row;
    }
    
    return $recommendations;
}

// Obtenir le nom d'un étudiant
function get_student_name($student_id) {
    global $conn;
    
    $sql = "SELECT nom, prenom FROM utilisateur WHERE id = ? AND role = 'ETUDIANT'";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$student_id]);
    $result = $stmt->fetchAll();
    
    if ($row = $result) {
        return $row['prenom'] . ' ' . $row['nom'];
    }
    
    return null;
}

// Obtenir le nom d'un enseignant
function get_teacher_name($teacher_id) {
    global $conn;
    
    $sql = "SELECT nom, prenom FROM utilisateur WHERE id = ? AND role = 'ENSEIGNANT'";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$teacher_id]);
    $result = $stmt->fetchAll();
    
    if ($row = $result) {
        return $row['prenom'] . ' ' . $row['nom'];
    }
    
    return null;
}

// Fonctions statistiques pour la page d'accueil (version simplifiée avec débogage)
function get_total_offres() {
    global $conn;
    try {
        $sql = "SELECT COUNT(*) as count FROM offre";
        error_log("SQL get_total_offres: " . $sql);
        $result = $conn->query($sql);
        if ($result) {
            $count = $result->fetch()['count'] ?? 0;
            error_log("Résultat get_total_offres: " . $count);
            return $count;
        } else {
            error_log("Erreur SQL get_total_offres: " . $conn->error);
            return 0;
        }
    } catch (Exception $e) {
        error_log("Exception get_total_offres: " . $e->getMessage());
        return 0;
    }
}

function get_total_events() {
    global $conn;
    try {
        $sql = "SELECT COUNT(*) as count FROM evenement";
        error_log("SQL get_total_events: " . $sql);
        $result = $conn->query($sql);
        if ($result) {
            $count = $result->fetch()['count'] ?? 0;
            error_log("Résultat get_total_events: " . $count);
            return $count;
        } else {
            error_log("Erreur SQL get_total_events: " . $conn->error);
            return 0;
        }
    } catch (Exception $e) {
        error_log("Exception get_total_events: " . $e->getMessage());
        return 0;
    }
}

function get_total_students() {
    global $conn;
    try {
        $sql = "SELECT COUNT(*) as count FROM etudiant";
        error_log("SQL get_total_students: " . $sql);
        $result = $conn->query($sql);
        if ($result) {
            $count = $result->fetch()['count'] ?? 0;
            error_log("Résultat get_total_students: " . $count);
            return $count;
        } else {
            error_log("Erreur SQL get_total_students: " . $conn->error);
            return 0;
        }
    } catch (Exception $e) {
        error_log("Exception get_total_students: " . $e->getMessage());
        return 0;
    }
}

function get_total_companies() {
    global $conn;
    try {
        $sql = "SELECT COUNT(*) as count FROM entreprise";
        error_log("SQL get_total_companies: " . $sql);
        $result = $conn->query($sql);
        if ($result) {
            $count = $result->fetch()['count'] ?? 0;
            error_log("Résultat get_total_companies: " . $count);
            return $count;
        } else {
            error_log("Erreur SQL get_total_companies: " . $conn->error);
            return 0;
        }
    } catch (Exception $e) {
        error_log("Exception get_total_companies: " . $e->getMessage());
        return 0;
    }
}

function get_active_offres_count() {
    global $conn;
    try {
        $sql = "SELECT COUNT(*) as count FROM offre WHERE statut = 'OUVERT'";
        error_log("SQL get_active_offres_count: " . $sql);
        $result = $conn->query($sql);
        if ($result) {
            $count = $result->fetch()['count'] ?? 0;
            error_log("Résultat get_active_offres_count: " . $count);
            return $count;
        } else {
            error_log("Erreur SQL get_active_offres_count: " . $conn->error);
            return 0;
        }
    } catch (Exception $e) {
        error_log("Exception get_active_offres_count: " . $e->getMessage());
        return 0;
    }
}

function get_upcoming_events_count() {
    global $conn;
    try {
        // Même requête que get_evenements_avenir mais avec COUNT
        $sql = "SELECT COUNT(*) as count FROM evenement WHERE dateEvent >= CURDATE()";
        error_log("SQL get_upcoming_events_count: " . $sql);
        $result = $conn->query($sql);
        if ($result) {
            $count = $result->fetch()['count'] ?? 0;
            error_log("Résultat get_upcoming_events_count: " . $count);
            return $count;
        } else {
            error_log("Erreur SQL get_upcoming_events_count: " . $conn->error);
            return 0;
        }
    } catch (Exception $e) {
        error_log("Exception get_upcoming_events_count: " . $e->getMessage());
        return 0;
    }
}

function get_recent_offres_with_companies($limit = 6) {
    global $conn;
    $sql = "SELECT o.*, e.nomsociete 
            FROM offre o 
            JOIN entreprise e ON o.idEntreprise = e.id 
            WHERE o.statut = 'OUVERT' 
            ORDER BY o.datePub DESC 
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function get_upcoming_events($limit = 6) {
    global $conn;
    try {
        // Même logique que get_evenements_avenir mais avec filtrage date et limit
        $sql = "SELECT * FROM evenement 
                WHERE dateEvent >= CURDATE() 
                ORDER BY dateEvent DESC";
        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }
        error_log("SQL get_upcoming_events: " . $sql);
        $result = $conn->query($sql);
        
        $events = [];
        while ($row = $result->fetch()) {
            $events[] = $row;
        }
        error_log("Résultat get_upcoming_events: " . count($events) . " événements");
        return $events;
    } catch (Exception $e) {
        error_log("Exception get_upcoming_events: " . $e->getMessage());
        return [];
    }
}

function get_top_students($limit = 9) {
    global $conn;
    $sql = "SELECT e.*, u.photo, COALESCE(SUM(b.score), 0) as total_score 
            FROM etudiant e 
            LEFT JOIN utilisateur u ON e.id = u.id 
            LEFT JOIN badge b ON e.id = b.idEtudiant 
            GROUP BY e.id 
            ORDER BY total_score DESC, e.nom ASC 
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$limit]);
    $students = $stmt->fetchAll();
    
    // Ajouter le score total pour chaque étudiant
    foreach ($students as &$student) {
        $student['score'] = $student['total_score'] ?? 0;
    }
    
    return $students;
}

function get_recent_testimonials($limit = 3) {
    global $conn;
    $sql = "SELECT r.*, et.nom, et.prenom, et.specialite, u.photo
            FROM recommandation r 
            JOIN etudiant et ON r.idEtudiant = et.id 
            LEFT JOIN utilisateur u ON et.id = u.id
            WHERE r.contenu IS NOT NULL AND r.contenu != ''
            ORDER BY r.dateRec DESC 
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}
// Fonction pour créer un nouvel étudiant
function create_student($prenom, $nom, $email, $mot_de_passe, $telephone = null, $cin = null, $promotion = null, $specialite = null, $experience = null, $photo = null, $cv = null) {
    global $conn;
    
    // Valider le format de l'email prenom.nom@esen.tn
    $expected_email = strtolower($prenom . '.' . $nom . '@esen.tn');
    if ($email !== $expected_email) {
        return ['success' => false, 'message' => "L'email doit être sous la forme: $expected_email"];
    }
    
    // Vérifier si l'email existe déjà
    $check_email = $conn->prepare("SELECT id FROM utilisateur WHERE email = ?");
    $check_email->execute([$email]);
        if ($check_email->rowCount() > 0) {
        return ['success' => false, 'message' => 'Cet email est déjà utilisé'];
    }
    
    // Stocker le mot de passe sans hachage
    $mot_de_passe_plain = $mot_de_passe;
    $statut = 'ETUDIANT';
    
    // Insérer dans la table utilisateur
    $stmt_utilisateur = $conn->prepare("INSERT INTO utilisateur (email, motDePasse, telephone, statut, photo) VALUES (?, ?, ?, ?, ?)");
        
    if ($stmt_utilisateur->execute([$email, $mot_de_passe_plain, $telephone, $statut, $photo])) {
        $user_id = $conn->lastInsertId();
        $date_inscription = date('Y-m-d');
        
        // Insérer dans la table etudiant
        $stmt_etudiant = $conn->prepare("INSERT INTO etudiant (id, prenom, nom, cin, promotion, specialite, experience, cv, dateInscription) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt_etudiant->execute([$user_id, $prenom, $nom, $cin, $promotion, $specialite, $experience, $cv, $date_inscription])) {
            return ['success' => true, 'user_id' => $user_id];
        } else {
            // Supprimer l'utilisateur si l'insertion dans etudiant échoue
            $stmt_delete = $conn->prepare("DELETE FROM utilisateur WHERE id = ?");
            $stmt_delete->execute([$user_id]);
            return ['success' => false, 'message' => 'Erreur etudiant: insertion échouée'];
        }
    } else {
        return ['success' => false, 'message' => 'Erreur utilisateur: insertion échouée'];
    }
}

// Fonction pour créer une nouvelle entreprise
function create_company($nom_entreprise, $email, $mot_de_passe, $telephone = null, $description = null, $logo = null) {
    global $conn;
    
    // Valider que l'email a un format valide (pas de restriction spécifique)
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Veuillez entrer un email valide'];
    }
    
    // Vérifier si l'email existe déjà
    $check_email = $conn->prepare("SELECT id FROM utilisateur WHERE email = ?");
    $check_email->execute([$email]);
        if ($check_email->rowCount() > 0) {
        return ['success' => false, 'message' => 'Cet email est déjà utilisé'];
    }
    
    // Stocker le mot de passe sans hachage
    $mot_de_passe_plain = $mot_de_passe;
    $statut = 'ENTREPRISE';
    
    // Insérer dans la table utilisateur
    $stmt_utilisateur = $conn->prepare("INSERT INTO utilisateur (email, motDePasse, telephone, statut, photo) VALUES (?, ?, ?, ?, ?)");
        
    if ($stmt_utilisateur->execute([$email, $mot_de_passe_plain, $telephone, $statut, $photo])) {
        $user_id = $conn->lastInsertId();
        $date_inscription = date('Y-m-d');
        
        // Insérer dans la table entreprise
        $stmt_entreprise = $conn->prepare("INSERT INTO entreprise (id, nomsociete, description, dateInscription, logo) VALUES (?, ?, ?, ?, ?)");
                
        if ($stmt_entreprise->execute([$user_id, $nom_entreprise, $description, $date_inscription, $logo])) {
            return ['success' => true, 'user_id' => $user_id];
        } else {
            // Supprimer l'utilisateur si l'insertion dans entreprise échoue
            $stmt_delete = $conn->prepare("DELETE FROM utilisateur WHERE id = ?");
                        $stmt_delete->execute([$user_id]);
            return ['success' => false, 'message' => 'Erreur entreprise: ' . $stmt_entreprise->error];
        }
    } else {
        return ['success' => false, 'message' => 'Erreur utilisateur: insertion échouée'];
    }
}

// Fonction pour créer un nouvel enseignant
function create_teacher($prenom, $nom, $email, $mot_de_passe, $telephone = null, $specialite = null, $photo = null) {
    global $conn;
    
    // Valider le format de l'email prenom.nom@esen.tn
    $expected_email = strtolower($prenom . '.' . $nom . '@esen.tn');
    if ($email !== $expected_email) {
        return ['success' => false, 'message' => "L'email doit être sous la forme: $expected_email"];
    }
    
    // Vérifier si l'email existe déjà
    $check_email = $conn->prepare("SELECT id FROM utilisateur WHERE email = ?");
    $check_email->execute([$email]);
        if ($check_email->rowCount() > 0) {
        return ['success' => false, 'message' => 'Cet email est déjà utilisé'];
    }
    
    // Stocker le mot de passe sans hachage
    $mot_de_passe_plain = $mot_de_passe;
    $statut = 'ENSEIGNANT';
    
    // Insérer dans la table utilisateur
    $stmt_utilisateur = $conn->prepare("INSERT INTO utilisateur (email, motDePasse, telephone, statut, photo) VALUES (?, ?, ?, ?, ?)");
        
    if ($stmt_utilisateur->execute([$email, $mot_de_passe_plain, $telephone, $statut, $photo])) {
        $user_id = $conn->lastInsertId();
        $date_inscription = date('Y-m-d');
        
        // Insérer dans la table enseignant
        $stmt_enseignant = $conn->prepare("INSERT INTO enseignant (id, prenom, nom, specialite, dateInscription) VALUES (?, ?, ?, ?, ?)");
                
        if ($stmt_enseignant->execute([$user_id, $prenom, $nom, $specialite, $date_inscription])) {
            return ['success' => true, 'user_id' => $user_id];
        } else {
            // Supprimer l'utilisateur si l'insertion dans enseignant échoue
            $stmt_delete = $conn->prepare("DELETE FROM utilisateur WHERE id = ?");
                        $stmt_delete->execute([$user_id]);
            return ['success' => false, 'message' => 'Erreur enseignant: ' . $stmt_enseignant->error];
        }
    } else {
        return ['success' => false, 'message' => 'Erreur utilisateur: insertion échouée'];
    }
}

// Fonction pour obtenir l'ID de l'entreprise connectée
function get_entreprise_id() {
    if (isset($_SESSION['entreprise_id'])) {
        return $_SESSION['entreprise_id'];
    }
    return null;
}

// Fonction pour obtenir les informations de l'entreprise
function get_entreprise_info($entreprise_id) {
    global $conn;
    $sql = "SELECT e.*, u.email, u.telephone, u.photo, e.logo 
            FROM entreprise e 
            JOIN utilisateur u ON e.id = u.id 
            WHERE e.id = ?";
    $stmt = $conn->prepare($sql);
        $stmt->execute([$entreprise_id]);
    return $stmt->fetch();
}

// Fonction pour obtenir l'ID de l'étudiant connecté
function get_etudiant_id() {
    if (isset($_SESSION['etudiant_id'])) {
        return $_SESSION['etudiant_id'];
    }
    return null;
}

// Fonction unifiée pour changer le mot de passe (sans hachage)
function change_user_password($user_id, $current_password, $new_password) {
    global $conn;
    
    try {
        // Vérifier le mot de passe actuel
        $stmt = $conn->prepare("SELECT motDePasse FROM utilisateur WHERE id = ?");
                $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return ['success' => false, 'message' => 'Utilisateur non trouvé.'];
        }
        
        // Comparaison directe sans hachage
        if ($current_password !== $result['motDePasse']) {
            return ['success' => false, 'message' => 'Le mot de passe actuel est incorrect.'];
        }
        
        // Mettre à jour le mot de passe (sans hachage)
        $stmt_update = $conn->prepare("UPDATE utilisateur SET motDePasse = ? WHERE id = ?");
                
        if ($stmt_update->execute([$new_password, $user_id])) {
            return ['success' => true, 'message' => 'Mot de passe mis à jour avec succès.'];
        } else {
            return ['success' => false, 'message' => 'Erreur lors de la mise à jour du mot de passe.'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
    }
}

// Fonction pour obtenir les informations de l'étudiant
function get_etudiant_info($etudiant_id) {
    global $conn;
    $sql = "SELECT et.*, u.email, u.telephone, u.photo, u.motDePasse 
            FROM etudiant et 
            JOIN utilisateur u ON et.id = u.id 
            WHERE et.id = ?";
    $stmt = $conn->prepare($sql);
        $stmt->execute([$etudiant_id]);
    return $stmt->fetch();
}

// Fonction pour obtenir les statistiques de l'étudiant
function get_etudiant_stats($etudiant_id) {
    global $conn;
    
    // Nombre de candidatures totales
    $sql = "SELECT COUNT(*) as total_candidatures FROM candidature WHERE idEtudiant = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$etudiant_id]);
    $result = $stmt->fetch();
    $candidatures = $result['total_candidatures'] ?? 0;
    
    // Nombre de candidatures acceptées
    $sql = "SELECT COUNT(*) as accepted_candidatures FROM candidature WHERE idEtudiant = ? AND statut = 'Acceptée'";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$etudiant_id]);
    $result = $stmt->fetch();
    $accepted = $result['accepted_candidatures'] ?? 0;
    
    // Nombre de participations aux événements
    $sql = "SELECT COUNT(*) as total_participations FROM participation WHERE idEtudiant = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$etudiant_id]);
    $result = $stmt->fetch();
    $participations = $result['total_participations'] ?? 0;
    
    // Score total de tous les badges de l'étudiant
    $sql = "SELECT COALESCE(SUM(score), 0) as total_score FROM badge WHERE idEtudiant = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$etudiant_id]);
    $result = $stmt->fetch();
    $score = $result['total_score'] ?? 0;
    
    return [
        'candidatures' => $candidatures,
        'accepted' => $accepted,
        'participations' => $participations,
        'score' => $score
    ];
}

// Fonction pour obtenir tous les badges d'un étudiant avec leur score total
function get_etudiant_badges_complets($etudiant_id) {
    global $conn;
    
    // Obtenir tous les badges de l'étudiant
    $sql = "SELECT * FROM badge WHERE idEtudiant = ? ORDER BY id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$etudiant_id]);
    
    $badges = [];
    foreach ($stmt->fetchAll() as $row) {
        $badges[] = $row;
    }
    
    // Calculer le score total
    $sql = "SELECT COALESCE(SUM(score), 0) as total_score FROM badge WHERE idEtudiant = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$etudiant_id]);
    $score_total = $stmt->fetch()['total_score'] ?? 0;
    
    return [
        'badges' => $badges,
        'score_total' => $score_total
    ];
}

// Fonction pour obtenir les participations de l'étudiant
function get_etudiant_participations($etudiant_id) {
    global $conn;
    $sql = "SELECT p.*, e.titre as event_titre, e.dateEvent, e.lieu
            FROM participation p
            JOIN evenement e ON p.idEvenement = e.id
            WHERE p.idEtudiant = ?
            ORDER BY e.dateEvent DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$etudiant_id]);
    
    $participations = [];
    foreach ($stmt->fetchAll() as $row) {
        $participations[] = $row;
    }
    
    return $participations;
}

// Fonction pour ajouter des points à un étudiant
function ajouter_points_etudiant($etudiant_id, $points, $raison) {
    global $conn;
    
    // Ajouter les points dans la table badge
    $sql = "INSERT INTO badge (idEtudiant, score, raison, dateObtention) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$etudiant_id, $points, $raison]);
}

// Fonction pour mettre à jour le score total d'un étudiant
function mettre_a_jour_score_total($etudiant_id, $nouveaux_points, $raison) {
    global $conn;
    
    // Obtenir le score actuel
    $sql = "SELECT COALESCE(SUM(score), 0) as score_actuel FROM badge WHERE idEtudiant = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$etudiant_id]);
    $score_actuel = $stmt->fetch()['score_actuel'] ?? 0;
    
    // Calculer le nouveau score total
    $nouveau_score_total = $score_actuel + $nouveaux_points;
    
    // Mettre à jour le score total dans un nouveau badge
    $sql = "INSERT INTO badge (idEtudiant, score, raison, dateObtention) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$etudiant_id, $nouveau_score_total, $raison]);
}

// Fonction pour ajouter des points et consolider en un seul badge
function ajouter_points_etudiant_consolidation($etudiant_id, $points, $raison) {
    global $conn;
    
    // Obtenir le score actuel
    $score_actuel = get_score_total_etudiant($etudiant_id);
    
    // Calculer le nouveau score total
    $nouveau_score_total = $score_actuel + $points;
    
    // Supprimer les anciens badges de l'étudiant
    $sql_delete = "DELETE FROM badge WHERE idEtudiant = ?";
    $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->execute([$etudiant_id]);
    
    // Créer un seul badge consolidé
    $sql_insert = "INSERT INTO badge (idEtudiant, score) VALUES (?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
        
    if ($stmt_insert->execute([$etudiant_id, $nouveau_score_total])) {
        return true;
    }
    
    return false;
}

// Fonction pour ajouter des points et mettre à jour le score total (ancienne méthode)
function ajouter_points_etudiant_avec_total($etudiant_id, $points, $raison) {
    global $conn;
    
    // Ajouter les points dans la table badge (colonnes existantes: id, score, idEtudiant)
    $sql = "INSERT INTO badge (idEtudiant, score) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
        
    if ($stmt->execute([$etudiant_id, $points])) {
        // Mettre à jour le score total (créer un nouveau badge avec le score cumulé)
        $score_actuel = get_score_total_etudiant($etudiant_id);
        $nouveau_score_total = $score_actuel + $points;
        
        $sql_update = "INSERT INTO badge (idEtudiant, score) VALUES (?, ?)";
        $stmt_update = $conn->prepare($sql_update);
                $stmt_update->execute([$etudiant_id, $nouveau_score_total]);
        
        return true;
    }
    
    return false;
}

// Fonction pour obtenir le score total d'un étudiant
function get_score_total_etudiant($etudiant_id) {
    global $conn;
    
    $sql = "SELECT COALESCE(SUM(score), 0) as total FROM badge WHERE idEtudiant = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$etudiant_id]);
    $result = $stmt->fetch();
    
    return $result['total'];
}

// Fonction pour vérifier et ajouter des points automatiquement
function verifier_et_ajouter_points_automatique($etudiant_id) {
    global $conn;
    
    // Vérifier les participations récentes sans points
    $sql = "SELECT p.idEvenement, e.titre 
            FROM participation p 
            JOIN evenement e ON p.idEvenement = e.id 
            LEFT JOIN badge b ON b.idEtudiant = ? AND b.raison LIKE CONCAT('%', e.titre, '%')
            WHERE p.idEtudiant = ? AND b.id IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$etudiant_id, $etudiant_id]);
    $participations_sans_points = $stmt;
    
    // Ajouter 10 points pour chaque participation sans points
    foreach ($participations_sans_points->fetchAll() as $row) {
        ajouter_points_etudiant($etudiant_id, 10, "Participation à l'événement: " . $row['titre']);
    }
    
    // Vérifier les candidatures acceptées récentes sans points
    $sql = "SELECT c.idOffre, o.titre 
            FROM candidature c 
            JOIN offre o ON c.idOffre = o.id 
            LEFT JOIN badge b ON b.idEtudiant = ? AND b.raison LIKE CONCAT('%', o.titre, '%')
            WHERE c.idEtudiant = ? AND c.statut = 'ACCEPTEE' AND b.id IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$etudiant_id, $etudiant_id]);
    $candidatures_acceptees_sans_points = $stmt;
    
    // Ajouter 20 points pour chaque candidature acceptée sans points
    foreach ($candidatures_acceptees_sans_points->fetchAll() as $row) {
        ajouter_points_etudiant($etudiant_id, 20, "Candidature acceptée: " . $row['titre']);
    }
    
    return true;
}

// Fonction pour ajouter des points aux participations anciennes sans points
function ajouter_points_participations_anciennes($etudiant_id) {
    global $conn;
    
    // Vérifier TOUTES les participations sans points (même anciennes)
    $sql = "SELECT p.idEvenement, e.titre 
            FROM participation p 
            JOIN evenement e ON p.idEvenement = e.id 
            LEFT JOIN badge b ON b.idEtudiant = ? AND b.raison LIKE CONCAT('%', e.titre, '%')
            WHERE p.idEtudiant = ? AND b.id IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$etudiant_id, $etudiant_id]);
    $participations_sans_points = $stmt;
    
    // Ajouter 10 points pour CHAQUE participation sans points (y compris anciennes)
    foreach ($participations_sans_points->fetchAll() as $row) {
        ajouter_points_etudiant($etudiant_id, 10, "Participation à l'événement: " . $row['titre']);
    }
    
    // Vérifier TOUTES les candidatures acceptées sans points (même anciennes)
    $sql = "SELECT c.idOffre, o.titre 
            FROM candidature c 
            JOIN offre o ON c.idOffre = o.id 
            LEFT JOIN badge b ON b.idEtudiant = ? AND b.raison LIKE CONCAT('%', o.titre, '%')
            WHERE c.idEtudiant = ? AND c.statut = 'ACCEPTEE' AND b.id IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$etudiant_id, $etudiant_id]);
    $candidatures_acceptees_sans_points = $stmt;
    
    // Ajouter 20 points pour CHAQUE candidature acceptée sans points (y compris anciennes)
    foreach ($candidatures_acceptees_sans_points->fetchAll() as $row) {
        ajouter_points_etudiant($etudiant_id, 20, "Candidature acceptée: " . $row['titre']);
    }
    
    return true;
}

// Fonction pour mettre à jour les points d'un badge
function mettre_a_jour_points_badge($badge_id, $nouveaux_points) {
    global $conn;
    
    $sql = "UPDATE badge SET score = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$nouveaux_points, $badge_id]);
}

// Fonction pour vérifier si un étudiant a déjà participé à un événement
function a_deja_participe($etudiant_id, $event_id) {
    global $conn;
    
    $sql = "SELECT COUNT(*) as count FROM participation WHERE idEtudiant = ? AND idEvenement = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$etudiant_id, $event_id]);
    $result = $stmt->fetch();
    return $result['count'] > 0;
}

// ===== FONCTIONS POUR LA PARTICIPATION DES ENSEIGNANTS =====

// Fonction pour vérifier si un enseignant a déjà participé à un événement
function teacher_a_deja_participe($teacher_id, $event_id) {
    global $conn;
    
    $sql = "SELECT COUNT(*) as count FROM teacher_participation WHERE idEnseignant = ? AND idEvenement = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$teacher_id, $event_id]);
    $result = $stmt->fetch();
    return $result['count'] > 0;
}

// Fonction pour ajouter la participation d'un enseignant à un événement
function teacher_participer_evenement($teacher_id, $event_id) {
    global $conn;
    
    // Vérifier si l'enseignant n'a pas déjà participé
    if (teacher_a_deja_participe($teacher_id, $event_id)) {
        return ['success' => false, 'message' => 'Vous participez déjà à cet événement'];
    }
    
    // Vérifier si l'événement existe et n'est pas expiré
    $sql = "SELECT dateEvent FROM evenement WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();
    
    if (!$event) {
        return ['success' => false, 'message' => 'Événement non trouvé'];
    }
    
    $event_date = new DateTime($event['dateEvent']);
    $today = new DateTime();
    if ($event_date < $today) {
        return ['success' => false, 'message' => 'Cet événement est déjà terminé'];
    }
    
    // Ajouter la participation
    $sql = "INSERT INTO teacher_participation (idEnseignant, idEvenement, dateParticipation, statut) VALUES (?, ?, NOW(), 'CONFIRME')";
    $stmt = $conn->prepare($sql);
    
    if ($stmt->execute([$teacher_id, $event_id])) {
        return ['success' => true, 'message' => 'Participation enregistrée avec succès'];
    } else {
        return ['success' => false, 'message' => 'Erreur lors de l\'enregistrement de la participation'];
    }
}

// Fonction pour annuler la participation d'un enseignant à un événement
function teacher_annuler_participation($teacher_id, $event_id) {
    global $conn;
    
    $sql = "DELETE FROM teacher_participation WHERE idEnseignant = ? AND idEvenement = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt->execute([$teacher_id, $event_id])) {
        return ['success' => true, 'message' => 'Participation annulée avec succès'];
    } else {
        return ['success' => false, 'message' => 'Erreur lors de l\'annulation de la participation'];
    }
}

// Fonction pour obtenir les participations de l'enseignant
function get_teacher_participations($teacher_id) {
    global $conn;
    $sql = "SELECT p.*, e.titre as event_titre, e.dateEvent, e.lieu
            FROM teacher_participation p
            JOIN evenement e ON p.idEvenement = e.id
            WHERE p.idEnseignant = ?
            ORDER BY e.dateEvent DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$teacher_id]);
    
    $participations = [];
    foreach ($stmt->fetchAll() as $row) {
        $participations[] = $row;
    }
    
    return $participations;
}

// Fonction pour obtenir les statistiques de participation aux événements pour un enseignant
function get_teacher_event_participation_stats($teacher_id) {
    global $conn;
    
    $stats = [
        'total_participations' => 0,
        'upcoming_participations' => 0,
        'past_participations' => 0,
        'total_events_available' => 0,
        'participation_rate' => 0
    ];
    
    // Total des participations de l'enseignant
    $sql = "SELECT COUNT(*) as count FROM teacher_participation WHERE idEnseignant = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$teacher_id]);
    $result = $stmt->fetch();
    $stats['total_participations'] = $result['count'] ?? 0;
    
    // Participations aux événements à venir
    $sql = "SELECT COUNT(*) as count 
            FROM teacher_participation tp
            JOIN evenement e ON tp.idEvenement = e.id
            WHERE tp.idEnseignant = ? AND e.dateEvent >= CURDATE()";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$teacher_id]);
    $result = $stmt->fetch();
    $stats['upcoming_participations'] = $result['count'] ?? 0;
    
    // Participations aux événements passés
    $sql = "SELECT COUNT(*) as count 
            FROM teacher_participation tp
            JOIN evenement e ON tp.idEvenement = e.id
            WHERE tp.idEnseignant = ? AND e.dateEvent < CURDATE()";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$teacher_id]);
    $result = $stmt->fetch();
    $stats['past_participations'] = $result['count'] ?? 0;
    
    // Total des événements disponibles
    $sql = "SELECT COUNT(*) as count FROM evenement WHERE dateEvent >= CURDATE()";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch();
    $stats['total_events_available'] = $result['count'] ?? 0;
    
    // Taux de participation (pour les événements à venir)
    if ($stats['total_events_available'] > 0) {
        $stats['participation_rate'] = round(($stats['upcoming_participations'] / $stats['total_events_available']) * 100, 1);
    }
    
    return $stats;
}

// Fonction pour récupérer les témoignages des étudiants avec notes
function get_testimonials_with_ratings($limit = 10) {
    global $conn;
    
    // Essayer d'abord avec les témoignages approuvés, sinon prendre tous les témoignages
    $sql = "SELECT t.*, e.prenom, e.nom, e.specialite, u.photo as user_photo 
            FROM temoignage t
            JOIN etudiant e ON t.idEtudiant = e.id
            JOIN utilisateur u ON e.id = u.id
            WHERE (t.statut = 'APPROUVE' OR t.statut IS NULL OR t.statut = '')
            ORDER BY t.dateTemoignage DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$limit]);
    
    $testimonials = [];
    foreach ($stmt->fetchAll() as $row) {
        $testimonials[] = $row;
    }
    
    // Si aucun témoignage trouvé, essayer sans filtre de statut
    if (empty($testimonials)) {
        $sql = "SELECT t.*, e.prenom, e.nom, e.specialite, u.photo as user_photo 
                FROM temoignage t
                JOIN etudiant e ON t.idEtudiant = e.id
                JOIN utilisateur u ON e.id = u.id
                ORDER BY t.dateTemoignage DESC
                LIMIT ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$limit]);
        
        $testimonials = [];
        foreach ($stmt->fetchAll() as $row) {
            $testimonials[] = $row;
        }
    }
    
    return $testimonials;
}

// Fonction pour générer le code HTML des étoiles basé sur la note
function generate_star_rating($note) {
    $note = max(1, min(5, (int)$note)); // Assurer que la note est entre 1 et 5
    $stars_html = '';
    
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $note) {
            $stars_html .= '<i class="fas fa-star" style="color: #f59e0b;"></i>';
        } else {
            $stars_html .= '<i class="far fa-star" style="color: #d1d5db;"></i>';
        }
    }
    
    return $stars_html;
}

// Fonction pour récupérer les étudiants avec badges
function get_students_with_badges($limit = 9) {
    global $conn;
    
    $sql = "SELECT e.*, u.photo, SUM(b.score) as total_score, COUNT(b.id) as badge_count
            FROM etudiant e
            JOIN utilisateur u ON e.id = u.id
            INNER JOIN badge b ON e.id = b.idEtudiant AND b.score > 0
            GROUP BY e.id, u.photo
            ORDER BY total_score DESC, badge_count DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$limit]);
    
    $students = [];
    foreach ($stmt->fetchAll() as $row) {
        $students[] = $row;
    }
    
    return $students;
}

// Fonction pour récupérer les badges d'un étudiant
function get_student_badges($etudiant_id) {
    global $conn;
    
    $sql = "SELECT * FROM badge WHERE idEtudiant = ? AND score > 0 ORDER BY id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$etudiant_id]);
    
    $badges = [];
    foreach ($stmt->fetchAll() as $row) {
        $badges[] = $row;
    }
    
    return $badges;
}

// Fonction pour vérifier si un étudiant a déjà postulé à une offre
function a_deja_postule($etudiant_id, $offre_id) {
    global $conn;
    
    $sql = "SELECT COUNT(*) as count FROM candidature WHERE idEtudiant = ? AND idOffre = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$etudiant_id, $offre_id]);
    $result = $stmt->fetch();
    return $result['count'] > 0;
}

// Fonction pour obtenir les candidatures de l'étudiant
function get_etudiant_candidatures($etudiant_id) {
    global $conn;
    $sql = "SELECT c.*, o.titre as offre_titre, o.type as offre_type, e.nomsociete, o.datePub 
            FROM candidature c 
            JOIN offre o ON c.idOffre = o.id 
            JOIN entreprise e ON o.idEntreprise = e.id 
            WHERE c.idEtudiant = ? 
            ORDER BY c.datePost DESC";
    $stmt = $conn->prepare($sql);
        $stmt->execute([$etudiant_id]);
    $result = $stmt;
    
    $candidatures = [];
    foreach ($result->fetchAll() as $row) {
        $candidatures[] = $row;
    }
    return $candidatures;
}

// Fonction pour obtenir les offres disponibles
function get_offres_disponibles($limit = null) {
    global $conn;
    $sql = "SELECT o.*, e.nomsociete, 
            (SELECT COUNT(*) FROM candidature c WHERE c.idOffre = o.id) as nb_candidatures
            FROM offre o 
            JOIN entreprise e ON o.idEntreprise = e.id 
            WHERE o.statut = 'OUVERT'
            ORDER BY o.datePub DESC";
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    $result = $conn->query($sql);
    
    $offres = [];
    foreach ($result->fetchAll() as $row) {
        $offres[] = $row;
    }
    return $offres;
}

// Fonction pour obtenir les événements à venir
function get_evenements_avenir($limit = null) {
    global $conn;
    $sql = "SELECT * FROM evenement 
            WHERE dateEvent >= CURDATE() 
            ORDER BY dateEvent ASC";
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    $result = $conn->query($sql);
    
    $events = [];
    foreach ($result->fetchAll() as $row) {
        $events[] = $row;
    }
    return $events;
}

// Fonction pour obtenir tous les événements (passés et à venir)
function get_all_evenements($limit = null) {
    global $conn;
    $sql = "SELECT e.*, 
                   CASE 
                       WHEN e.dateEvent >= CURDATE() THEN 'upcoming'
                       ELSE 'past'
                   END as status
            FROM evenement e 
            ORDER BY e.dateEvent DESC";
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    $result = $conn->query($sql);
    
    $events = [];
    foreach ($result->fetchAll() as $row) {
        $events[] = $row;
    }
    return $events;
}

// Fonction pour obtenir les statistiques des événements
function get_evenements_stats() {
    global $conn;
    
    $stats = [];
    
    // Total des événements
    $result = $conn->query("SELECT COUNT(*) as total FROM evenement");
    $row = $result->fetch();
    $stats['total'] = $row['total'] ?? 0;
    
    // Événements à venir
    $result = $conn->query("SELECT COUNT(*) as upcoming FROM evenement WHERE dateEvent >= CURDATE()");
    $row = $result->fetch();
    $stats['upcoming'] = $row['upcoming'] ?? 0;
    
    // Événements passés
    $result = $conn->query("SELECT COUNT(*) as past FROM evenement WHERE dateEvent < CURDATE()");
    $row = $result->fetch();
    $stats['past'] = $row['past'] ?? 0;
    
    // Capacité totale
    $result = $conn->query("SELECT SUM(capacite) as total_capacity FROM evenement WHERE capacite IS NOT NULL");
    $row = $result->fetch();
    $stats['total_capacity'] = $row['total_capacity'] ?? 0;
    
    return $stats;
}

// Fonction pour créer un nouvel admin
function create_admin($prenom, $nom, $email, $mot_de_passe, $telephone = null, $photo = null) {
    global $conn;
    
    // Valider le format de l'email prenom.nom@esen.tn
    $expected_email = strtolower($prenom . '.' . $nom . '@esen.tn');
    if ($email !== $expected_email) {
        return ['success' => false, 'message' => "L'email doit être sous la forme: $expected_email"];
    }
    
    // Vérifier si l'email existe déjà
    $check_email = $conn->prepare("SELECT id FROM utilisateur WHERE email = ?");
    $check_email->execute([$email]);
        if ($check_email->rowCount() > 0) {
        return ['success' => false, 'message' => 'Cet email est déjà utilisé'];
    }
    
    // Stocker le mot de passe sans hachage
    $mot_de_passe_plain = $mot_de_passe;
    $statut = 'ADMIN';
    
    // Insérer dans la table utilisateur
    $stmt_utilisateur = $conn->prepare("INSERT INTO utilisateur (email, motDePasse, telephone, statut, photo) VALUES (?, ?, ?, ?, ?)");
        
    if ($stmt_utilisateur->execute([$email, $mot_de_passe_plain, $telephone, $statut, $photo])) {
        $user_id = $conn->lastInsertId();
        $date_inscription = date('Y-m-d');
        
        // Insérer dans la table admin (si elle existe)
        $stmt_admin = $conn->prepare("INSERT INTO admin (id, prenom, nom, dateInscription) VALUES (?, ?, ?, ?)");
                
        if ($stmt_admin->execute([$user_id, $prenom, $nom, $date_inscription])) {
            return ['success' => true, 'user_id' => $user_id];
        } else {
            // Supprimer l'utilisateur si l'insertion dans admin échoue
            $stmt_delete = $conn->prepare("DELETE FROM utilisateur WHERE id = ?");
                        $stmt_delete->execute([$etudiant_id]);
            return ['success' => false, 'message' => 'Erreur admin: ' . $stmt_admin->error];
        }
    } else {
        return ['success' => false, 'message' => 'Erreur utilisateur: insertion échouée'];
    }
}

// ===== FONCTIONS POUR LES ENSEIGNANTS =====

// Fonction pour obtenir l'ID de l'enseignant connecté
function get_teacher_id() {
    if (isset($_SESSION['teacher_id'])) {
        return $_SESSION['teacher_id'];
    }
    return null;
}

// Fonction pour obtenir les informations de l'enseignant
function get_teacher_info($teacher_id) {
    global $conn;
    $sql = "SELECT t.*, u.email, u.telephone, u.photo, u.motDePasse 
            FROM enseignant t 
            JOIN utilisateur u ON t.id = u.id 
            WHERE t.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$teacher_id]);
    return $stmt->fetch();
}

// Fonction pour obtenir les statistiques de l'enseignant
function get_teacher_stats($teacher_id) {
    global $conn;
    
    // Nombre d'étudiants (retourne 0 pour le moment car la relation n'existe pas encore)
    $nb_students = 0;
    
    // Nombre de recommandations
    $sql_recommendations = "SELECT COUNT(*) as total FROM recommandation WHERE idEnseignant = ?";
    $stmt = $conn->prepare($sql_recommendations);
    $stmt->execute([$teacher_id]);
    $result = $stmt->fetch();
    $nb_recommendations = $result['total'] ?? 0;
    
    // Nombre d'événements
    $sql_events = "SELECT COUNT(*) as total FROM evenement WHERE dateEvent >= CURDATE()";
    $result = $conn->query($sql_events);
    $row = $result->fetch();
    $nb_events = $row['total'] ?? 0;
    
    // Score de profil dynamique basé sur l'activité
    $sql_score = "SELECT 
                    -- Profil de base (40%)
                    CASE WHEN t.nom IS NOT NULL AND t.nom != '' THEN 10 ELSE 0 END +
                    CASE WHEN t.prenom IS NOT NULL AND t.prenom != '' THEN 10 ELSE 0 END +
                    CASE WHEN t.specialite IS NOT NULL AND t.specialite != '' THEN 10 ELSE 0 END +
                    CASE WHEN u.photo IS NOT NULL AND u.photo != '' THEN 10 ELSE 0 END +
                    
                    -- Activité d'enseignement (40%)
                    LEAST(GREATEST(
                        (SELECT COUNT(*) FROM recommandation WHERE idEnseignant = t.id AND dateRec >= DATE_SUB(NOW(), INTERVAL 30 DAY)) * 10,
                        0), 20) +
                    LEAST(GREATEST(
                        (SELECT COUNT(*) FROM evenement WHERE dateEvent >= CURDATE()) * 5,
                        0), 20) +
                    
                    -- Engagement (20%)
                    CASE WHEN u.telephone IS NOT NULL AND u.telephone != '' THEN 10 ELSE 0 END +
                    CASE WHEN t.dateInscription IS NOT NULL AND DATEDIFF(NOW(), t.dateInscription) > 30 THEN 10 ELSE 0 END as score
                  FROM enseignant t 
                  JOIN utilisateur u ON t.id = u.id 
                  WHERE t.id = ?";
    $stmt = $conn->prepare($sql_score);
    $stmt->execute([$teacher_id]);
    $score_result = $stmt->fetch();
    $score = $score_result ? $score_result['score'] : 0;
    
    return [
        'students' => $nb_students,
        'recommendations' => $nb_recommendations,
        'events' => $nb_events,
        'score' => $score
    ];
}

// Fonction pour obtenir les étudiants d'un enseignant
function get_teacher_students($teacher_id) {
    global $conn;
    try {
        // Pour le moment, retourne tous les étudiants car la relation n'existe pas encore
        // TODO: Ajouter la colonne idEnseignant dans la table etudiant ou créer une table de liaison
        $sql = "SELECT e.id, e.nom, e.prenom, e.promotion as niveauEtude, e.specialite, e.dateInscription, e.cv, 
                COALESCE(b.score, 0) as badge_score,
                (SELECT COUNT(*) FROM candidature WHERE idEtudiant = e.id) as nb_candidatures
                FROM etudiant e 
                LEFT JOIN badge b ON e.id = b.idEtudiant";
        $result = $conn->query($sql);
        
        $students = [];
        if ($result) {
            foreach ($result->fetchAll() as $row) {
                $students[] = $row;
            }
        }
        return $students;
    } catch (Exception $e) {
        return [];
    }
}

// Fonction pour obtenir les statistiques des recommandations pour un enseignant
function get_recommendations_stats($teacher_id) {
    global $conn;
    
    $stats = [];
    
    // Total des étudiants
    $result = $conn->query("SELECT COUNT(*) as total FROM etudiant");
    $row = $result->fetch();
    $stats['total_students'] = $row['total'] ?? 0;
    
    // Recommandations faites par l'enseignant
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM recommandation WHERE idEnseignant = ?");
    $stmt->execute([$teacher_id]);
    $row = $stmt->fetch();
    $stats['recommendations_made'] = $row['total'] ?? 0;
    
    // Demandes de recommandation reçues
    // La table demande_recommandation n'existe pas encore dans la base de données
    $stats['requests_received'] = 0;
    
    // Total des candidatures
    $result = $conn->query("SELECT COUNT(*) as total FROM candidature");
    $row = $result->fetch();
    $stats['total_applications'] = $row['total'] ?? 0;
    
    // Recommandations récentes (30 derniers jours)
    $stmt = $conn->prepare("SELECT COUNT(*) as recent FROM recommandation WHERE idEnseignant = ? AND dateRec >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute([$teacher_id]);
    $row = $stmt->fetch();
    $stats['recent_recommendations'] = $row['recent'] ?? 0;
    
    return $stats;
}

// Fonction pour obtenir les recommandations détaillées d'un enseignant
function get_teacher_recommendations_details($teacher_id, $limit = 10) {
    global $conn;
    
    $sql = "SELECT r.*, e.nom as student_nom, e.prenom as student_prenom, e.promotion,
                   ent.nomsociete as entreprise_nom
            FROM recommandation r
            LEFT JOIN etudiant e ON r.idEtudiant = e.id
            LEFT JOIN utilisateur u ON r.idEnseignant = u.id
            LEFT JOIN candidature c ON r.idEtudiant = c.idEtudiant
            LEFT JOIN offre o ON c.idOffre = o.id
            LEFT JOIN entreprise ent ON o.idEntreprise = ent.id
            WHERE r.idEnseignant = ?
            ORDER BY r.dateRec DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$teacher_id, $limit]);
    
    $recommendations = [];
    foreach ($stmt->fetchAll() as $row) {
        $recommendations[] = $row;
    }
    
    return $recommendations;
}

// Fonction pour obtenir les recommandations d'un enseignant
function get_teacher_recommendations($teacher_id) {
    global $conn;
    try {
        $sql = "SELECT r.id, r.profil, r.contenu, r.dateRec as dateRecommandation, r.idEnseignant, r.idEntreprise, r.idEtudiant,
                e.nom as etudiant_nom, e.prenom as etudiant_prenom, e.specialite,
                ent.nomSociete as nomsociete
                FROM recommandation r
                LEFT JOIN etudiant e ON r.idEtudiant = e.id
                LEFT JOIN entreprise ent ON r.idEntreprise = ent.id
                WHERE r.idEnseignant = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$teacher_id]);
        $result = $stmt;
        
        $recommendations = [];
        if ($result) {
            foreach ($result->fetchAll() as $row) {
                $recommendations[] = $row;
            }
        }
        return $recommendations;
    } catch (Exception $e) {
        return [];
    }
}

// Fonction pour mettre à jour le badge d'un étudiant
function update_student_badge($student_id, $new_score) {
    global $conn;
    try {
        // Vérifier si l'étudiant a déjà un badge
        $sql = "SELECT id, score FROM badge WHERE idEtudiant = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$student_id]);
        $result = $stmt->fetch();
        
        if ($result) {
            // Mettre à jour le score existant (somme entre l'ancien et le nouveau)
            $old_score = $result['score'];
            $new_total_score = $old_score + $new_score;
            
            $sql = "UPDATE badge SET score = ? WHERE idEtudiant = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$new_total_score, $student_id]);
            
            // Journal de débogage
            error_log("Badge mis à jour: étudiant $student_id, ancien score: $old_score, nouveau score: $new_score, total: $new_total_score");
        } else {
            // Créer un nouveau badge
            $sql = "INSERT INTO badge (score, idEtudiant) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$new_score, $student_id]);
            
            // Journal de débogage
            error_log("Nouveau badge créé: étudiant $student_id, score: $new_score");
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Erreur dans update_student_badge: " . $e->getMessage());
        return false;
    }
}

// ===== FONCTIONS POUR LES ADMINS =====

// Fonction pour obtenir l'ID de l'admin connecté
function get_admin_id() {
    if (isset($_SESSION['admin_id'])) {
        return $_SESSION['admin_id'];
    }
    return null;
}

// Fonction pour obtenir les informations de l'admin
function get_admin_info($admin_id) {
    global $conn;
    $sql = "SELECT a.*, u.email, u.telephone, u.photo, u.motDePasse 
            FROM admin a 
            JOIN utilisateur u ON a.id = u.id 
            WHERE a.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$admin_id]);
    return $stmt->fetch();
}

// Fonction pour obtenir les statistiques globales
function get_admin_stats() {
    global $conn;
    
    // Utiliser la même approche que dashboard.php avec la table utilisateur
    $stats = [
        'students' => 0,
        'teachers' => 0,
        'companies' => 0,
        'offers' => 0,
        'events' => 0,
        'candidatures' => 0
    ];
    
    // Compter les étudiants
    $result = $conn->query("SELECT COUNT(*) as total FROM utilisateur WHERE statut = 'ETUDIANT'");
    if ($result) {
        $row = $result->fetch();
        $stats['students'] = $row['total'] ?? 0;
    }
    
    // Compter les enseignants
    $result = $conn->query("SELECT COUNT(*) as total FROM utilisateur WHERE statut = 'ENSEIGNANT'");
    if ($result) {
        $row = $result->fetch();
        $stats['teachers'] = $row['total'] ?? 0;
    }
    
    // Nombre total d'entreprises (utilisateurs avec statut ENTREPRISE)
    $result = $conn->query("SELECT COUNT(*) as total FROM utilisateur WHERE statut = 'ENTREPRISE'");
    if ($result) {
        $row = $result->fetch();
        $stats['companies'] = $row['total'] ?? 0;
    } else {
                $stats['companies'] = 0;
    }
    
    // Nombre total d'offres
    $result = $conn->query("SELECT COUNT(*) as total FROM offre");
    if ($result) {
        $row = $result->fetch();
        $stats['offers'] = $row['total'] ?? 0;
    }
    
    // Nombre total d'événements
    $result = $conn->query("SELECT COUNT(*) as total FROM evenement");
    if ($result) {
        $row = $result->fetch();
        $stats['events'] = $row['total'] ?? 0;
    }
    
    // Nombre total de candidatures
    $result = $conn->query("SELECT COUNT(*) as total FROM candidature");
    if ($result) {
        $row = $result->fetch();
        $stats['candidatures'] = $row['total'] ?? 0;
    }
    
    return $stats;
}

// Fonction pour envoyer une notification unique
function send_notification($user_id, $type, $message, $reference_id = null, $reference_type = null) {
    global $conn;
    
    try {
        error_log("=== DEBUG SEND_NOTIFICATION ===");
        error_log("Tentative d'envoi de notification:");
        error_log("- User ID: $user_id");
        error_log("- Type: $type");
        error_log("- Message: $message");
        error_log("- Reference ID: $reference_id");
        error_log("- Reference Type: $reference_type");
        
        // Vérifier si une notification similaire existe déjà
        $check_sql = "SELECT id FROM notifications 
                     WHERE user_id = ? AND type = ? AND reference_id = ? AND reference_type = ? 
                     AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        $check_stmt = $conn->prepare($check_sql);
        
        $check_stmt->execute([$user_id, $type, $reference_id, $reference_type]);
        $existing = $check_stmt->fetch();
        
        // Si une notification similaire existe déjà, Ne pas créer de doublon
        if ($existing) {
            error_log("Notification similaire existante, ignorée pour éviter doublon");
            return false;
        }
        
        // Créer la nouvelle notification
        $sql = "INSERT INTO notifications (user_id, type, message, reference_id, reference_type, created_at, is_read) 
                 VALUES (?, ?, ?, ?, ?, NOW(), 0)";
        $stmt = $conn->prepare($sql);
        
        $stmt->execute([$user_id, $type, $message, $reference_id, $reference_type]);
        
        error_log("✅ Notification insérée avec succès en base de données");
        return true;
        
    } catch (Exception $e) {
        error_log("Exception notification: " . $e->getMessage());
        return false;
    }
}

// Fonction pour envoyer une notification d'acceptation d'offre
function send_offer_acceptance_notification($entreprise_id, $etudiant_id, $offre_id) {
    $message = "L'étudiant a accepté votre offre d'emploi";
    return send_notification($entreprise_id, 'OFFER_ACCEPTED', $message, $offre_id, 'offre');
}

// Fonction pour envoyer une notification de nouvelle candidature
function send_new_application_notification($entreprise_id, $etudiant_id, $offre_id) {
    $message = "Nouvelle candidature pour votre offre";
    return send_notification($entreprise_id, 'NEW_APPLICATION', $message, $offre_id, 'offre');
}

// Fonction pour envoyer une notification de nouvelle offre à tous les étudiants
function send_new_offer_notification_to_students($offre_id, $offre_titre, $entreprise_nom) {
    global $conn;
    
    try {
        // Récupérer tous les étudiants
        $stmt = $conn->prepare("SELECT id FROM etudiant");
        $stmt->execute();
        $students = $stmt->fetchAll();
        
        $message = "Nouvelle offre disponible ! $offre_titre chez $entreprise_nom";
        
        // Envoyer la notification à chaque étudiant
        $success_count = 0;
        foreach ($students as $student) {
            $result = send_notification($student['id'], 'NEW_OFFER', $message, $offre_id, 'offre');
            if ($result) {
                $success_count++;
            }
        }
        
        error_log("=== DEBUG NOTIFICATION OFFRE ===");
        error_log("Offre ID: $offre_id");
        error_log("Offre titre: $offre_titre");
        error_log("Entreprise: $entreprise_nom");
        error_log("Nombre d'étudiants: " . count($students));
        error_log("Notifications envoyées avec succès: $success_count");
        error_log("Notifications échouées: " . (count($students) - $success_count));
        
        return $success_count > 0;
        
    } catch (Exception $e) {
        error_log("Erreur envoi notification nouvelle offre: " . $e->getMessage());
        return false;
    }
}

// Fonction pour mettre à jour le statut d'une candidature
function update_candidature_status($candidature_id, $new_status) {
    global $conn;
    
    try {
        // Obtenir l'ancien statut et les informations de la candidature
        $stmt_old = $conn->prepare("SELECT statut, idOffre, idEtudiant FROM candidature WHERE id = ?");
        $stmt_old->execute([$candidature_id]);
        $old_candidature = $stmt_old->fetch();
        
        if (!$old_candidature) {
            return false;
        }
        
        // Mettre à jour le statut
        $stmt_update = $conn->prepare("UPDATE candidature SET statut = ? WHERE id = ?");
        if ($stmt_update->execute([$new_status, $candidature_id])) {
            // Si le statut est ACCEPTE, envoyer une notification à l'entreprise
            if ($new_status === 'ACCEPTE') {
                $message = "Votre candidature a été acceptée par l'étudiant";
                send_notification($old_candidature['idOffre'], 'APPLICATION_ACCEPTED', $message, $candidature_id, 'candidature');
            }
            
            return true;
        } else {
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Erreur mise à jour statut: " . $e->getMessage());
        return false;
    }
}

// Fonction pour obtenir tous les utilisateurs
function get_all_users() {
    global $conn;
    $sql = "SELECT u.id, u.email, u.telephone, u.statut, 
                   CONCAT('Utilisateur ', u.id) as nom
            FROM utilisateur u 
            ORDER BY u.id DESC";
    $result = $conn->query($sql);
    
    $users = [];
    foreach ($result->fetchAll() as $row) {
        $users[] = $row;
    }
    return $users;
}

// Fonction pour obtenir toutes les entreprises
function get_all_companies() {
    global $conn;
    $sql = "SELECT u.id, u.email, u.telephone, u.statut,
                   CONCAT('Entreprise ', u.id) as nomsociete,
                   (SELECT COUNT(*) FROM offre o WHERE o.idEntreprise = u.id) as nb_offres
            FROM utilisateur u 
            WHERE u.statut = 'ENTREPRISE'
            ORDER BY u.id ASC";
    $result = $conn->query($sql);
    
    $companies = [];
    foreach ($result->fetchAll() as $row) {
        $companies[] = $row;
    }
    return $companies;
}

// Fonction pour obtenir toutes les offres
function get_all_offers() {
    global $conn;
    $sql = "SELECT o.*, CONCAT('Entreprise ', o.idEntreprise) as nomsociete, 
                   (SELECT COUNT(*) FROM candidature c WHERE c.idOffre = o.id) as nb_candidatures
            FROM offre o 
            ORDER BY o.id DESC";
    $result = $conn->query($sql);
    
    $offers = [];
    foreach ($result->fetchAll() as $row) {
        $offers[] = $row;
    }
    return $offers;
}
?>
