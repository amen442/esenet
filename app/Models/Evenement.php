<?php

namespace App\Models;

class Evenement
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll()
    {
        $sql = "SELECT * FROM evenement ORDER BY dateEvent ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function getUpcoming($limit = 3)
    {
        $sql = "SELECT * FROM evenement 
                WHERE dateEvent >= CURDATE() 
                ORDER BY dateEvent ASC 
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public function create($titre, $description, $date_event, $lieu, $capacite, $statut = 'OUVERT')
    {
        $sql = "INSERT INTO evenement (titre, description, dateEvent, lieu, capacite, statut) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$titre, $description, $date_event, $lieu, $capacite, $statut]);
    }

    public function update($event_id, $titre, $description, $date_event, $lieu, $capacite, $statut)
    {
        $sql = "UPDATE evenement SET titre = ?, description = ?, dateEvent = ?, lieu = ?, capacite = ?, statut = ? 
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$titre, $description, $date_event, $lieu, $capacite, $statut, $event_id]);
    }

    public function delete($event_id)
    {
        $sql = "DELETE FROM evenement WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$event_id]);
    }

    public function getById($event_id)
    {
        $sql = "SELECT * FROM evenement WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$event_id]);
        return $stmt->fetch();
    }
}
