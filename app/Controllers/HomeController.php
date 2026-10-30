<?php

namespace App\Controllers;

use App\Models\Entreprise;
use App\Models\Offre;
use App\Models\Evenement;
use App\Models\Candidature;

class HomeController
{
    private $entrepriseModel;
    private $offreModel;
    private $evenementModel;
    private $candidatureModel;

    public function __construct()
    {
        $this->entrepriseModel = new Entreprise();
        $this->offreModel = new Offre();
        $this->evenementModel = new Evenement();
        $this->candidatureModel = new Candidature();
    }

    public function index()
    {
        session_start();
        
        if (!isset($_SESSION['entreprise_id'])) {
            header('Location: /login.php');
            exit();
        }

        $entreprise_id = $_SESSION['entreprise_id'];
        
        $entreprise_info = $this->entrepriseModel->getInfo($entreprise_id);
        $events = $this->evenementModel->getUpcoming(10);
        $offres = $this->offreModel->getEntrepriseOffres($entreprise_id);
        $candidatures = $this->candidatureModel->getEntrepriseCandidatures($entreprise_id);

        // Fusionner et trier par date chronologique
        $feed_items = [];

        foreach ($events as $event) {
            $feed_items[] = [
                'type' => 'event',
                'date' => $event['dateEvent'],
                'data' => $event
            ];
        }

        foreach ($offres as $offre) {
            $feed_items[] = [
                'type' => 'offer',
                'date' => $offre['datePub'],
                'data' => $offre
            ];
        }

        foreach ($candidatures as $candidature) {
            $feed_items[] = [
                'type' => 'application',
                'date' => $candidature['datePost'],
                'data' => $candidature
            ];
        }

        usort($feed_items, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        require __DIR__ . '/../Views/entreprise/home.php';
    }
}
