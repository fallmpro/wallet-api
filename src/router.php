<?php

declare(strict_types=1);

namespace App;

class Router
{
    // Liste des routes (privée : invisible hors de la classe)
    private array $routes = [];

    // Enregistre une route GET
    public function get(string $pattern, callable $handler): void
    {
        $this->routes[] = ['method' => 'GET', 'pattern' => $pattern, 'handler' => $handler];
    }

    // Enregistre une route POST
    public function post(string $pattern, callable $handler): void
    {
        $this->routes[] = ['method' => 'POST', 'pattern' => $pattern, 'handler' => $handler];
    }
    
    // Trouve la route qui correspond et l'exécute
    public function dispatch(string $method, string $path): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], $path, $matches)) {
                $params = array_slice($matches, 1); // retire $matches[0] (le chemin complet)
                $route['handler'](...$params);      // appelle la fonction avec les paramètres
                return;                             // trouvé : on s'arrête
            }
        }

        http_response_code(404);
        echo json_encode(['error' => 'Route introuvable']);
    }
}