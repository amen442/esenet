<?php

namespace App\Controllers;

use App\Core\Template;
use App\Models\Offre;
use App\Models\Candidature;

class StudentController
{
    private $offreModel;
    private $candidatureModel;

    public function __construct()
    {
        $this->offreModel = new Offre();
        $this->candidatureModel = new Candidature();
    }

    public function dashboard()
    {
        session_start();
        
        if (!isset($_SESSION['student_id'])) {
            header('Location: ' . Template::url('/login'));
            exit();
        }

        $student_id = $_SESSION['student_id'];
        $recent_offers = $this->offreModel->getRecentOffres(6);
        $my_candidatures = $this->candidatureModel->getStudentCandidatures($student_id);

        Template::render('student/dashboard', [
            'title' => 'Tableau de bord - ESEN NET',
            'css' => ['css/student-dashboard.css'],
            'recent_offers' => $recent_offers,
            'my_candidatures' => $my_candidatures
        ]);
    }

    public function offers()
    {
        session_start();
        
        if (!isset($_SESSION['student_id'])) {
            header('Location: ' . Template::url('/login'));
            exit();
        }

        $offers = $this->offreModel->getAllActiveOffers();

        Template::render('student/offers', [
            'title' => 'Offres de stage - ESEN NET',
            'css' => ['css/student-offers.css'],
            'offers' => $offers
        ]);
    }

    public function profile()
    {
        session_start();
        
        if (!isset($_SESSION['student_id'])) {
            header('Location: ' . Template::url('/login'));
            exit();
        }

        Template::render('student/profile', [
            'title' => 'Mon profil - ESEN NET',
            'css' => ['css/student-profile.css']
        ]);
    }

    public function events()
    {
        session_start();
        
        if (!isset($_SESSION['student_id'])) {
            header('Location: ' . Template::url('/login'));
            exit();
        }

        Template::render('student/events', [
            'title' => 'Événements - ESEN NET',
            'css' => ['css/student-events.css']
        ]);
    }

    public function messages()
    {
        session_start();
        
        if (!isset($_SESSION['student_id'])) {
            header('Location: ' . Template::url('/login'));
            exit();
        }

        Template::render('student/messages', [
            'title' => 'Messages - ESEN NET',
            'css' => ['css/student-messages.css']
        ]);
    }

    public function network()
    {
        session_start();
        
        if (!isset($_SESSION['student_id'])) {
            header('Location: ' . Template::url('/login'));
            exit();
        }

        Template::render('student/network', [
            'title' => 'Réseau - ESEN NET',
            'css' => ['css/student-network.css']
        ]);
    }

    public function settings()
    {
        session_start();
        
        if (!isset($_SESSION['student_id'])) {
            header('Location: ' . Template::url('/login'));
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->updateSettings();
        }

        Template::render('student/settings', [
            'title' => 'Paramètres - ESEN NET',
            'css' => ['css/student-settings.css']
        ]);
    }

    private function updateSettings()
    {
        $student_id = $_SESSION['student_id'];
        $nom = $_POST['nom'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        require_once __DIR__ . '/../../config/db_connect.php';

        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE utilisateur SET nom = ?, email = ?, motdepasse = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$nom, $email, $hashed_password, $student_id]);
        } else {
            $sql = "UPDATE utilisateur SET nom = ?, email = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$nom, $email, $student_id]);
        }

        Template::setFlash('success', 'Paramètres mis à jour avec succès');
        header('Location: ' . Template::url('/student/settings'));
        exit();
    }
}
