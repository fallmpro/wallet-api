<?php

declare(strict_types=1);

namespace App;

use PDO; // PDO est une classe intégrée à PHP (namespace global)

class Database
{
    // static : on l'appelle sans créer d'objet → Database::connect()
    public static function connect(): PDO
    {
        return new PDO(
            'pgsql:host=localhost;port=5432;dbname=wallet', // où est la base
            'postgres',                                     // utilisateur
            'root',                                         // mot de passe (local uniquement !)
            [
                // Une erreur SQL lève une exception au lieu d'échouer en silence
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                // Les résultats arrivent en tableaux ['id' => 1, 'owner' => 'Alice', ...]
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
}