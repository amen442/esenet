<?php

namespace App\Controllers;

use App\Core\Template;

class AdminController
{
    public function dashboard()
    {
        session_start();
        
        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
            header('Location: ' . Template::url('/login'));
            exit();
        }

        Template::render('admin/dashboard', [
            'title' => 'Tableau de bord - Administration',
            'css' => ['css/admin-dashboard.css']
        ]);
    }

    public function users()
    {
        session_start();
        
        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
            header('Location: ' . Template::url('/login'));
            exit();
        }

        Template::render('admin/users', [
            'title' => 'Gestion des utilisateurs - Administration',
            'css' => ['css/admin-users.css']
        ]);
    }

    public function companies()
    {
        session_start();
        
        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
            header('Location: ' . Template::url('/login'));
            exit();
        }

        Template::render('admin/companies', [
            'title' => 'Gestion des entreprises - Administration',
            'css' => ['css/admin-companies.css']
        ]);
    }

    public function offers()
    {
        session_start();
        
        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
            header('Location: ' . Template::url('/login'));
            exit();
        }

        Template::render('admin/offers', [
            'title' => 'Gestion des offres - Administration',
            'css' => ['css/admin-offers.css']
        ]);
    }

    public function events()
    {
        session_start();
        
        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
            header('Location: ' . Template::url('/login'));
            exit();
        }

        Template::render('admin/events', [
            'title' => 'Gestion des événements - Administration',
            'css' => ['css/admin-events.css']
        ]);
    }

    public function reports()
    {
        session_start();
        
        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
            header('Location: ' . Template::url('/login'));
            exit();
        }

        Template::render('admin/reports', [
            'title' => 'Rapports - Administration',
            'css' => ['css/admin-reports.css']
        ]);
    }

    public function settings()
    {
        session_start();
        
        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
            header('Location: ' . Template::url('/login'));
            exit();
        }

        Template::render('admin/settings', [
            'title' => 'Paramètres - Administration',
            'css' => ['css/admin-settings.css']
        ]);
    }
}
