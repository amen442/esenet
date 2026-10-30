<?php

namespace App\Controllers;

use App\Core\Template;
use App\Models\Offre;
use App\Models\Entreprise;

class CompanyController
{
    private $offreModel;
    private $entrepriseModel;

    public function __construct()
    {
        $this->offreModel = new Offre();
        $this->entrepriseModel = new Entreprise();
    }

    public function dashboard()
    {
        session_start();
        
        if (!isset($_SESSION['entreprise_id'])) {
            header('Location: ' . Template::url('/login'));
            exit();
        }

        $entreprise_id = $_SESSION['entreprise_id'];
        $entreprise_info = $this->entrepriseModel->getInfo($entreprise_id);
        $recent_offers = $this->offreModel->getRecentOffres($entreprise_id, 3);
        $stats = $this->entrepriseModel->getStats($entreprise_id);

        Template::render('entreprise/dashboard', [
            'title' => 'Tableau de bord - ESEN NET',
            'css' => ['css/company-dashboard.css'],
            'entreprise_info' => $entreprise_info,
            'recent_offers' => $recent_offers,
            'stats' => $stats
        ]);
    }

    public function offers()
    {
        session_start();
        
        if (!isset($_SESSION['entreprise_id'])) {
            header('Location: ' . Template::url('/login'));
            exit();
        }

        $entreprise_id = $_SESSION['entreprise_id'];
        $entreprise_info = $this->entrepriseModel->getInfo($entreprise_id);
        $offres = $this->offreModel->getEntrepriseOffres($entreprise_id);

        Template::render('entreprise/offers', [
            'title' => 'Mes offres - ESEN NET',
            'css' => ['css/company-offers.css'],
            'entreprise_info' => $entreprise_info,
            'offres' => $offres
        ]);
    }

    public function profile()
    {
        session_start();
        
        if (!isset($_SESSION['entreprise_id'])) {
            header('Location: ' . Template::url('/login'));
            exit();
        }

        $entreprise_id = $_SESSION['entreprise_id'];
        $entreprise_info = $this->entrepriseModel->getInfo($entreprise_id);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->updateProfile();
        }

        Template::render('entreprise/profile', [
            'title' => 'Mon profil - ESEN NET',
            'css' => ['css/company-profile.css'],
            'entreprise_info' => $entreprise_info
        ]);
    }

    public function events()
    {
        session_start();
        
        if (!isset($_SESSION['entreprise_id'])) {
            header('Location: ' . Template::url('/login'));
            exit();
        }

        Template::render('entreprise/events', [
            'title' => 'Événements - ESEN NET',
            'css' => ['css/company-events.css']
        ]);
    }

    public function candidates()
    {
        session_start();
        
        if (!isset($_SESSION['entreprise_id'])) {
            header('Location: ' . Template::url('/login'));
            exit();
        }

        $entreprise_id = $_SESSION['entreprise_id'];
        $candidates = $this->entrepriseModel->getCandidates($entreprise_id);

        Template::render('entreprise/candidates', [
            'title' => 'Candidats - ESEN NET',
            'css' => ['css/company-candidates.css'],
            'candidates' => $candidates
        ]);
    }

    public function settings()
    {
        session_start();
        
        if (!isset($_SESSION['entreprise_id'])) {
            header('Location: ' . Template::url('/login'));
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->updateSettings();
        }

        Template::render('entreprise/settings', [
            'title' => 'Paramètres - ESEN NET',
            'css' => ['css/company-settings.css']
        ]);
    }

    private function updateProfile()
    {
        $entreprise_id = $_SESSION['entreprise_id'];
        $nom = $_POST['nom'] ?? '';
        $description = $_POST['description'] ?? '';
        $secteur = $_POST['secteur'] ?? '';
        $taille = $_POST['taille'] ?? '';
        $site_web = $_POST['site_web'] ?? '';

        require_once __DIR__ . '/../../config/db_connect.php';

        $sql = "UPDATE utilisateur SET nom = ?, description = ?, secteur = ?, taille = ?, site_web = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nom, $description, $secteur, $taille, $site_web, $entreprise_id]);

        Template::setFlash('success', 'Profil mis à jour avec succès');
        header('Location: ' . Template::url('/company/profile'));
        exit();
    }

    private function updateSettings()
    {
        $entreprise_id = $_SESSION['entreprise_id'];
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        require_once __DIR__ . '/../../config/db_connect.php';

        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE utilisateur SET email = ?, motdepasse = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$email, $hashed_password, $entreprise_id]);
        } else {
            $sql = "UPDATE utilisateur SET email = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$email, $entreprise_id]);
        }

        Template::setFlash('success', 'Paramètres mis à jour avec succès');
        header('Location: ' . Template::url('/company/settings'));
        exit();
    }
}
