<?php

namespace App\Controllers;

use App\Core\Template;

class AuthController
{
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            Template::render('auth/login', [    
                'title' => 'Connexion - ESEN NET',
                'css' => ['css/auth.css']
            ]);
        }
    }

    public function authenticate()
    {
        session_start();
        
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // Validation basique
        if (empty($email) || empty($password)) {
            Template::setFlash('error', 'Veuillez remplir tous les champs');
            header('Location: ' . Template::url('/login'));
            exit();
        }
        
        // Inclusion des fonctions d'authentification existantes
        require_once __DIR__ . '/../Views/layouts/components.php';
        require_once __DIR__ . '/../../config/db_connect.php';
        
        // Logique d'authentification existante
        $sql = "SELECT * FROM utilisateur WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['motdepasse'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $user['type'];
            $_SESSION['user_name'] = $user['nom'];
            
            // Redirection selon le type d'utilisateur
            switch ($user['type']) {
                case 'entreprise':
                    $_SESSION['entreprise_id'] = $user['id'];
                    header('Location: ' . Template::url('/company/dashboard'));
                    break;
                case 'etudiant':
                    $_SESSION['student_id'] = $user['id'];
                    header('Location: ' . Template::url('/student/dashboard'));
                    break;
                case 'enseignant':
                    $_SESSION['teacher_id'] = $user['id'];
                    header('Location: ' . Template::url('/teacher/dashboard'));
                    break;
                case 'admin':
                    header('Location: ' . Template::url('/admin/dashboard'));
                    break;
                default:
                    header('Location: ' . Template::url('/dashboard'));
            }
            exit();
        } else {
            Template::setFlash('error', 'Email ou mot de passe incorrect');
            header('Location: ' . Template::url('/login'));
            exit();
        }
    }

    public function logout()
    {
        session_start();
        session_destroy();
        Template::setFlash('success', 'Vous avez été déconnecté avec succès');
        header('Location: ' . Template::url('/login'));
        exit();
    }

    public function signup()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            Template::render('auth/signup', [
                'title' => 'Inscription - ESEN NET',
                'css' => ['css/auth.css']
            ]);
        }
    }

    public function register()
    {
        session_start();
        
        // Récupération des données
        $nom = $_POST['nom'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $type = $_POST['user_type'] ?? '';
        
        // Validation
        if (empty($nom) || empty($email) || empty($password) || empty($type)) {
            Template::setFlash('error', 'Veuillez remplir tous les champs');
            header('Location: ' . Template::url('/signup'));
            exit();
        }
        
        if ($password !== $password_confirm) {
            Template::setFlash('error', 'Les mots de passe ne correspondent pas');
            header('Location: ' . Template::url('/signup'));
            exit();
        }
        
        // Inclusion des dépendances
        require_once __DIR__ . '/../../config/db_connect.php';
        
        // Vérification si l'email existe déjà
        $sql = "SELECT id FROM utilisateur WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            Template::setFlash('error', 'Cet email est déjà utilisé');
            header('Location: ' . Template::url('/signup'));
            exit();
        }
        
        // Insertion du nouvel utilisateur
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO utilisateur (nom, email, motdepasse, type, date_inscription) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        
        if ($stmt->execute([$nom, $email, $hashed_password, $type])) {
            Template::setFlash('success', 'Inscription réussie ! Vous pouvez maintenant vous connecter');
            header('Location: ' . Template::url('/login'));
        } else {
            Template::setFlash('error', 'Erreur lors de l\'inscription');
            header('Location: ' . Template::url('/signup'));
        }
        exit();
    }
}
