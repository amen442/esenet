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

// Obtenir toutes les offres disponibles
$offres = get_offres_disponibles();

// Traitement de la candidature
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['postuler_offre'])) {
    $offre_id = $_POST['offre_id'] ?? 0;
    $badge_id = $_POST['badge_id'] ?? 0;
    $lettre_motiv = $_POST['lettre_motiv'] ?? '';
    $experience = $_POST['experience'] ?? 0;
    $cv_path = null;
    
    // Obtenir les candidatures de l'étudiant pour vérifier les doublons
    $mes_candidatures = get_etudiant_candidatures($etudiant_id);
    $candidature_ids = $mes_candidatures ? array_column($mes_candidatures, 'idOffre') : [];

    // Gestion de l'upload du CV
    if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../../app/uploads/cv/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_name = 'cv_' . $etudiant_id . '_' . $offre_id . '_' . time() . '.pdf';
        $file_path = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['cv_file']['tmp_name'], $file_path)) {
            $cv_path = 'app/uploads/cv/' . $file_name;
        }
    }

    // Vérifier si l'étudiant n'a pas déjà postulé
    if (!is_array($candidature_ids) || !in_array($offre_id, $candidature_ids)) {
        // Insérer la candidature
        $stmt = $conn->prepare("INSERT INTO candidature (idEtudiant, idOffre, lettreMotiv, experience, cv, datePost) VALUES (?, ?, ?, ?, ?, NOW())");
        if ($stmt->execute([$etudiant_id, $offre_id, $lettre_motiv, $experience, $cv_path])) {
            // Ajouter 20 points automatiquement pour la candidature (consolidation)
            ajouter_points_etudiant_consolidation($etudiant_id, 20, "Candidature postulée à l'offre");
            $success_message = "Candidature envoyée avec succès !";
        } else {
            $error_message = "Erreur lors de l'envoi de la candidature. Si l'erreur persiste, la colonne CV doit être ajoutée à la table candidature.";
        }
    } else {
        $error_message = "Vous avez déjà postulé à cette offre.";
    }
}

// Traitement de l'annulation de candidature
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['annuler_candidature'])) {
    $offre_id = $_POST['offre_id'] ?? 0;
    
    // Supprimer la candidature
    $stmt = $conn->prepare("DELETE FROM candidature WHERE idEtudiant = ? AND idOffre = ?");
    if ($stmt->execute([$etudiant_id, $offre_id])) {
        $success_message = "Candidature annulée avec succès !";
        // Rafraîchir la liste des candidatures
        $mes_candidatures = get_etudiant_candidatures($etudiant_id);
        $candidature_ids = array_column($mes_candidatures, 'idOffre');
    } else {
        $error_message = "Erreur lors de l'annulation de la candidature.";
    }
}

// Traitement automatique des candidatures acceptées pour ajouter des points
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['valider_candidature'])) {
    $offre_id = $_POST['offre_id'] ?? 0;
    
    // Vérifier si la candidature existe et est acceptée
    $sql = "SELECT * FROM candidature WHERE idEtudiant = ? AND idOffre = ? AND statut = 'ACCEPTEE'";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$etudiant_id, $offre_id]);
    $candidature = $stmt->fetch();
    
    if ($candidature && !a_deja_postule($etudiant_id, $offre_id)) {
        // Ajouter 20 points pour candidature acceptée
        if (ajouter_points_etudiant($etudiant_id, 20, "Candidature acceptée")) {
            $success_message = "Félicitations ! 20 points ont été ajoutés pour votre candidature acceptée.";
        } else {
            $error_message = "Erreur lors de l'ajout des points.";
        }
        
        // Rafraîchir la liste des candidatures
        $mes_candidatures = get_etudiant_candidatures($etudiant_id);
        $candidature_ids = array_column($mes_candidatures, 'idOffre');
    } else {
        $error_message = "Cette candidature n'existe pas ou n'est pas acceptée.";
    }
}

