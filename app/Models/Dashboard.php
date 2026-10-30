<?php

namespace App\Models;

class Dashboard
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getStats($entreprise_id)
    {
        // Nombre d'offres publiées
        $sql_offres = "SELECT COUNT(*) as total FROM offre WHERE idEntreprise = ?";
        $stmt = $this->db->prepare($sql_offres);
        $stmt->execute([$entreprise_id]);
        $result = $stmt->fetch();
        $nb_offres = $result['total'] ?? 0;
        
        // Nombre de candidatures reçues
        $sql_candidatures = "SELECT COUNT(*) as total 
                            FROM candidature c 
                            JOIN offre o ON c.idOffre = o.id 
                            WHERE o.idEntreprise = ?";
        $stmt = $this->db->prepare($sql_candidatures);
        $stmt->execute([$entreprise_id]);
        $result = $stmt->fetch();
        $nb_candidatures = $result['total'] ?? 0;
        
        // Nombre de candidatures acceptées
        $sql_acceptees = "SELECT COUNT(*) as total 
                         FROM candidature c 
                         JOIN offre o ON c.idOffre = o.id 
                         WHERE o.idEntreprise = ? AND c.statut = 'ACCEPTE'";
        $stmt = $this->db->prepare($sql_acceptees);
        $stmt->execute([$entreprise_id]);
        $result = $stmt->fetch();
        $nb_acceptees = $result['total'] ?? 0;
        
        // Nombre d'offres actives
        $sql_actives = "SELECT COUNT(*) as total FROM offre 
                       WHERE idEntreprise = ? AND statut = 'OUVERT'";
        $stmt = $this->db->prepare($sql_actives);
        $stmt->execute([$entreprise_id]);
        $result = $stmt->fetch();
        $nb_actives = $result['total'] ?? 0;
        
        return [
            'offres' => $nb_offres,
            'candidatures' => $nb_candidatures,
            'acceptees' => $nb_acceptees,
            'actives' => $nb_actives
        ];
    }
}
