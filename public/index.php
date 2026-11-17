<?php

// ============================================================
// FRONT CONTROLLER : toutes les requêtes HTTP arrivent ici
// ============================================================

// PHP refuse les conversions de type silencieuses ("12" ne devient pas 12 tout seul)
declare(strict_types=1);

// Connexion à PostgreSQL

// Charge l'autoloader de Composer : il trouvera src/Router.php tout seul
require __DIR__ . '/../vendor/autoload.php';

// On indique qu'on va utiliser la classe Router (fichier src/Router.php)
use App\Router;
use App\Database;

// ------------------------------------------------------------
// 1. LIRE LA REQUÊTE
// ------------------------------------------------------------

// La méthode HTTP : GET, POST...
$method = $_SERVER['REQUEST_METHOD'];

// Le chemin demandé, sans la partie après le "?" (ex : "/wallets/3")
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Toutes nos réponses seront en JSON
header('Content-Type: application/json');

$pdo = Database::connect();


// ------------------------------------------------------------
// 2. LES DONNÉES (fausses pour l'instant, remplacées par PostgreSQL à l'étape 3)
// Montants en CENTIMES et en entiers : 1000 = 10,00 €
// ------------------------------------------------------------
$wallets = [
    1 => ['id' => 1, 'owner' => 'Alice', 'balance' => 1000],
    2 => ['id' => 2, 'owner' => 'Bob', 'balance' => 500],
    3 => ['id' => 3, 'owner' => 'Charlie', 'balance' => 2000],
];

// ------------------------------------------------------------
// 3. LES ROUTES
// $router->get(regex, fonction) = "si une requête GET correspond à cette regex,
// exécute cette fonction". (\d+) capture un nombre et le passe en paramètre.
// ------------------------------------------------------------
$router = new Router();

// GET /wallets → liste de tous les wallets
$router->get('#^/wallets$#', function () use ($pdo) {
    $stmt = $pdo->query('SELECT id, owner, balance FROM wallets ORDER BY id');
    echo json_encode($stmt->fetchAll()); // fetchAll = toutes les lignes du résultat
});

// GET /wallets/{id} → un wallet précis
$router->get('#^/wallets/(\d+)$#', function (string $id) use ($wallets) {
    $id = (int) $id;                    // "3" (texte capturé par la regex) → 3 (entier)
    $wallet = $wallets[$id] ?? null;    // le wallet, ou null s'il n'existe pas

    if ($wallet !== null) {
        echo json_encode($wallet);      // trouvé → 200 par défaut
    } else {
        http_response_code(404);        // pas trouvé → 404 Not Found
        echo json_encode(['error' => "Wallet $id introuvable"]);
    }
});

// GET /wallets/{id}/balance → seulement le solde
$router->get('#^/wallets/(\d+)/balance$#', function (string $id) use ($wallets) {
    $id = (int) $id;

    if (isset($wallets[$id])) {
        // $wallets[$id] = le wallet, ['balance'] = la case balance de ce wallet
        echo json_encode(['balance' => $wallets[$id]['balance']]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => "Wallet $id introuvable"]);
    }
});

// GET /wallets/{id}/owner → seulement le propriétaire
$router->get('#^/wallets/(\d+)/owner$#', function (string $id) use ($wallets) {
    $id = (int) $id;

    if (isset($wallets[$id])) {
        echo json_encode(['owner' => $wallets[$id]['owner']]);
    } else {
        http_response_code(404);
        // ⚠️ CORRIGÉ : c'est le WALLET qui est introuvable
        echo json_encode(['error' => "Wallet $id introuvable"]);
    }
});

// POST /wallets → créer un wallet. Corps attendu : {"owner": "Claude"}
$router->post('#^/wallets$#', function () {
    // Lit le corps de la requête (texte JSON) et le transforme en tableau PHP
    $data = json_decode(file_get_contents('php://input'), true);

    // GARDE : owner manquant → 400 Bad Request, et on s'arrête
    if (!isset($data['owner'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Champ owner requis']);
        return; // le wallet n'est jamais créé
    }

    // Ici, on est sûr que owner existe
    $wallet = [
        'id' => 4,                   // en dur pour l'instant ; PostgreSQL le générera
        'owner' => $data['owner'],   // le nom envoyé par le client
        'balance' => 0,              // un nouveau wallet commence TOUJOURS vide
    ];

    http_response_code(201);         // 201 Created
    echo json_encode($wallet);
});

// POST /wallets/{id}/deposits → déposer de l'argent. Corps attendu : {"amount": 500}
// ⚠️ CORRIGÉ : /deposits au pluriel (convention REST)
// &$wallets : passage par RÉFÉRENCE, pour modifier le vrai tableau et pas une copie
$router->post('#^/wallets/(\d+)/deposits$#', function (string $id) use (&$wallets) {
    $id = (int) $id;

    // GARDE 1 : le wallet doit exister → sinon 404
    if (!isset($wallets[$id])) {
        http_response_code(404);
        echo json_encode(['error' => "Wallet $id introuvable"]);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    // GARDE 2 : amount présent, ENTIER, et strictement positif → sinon 400
    // || s'arrête au premier "vrai" : si amount manque, PHP ne lit jamais $data['amount']
    if (!isset($data['amount']) || !is_int($data['amount']) || $data['amount'] <= 0) {
        http_response_code(400);   // ⚠️ CORRIGÉ : remis à la ligne
        echo json_encode(['error' => 'Montant invalide : entier positif en centimes attendu']);
        return;
    }

    // ⚠️ CORRIGÉ : plus de (float). Argent = entiers en centimes, toujours
    $wallets[$id]['balance'] += $data['amount'];

    // ⚠️ CORRIGÉ : on renvoie le wallet complet, plus utile au client que le solde seul
    echo json_encode($wallets[$id]);
});

// ------------------------------------------------------------
// 4. LANCER LE ROUTEUR : cherche la route qui correspond et l'exécute.
// TOUJOURS EN DERNIER : les routes doivent être enregistrées avant.
// ------------------------------------------------------------
$router->dispatch($method, $path);