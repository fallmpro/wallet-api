<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Router;

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

header('Content-Type: application/json');

$wallets = [
    1 => ['id' => 1, 'owner' => 'Alice', 'balance' => 1000],
    2 => ['id' => 2, 'owner' => 'Bob', 'balance' => 500],
    3 => ['id' => 3, 'owner' => 'Charlie', 'balance' => 2000],
];

$router = new Router();

$router->get('#^/wallets$#', function () use ($wallets) {
    echo json_encode(array_values($wallets));
});

$router->get('#^/wallets/(\d+)$#', function (string $id) use ($wallets) {
    $id = (int) $id;
    $wallet = $wallets[$id] ?? null;
    if ($wallet !== null) {
        echo json_encode($wallet);
    } else {
        http_response_code(404);
        echo json_encode(['error' => "Wallet $id introuvable"]);
    }
});

$router->get('#^/wallets/(\d+)/balance$#', function (string $id) use ($wallets) {
    $id = (int) $id;
    if (isset($wallets[$id])) {
        echo json_encode(['balance' => $wallets[$id]['balance']]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => "Wallet $id introuvable"]);
    }
});


$router->get('#^/wallets/(\d+)/owner$#', function (string $id) use ($wallets) {
    $id = (int) $id;
    if (isset($wallets[$id])) {
        echo json_encode(['owner' => $wallets[$id]['owner']]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => "Owner $id introuvable"]);
    }
});
$router->dispatch($method, $path); // toujours en dernier