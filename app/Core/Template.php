<?php

namespace App\Core;

class Template
{
    private static $data = [];
    private static $layout = 'default';

    public static function setLayout($layout)
    {
        self::$layout = $layout;
    }

    public static function set($key, $value)
    {
        self::$data[$key] = $value;
    }

    public static function get($key, $default = null)
    {
        return self::$data[$key] ?? $default;
    }

    public static function render($view, $data = [])
    {
        // Merge data with existing data
        self::$data = array_merge(self::$data, $data);
        
        // Extract data to make variables available in view
        extract(self::$data);
        
        // Start output buffering
        ob_start();
        
        // Include the view file
        $viewPath = __DIR__ . '/../Views/' . $view . '.php';
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            throw new \Exception("View {$view} not found");
        }
        
        // Get the content
        $content = ob_get_clean();
        
        // Include layout if exists
        $layoutPath = __DIR__ . '/../Views/layouts/' . self::$layout . '.php';
        if (file_exists($layoutPath)) {
            include $layoutPath;
        } else {
            echo $content;
        }
    }

    public static function asset($path)
    {
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        return $basePath . '/' . ltrim($path, '/');
    }

    public static function url($path)
    {
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        return $basePath . '/' . ltrim($path, '/');
    }

    public static function escape($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    public static function truncate($string, $length = 100, $suffix = '...')
    {
        if (strlen($string) <= $length) {
            return $string;
        }
        return substr($string, 0, $length) . $suffix;
    }

    public static function formatDate($date, $format = 'd M Y')
    {
        $dateObj = new DateTime($date);
        return $dateObj->format($format);
    }

    public static function flash($type = null)
    {
        session_start();
        
        if ($type === null) {
            // Return all flash messages
            $messages = $_SESSION['flash'] ?? [];
            unset($_SESSION['flash']);
            return $messages;
        }
        
        $message = $_SESSION['flash'][$type] ?? null;
        unset($_SESSION['flash'][$type]);
        return $message;
    }

    public static function setFlash($type, $message)
    {
        session_start();
        $_SESSION['flash'][$type] = $message;
    }
}
