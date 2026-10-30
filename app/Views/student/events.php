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

// Obtenir tous les événements à venir
$events_avenir = get_evenements_avenir();

// Obtenir les événements expirés
$events_expires = [];
try {
    $stmt = $conn->prepare("SELECT * FROM evenement WHERE dateEvent < CURDATE() ORDER BY dateEvent DESC");
    $stmt->execute();
    foreach ($stmt->fetchAll() as $row) {
        $events_expires[] = $row;
    }
} catch (Exception $e) {
    $events_expires = [];
}

// Fusionner tous les événements pour la filtration sans doublons
$events = [];
$events_indexed = [];

// Ajouter les événements à venir
foreach ($events_avenir as $event) {
    $events_indexed[$event['id']] = $event;
}

// Ajouter les événements expirés (pas de doublons possible car dates différentes)
foreach ($events_expires as $event) {
    $events_indexed[$event['id']] = $event;
}

// Convertir en tableau simple
$events = array_values($events_indexed);

// Récupérer les informations de l'étudiant
$etudiant_info = [];
try {
    $stmt = $conn->prepare("SELECT * FROM etudiant WHERE id = ?");
    $stmt->execute([$etudiant_id]);
    if ($row = $stmt->fetch()) {
        $etudiant_info = $row;
    }
} catch (Exception $e) {
    $etudiant_info = [];
}

// Récupérer les participations de l'étudiant
$mes_participations = [];
try {
    $stmt = $conn->prepare("SELECT idEvenement FROM participation WHERE idEtudiant = ?");
    $stmt->execute([$etudiant_id]);
    foreach ($stmt->fetchAll() as $row) {
        $mes_participations[] = $row['idEvenement'];
    }
} catch (Exception $e) {
    $mes_participations = [];
}

