<?php

namespace App\Models;

class Entreprise
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getInfo($entreprise_id)
    {
        $sql = "SELECT e.*, u.email, u.telephone 
                FROM entreprise e 
                JOIN utilisateur u ON e.id = u.id 
                WHERE e.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$entreprise_id]);
        return $stmt->fetch();
    }

    public function updateInfo($entreprise_id, $nomsociete, $secteur, $description, $siteweb)
    {
        $sql = "UPDATE entreprise SET nomsociete = ?, secteur = ?, description = ?, siteweb = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$nomsociete, $secteur, $description, $siteweb, $entreprise_id]);
    }

    public function updateUser($entreprise_id, $email, $telephone)
    {
        $sql = "UPDATE utilisateur SET email = ?, telephone = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$email, $telephone, $entreprise_id]);
    }

    public function updatePassword($user_id, $new_password)
    {
        $sql = "UPDATE utilisateur SET motDePasse = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$new_password, $user_id]);
    }

    public function verifyPassword($user_id, $current_password)
    {
        $sql = "SELECT motDePasse FROM utilisateur WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result && $result['motDePasse'] === $current_password;
    }
}
