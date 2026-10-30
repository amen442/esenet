<?php

namespace App\Controllers;

use App\Models\Entreprise;
use App\Models\Dashboard;

class DashboardController
{
    private $entrepriseModel;
    private $dashboardModel;

    public function __construct()
    {
        $this->entrepriseModel = new Entreprise();
        $this->dashboardModel = new Dashboard();
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
        $stats = $this->dashboardModel->getStats($entreprise_id);

        require __DIR__ . '/../Views/admin/dashboard.php';
    }
}
