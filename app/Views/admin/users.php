<?php
require_once __DIR__ . '/../layouts/components.php';
require_once __DIR__ . '/../../config/db_connect.php';

// Obtenir l'ID de l'admin connecté
$admin_id = get_admin_id();

// Si aucun admin n'est connecté, rediriger vers la page de connexion
if (!$admin_id) {
    header("Location: ../../login.php");
    exit();
}

// Obtenir tous les utilisateurs
$users = get_all_users();

// Statistiques utilisateurs
$stats = [
    'total' => count($users),
    'etudiants' => 0,
    'enseignants' => 0,
    'admins' => 0,
    'entreprises' => 0
];

foreach ($users as $user) {
    switch ($user['statut'] ?? '') {
        case 'ETUDIANT': $stats['etudiants']++; break;
        case 'ENSEIGNANT': $stats['enseignants']++; break;
        case 'ADMIN': $stats['admins']++; break;
        case 'ENTREPRISE': $stats['entreprises']++; break;
    }
}

// Obtenir les statistiques détaillées des entreprises
$entreprise_stats = [];
try {
    // Compter le total des entreprises (utilisateurs avec statut ENTREPRISE)
    $result = $conn->query("SELECT COUNT(*) as total FROM utilisateur WHERE statut = 'ENTREPRISE'");
    if ($result) {
        $row = $result->fetch();
        $entreprise_stats['total'] = $row['total'] ?? 0;
    } else {
        $entreprise_stats['total'] = 0;
    }
    
    // Compter les entreprises avec des offres actives
    $result = $conn->query("SELECT COUNT(DISTINCT u.id) as active_companies 
                             FROM utilisateur u 
                             JOIN offre o ON u.id = o.idEntreprise 
                             WHERE u.statut = 'ENTREPRISE'");
    if ($result) {
        $row = $result->fetch();
        $entreprise_stats['active_companies'] = $row['active_companies'] ?? 0;
    } else {
        $entreprise_stats['active_companies'] = 0;
    }
    
    // Obtenir les entreprises les plus actives (avec le plus d'offres)
    $result = $conn->query("SELECT CONCAT('Entreprise ', u.id) as nomsociete, COUNT(o.id) as nb_offres 
                             FROM utilisateur u 
                             LEFT JOIN offre o ON u.id = o.idEntreprise 
                             WHERE u.statut = 'ENTREPRISE'
                             GROUP BY u.id 
                             ORDER BY nb_offers DESC 
                             LIMIT 5");
    $entreprise_stats['top_companies'] = [];
    if ($result) {
        foreach ($result->fetchAll() as $row) {
            $entreprise_stats['top_companies'][] = $row;
        }
    }
    
    // Statistiques des offres par entreprise
    $result = $conn->query("SELECT AVG(offre_count) as avg_offers, 
                             MAX(offre_count) as max_offers,
                             MIN(offre_count) as min_offers
                             FROM (
                                 SELECT COUNT(o.id) as offre_count 
                                 FROM entreprise e 
                                 LEFT JOIN offre o ON e.id = o.idEntreprise 
                                 GROUP BY e.id
                             ) as company_stats");
    if ($result) {
        $offre_stats = $result->fetch();
        $entreprise_stats['avg_offers_per_company'] = round($offre_stats['avg_offers'], 1);
        $entreprise_stats['max_offers_per_company'] = $offre_stats['max_offers'];
        $entreprise_stats['min_offers_per_company'] = $offre_stats['min_offers'];
    }
    
} catch (Exception $e) {
    error_log("Erreur récupération stats entreprises: " . $e->getMessage());
    $entreprise_stats = [
        'total' => 0,
        'active_companies' => 0,
        'top_companies' => [],
        'avg_offers_per_company' => 0,
        'max_offers_per_company' => 0,
        'min_offers_per_company' => 0
    ];
}

// Statistiques dynamiques supplémentaires
$additional_stats = [];
try {
    // Nombre total d'offres
    $result = $conn->query("SELECT COUNT(*) as total_offres FROM offre");
    if ($result) {
        $row = $result->fetch();
        $additional_stats['total_offres'] = $row['total_offres'] ?? 0;
    } else {
        $additional_stats['total_offres'] = 0;
    }
    
    // Nombre de candidatures
    $result = $conn->query("SELECT COUNT(*) as total_candidatures FROM candidature");
    if ($result) {
        $row = $result->fetch();
        $additional_stats['total_candidatures'] = $row['total_candidatures'] ?? 0;
    } else {
        $additional_stats['total_candidatures'] = 0;
    }
    
    // Nombre d'événements
    $result = $conn->query("SELECT COUNT(*) as total_events FROM evenement");
    if ($result) {
        $row = $result->fetch();
        $additional_stats['total_events'] = $row['total_events'] ?? 0;
    } else {
        $additional_stats['total_events'] = 0;
    }
    
    // Répartition des candidatures par statut
    $result = $conn->query("SELECT statut, COUNT(*) as count FROM candidature GROUP BY statut");
    if ($result) {
        $additional_stats['candidature_breakdown'] = [];
        foreach ($result->fetchAll() as $row) {
            $additional_stats['candidature_breakdown'][] = $row;
        }
    } else {
        $additional_stats['candidature_breakdown'] = [];
    }
    
    // Évolution des inscriptions par mois
    $result = $conn->query("SELECT DATE_FORMAT(dateInscription, '%Y-%m') as month, COUNT(*) as count 
                            FROM utilisateur 
                            WHERE dateInscription IS NOT NULL 
                            GROUP BY DATE_FORMAT(dateInscription, '%Y-%m') 
                            ORDER BY month DESC 
                            LIMIT 6");
    if ($result) {
        $additional_stats['monthly_registrations'] = [];
        foreach ($result->fetchAll() as $row) {
            $additional_stats['monthly_registrations'][] = $row;
        }
    } else {
        $additional_stats['monthly_registrations'] = [];
    }
    
} catch (Exception $e) {
    error_log("Erreur récupération stats supplémentaires: " . $e->getMessage());
    $additional_stats = [
        'total_offres' => 0,
        'total_candidatures' => 0,
        'total_events' => 0,
        'candidature_breakdown' => [],
        'monthly_registrations' => []
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utilisateurs - ESEN Connect</title>
    <meta name="description" content="Gérez les utilisateurs">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../public/assets/css/main.css">
    <link rel="stylesheet" href="../../public/assets/css/admin-navbar.css">
    <link rel="stylesheet" href="../../public/assets/css/admin-dashboard.css">
    <link rel="icon" href="../../public/assets/images/1.jpeg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Enterprise Statistics Styles */
        .enterprise-stats-section {
            margin-top: 3rem;
        }
        
        .top-companies-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .company-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        
        .company-item:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .company-rank {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.1rem;
            margin-right: 1rem;
        }
        
        .company-info {
            flex: 1;
        }
        
        .company-info h4 {
            color: #ffffff;
            margin: 0 0 0.25rem 0;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .company-info p {
            color: #94a3b8;
            margin: 0;
            font-size: 0.9rem;
        }
        
        .company-offers {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .offer-count {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        /* General Statistics Styles */
        .general-stats-section {
            margin-top: 3rem;
        }
        
        .candidature-stats {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .candidature-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .candidature-item:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-1px);
        }
        
        .candidature-status {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .candidature-status.en_attente {
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
        }
        
        .candidature-status.accepte {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }
        
        .candidature-status.refuse {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
        
        .candidature-count {
            font-size: 1.2rem;
            font-weight: bold;
            color: #ffffff;
        }
        
        .monthly-registrations {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .month-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .month-item:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-1px);
        }
        
        .month-date {
            color: #94a3b8;
            font-size: 0.9rem;
        }
        
        .month-count {
            color: #ffffff;
            font-weight: 600;
        }
        
        /* Dark Mode Styles */
        body.dark .enterprise-stats-section,
        body.dark .general-stats-section {
            background: rgba(0, 0, 0, 0.3);
        }
        
        body.dark .company-item {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        body.dark .company-item:hover {
            background: rgba(0, 0, 0, 0.5);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }
        
        body.dark .company-info h4 {
            color: #f1f5f9;
        }
        
        body.dark .company-info p {
            color: #94a3b8;
        }
        
        body.dark .offer-count {
            background: rgba(16, 185, 129, 0.3);
            color: #10b981;
        }
        
        body.dark .candidature-item {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        body.dark .candidature-item:hover {
            background: rgba(0, 0, 0, 0.5);
        }
        
        body.dark .candidature-status.en_attente {
            background: rgba(245, 158, 11, 0.3);
            color: #f59e0b;
        }
        
        body.dark .candidature-status.accepte {
            background: rgba(16, 185, 129, 0.3);
            color: #10b981;
        }
        
        body.dark .candidature-status.refuse {
            background: rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }
        
        body.dark .candidature-count {
            color: #f1f5f9;
        }
        
        body.dark .month-item {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        body.dark .month-item:hover {
            background: rgba(0, 0, 0, 0.5);
        }
        
        body.dark .month-date {
            color: #94a3b8;
        }
        
        body.dark .month-count {
            color: #f1f5f9;
        }
        
        body.dark .company-rank {
            background: linear-gradient(135deg, #4c1d95 0%, #5b21b6 100%);
        }
        
        /* Dark mode for stat cards */
        body.dark .stat-card {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        body.dark .stat-card:hover {
            background: rgba(0, 0, 0, 0.5);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }
        
        body.dark .dashboard-card {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        body.dark .dashboard-card h3 {
            color: #f1f5f9;
        }
        
        body.dark .dashboard-header h2 {
            color: #f1f5f9;
        }
        
        body.dark .dashboard-header p {
            color: #94a3b8;
        }
        
        /* Dark mode for user items */
        body.dark .user-item {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        body.dark .user-item:hover {
            background: rgba(0, 0, 0, 0.5);
        }
        
        body.dark .user-info h4 {
            color: #f1f5f9;
        }
        
        body.dark .user-info p {
            color: #94a3b8;
        }
        
        /* Dark mode for search and filter */
        body.dark .search-input {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #f1f5f9;
        }
        
        body.dark .search-input::placeholder {
            color: #94a3b8;
        }
        
        body.dark .filter-btn {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #f1f5f9;
        }
        
        body.dark .filter-btn:hover {
            background: rgba(0, 0, 0, 0.5);
        }
        
        body.dark .filter-btn.active {
            background: rgba(99, 102, 241, 0.3);
            border-color: #6366f1;
            color: #818cf8;
        }
        
        /* Enhanced Header Styles */
        .dashboard-header {
            position: relative;
            margin-bottom: 2rem;
        }
        
        .dashboard-header h2 {
            position: relative;
            display: inline-block;
            font-size: 2rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
        }
        
        .dashboard-header h2::before {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 2px;
            animation: slideIn 0.6s ease-out;
        }
        
        .dashboard-header p {
            color: #94a3b8;
            font-size: 1.1rem;
            font-weight: 500;
            margin-top: 1rem;
            opacity: 0.9;
        }
        
        @keyframes slideIn {
            from {
                width: 0;
                opacity: 0;
            }
            to {
                width: 60px;
                opacity: 1;
            }
        }
        
        /* Dark mode enhanced header */
        body.dark .dashboard-header h2 {
            background: linear-gradient(135deg, #818cf8 0%, #c084fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 2px 10px rgba(129, 140, 248, 0.3);
        }
        
        body.dark .dashboard-header h2::before {
            background: linear-gradient(135deg, #818cf8 0%, #c084fc 100%);
        }
        
        body.dark .dashboard-header p {
            color: #cbd5e1;
        }
        
        /* Special styling for "Statistiques Générales" */
        .general-stats-section .dashboard-header h2 {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 2px 10px rgba(16, 185, 129, 0.3);
        }
        
        .general-stats-section .dashboard-header h2::before {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            width: 80px;
        }
        
        body.dark .general-stats-section .dashboard-header h2 {
            background: linear-gradient(135deg, #34d399 0%, #10b981 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 2px 10px rgba(52, 211, 153, 0.3);
        }
        
        body.dark .general-stats-section .dashboard-header h2::before {
            background: linear-gradient(135deg, #34d399 0%, #10b981 100%);
        }
        
        /* Special styling for "Statistiques des Entreprises" */
        .enterprise-stats-section .dashboard-header h2 {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 2px 10px rgba(245, 158, 11, 0.3);
        }
        
        .enterprise-stats-section .dashboard-header h2::before {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            width: 80px;
        }
        
        body.dark .enterprise-stats-section .dashboard-header h2 {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 2px 10px rgba(251, 191, 36, 0.3);
        }
        
        body.dark .enterprise-stats-section .dashboard-header h2::before {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
        }
        
        /* Special styling for "Répartition des candidatures" */
        .candidature-title {
            position: relative;
            display: inline-block;
            font-size: 1.5rem;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
        }
        
        .candidature-title::before {
            content: '';
            position: absolute;
            bottom: -6px;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border-radius: 2px;
            animation: slideIn 0.6s ease-out;
        }
        
        /* Dark mode for "Répartition des candidatures" */
        body.dark .candidature-title {
            background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 2px 8px rgba(248, 113, 113, 0.3);
        }
        
        body.dark .candidature-title::before {
            background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
        }
        
        /* Special styling for "Inscriptions mensuelles récentes" */
        .monthly-title {
            position: relative;
            display: inline-block;
            font-size: 1.5rem;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }
        
        .monthly-title::before {
            content: '';
            position: absolute;
            bottom: -6px;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            border-radius: 2px;
            animation: slideIn 0.6s ease-out;
        }
        
        body.dark .monthly-title {
            background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 2px 8px rgba(96, 165, 250, 0.3);
        }
        
        body.dark .monthly-title::before {
            background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
        }
        
        /* Special styling for "Top 5 des entreprises les plus actives" */
        .top-companies-title {
            position: relative;
            display: inline-block;
            font-size: 1.5rem;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 2px 8px rgba(168, 85, 247, 0.3);
        }
        
        .top-companies-title::before {
            content: '';
            position: absolute;
            bottom: -6px;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%);
            border-radius: 2px;
            animation: slideIn 0.6s ease-out;
        }
        
        body.dark .top-companies-title {
            background: linear-gradient(135deg, #c084fc 0%, #a855f7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 2px 8px rgba(192, 132, 252, 0.3);
        }
        
        body.dark .top-companies-title::before {
            background: linear-gradient(135deg, #c084fc 0%, #a855f7 100%);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .company-item {
                flex-direction: column;
                text-align: center;
                gap: 0.5rem;
            }
            
            .company-rank {
                margin-right: 0;
                margin-bottom: 0.5rem;
            }
            
            .candidature-item,
            .month-item {
                flex-direction: column;
                text-align: center;
                gap: 0.5rem;
            }
        }
    </style>
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
                    navbarShowBtn.classList.add('visible');
                } else {
                    navbarShowBtn.classList.remove('visible');
                }
            }

            if (verticalNavbar && navbarShowBtn) {
                updateNavbarShowBtn();
            }

            // Custom Cursor (identique au student/teacher/entreprise)
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

                const interactiveElements = document.querySelectorAll('a, button, input, .btn, .stat-card, .dashboard-card, .user-item, .nav-item, .search-input, .filter-btn');
                interactiveElements.forEach(el => {
                    el.addEventListener('mouseenter', () => cursor.classList.add('hover'));
                    el.addEventListener('mouseleave', () => cursor.classList.remove('hover'));
                });

                document.addEventListener('mousedown', () => cursor.classList.add('clicking'));
                document.addEventListener('mouseup', () => cursor.classList.remove('clicking'));
            }

            // Search & Filter functionality
            const searchInput = document.getElementById('searchUsers');
            const filterBtns = document.querySelectorAll('.filter-btn');
            const userItems = document.querySelectorAll('.user-item');

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const query = this.value.toLowerCase();
                    const activeFilter = document.querySelector('.filter-btn.active');
                    const filterRole = activeFilter ? activeFilter.dataset.role : 'all';
                    
                    userItems.forEach(item => {
                        const name = item.dataset.name.toLowerCase();
                        const email = item.dataset.email.toLowerCase();
                        const role = item.dataset.role;
                        const matchesSearch = name.includes(query) || email.includes(query);
                        const matchesFilter = filterRole === 'all' || role === filterRole;
                        item.style.display = matchesSearch && matchesFilter ? '' : 'none';
                    });
                });
            }

            filterBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    filterBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    const filterRole = this.dataset.role;
                    const query = searchInput ? searchInput.value.toLowerCase() : '';
                    
                    userItems.forEach(item => {
                        const name = item.dataset.name.toLowerCase();
                        const email = item.dataset.email.toLowerCase();
                        const role = item.dataset.role;
                        const matchesSearch = name.includes(query) || email.includes(query);
                        const matchesFilter = filterRole === 'all' || role === filterRole;
                        item.style.display = matchesSearch && matchesFilter ? '' : 'none';
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

    <?php include 'admin-navbar.php'; ?>

    <main class="main-content-wrapper">
        <div class="dashboard-container">
            <div class="dashboard-header">
                <h1>Utilisateurs</h1>
                <p>Gérez tous les utilisateurs de la plateforme</p>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon blue"><i class="fas fa-users"></i></div>
                    <div class="value"><?php echo $stats['total']; ?></div>
                    <div class="label">Total utilisateurs</div>
                </div>
                <div class="stat-card">
                    <div class="icon green"><i class="fas fa-graduation-cap"></i></div>
                    <div class="value"><?php echo $stats['etudiants']; ?></div>
                    <div class="label">Étudiants</div>
                </div>
                <div class="stat-card">
                    <div class="icon orange"><i class="fas fa-chalkboard-teacher"></i></div>
                    <div class="value"><?php echo $stats['enseignants']; ?></div>
                    <div class="label">Enseignants</div>
                </div>
                <div class="stat-card">
                    <div class="icon red"><i class="fas fa-building"></i></div>
                    <div class="value"><?php echo $stats['entreprises']; ?></div>
                    <div class="label">Entreprises</div>
                </div>
                <div class="stat-card">
                    <div class="icon purple"><i class="fas fa-shield-alt"></i></div>
                    <div class="value"><?php echo $stats['admins']; ?></div>
                    <div class="label">Administrateurs</div>
                </div>
            </div>

            <!-- General Statistics Section -->
            <div class="general-stats-section">
                <div class="dashboard-header">
                    <h2>Statistiques Générales</h2>
                    <p>Aperçu complet de l'activité sur la plateforme</p>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="icon blue"><i class="fas fa-briefcase"></i></div>
                        <div class="value"><?php echo $additional_stats['total_offres']; ?></div>
                        <div class="label">Total offres</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon green"><i class="fas fa-file-alt"></i></div>
                        <div class="value"><?php echo $additional_stats['total_candidatures']; ?></div>
                        <div class="label">Total candidatures</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon orange"><i class="fas fa-calendar-alt"></i></div>
                        <div class="value"><?php echo $additional_stats['total_events']; ?></div>
                        <div class="label">Total événements</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon purple"><i class="fas fa-chart-line"></i></div>
                        <div class="value"><?php echo count($additional_stats['monthly_registrations']); ?></div>
                        <div class="label">Mois avec inscriptions</div>
                    </div>
                </div>

                <!-- Candidature Breakdown -->
                <?php if (!empty($additional_stats['candidature_breakdown'])): ?>
                <div class="dashboard-card">
                    <h3 class="candidature-title">Répartition des candidatures</h3>
                    <div class="candidature-stats">
                        <?php foreach ($additional_stats['candidature_breakdown'] as $candidature): ?>
                        <div class="candidature-item">
                            <div class="candidature-status <?php echo strtolower($candidature['statut']); ?>">
                                <?php echo htmlspecialchars($candidature['statut']); ?>
                            </div>
                            <div class="candidature-count"><?php echo $candidature['count']; ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Monthly Registrations -->
                <?php if (!empty($additional_stats['monthly_registrations'])): ?>
                <div class="dashboard-card">
                    <h3 class="monthly-title">Inscriptions mensuelles récentes</h3>
                    <div class="monthly-registrations">
                        <?php foreach ($additional_stats['monthly_registrations'] as $month): ?>
                        <div class="month-item">
                            <div class="month-date"><?php echo htmlspecialchars($month['month']); ?></div>
                            <div class="month-count"><?php echo $month['count']; ?> inscription(s)</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Enterprise Statistics Section -->
            <div class="enterprise-stats-section">
                <div class="dashboard-header">
                    <h2>Statistiques des Entreprises</h2>
                    <p>Aperçu détaillé des entreprises et de leurs activités</p>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="icon red"><i class="fas fa-building"></i></div>
                        <div class="value"><?php echo $entreprise_stats['total']; ?></div>
                        <div class="label">Total entreprises</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon green"><i class="fas fa-briefcase"></i></div>
                        <div class="value"><?php echo $entreprise_stats['active_companies']; ?></div>
                        <div class="label">Entreprises actives</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon blue"><i class="fas fa-chart-bar"></i></div>
                        <div class="value"><?php echo $entreprise_stats['avg_offers_per_company']; ?></div>
                        <div class="label">Offres moyennes/entreprise</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon orange"><i class="fas fa-trophy"></i></div>
                        <div class="value"><?php echo $entreprise_stats['max_offers_per_company']; ?></div>
                        <div class="label">Max offres/entreprise</div>
                    </div>
                </div>

                <!-- Top Companies -->
                <?php if (!empty($entreprise_stats['top_companies'])): ?>
                <div class="dashboard-card">
                    <h3 class="top-companies-title">Top 5 des entreprises les plus actives</h3>
                    <div class="top-companies-list">
                        <?php foreach ($entreprise_stats['top_companies'] as $index => $company): ?>
                        <div class="company-item">
                            <div class="company-rank"><?php echo $index + 1; ?></div>
                            <div class="company-info">
                                <h4><?php echo htmlspecialchars($company['nomsociete']); ?></h4>
                                <p><?php echo $company['nb_offres']; ?> offre(s) publiée(s)</p>
                            </div>
                            <div class="company-offers">
                                <span class="offer-count"><?php echo $company['nb_offres']; ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Search & Filter Bar -->
            <div class="dashboard-card" style="margin-bottom: 2rem;">
                <div class="search-filter-bar">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchUsers" class="search-input" placeholder="Rechercher un utilisateur...">
                    </div>
                    <div class="filter-group">
                        <button class="filter-btn active" data-role="all">
                            <i class="fas fa-globe"></i> Tous
                        </button>
                        <button class="filter-btn" data-role="ETUDIANT">
                            <i class="fas fa-graduation-cap"></i> Étudiants
                        </button>
                        <button class="filter-btn" data-role="ENSEIGNANT">
                            <i class="fas fa-chalkboard-teacher"></i> Enseignants
                        </button>
                        <button class="filter-btn" data-role="ENTREPRISE">
                            <i class="fas fa-building"></i> Entreprises
                        </button>
                        <button class="filter-btn" data-role="ADMIN">
                            <i class="fas fa-shield-alt"></i> Admins
                        </button>
                    </div>
                </div>
            </div>

            <!-- Users List -->
            <div class="dashboard-card">
                <h3>Liste des utilisateurs</h3>
                <div class="users-list">
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                            <div class="user-item" 
                                 data-name="<?php echo htmlspecialchars($user['nom'] ?? ''); ?>" 
                                 data-email="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" 
                                 data-role="<?php echo htmlspecialchars($user['statut'] ?? ''); ?>">
                                <div class="user-info">
                                    <h4><?php echo htmlspecialchars($user['nom'] ?? 'N/A'); ?></h4>
                                    <p><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></p>
                                </div>
                                <div class="user-role <?php echo strtolower($user['statut'] ?? ''); ?>">
                                    <?php 
                                    switch($user['statut'] ?? '') {
                                        case 'ETUDIANT': echo '<i class="fas fa-graduation-cap"></i> Étudiant'; break;
                                        case 'ENSEIGNANT': echo '<i class="fas fa-chalkboard-teacher"></i> Enseignant'; break;
                                        case 'ENTREPRISE': echo '<i class="fas fa-building"></i> Entreprise'; break;
                                        case 'ADMIN': echo '<i class="fas fa-shield-alt"></i> Admin'; break;
                                        default: echo htmlspecialchars($user['statut'] ?? 'N/A'); 
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: #e2e8f0; padding: 2rem;">Aucun utilisateur trouvé</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../public/assets/js/main.js"></script>
</body>
</html>
