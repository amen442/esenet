<?php

namespace App\Controllers;

use App\Core\Template;

class TeacherController
{
    public function dashboard()
    {
        session_start();
        
        if (!isset($_SESSION['teacher_id'])) {
            header('Location: ' . Template::url('/login'));
            exit();
        }

        Template::render('teacher/dashboard', [
            'title' => 'Tableau de bord - ESEN NET',
            'css' => ['css/teacher-dashboard.css']
        ]);
    }

    public function profile()
    {
        session_start();
        
        if (!isset($_SESSION['teacher_id'])) {
            header('Location: ' . Template::url('/login'));
            exit();
        }

        Template::render('teacher/profile', [
            'title' => 'Mon profil - ESEN NET',
            'css' => ['css/teacher-profile.css']
        ]);
    }

    public function events()
    {
        session_start();
        
        if (!isset($_SESSION['teacher_id'])) {
            header('Location: ' . Template::url('/login'));
            exit();
        }

        Template::render('teacher/events', [
            'title' => 'Événements - ESEN NET',
            'css' => ['css/teacher-events.css']
        ]);
    }

    public function recommendations()
    {
        session_start();
        
        if (!isset($_SESSION['teacher_id'])) {
            header('Location: ' . Template::url('/login'));
            exit();
        }

        Template::render('teacher/recommendations', [
            'title' => 'Recommandations - ESEN NET',
            'css' => ['css/teacher-recommendations.css']
        ]);
    }

    public function recommendationRequests()
    {
        session_start();
        
        if (!isset($_SESSION['teacher_id'])) {
            header('Location: ' . Template::url('/login'));
            exit();
        }

        Template::render('teacher/recommendation-requests', [
            'title' => 'Demandes de recommandation - ESEN NET',
            'css' => ['css/teacher-recommendation-requests.css']
        ]);
    }

    public function messages()
    {
        session_start();
        
        if (!isset($_SESSION['teacher_id'])) {
            header('Location: ' . Template::url('/login'));
            exit();
        }

        Template::render('teacher/messages', [
            'title' => 'Messages - ESEN NET',
            'css' => ['css/teacher-messages.css']
        ]);
    }

    public function settings()
    {
        session_start();
        
        if (!isset($_SESSION['teacher_id'])) {
            header('Location: ' . Template::url('/login'));
            exit();
        }

        Template::render('teacher/settings', [
            'title' => 'Paramètres - ESEN NET',
            'css' => ['css/teacher-settings.css']
        ]);
    }
}
