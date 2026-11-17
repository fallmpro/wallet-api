<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

header('Content-Type: application/json');

// Fausses données (montants en centimes : 1000 = 10,00 €)
$wallets = [
    1 => ['id' => 1, 'owner' => 'Alice', 'balance' => 1000],
    2 => ['id' => 2, 'owner' => 'Bob', 'balance' => 500],
    3 => ['id' => 3, 'owner' => 'Charlie', 'balance' => 2000],
];


if ($method === 'GET' && $path === '/wallets') {
    // Liste de tous les wallets
    echo json_encode(array_values($wallets));

} elseif ($method === 'GET' && preg_match('#^/wallets/(\d+)$#', $path, $matches)) {
    // Un wallet précis : /wallets/1, /wallets/2...
    $id = (int) $matches[1];

    if (isset($wallets[$id])) {
        echo json_encode($wallets[$id]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => "Wallet $id introuvable"]);
    }

} elseif ($method === 'GET' && preg_match('#^/wallets/(\d+)/balance$#', $path, $matches)) {
    // Solde d'un wallet : /wallets/1/balance
    $id = (int) $matches[1];

    if (isset($wallets[$id])) {
        echo json_encode(['balance' => $wallets[$id]['balance']]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => "Wallet $id introuvable"]);
    }

}elseif ($method === 'GET' && preg_match('#^/wallets/(\d+)/owner$#', $path, $matches)) {
   // Changement du propriétaire d'un wallet : /wallets/1/owner
    $id = (int) $matches[1];

    if (isset($wallets[$id])) {
        echo json_encode((['owner' => $wallets[$id]['owner']]));
    } else {
        http_response_code(404);
        echo json_encode(['error' => "Owner' $id introuvable"]);
    }
    

} else {
    http_response_code(404);
    echo json_encode(['error' => 'Route introuvable']);
}