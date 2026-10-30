<?php

namespace App\Controllers;

use App\Core\Template;

class ApiController
{
    public function checkNewItems()
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET');
        header('Access-Control-Allow-Headers: Content-Type');
        
        session_start();
        
        // Si non authentifié, retourner des données publiques
        if (!isset($_SESSION['user_id'])) {
            echo json_encode([
                'success' => true,
                'data' => [
                    'new_messages' => 0,
                    'new_notifications' => 0,
                    'new_candidatures' => 0,
                    'authenticated' => false
                ]
            ]);
            return;
        }

        require_once __DIR__ . '/../../config/db_connect.php';
        
        $user_id = $_SESSION['user_id'];
        $user_type = $_SESSION['user_type'];
        
        $response = [
            'success' => true,
            'data' => [
                'new_messages' => 0,
                'new_notifications' => 0,
                'new_candidatures' => 0,
                'authenticated' => true
            ]
        ];
        
        // Check for new messages
        $sql = "SELECT COUNT(*) as count FROM messages WHERE destinataire_id = ? AND lu = 0";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_id]);
        $response['data']['new_messages'] = $stmt->fetch()['count'];
        
        // Check for new notifications
        $sql = "SELECT COUNT(*) as count FROM notifications WHERE utilisateur_id = ? AND lu = 0";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_id]);
        $response['data']['new_notifications'] = $stmt->fetch()['count'];
        
        // For companies, check new candidatures
        if ($user_type === 'entreprise') {
            $sql = "SELECT COUNT(*) as count FROM candidature c 
                    JOIN offre o ON c.idOffre = o.id 
                    WHERE o.idEntreprise = ? AND c.lu = 0";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$user_id]);
            $response['data']['new_candidatures'] = $stmt->fetch()['count'];
        }
        
        echo json_encode($response);
    }
}
