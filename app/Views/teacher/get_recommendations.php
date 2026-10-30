<?php
require_once __DIR__ . '/../layouts/components.php';
require_once __DIR__ . '/../../config/db_connect.php';

header('Content-Type: application/json');

// Obtenir l'ID de l'enseignant connecté
$teacher_id = get_teacher_id();

if (!$teacher_id) {
    echo json_encode(['success' => false, 'message' => 'Enseignant non connecté']);
    exit();
}

// Obtenir l'ID de l'étudiant depuis la requête
$student_id = $_GET['student_id'] ?? null;

if (!$student_id) {
    echo json_encode(['success' => false, 'message' => 'ID étudiant manquant']);
    exit();
}

// Récupérer les recommandations pour cet étudiant
$recommendations = get_student_recommendations($student_id, $teacher_id);

// Débogage
error_log("Teacher ID: $teacher_id, Student ID: $student_id");
error_log("Recommendations found: " . print_r($recommendations, true));

echo json_encode(['success' => true, 'recommendations' => $recommendations]);
?>
