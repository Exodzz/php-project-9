<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Hexlet\Code\Connection;
use Hexlet\Code\Controllers\UrlController;
use Slim\Factory\AppFactory;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use DI\Container;
use GuzzleHttp\Client;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeload();

$container = new Container();
AppFactory::setContainer($container);

$PDO = new Connection();
$app = AppFactory::create();
$loader = new FilesystemLoader(__DIR__ . '/../templates');
$view = new Environment($loader);

$urlController = new UrlController($view);


$app->get('/', [$urlController, 'index'])->setName('main');
$app->get('/urls', [$urlController, 'list'])->setName('url.index');
$app->get('/urls/{id}', [$urlController, 'show'])->setName('urls.show');
$app->post('/urls/{id}/check', [$urlController, 'show'])->setName('urls.checks');

$app->run();