// Traitement de la participation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['participer_event'])) {
    $event_id = $_POST['event_id'] ?? 0;
    $role = $_POST['role'] ?? 'participant';
    $statut = $_POST['statut'] ?? 'EN_ATTENTE';
    
    // Vérifier si l'événement est expiré
    $stmt_check = $conn->prepare("SELECT dateEvent FROM evenement WHERE id = ?");
    $stmt_check->execute([$event_id]);
    if ($row = $stmt_check->fetch()) {
        $event_date = $row['dateEvent'];
        if ($event_date < date('Y-m-d')) {
            $error_message = "Cet événement est expiré, vous ne pouvez plus y participer.";
        } else {
            // Vérifier si l'étudiant a déjà participé
            if (!in_array($event_id, $mes_participations)) {
                $stmt = $conn->prepare("INSERT INTO participation (idEtudiant, idEvenement, dateInscri, role, statut) VALUES (?, ?, NOW(), ?, ?)");
                if ($stmt->execute([$etudiant_id, $event_id, $role, $statut])) {
                    // Ajouter 10 points automatiquement avec consolidation
                    ajouter_points_etudiant_consolidation($etudiant_id, 10, "Participation à l'événement");
                    $success_message = "Participation enregistrée avec succès !";
                    $mes_participations[] = $event_id;
                } else {
                    $error_message = "Erreur lors de l'inscription à l'événement: " . $stmt->error;
                }
            } else {
                $error_message = "Vous participez déjà à cet événement.";
            }
        }
    } else {
        $error_message = "Événement introuvable.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Événements - ESEN NET</title>
    <meta name="description" content="Participez aux événements ESEN">
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

        /* ===== EVENTS PAGE STYLES ===== */
        .events-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .events-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        /* Expired Events Styles */
        .event-card-advanced.expired {
            opacity: 0.7;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .event-card-advanced.expired:hover {
            opacity: 0.8;
            border-color: rgba(239, 68, 68, 0.3);
        }

        .event-card-banner-overlay.expired {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.3), rgba(239, 68, 68, 0.1));
        }

        .event-card-banner-placeholder.expired {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(239, 68, 68, 0.1));
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .event-card-status.expired {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .event-card-badge.expired {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        /* Dark mode support for expired events */
        @media (prefers-color-scheme: dark) {
            .event-card-advanced.expired {
                border-color: rgba(239, 68, 68, 0.3);
            }
            
            .event-card-advanced.expired:hover {
                border-color: rgba(239, 68, 68, 0.4);
            }
            
            .event-card-status.expired {
                background: rgba(239, 68, 68, 0.15);
                color: #f87171;
                border-color: rgba(239, 68, 68, 0.3);
            }
            
            .event-card-badge.expired {
                background: rgba(239, 68, 68, 0.15);
                color: #f87171;
                border-color: rgba(239, 68, 68, 0.3);
            }
        }

        .events-header h1 {
            color: #2c3e50;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .events-header p {
            color: #6c757d;
            font-size: 1.1rem;
        }

        /* Stats Row */
        .events-stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .events-stat-chip {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .events-stat-chip:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .events-stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .events-stat-icon.blue { background: linear-gradient(135deg, #667eea, #764ba2); }
        .events-stat-icon.green { background: linear-gradient(135deg, #10b981, #059669); }
        .events-stat-icon.red { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .events-stat-icon.purple { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }

        .events-stat-data {
            display: flex;
            flex-direction: column;
        }

        .events-stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            line-height: 1;
        }

        .events-stat-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }

        /* Alerts */
        .events-alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .events-alert.success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #10b981;
        }

        .events-alert.error {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05));
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }

        /* Toolbar */
        .events-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .events-search-box {
            position: relative;
            flex: 1;
            max-width: 400px;
        }

        .events-search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .events-search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .events-search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .events-filter-tabs {
            display: flex;
            gap: 0.5rem;
            background: #f8f9fa;
            padding: 0.25rem;
            border-radius: 12px;
        }

        .events-filter-tab {
            padding: 0.75rem 1.25rem;
            border: none;
            background: transparent;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            font-weight: 500;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .events-filter-tab.active {
            background: white;
            color: #667eea;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .events-filter-tab:hover:not(.active) {
            background: rgba(255, 255, 255, 0.5);
        }

        /* Grid */
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }

        /* Event Cards */
        .event-card-advanced {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            animation: eventCardIn 0.5s ease forwards;
            cursor: pointer;
        }

        .event-card-advanced:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        @keyframes eventCardIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .event-card-banner {
            position: relative;
            height: 180px;
            overflow: hidden;
        }

        .event-card-banner img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .event-card-banner-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.1), rgba(0,0,0,0.3));
        }

        .event-card-banner-placeholder {
            height: 180px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .event-card-banner-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .event-card-body {
            padding: 1.5rem;
        }

        .event-card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .event-card-type {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }

        .event-card-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .event-card-status.upcoming {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .event-card-status.participated {
            background: rgba(139, 92, 246, 0.1);
            color: #8b5cf6;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
        }

        .event-card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 1rem;
            line-height: 1.3;
        }

        .event-card-date,
        .event-card-location {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
        }

        .event-card-description {
            margin-bottom: 1.5rem;
        }

        .event-card-description p {
            color: #6c757d;
            font-size: 0.9rem;
            line-height: 1.4;
            margin: 0;
        }

        .event-card-actions {
            display: flex;
            justify-content: flex-end;
        }

        .event-card-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .event-card-badge.available {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .event-card-badge.participated {
            background: rgba(139, 92, 246, 0.1);
            color: #8b5cf6;
            border: 1px solid rgba(139, 92, 246, 0.2);
        }

        /* Empty State */
        .events-empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }

        .events-empty-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            margin: 0 auto 1.5rem;
        }

        .events-empty-state h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }

        .events-empty-state p {
            font-size: 1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .events-stats-row {
                grid-template-columns: 1fr;
            }

            .events-toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .events-search-box {
                max-width: none;
            }

            .events-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ===== DARK MODE ===== */
        body.dark .events-header h1 {
            background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        body.dark .events-header p {
            color: #9ca3af;
        }

        body.dark .events-stat-chip {
            background: rgba(31, 41, 55, 0.8);
            border: 1px solid rgba(75, 85, 99, 0.3);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        body.dark .events-stat-chip:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
        }

        body.dark .events-stat-number {
            color: #f3f4f6;
        }

        body.dark .events-stat-label {
            color: #9ca3af;
        }

        body.dark .events-alert.success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(16, 185, 129, 0.05));
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        body.dark .events-alert.error {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(239, 68, 68, 0.05));
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        body.dark .events-search-input {
            background: rgba(31, 41, 55, 0.8);
            border: 2px solid rgba(75, 85, 99, 0.3);
            color: #f3f4f6;
        }

        body.dark .events-search-input:focus {
            border-color: #8b5cf6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }

        body.dark .events-filter-tabs {
            background: rgba(31, 41, 55, 0.8);
            border: 1px solid rgba(75, 85, 99, 0.3);
        }

        body.dark .events-filter-tab {
            color: #9ca3af;
        }

        body.dark .events-filter-tab.active {
            background: rgba(17, 24, 39, 0.9);
            color: #8b5cf6;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        body.dark .events-filter-tab:hover:not(.active) {
            background: rgba(55, 65, 81, 0.5);
        }

        body.dark .event-card-advanced {
            background: rgba(31, 41, 55, 0.9);
            border: 1px solid rgba(75, 85, 99, 0.3);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        body.dark .event-card-advanced:hover {
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
        }

        body.dark .event-card-banner-placeholder {
            background: linear-gradient(135deg, #8b5cf6, #ec4899);
        }

        body.dark .event-card-title {
            color: #f3f4f6;
        }

        body.dark .event-card-date,
        body.dark .event-card-location,
        body.dark .event-card-description p {
            color: #9ca3af;
        }

        body.dark .event-card-type { 
            background: rgba(139, 92, 246, 0.2); 
            color: #a78bfa; 
        }

        body.dark .event-card-status.upcoming {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
        }

        body.dark .event-card-status.participated {
            background: rgba(139, 92, 246, 0.2);
            color: #a78bfa;
        }

        body.dark .event-card-badge.available {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        body.dark .event-card-badge.participated {
            background: rgba(139, 92, 246, 0.2);
            color: #a78bfa;
            border: 1px solid rgba(139, 92, 246, 0.3);
        }

        body.dark .events-empty-state {
            color: #9ca3af;
        }

        body.dark .events-empty-icon {
            background: linear-gradient(135deg, #8b5cf6, #ec4899);
        }

        body.dark .events-empty-state h3 {
            color: #f3f4f6;
        }

        body.dark .events-empty-state p {
            color: #9ca3af;
        }

        /* Dark mode for modals */
        body.dark .modal-overlay {
            background: rgba(0, 0, 0, 0.8);
        }

        body.dark .modal-container {
            background: rgba(31, 41, 55, 0.95);
            border: 1px solid rgba(75, 85, 99, 0.3);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        body.dark .modal-header {
            background: rgba(17, 24, 39, 0.9);
            border-bottom: 1px solid rgba(75, 85, 99, 0.3);
        }

        body.dark .modal-header h3 {
            color: #f3f4f6;
        }

        body.dark .modal-close {
            color: #9ca3af;
            background: rgba(75, 85, 99, 0.2);
        }

        body.dark .modal-close:hover {
            color: #f3f4f6;
            background: rgba(239, 68, 68, 0.2);
        }

        body.dark .modal-body {
            background: rgba(31, 41, 55, 0.95);
            color: #f3f4f6;
        }

        body.dark .form-group label {
            color: #e5e7eb;
        }

        body.dark .form-group input,
        body.dark .form-group textarea {
            background: rgba(17, 24, 39, 0.9);
            border: 1px solid rgba(75, 85, 99, 0.3);
            color: #f3f4f6;
        }

        body.dark .form-group input:focus,
        body.dark .form-group textarea:focus {
            border-color: #8b5cf6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }

        body.dark .btn.primary {
            background: linear-gradient(135deg, #8b5cf6, #ec4899);
        }

        body.dark .btn.secondary {
            background: rgba(75, 85, 99, 0.5);
            color: #9ca3af;
            border: 1px solid rgba(75, 85, 99, 0.3);
        }

        body.dark .btn.danger {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 2rem;
        }

        .modal-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-height: 90vh;
            overflow-y: auto;
            animation: modalFadeIn 0.3s ease;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
            border-radius: 12px 12px 0 0;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.25rem;
            color: #6b7280;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .modal-close:hover {
            background: #f3f4f6;
            color: #374151;
        }

        .modal-body {
            padding: 1.5rem;
            background: white;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.875rem;
            transition: border-color 0.2s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn.primary {
            background: #3b82f6;
            color: white;
        }

        .btn.primary:hover {
            background: #2563eb;
        }

        .btn.secondary {
            background: #6b7280;
            color: white;
        }

        .btn.secondary:hover {
            background: #4b5563;
        }

        .btn.danger {
            background: #ef4444;
            color: white;
        }

        .btn.danger:hover {
            background: #dc2626;
        }
    </style>
</head>
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
            
            // Filter tabs
            const filterTabs = document.querySelectorAll('.events-filter-tab');
            const eventCards = document.querySelectorAll('.event-card-advanced');
            filterTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    filterTabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    const filter = this.dataset.filter;
                    eventCards.forEach(card => {
                        if (filter === 'all' || card.dataset.status === filter) {
                            card.style.display = '';
                            card.style.animation = 'eventCardIn 0.5s ease forwards';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            });

            // Search
            const searchInput = document.querySelector('.events-search-input');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const query = this.value.toLowerCase();
                    eventCards.forEach(card => {
                        const title = card.querySelector('.event-card-title').textContent.toLowerCase();
                        const location = card.querySelector('.event-card-location').textContent.toLowerCase();
                        const description = card.querySelector('.event-card-description p').textContent.toLowerCase();
                        if (title.includes(query) || location.includes(query) || description.includes(query)) {
                            card.style.display = '';
                            card.style.animation = 'eventCardIn 0.5s ease forwards';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            }
        });
    </script>

    <!-- Chatbot Styles -->
    <style>
        /* Chatbot Widget */
        .chatbot-widget {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 10000;
        }

        .chatbot-toggle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chatbot-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 30px rgba(102, 126, 234, 0.4);
        }

        .chatbot-toggle.active {
            transform: rotate(45deg);
        }

        .chatbot-container {
            position: absolute;
            bottom: 80px;
            right: 0;
            width: 380px;
            height: 500px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            opacity: 0;
            visibility: hidden;
            transform: translateY(20px);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .chatbot-container.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .chatbot-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px 20px;
            border-radius: 12px 12px 0 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .chatbot-avatar {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .chatbot-title {
            flex: 1;
        }

        .chatbot-title h4 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }

        .chatbot-title .status {
            font-size: 12px;
            opacity: 0.8;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .chatbot-title .status::before {
            content: '';
            width: 8px;
            height: 8px;
            background: #4ade80;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .chatbot-close {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            transition: background 0.3s ease;
        }

        .chatbot-close:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .chatbot-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f8fafc;
        }

        .message {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
            animation: messageSlide 0.3s ease;
        }

        @keyframes messageSlide {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.bot {
            flex-direction: row;
        }

        .message.user {
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }

        .message.bot .message-avatar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .message.user .message-avatar {
            background: #e2e8f0;
            color: #64748b;
        }

        .message-content {
            flex: 1;
            max-width: 70%;
        }

        .message-content p {
            margin: 0;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
            line-height: 1.4;
        }

        .message.bot .message-content p {
            background: white;
            color: #1e293b;
            border-bottom-left-radius: 4px;
        }

        .message.user .message-content p {
            background: #667eea;
            color: white;
            border-bottom-right-radius: 4px;
        }

        .message-time {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 4px;
            text-align: right;
        }

        .message.user .message-time {
            text-align: left;
        }

        .chatbot-input {
            padding: 16px 20px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 12px;
            background: white;
            border-radius: 0 0 12px 12px;
        }

        .chatbot-input input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.3s ease;
        }

        .chatbot-input input:focus {
            border-color: #667eea;
        }

        .chatbot-input button {
            width: 44px;
            height: 44px;
            border: none;
            border-radius: 8px;
            background: #667eea;
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chatbot-input button:hover {
            background: #5a67d8;
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            .chatbot-container {
                width: calc(100vw - 40px);
                right: -10px;
            }
            
            .chatbot-toggle {
                width: 50px;
                height: 50px;
                font-size: 20px;
            }
        }

        /* Dark Mode Support */
        body.dark .chatbot-container {
            background: #1f2937;
            color: #f3f4f6;
        }

        body.dark .chatbot-messages {
            background: #111827;
        }

        body.dark .message.bot .message-content p {
            background: #374151;
            color: #f3f4f6;
        }

        body.dark .chatbot-input {
            border-top-color: #374151;
            background: #1f2937;
        }

        body.dark .chatbot-input input {
            background: #374151;
            border-color: #4b5563;
            color: #f3f4f6;
        }
    </style>
    </head>
<body>
    
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
                        <p>Bonjour ! Je suis votre assistant ESEN NET. Je peux vous aider avec les événements, les offres, votre profil et répondre à toutes vos questions.</p>
                    </div>
                    <div class="message-time">12:00</div>
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

    <?php include 'student-navbar.php'; ?>

    <main class="main-content-wrapper">
        <div class="dashboard-container">
            <div class="dashboard-header">
                <h1>Événements</h1>
                <p>Participez aux événements ESEN</p>
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

            <!-- Stats Row -->
            <div class="events-stats-row">
                <div class="events-stat-chip">
                    <div class="events-stat-icon blue"><i class="fas fa-calendar-alt"></i></div>
                    <div class="events-stat-data">
                        <span class="events-stat-number"><?php echo count($events); ?></span>
                        <span class="events-stat-label">Total</span>
                    </div>
                </div>
                <div class="events-stat-chip">
                    <div class="events-stat-icon green"><i class="fas fa-clock"></i></div>
                    <div class="events-stat-data">
                        <span class="events-stat-number"><?php echo count($events_avenir); ?></span>
                        <span class="events-stat-label">À venir</span>
                    </div>
                </div>
                <div class="events-stat-chip">
                    <div class="events-stat-icon red"><i class="fas fa-calendar-times"></i></div>
                    <div class="events-stat-data">
                        <span class="events-stat-number"><?php echo count($events_expires); ?></span>
                        <span class="events-stat-label">Expirés</span>
                    </div>
                </div>
                <div class="events-stat-chip">
                    <div class="events-stat-icon purple"><i class="fas fa-user-check"></i></div>
                    <div class="events-stat-data">
                        <span class="events-stat-number"><?php echo count($mes_participations); ?></span>
                        <span class="events-stat-label">Participations</span>
                    </div>
                </div>
            </div>


            <!-- Toolbar: Search + Filters -->
            <div class="events-toolbar">
                <div class="events-search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" class="events-search-input" placeholder="Rechercher un événement...">
                </div>
                <div class="events-filter-tabs">
                    <button class="events-filter-tab active" data-filter="all">
                        <i class="fas fa-th-large"></i> Tous
                    </button>
                    <button class="events-filter-tab" data-filter="upcoming">
                        <i class="fas fa-clock"></i> À venir
                    </button>
                    <button class="events-filter-tab" data-filter="expired">
                        <i class="fas fa-calendar-times"></i> Expirés
                    </button>
                    <button class="events-filter-tab" data-filter="participated">
                        <i class="fas fa-user-check"></i> Participés
                    </button>
                </div>
            </div>

            <!-- Events Grid -->
            <?php if (!empty($events)): ?>
                <div class="events-grid">
                    <?php foreach ($events as $event): ?>
                        <?php 
                        $is_participated = in_array($event['id'], $mes_participations);
                        $is_expired = $event['dateEvent'] < date('Y-m-d');
                        
                        if ($is_expired) {
                            $status_key = $is_participated ? 'participated' : 'expired';
                            $card_class = 'event-card-advanced expired';
                            $event_type = '<i class="fas fa-calendar-times"></i> Événement expiré';
                            $status_text = $is_participated ? 'Participé' : 'Expiré';
                            $banner_overlay_class = 'event-card-banner-overlay expired';
                            $placeholder_class = 'event-card-banner-placeholder expired';
                            $banner_icon = 'fa-calendar-times';
                        } else {
                            $status_key = $is_participated ? 'participated' : 'upcoming';
                            $card_class = 'event-card-advanced';
                            $event_type = '<i class="fas fa-calendar"></i> Événement';
                            $status_text = $is_participated ? 'Participé' : 'À venir';
                            $banner_overlay_class = 'event-card-banner-overlay';
                            $placeholder_class = 'event-card-banner-placeholder';
                            $banner_icon = 'fa-calendar-alt';
                        }
                        ?>
                        <div class="<?php echo $card_class; ?>" data-status="<?php echo $status_key; ?>" onclick="openEventModal(<?php echo $event['id']; ?>, '<?php echo htmlspecialchars($event['titre']); ?>', '<?php echo htmlspecialchars($event['description']); ?>', '<?php echo date('d/m/Y', strtotime($event['dateEvent'])); ?>', '<?php echo htmlspecialchars($event['lieu'] ?? 'ESEN'); ?>', '<?php echo htmlspecialchars($event['image'] ?? ''); ?>', <?php echo $is_participated ? 'true' : 'false'; ?>)">
                            <?php if (!empty($event['image'])): ?>
                                <div class="event-card-banner">
                                    <img src="../../../<?php echo htmlspecialchars($event['image']); ?>" alt="<?php echo htmlspecialchars($event['titre']); ?>">
                                    <div class="<?php echo $banner_overlay_class; ?>"></div>
                                </div>
                            <?php else: ?>
                                <div class="event-card-banner <?php echo $placeholder_class; ?>">
                                    <div class="event-card-banner-icon">
                                        <i class="fas <?php echo $banner_icon; ?>"></i>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="event-card-body">
                                <div class="event-card-top">
                                    <span class="event-card-type">
                                        <?php echo $event_type; ?>
                                    </span>
                                    <span class="event-card-status <?php echo $status_key; ?>">
                                        <span class="status-dot"></span>
                                        <?php echo $status_text; ?>
                                    </span>
                                </div>

                                <h3 class="event-card-title"><?php echo htmlspecialchars($event['titre']); ?></h3>

                                <div class="event-card-date">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span><?php echo date('d M Y', strtotime($event['dateEvent'])); ?></span>
                                </div>

                                <div class="event-card-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($event['lieu'] ?? 'ESEN'); ?></span>
                                </div>

                                <div class="event-card-description">
                                    <p><?php echo htmlspecialchars(substr($event['description'], 0, 100)) . (strlen($event['description']) > 100 ? '...' : ''); ?></p>
                                </div>

                                <div class="event-card-actions">
                                    <?php if ($is_participated): ?>
                                        <div class="event-card-badge participated">
                                            <i class="fas fa-check"></i> <?php echo $is_expired ? 'Participé' : 'Inscrit'; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="event-card-badge <?php echo $is_expired ? 'expired' : 'available'; ?>">
                                            <i class="fas <?php echo $is_expired ? 'fa-times-circle' : 'fa-user-plus'; ?>"></i> <?php echo $is_expired ? 'Expiré' : 'Participer'; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="events-empty-state">
                    <div class="events-empty-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3>Aucun événement à venir</h3>
                    <p>Revenez plus tard pour découvrir de nouveaux événements</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal de détails de l'événement -->
    <div class="modal-overlay" id="eventModal" style="display: none;">
        <div class="modal-container" style="max-width: 600px;">
            <div class="modal-header">
                <h3 id="modalEventTitle">Détails de l'événement</h3>
                <button class="modal-close" onclick="closeEventModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="modalEventImageContainer" style="width: 100%; height: 200px; border-radius: 12px; overflow: hidden; margin-bottom: 1rem; display: none;">
                    <img id="modalEventImage" src="" alt="Événement" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <p id="modalEventDescription" style="color: #e2e8f0; margin-bottom: 1rem;"></p>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                    <div style="background: rgba(255, 255, 255, 0.05); padding: 1rem; border-radius: 8px;">
                        <i class="fas fa-calendar-alt" style="color: #00d4c4;"></i>
                        <span style="color: #e2e8f0; margin-left: 0.5rem;" id="modalEventDate"></span>
                    </div>
                    <div style="background: rgba(255, 255, 255, 0.05); padding: 1rem; border-radius: 8px;">
                        <i class="fas fa-map-marker-alt" style="color: #6366f1;"></i>
                        <span style="color: #e2e8f0; margin-left: 0.5rem;" id="modalEventLocation"></span>
                    </div>
                </div>
                
                <div id="modalEventAction">
                    <!-- Le contenu sera inséré dynamiquement par JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <script>
        function openEventModal(eventId, title, description, date, location, image, isParticipating) {
            document.getElementById('modalEventTitle').textContent = title;
            document.getElementById('modalEventDescription').textContent = description;
            document.getElementById('modalEventDate').textContent = date;
            document.getElementById('modalEventLocation').textContent = location;
            
            if (image) {
                document.getElementById('modalEventImage').src = '../../../' + image;
                document.getElementById('modalEventImageContainer').style.display = 'block';
            } else {
                document.getElementById('modalEventImageContainer').style.display = 'none';
            }
            
            // Vérifier si l'événement est expiré
            const eventDate = new Date(date.split('/').reverse().join('-')); // Convertir DD/MM/YYYY à YYYY-MM-DD
            const today = new Date();
            const isExpired = eventDate < today;
            
            const actionDiv = document.getElementById('modalEventAction');
            if (isExpired) {
                if (isParticipating) {
                    actionDiv.innerHTML = '<div style="text-align: center; padding: 1rem; background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; color: #10b981;"><i class="fas fa-check-circle"></i> Vous avez participé à cet événement</div>';
                } else {
                    actionDiv.innerHTML = '<div style="text-align: center; padding: 1rem; background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; color: #ef4444;"><i class="fas fa-times-circle"></i> Cet événement est expiré, vous ne pouvez plus y participer</div>';
                }
            } else {
                if (isParticipating) {
                    actionDiv.innerHTML = '<div style="text-align: center; padding: 1rem; background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; color: #10b981;"><i class="fas fa-check-circle"></i> Vous êtes déjà inscrit à cet événement</div>';
                } else {
                    actionDiv.innerHTML = '<form method="POST" action=""><input type="hidden" name="event_id" value="' + eventId + '"><input type="hidden" name="participer_event" value="1"><div class="form-group" style="margin-bottom: 1rem;"><label for="role">Rôle</label><select name="role" id="role" required style="width: 100%; padding: 0.75rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #ffffff;"><option value="participant">Participant</option><option value="organisateur">Organisateur</option><option value="benevole">Bénévole</option><option value="speaker">Speaker</option></select></div><div class="form-group" style="margin-bottom: 1rem;"><label for="statut">Statut</label><select name="statut" id="statut" required style="width: 100%; padding: 0.75rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #ffffff;"><option value="EN_ATTENTE">En attente</option><option value="CONFIRME">Confirmé</option><option value="ANNULE">Annulé</option></select></div><button type="submit" class="btn primary" style="width: 100%; padding: 0.75rem; background: linear-gradient(135deg, #0f0c29, #302b63, #24243e); border: 1px solid rgba(0, 240, 255, 0.3); color: #ffffff; border-radius: 8px; cursor: pointer;"><i class="fas fa-user-plus"></i> Participer à cet événement</button></form>';
                }
            }
            
            document.getElementById('eventModal').style.display = 'flex';
        }

        function closeEventModal() {
            document.getElementById('eventModal').style.display = 'none';
        }

        // Fermer le modal en cliquant à l'extérieur
        document.getElementById('eventModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEventModal();
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../public/assets/js/main.js"></script>

    <!-- Chatbot Data -->
    <script>
        window.studentData = {
            badges: <?php echo json_encode($etudiant_info['badges'] ?? ['Débutant']); ?>,
            skills: <?php echo json_encode(is_array($etudiant_info['specialite'] ?? 'Informatique') ? ($etudiant_info['specialite'] ?? ['Informatique']) : [($etudiant_info['specialite'] ?? 'Informatique')]); ?>,
            speciality: <?php echo json_encode($etudiant_info['specialite'] ?? 'Informatique'); ?>,
            offers: <?php echo json_encode($offres ?? []); ?>,
            events: <?php echo json_encode($events ?? []); ?>
        };
    </script>

    
    <!-- Chatbot JavaScript -->
    <script src="../../public/assets/js/chatbot.js"></script>

    <!-- Custom Cursor JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cursor = document.querySelector('.custom-cursor');
            const cursorDot = document.querySelector('.custom-cursor-dot');
            
            if (cursor && cursorDot) {
                // Suivi du curseur
                document.addEventListener('mousemove', (e) => {
                    cursor.style.left = e.clientX + 'px';
                    cursor.style.top = e.clientY + 'px';
                    cursorDot.style.left = e.clientX + 'px';
                    cursorDot.style.top = e.clientY + 'px';
                });
                
                // Effet hover sur les éléments cliquables
                const hoverElements = 'a, button, .btn, input, select, textarea, .event-card, .clickable, [onclick]';
                document.addEventListener('mouseover', (e) => {
                    if (e.target.matches(hoverElements) || e.target.closest(hoverElements)) {
                        cursor.classList.add('hover');
                    } else {
                        cursor.classList.remove('hover');
                    }
                });
                
                // Effet click
                document.addEventListener('mousedown', () => {
                    cursor.classList.add('clicking');
                });
                
                document.addEventListener('mouseup', () => {
                    cursor.classList.remove('clicking');
                });
                
                // Cacher le curseur personnalisé quand il quitte la fenêtre
                document.addEventListener('mouseleave', () => {
                    cursor.style.display = 'none';
                    cursorDot.style.display = 'none';
                });
                
                document.addEventListener('mouseenter', () => {
                    cursor.style.display = 'block';
                    cursorDot.style.display = 'block';
                });
            }
        });
    </script>
</body>
</html>
