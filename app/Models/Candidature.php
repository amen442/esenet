<?php

namespace App\Models;

class Candidature
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getEntrepriseCandidatures($entreprise_id)
    {
        $sql = "SELECT c.*, e.nom, e.prenom, e.specialite, o.titre as offre_titre
                FROM candidature c
                JOIN etudiant e ON c.idEtudiant = e.id
                JOIN offre o ON c.idOffre = o.id
                WHERE o.idEntreprise = ?
                ORDER BY c.datePost DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$entreprise_id]);
        return $stmt->fetchAll();
    }

    public function updateStatut($candidature_id, $statut)
    {
        $sql = "UPDATE candidature SET statut = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$statut, $candidature_id]);
    }
}
