<?php

require_once 'app/Core/Router.php';
require_once 'app/Core/Route.php';

use App\Core\Router;

// Create router instance
$router = new Router();

// Define routes

// Home routes
Route::get('/', 'HomeController', 'index');

// Auth routes
Route::get('/login', 'AuthController', 'login');
Route::post('/login', 'AuthController', 'authenticate');
Route::get('/logout', 'AuthController', 'logout');
Route::get('/signup', 'AuthController', 'signup');
Route::post('/signup', 'AuthController', 'register');

// Dashboard routes
Route::get('/dashboard', 'DashboardController', 'index');

// Offers routes
Route::get('/offers', 'OffersController', 'index');
Route::post('/offers/create', 'OffersController', 'create');
Route::post('/offers/update', 'OffersController', 'update');
Route::post('/offers/delete', 'OffersController', 'delete');
Route::get('/offers/{id}', 'OffersController', 'show');

// Student routes
Route::get('/student/offers', 'StudentController', 'offers');
Route::get('/student/dashboard', 'StudentController', 'dashboard');
Route::get('/student/profile', 'StudentController', 'profile');
Route::get('/student/events', 'StudentController', 'events');
Route::get('/student/messages', 'StudentController', 'messages');
Route::get('/student/network', 'StudentController', 'network');
Route::get('/student/settings', 'StudentController', 'settings');

// Company routes
Route::get('/company/offers', 'CompanyController', 'offers');
Route::get('/company/dashboard', 'CompanyController', 'dashboard');
Route::get('/company/profile', 'CompanyController', 'profile');
Route::get('/company/events', 'CompanyController', 'events');
Route::get('/company/candidates', 'CompanyController', 'candidates');
Route::get('/company/settings', 'CompanyController', 'settings');

// Teacher routes
Route::get('/teacher/dashboard', 'TeacherController', 'dashboard');
Route::get('/teacher/profile', 'TeacherController', 'profile');
Route::get('/teacher/events', 'TeacherController', 'events');
Route::get('/teacher/recommendations', 'TeacherController', 'recommendations');
Route::get('/teacher/recommendation-requests', 'TeacherController', 'recommendationRequests');
Route::get('/teacher/messages', 'TeacherController', 'messages');
Route::get('/teacher/settings', 'TeacherController', 'settings');

// Admin routes
Route::get('/admin/dashboard', 'AdminController', 'dashboard');
Route::get('/admin/users', 'AdminController', 'users');
Route::get('/admin/companies', 'AdminController', 'companies');
Route::get('/admin/offers', 'AdminController', 'offers');
Route::get('/admin/events', 'AdminController', 'events');
Route::get('/admin/reports', 'AdminController', 'reports');
Route::get('/admin/settings', 'AdminController', 'settings');

// API routes
Route::get('/api/check-new-items', 'ApiController', 'checkNewItems');

// Dispatch the request
$router->dispatch();
