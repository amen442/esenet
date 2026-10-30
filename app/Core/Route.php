<?php

namespace App\Core;

class Route
{
    public static function get($path, $controller, $action)
    {
        Router::getInstance()->addRoute('GET', $path, $controller, $action);
    }

    public static function post($path, $controller, $action)
    {
        Router::getInstance()->addRoute('POST', $path, $controller, $action);
    }

    public static function put($path, $controller, $action)
    {
        Router::getInstance()->addRoute('PUT', $path, $controller, $action);
    }

    public static function delete($path, $controller, $action)
    {
        Router::getInstance()->addRoute('DELETE', $path, $controller, $action);
    }
}
