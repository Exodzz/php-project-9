<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Hexlet\Code\Connection;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;


$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeload();
$PDO = new Connection();
$app = AppFactory::create();
$loader = new FilesystemLoader(__DIR__ . '/../templates');
$view = new Environment($loader);
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) use ($view){
    $body = $view->render('index.twig');
    $response->write($body);
    return $response;
});
$app->get('/urls', function ($request, $response) use ($view){
    $body = $view->render('urls/index.twig');
    $response->write($body);
    return $response;
});
$app->get('/urls/{id}', function ($request, $response,$args) use ($view){
    $body = $view->render('urls/detail.twig',$args);
    $response->write($body);
    return $response;
});
$app->run();
