<?php

namespace App\Models;

class Offre
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getEntrepriseOffres($entreprise_id)
    {
        $sql = "SELECT o.*, 
                (SELECT COUNT(*) FROM candidature c WHERE c.idOffre = o.id) as nb_candidatures
                FROM offre o 
                WHERE o.idEntreprise = ? 
                ORDER BY o.datePub DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$entreprise_id]);
        return $stmt->fetchAll();
    }

    public function getRecentOffres($entreprise_id, $limit = 3)
    {
        $sql = "SELECT o.*, 
                (SELECT COUNT(*) FROM candidature c WHERE c.idOffre = o.id) as nb_candidatures
                FROM offre o 
                WHERE o.idEntreprise = ? 
                ORDER BY o.datePub DESC 
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$entreprise_id, $limit]);
        return $stmt->fetchAll();
    }

    public function create($entreprise_id, $titre, $type, $date_exp, $statut = 'OUVERT')
    {
        global $conn;
        
        $date_pub = date('Y-m-d');
        $sql = "INSERT INTO offre (titre, type, datePub, dateExp, statut, idEntreprise) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute([$titre, $type, $date_pub, $date_exp, $statut, $entreprise_id])) {
            $offre_id = $this->db->lastInsertId();
            
            // Récupérer le nom de l'entreprise
            $entreprise_stmt = $conn->prepare("SELECT nom FROM utilisateur WHERE id = ?");
            $entreprise_stmt->execute([$entreprise_id]);
            $entreprise_result = $entreprise_stmt->fetch();
            $entreprise_nom = $entreprise_result['nom'] ?? 'Entreprise';
            
            // Envoyer la notification à tous les étudiants
            send_new_offer_notification_to_students($offre_id, $titre, $entreprise_nom);
            
            error_log("Nouvelle offre créée: $titre par $entreprise_nom - Notifications envoyées");
            
            return true;
        }
        
        return false;
    }

    public function update($offre_id, $titre, $type, $date_exp, $statut)
    {
        $sql = "UPDATE offre SET titre = ?, type = ?, dateExp = ?, statut = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$titre, $type, $date_exp, $statut, $offre_id]);
    }

    public function delete($offre_id)
    {
        $sql = "DELETE FROM offre WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$offre_id]);
    }

    public function getById($offre_id)
    {
        $sql = "SELECT * FROM offre WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$offre_id]);
        return $stmt->fetch();
    }

    public function close($offre_id)
    {
        $sql = "UPDATE offre SET statut = 'FERME' WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$offre_id]);
    }
}
