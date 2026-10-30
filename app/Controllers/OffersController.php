<?php

namespace App\Controllers;

use App\Core\Template;
use App\Models\Entreprise;
use App\Models\Offre;

class OffersController
{
    private $entrepriseModel;
    private $offreModel;

    public function __construct()
    {
        $this->entrepriseModel = new Entreprise();
        $this->offreModel = new Offre();
    }

    public function index()
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

    public function create()
    {
        session_start();
        
        if (!isset($_SESSION['entreprise_id'])) {
            header('Location: ' . Template::url('/login'));
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $entreprise_id = $_SESSION['entreprise_id'];
            $titre = $_POST['titre'] ?? '';
            $type = $_POST['type'] ?? '';
            $date_exp = $_POST['date_exp'] ?? '';
            $statut = $_POST['statut'] ?? 'OUVERT';
            $image = null;

            // Validation
            if (empty($titre) || empty($type) || empty($date_exp)) {
                Template::setFlash('error', 'Veuillez remplir tous les champs obligatoires');
                header('Location: ' . Template::url('/offers'));
                exit();
            }

            // Gestion de l'upload d'image
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../../app/uploads/offres/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array($file_extension, $allowed_extensions)) {
                    $file_name = uniqid() . '.' . $file_extension;
                    $file_path = $upload_dir . $file_name;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                        $image = 'app/uploads/offres/' . $file_name;
                    }
                }
            }

            if ($this->offreModel->create($entreprise_id, $titre, $type, $date_exp, $statut, $image)) {
                Template::setFlash('success', 'Offre créée avec succès !');
            } else {
                Template::setFlash('error', 'Erreur lors de la création de l\'offre');
            }
        }

        header('Location: ' . Template::url('/offers'));
        exit();
    }

    public function update()
    {
        session_start();
        
        if (!isset($_SESSION['entreprise_id'])) {
            header('Location: ' . Template::url('/login'));
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $offre_id = $_POST['offre_id'] ?? '';
            $titre = $_POST['titre'] ?? '';
            $type = $_POST['type'] ?? '';
            $date_exp = $_POST['date_exp'] ?? '';
            $statut = $_POST['statut'] ?? '';
            $image = null;

            // Validation
            if (empty($offre_id) || empty($titre) || empty($type) || empty($date_exp)) {
                Template::setFlash('error', 'Veuillez remplir tous les champs obligatoires');
                header('Location: ' . Template::url('/offers'));
                exit();
            }

            // Gestion de l'upload d'image
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../../app/uploads/offres/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array($file_extension, $allowed_extensions)) {
                    $file_name = uniqid() . '.' . $file_extension;
                    $file_path = $upload_dir . $file_name;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                        $image = 'app/uploads/offres/' . $file_name;
                    }
                }
            }

            if ($this->offreModel->update($offre_id, $titre, $type, $date_exp, $statut, $image)) {
                Template::setFlash('success', 'Offre mise à jour avec succès !');
            } else {
                Template::setFlash('error', 'Erreur lors de la mise à jour de l\'offre');
            }
        }

        header('Location: ' . Template::url('/offers'));
        exit();
    }

    public function delete()
    {
        session_start();
        
        if (!isset($_SESSION['entreprise_id'])) {
            header('Location: ' . Template::url('/login'));
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $offre_id = $_POST['offre_id'] ?? '';

            if (empty($offre_id)) {
                Template::setFlash('error', 'ID d\'offre manquant');
            } elseif ($this->offreModel->delete($offre_id)) {
                Template::setFlash('success', 'Offre supprimée avec succès !');
            } else {
                Template::setFlash('error', 'Erreur lors de la suppression de l\'offre');
            }
        }

        header('Location: ' . Template::url('/offers'));
        exit();
    }

    public function show()
    {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . Template::url('/login'));
            exit();
        }

        $offre_id = $_GET['id'] ?? '';
        
        if (empty($offre_id)) {
            Template::setFlash('error', 'ID d\'offre manquant');
            header('Location: ' . Template::url('/offers'));
            exit();
        }

        $offre = $this->offreModel->getById($offre_id);
        
        if (!$offre) {
            Template::setFlash('error', 'Offre non trouvée');
            header('Location: ' . Template::url('/offers'));
            exit();
        }

        Template::render('offers/show', [
            'title' => $offre['titre'] . ' - ESEN NET',
            'css' => ['css/offer-detail.css'],
            'offre' => $offre
        ]);
    }
}
