<?php

use Slim\Factory\AppFactory;
use App\Database\Connection;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Controller\PostIndexController;
use App\Service\PostIndexService;
use App\Repository\PostIndexRepository;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();
$pdo = Connection::make();
$repository = new PostIndexRepository($pdo);
$service = new PostIndexService($repository);
$controller = new PostIndexController($service);

$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$app->add(function (Request $request, $handler): Response {
    $response = $handler->handle($request);

    return $response
        ->withHeader('Access-Control-Allow-Origin', 'http://localhost:5173')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, DELETE, OPTIONS')
        ->withHeader('Access-Control-Allow-Credentials', 'true');
});

$app->addBodyParsingMiddleware();

/**
 * Health check endpoint
 */
$app->get('/health', function ($request, $response) {
    $response->getBody()->write('OK');
    return $response;
});

/**
 * Example DB check
 */
$app->get('/db-check', function ($request, $response) use ($pdo) {
    $stmt = $pdo->query("SELECT 1");
    $response->getBody()->write('DB works');
    return $response;
});

$app->get('/post-indexes', [$controller, 'list']);
$app->post('/post-indexes', [$controller, 'add']);
$app->delete('/post-indexes', [$controller, 'delete']);

$app->run();