// Traitement de la participation à un événement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['participer_event'])) {
    $event_id = $_POST['event_id'] ?? 0;
    
    // Vérifier si l'étudiant n'a pas déjà participé
    if (!a_deja_participe($etudiant_id, $event_id)) {
        // Insérer la participation
        $stmt = $conn->prepare("INSERT INTO participation (idEtudiant, idEvenement, dateParticipation, statut) VALUES (?, ?, NOW(), 'EN_ATTENTE')");
        if ($stmt->execute([$etudiant_id, $event_id])) {
            verifier_et_ajouter_points_automatique($etudiant_id);
            ajouter_points_participations_anciennes($etudiant_id);
            ajouter_points_etudiant_consolidation($etudiant_id, 10, "Participation à l'événement");
            $success_message = "Participation enregistrée avec succès !";
        } else {
            $error_message = "Erreur lors de l'enregistrement de la participation.";
        }
    } else {
        $error_message = "Vous avez déjà participé à cet événement.";
    }
}

// Obtenir les candidatures de l'étudiant
$mes_candidatures = get_etudiant_candidatures($etudiant_id);
$candidature_ids = $mes_candidatures ? array_column($mes_candidatures, 'idOffre') : [];

// Obtenir les badges de l'étudiant
$student_badges = [];
$best_badge_id = 0;
try {
    $stmt = $conn->prepare("SELECT * FROM badge WHERE idEtudiant = ? ORDER BY score DESC");
    $stmt->execute([$etudiant_id]);
    foreach ($stmt->fetchAll() as $row) {
        $student_badges[] = $row;
    }
    // Sélectionner le badge avec le score le plus élevé par défaut
    if (!empty($student_badges)) {
        $best_badge_id = $student_badges[0]['id'];
    }
} catch (Exception $e) {
    $student_badges = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offres - ESEN NET</title>
    <meta name="description" content="Découvrez les offres disponibles">
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

        /* ===== OFFERS PAGE STYLES ===== */
        .offers-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .offers-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .offers-header h1 {
            color: #2c3e50;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .offers-header p {
            color: #6c757d;
            font-size: 1.1rem;
        }

        /* Stats Row */
        .offers-stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .offers-stat-chip {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .offers-stat-chip:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .offers-stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .offers-stat-icon.blue { background: linear-gradient(135deg, #667eea, #764ba2); }
        .offers-stat-icon.green { background: linear-gradient(135deg, #10b981, #059669); }
        .offers-stat-icon.red { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .offers-stat-icon.purple { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }

        .offers-stat-data {
            display: flex;
            flex-direction: column;
        }

        .offers-stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            line-height: 1;
        }

        .offers-stat-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }

        /* Alerts */
        .offers-alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .offers-alert.success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #10b981;
        }

        .offers-alert.error {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05));
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }

        /* Toolbar */
        .offers-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .offers-search-box {
            position: relative;
            flex: 1;
            max-width: 400px;
        }

        .offers-search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .offers-search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .offers-search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .offers-filter-tabs {
            display: flex;
            gap: 0.5rem;
            background: #f8f9fa;
            padding: 0.25rem;
            border-radius: 12px;
        }

        .offers-filter-tab {
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

        .offers-filter-tab.active {
            background: white;
            color: #667eea;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .offers-filter-tab:hover:not(.active) {
            background: rgba(255, 255, 255, 0.5);
        }

        /* Grid */
        .offers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }

        /* Offer Cards */
        .offer-card-advanced {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            animation: offerCardIn 0.5s ease forwards;
        }

        .offer-card-advanced:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        @keyframes offerCardIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .offer-card-banner {
            position: relative;
            height: 180px;
            overflow: hidden;
        }

        .offer-card-banner img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .offer-card-banner-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.1), rgba(0,0,0,0.3));
        }

        .offer-card-banner-placeholder {
            height: 180px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .offer-card-banner-icon {
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

        .offer-card-body {
            padding: 1.5rem;
        }

        .offer-card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .offer-card-type {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .offer-card-type.stage { background: rgba(102, 126, 234, 0.1); color: #667eea; }
        .offer-card-type.alternance { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .offer-card-type.emploi { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }

        .offer-card-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .offer-card-status.available {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .offer-card-status.applied {
            background: rgba(139, 92, 246, 0.1);
            color: #8b5cf6;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
        }

        .offer-card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 1rem;
            line-height: 1.3;
        }

        .offer-card-company {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .offer-card-dates {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .offer-card-date-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6c757d;
            font-size: 0.85rem;
        }

        .offer-card-location {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .offer-card-actions {
            display: flex;
            gap: 0.75rem;
        }

        .offer-card-btn {
            flex: 1;
            padding: 0.75rem 1rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .offer-card-btn.apply {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .offer-card-btn.apply:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .offer-card-btn.applied {
            background: #e9ecef;
            color: #6c757d;
            cursor: not-allowed;
        }

        .offer-card-btn.cancel {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .offer-card-btn.cancel:hover {
            background: rgba(239, 68, 68, 0.2);
        }

        /* Empty State */
        .offers-empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }

        .offers-empty-icon {
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

        .offers-empty-state h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }

        .offers-empty-state p {
            font-size: 1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .offers-stats-row {
                grid-template-columns: 1fr;
            }

            .offers-toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .offers-search-box {
                max-width: none;
            }

            .offers-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ===== DARK MODE ===== */
        body.dark .offers-header h1 {
            background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        body.dark .offers-header p {
            color: #9ca3af;
        }

        body.dark .offers-stat-chip {
            background: rgba(31, 41, 55, 0.8);
            border: 1px solid rgba(75, 85, 99, 0.3);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        body.dark .offers-stat-chip:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
        }

        body.dark .offers-stat-number {
            color: #f3f4f6;
        }

        body.dark .offers-stat-label {
            color: #9ca3af;
        }

        body.dark .offers-alert.success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(16, 185, 129, 0.05));
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        body.dark .offers-alert.error {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(239, 68, 68, 0.05));
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        body.dark .offers-search-input {
            background: rgba(31, 41, 55, 0.8);
            border: 2px solid rgba(75, 85, 99, 0.3);
            color: #f3f4f6;
        }

        body.dark .offers-search-input:focus {
            border-color: #8b5cf6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }

        body.dark .offers-filter-tabs {
            background: rgba(31, 41, 55, 0.8);
            border: 1px solid rgba(75, 85, 99, 0.3);
        }

        body.dark .offers-filter-tab {
            color: #9ca3af;
        }

        body.dark .offers-filter-tab.active {
            background: rgba(17, 24, 39, 0.9);
            color: #8b5cf6;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        body.dark .offers-filter-tab:hover:not(.active) {
            background: rgba(55, 65, 81, 0.5);
        }

        body.dark .offer-card-advanced {
            background: rgba(31, 41, 55, 0.9);
            border: 1px solid rgba(75, 85, 99, 0.3);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        body.dark .offer-card-advanced:hover {
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
        }

        body.dark .offer-card-banner-placeholder {
            background: linear-gradient(135deg, #8b5cf6, #ec4899);
        }

        body.dark .offer-card-title {
            color: #f3f4f6;
        }

        body.dark .offer-card-company,
        body.dark .offer-card-date-item,
        body.dark .offer-card-location {
            color: #9ca3af;
        }

        body.dark .offer-card-type.stage { 
            background: rgba(139, 92, 246, 0.2); 
            color: #a78bfa; 
        }

        body.dark .offer-card-type.alternance { 
            background: rgba(16, 185, 129, 0.2); 
            color: #34d399; 
        }

        body.dark .offer-card-type.emploi { 
            background: rgba(245, 158, 11, 0.2); 
            color: #fbbf24; 
        }

        body.dark .offer-card-status.available {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
        }

        body.dark .offer-card-status.applied {
            background: rgba(139, 92, 246, 0.2);
            color: #a78bfa;
        }

        body.dark .offer-card-btn.apply {
            background: linear-gradient(135deg, #8b5cf6, #ec4899);
        }

        body.dark .offer-card-btn.apply:hover {
            box-shadow: 0 8px 20px rgba(139, 92, 246, 0.4);
        }

        body.dark .offer-card-btn.applied {
            background: rgba(75, 85, 99, 0.5);
            color: #9ca3af;
        }

        body.dark .offer-card-btn.cancel {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        body.dark .offer-card-btn.cancel:hover {
            background: rgba(239, 68, 68, 0.3);
        }

        body.dark .offers-empty-state {
            color: #9ca3af;
        }

        body.dark .offers-empty-icon {
            background: linear-gradient(135deg, #8b5cf6, #ec4899);
        }

        body.dark .offers-empty-state h3 {
            color: #f3f4f6;
        }

        body.dark .offers-empty-state p {
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
                const interactiveElements = document.querySelectorAll('a, button, input, .offer-card-advanced, .offers-filter-tab, .offers-search-input, .modal-close');
                interactiveElements.forEach(el => {
                    el.addEventListener('mouseenter', () => cursor.classList.add('hover'));
                    el.addEventListener('mouseleave', () => cursor.classList.remove('hover'));
                });

                // Click effect
                document.addEventListener('mousedown', () => cursor.classList.add('clicking'));
                document.addEventListener('mouseup', () => cursor.classList.remove('clicking'));
            }
            
            // Filter tabs
            const filterTabs = document.querySelectorAll('.offers-filter-tab');
            const offerCards = document.querySelectorAll('.offer-card-advanced');
            filterTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    filterTabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    const filter = this.dataset.filter;
                    offerCards.forEach(card => {
                        if (filter === 'all' || card.dataset.status === filter) {
                            card.style.display = '';
                            card.style.animation = 'offerCardIn 0.5s ease forwards';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            });

            // Search
            const searchInput = document.querySelector('.offers-search-input');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const query = this.value.toLowerCase();
                    offerCards.forEach(card => {
                        const title = card.querySelector('.offer-card-title').textContent.toLowerCase();
                        const company = card.querySelector('.offer-card-company').textContent.toLowerCase();
                        const type = card.querySelector('.offer-card-type').textContent.toLowerCase();
                        if (title.includes(query) || company.includes(query) || type.includes(query)) {
                            card.style.display = '';
                            card.style.animation = 'offerCardIn 0.5s ease forwards';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
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
                <h1>Offres de stage</h1>
                <p>Consultez les opportunités disponibles</p>
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
            <div class="offers-stats-row">
                <div class="offers-stat-chip">
                    <div class="offers-stat-icon blue"><i class="fas fa-briefcase"></i></div>
                    <div class="offers-stat-data">
                        <span class="offers-stat-number"><?php echo count($offres); ?></span>
                        <span class="offers-stat-label">Total</span>
                    </div>
                </div>
                <div class="offers-stat-chip">
                    <div class="offers-stat-icon green"><i class="fas fa-check-circle"></i></div>
                    <div class="offers-stat-data">
                        <span class="offers-stat-number"><?php echo count($offres); ?></span>
                        <span class="offers-stat-label">Disponibles</span>
                    </div>
                </div>
                <div class="offers-stat-chip">
                    <div class="offers-stat-icon purple"><i class="fas fa-paper-plane"></i></div>
                    <div class="offers-stat-data">
                        <span class="offers-stat-number"><?php echo count($candidature_ids); ?></span>
                        <span class="offers-stat-label">Candidatures</span>
                    </div>
                </div>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="offers-alert success"><i class="fas fa-check-circle"></i> <?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if (isset($error_message)): ?>
                <div class="offers-alert error"><i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?></div>
            <?php endif; ?>

            <!-- Toolbar: Search + Filters -->
            <div class="offers-toolbar">
                <div class="offers-search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" class="offers-search-input" placeholder="Rechercher une offre...">
                </div>
                <div class="offers-filter-tabs">
                    <button class="offers-filter-tab active" data-filter="all">
                        <i class="fas fa-th-large"></i> Toutes
                    </button>
                    <button class="offers-filter-tab" data-filter="available">
                        <i class="fas fa-check-circle"></i> Disponibles
                    </button>
                    <button class="offers-filter-tab" data-filter="applied">
                        <i class="fas fa-paper-plane"></i> Postulées
                    </button>
                </div>
            </div>

            <!-- Offers Grid -->
            <?php if (!empty($offres)): ?>
                <div class="offers-grid">
                    <?php foreach ($offres as $offre): ?>
                        <?php 
                        $is_applied = in_array($offre['id'], $candidature_ids);
                        $status_key = $is_applied ? 'applied' : 'available';
                        ?>
                        <div class="offer-card-advanced" data-status="<?php echo $status_key; ?>">
                            <?php if (!empty($offre['image'])): ?>
                                <div class="offer-card-banner">
                                    <img src="../../../<?php echo htmlspecialchars($offre['image']); ?>" alt="<?php echo htmlspecialchars($offre['titre']); ?>">
                                    <div class="offer-card-banner-overlay"></div>
                                </div>
                            <?php else: ?>
                                <div class="offer-card-banner offer-card-banner-placeholder">
                                    <div class="offer-card-banner-icon">
                                        <i class="fas fa-briefcase"></i>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="offer-card-body">
                                <div class="offer-card-top">
                                    <span class="offer-card-type <?php echo strtolower($offre['type'] ?? 'stage'); ?>">
                                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($offre['type'] ?? 'Stage'); ?>
                                    </span>
                                    <span class="offer-card-status <?php echo $status_key; ?>">
                                        <span class="status-dot"></span>
                                        <?php echo $is_applied ? 'Postulée' : 'Disponible'; ?>
                                    </span>
                                </div>

                                <h3 class="offer-card-title"><?php echo htmlspecialchars($offre['titre']); ?></h3>

                                <div class="offer-card-company">
                                    <i class="fas fa-building"></i>
                                    <span><?php echo htmlspecialchars($offre['nomsociete']); ?></span>
                                </div>

                                <div class="offer-card-dates">
                                    <div class="offer-card-date-item">
                                        <i class="fas fa-calendar-plus"></i>
                                        <span>Publié le <?php echo date('d M Y', strtotime($offre['datePub'])); ?></span>
                                    </div>
                                    <div class="offer-card-date-item">
                                        <i class="fas fa-calendar-times"></i>
                                        <span>Expire le <?php echo date('d M Y', strtotime($offre['dateExp'])); ?></span>
                                    </div>
                                </div>

                                <div class="offer-card-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($offre['lieu'] ?? 'Tunis'); ?></span>
                                </div>

                                <div class="offer-card-actions">
                                    <?php if ($is_applied): ?>
                                        <button class="offer-card-btn applied" disabled>
                                            <i class="fas fa-check"></i> Déjà postulé
                                        </button>
                                        <button type="button" class="offer-card-btn cancel" onclick="openCancelModal(<?php echo $offre['id']; ?>, '<?php echo htmlspecialchars($offre['titre']); ?>')">
                                            <i class="fas fa-times"></i> Annuler
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="offer-card-btn apply" onclick="openModal(<?php echo $offre['id']; ?>, '<?php echo htmlspecialchars($offre['titre']); ?>')">
                                            <i class="fas fa-paper-plane"></i> Postuler
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="offers-empty-state">
                    <div class="offers-empty-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <h3>Aucune offre disponible</h3>
                    <p>Revenez plus tard pour découvrir de nouvelles opportunités</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal de candidature -->
    <div class="modal-overlay" id="candidatureModal" style="display: none;">
        <div class="modal-container">
            <div class="modal-header">
                <h3>Postuler à l'offre</h3>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p class="modal-offre-title" id="modalOffreTitle"></p>
                <form method="POST" id="candidatureForm" enctype="multipart/form-data">
                    <input type="hidden" name="offre_id" id="modalOffreId">
                    <input type="hidden" name="postuler_offre" value="1">
                    <input type="hidden" name="badge_id" value="<?php echo $best_badge_id; ?>">
                    
                    <?php if (!empty($student_badges)): ?>
                        <div class="form-group">
                            <label>Badge utilisé automatiquement</label>
                            <div style="background: rgba(0, 240, 255, 0.1); border: 1px solid rgba(0, 240, 255, 0.3); border-radius: 12px; padding: 1rem; display: flex; align-items: center; gap: 1rem;">
                                <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #00d4c4, #00b4d8); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white;">
                                    <i class="fas fa-award"></i>
                                </div>
                                <div>
                                    <p style="margin: 0; color: #ffffff; font-weight: 600;">Score: <?php echo $student_badges[0]['score'] ?? 0; ?> points</p>
                                    <p style="margin: 0.25rem 0 0 0; color: #e2e8f0; font-size: 0.85rem;">Meilleur badge sélectionné automatiquement</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="cv_file">Votre CV (PDF)</label>
                        <input type="file" name="cv_file" id="cv_file" accept=".pdf" required style="width: 100%; padding: 0.75rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #ffffff;">
                        <p style="margin: 0.5rem 0 0 0; color: #e2e8f0; font-size: 0.8rem;">Formats acceptés: PDF uniquement</p>
                    </div>

                    <div class="form-group">
                        <label for="experience">Expérience professionnelle (années)</label>
                        <input type="number" name="experience" id="experience" min="0" step="0.5" value="<?php echo htmlspecialchars($etudiant_info['experience'] ?? 0); ?>" required style="width: 100%; padding: 0.75rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #ffffff;">
                        <p style="margin: 0.5rem 0 0 0; color: #e2e8f0; font-size: 0.8rem;">Indiquez vos années d'expérience professionnelle</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="lettre_motiv">Lettre de motivation</label>
                        <textarea name="lettre_motiv" id="lettre_motiv" rows="5" placeholder="Expliquez pourquoi vous êtes intéressé par cette offre..." required></textarea>
                    </div>
                    
                    <button type="submit" class="btn primary">
                        <i class="fas fa-paper-plane"></i> Envoyer ma candidature
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation d'annulation -->
    <div class="modal-overlay" id="cancelModal" style="display: none;">
        <div class="modal-container" style="max-width: 400px;">
            <div class="modal-header">
                <h3>Annuler la candidature</h3>
                <button class="modal-close" onclick="closeCancelModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p class="modal-offre-title" id="cancelOffreTitle"></p>
                <p style="color: #e2e8f0; margin-bottom: 1.5rem;">Êtes-vous sûr de vouloir annuler votre candidature à cette offre ?</p>
                <form method="POST" id="cancelForm">
                    <input type="hidden" name="offre_id" id="cancelOffreId">
                    <input type="hidden" name="annuler_candidature" value="1">
                    <div style="display: flex; gap: 1rem;">
                        <button type="button" class="btn btn-secondary" onclick="closeCancelModal()" style="flex: 1; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); color: #ffffff;">
                            Non, garder
                        </button>
                        <button type="submit" class="btn btn-danger" style="flex: 1; background: rgba(239, 68, 68, 0.3); border: 1px solid rgba(239, 68, 68, 0.5); color: #ef4444;">
                            <i class="fas fa-times"></i> Oui, annuler
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openModal(offreId, offreTitle) {
            document.getElementById('modalOffreId').value = offreId;
            document.getElementById('modalOffreTitle').textContent = offreTitle;
            document.getElementById('candidatureModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('candidatureModal').style.display = 'none';
        }

        function openCancelModal(offreId, offreTitle) {
            document.getElementById('cancelOffreId').value = offreId;
            document.getElementById('cancelOffreTitle').textContent = offreTitle;
            document.getElementById('cancelModal').style.display = 'flex';
        }

        function closeCancelModal() {
            document.getElementById('cancelModal').style.display = 'none';
        }

        // Fermer les modals en cliquant à l'extérieur
        document.getElementById('candidatureModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        document.getElementById('cancelModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCancelModal();
            }
        });
    </script>

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
