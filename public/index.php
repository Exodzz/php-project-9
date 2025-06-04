<?php

// Подключение автозагрузки через composer
require_once __DIR__ . '/../vendor/autoload.php';

use Hexlet\Code\Controllers\UrlController;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use DI\Container;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Flash\Messages;

session_start();

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeload();

$container = new Container();
AppFactory::setContainer($container);

$container->set('flash', function () {
    return new Messages();
});

/** @var \Slim\App<ContainerInterface> $app */
$app = AppFactory::create();
$loader = new FilesystemLoader(__DIR__ . '/../templates');
$twig = new Environment($loader, [
    'debug' => true,
    'cache' => false
]);
$twig->addExtension(new DebugExtension());

// Добавляем функцию url_for в Twig
$twig->addFunction(new \Twig\TwigFunction('url_for', function (string $routeName, array $params = []) use ($app) {
    $routeParser = $app->getRouteCollector()->getRouteParser();
    return $routeParser->urlFor($routeName, $params);
}));

$urlController = new UrlController($twig, $app);

foreach ($urlController::ROUT_LIST as $routName => $rout) {
    $method = $rout['type'];
    $app->$method($rout['path'], [$urlController, $rout['method']])->setName($routName);
}

$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$app->add(function ($request, $handler) use ($container) {
    $response = $handler->handle($request);
    $flash = $container->get('flash');
    $messages = $flash->getMessages();
    if (!empty($messages)) {
        $flash->clearMessages();
    }
    return $response;
});

$errorMiddleware->setErrorHandler(
    HttpNotFoundException::class,
    function (
        Request $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ) use ($twig) {
        $response = new \Slim\Psr7\Response();
        $body = $twig->render('404.twig');
        $response->getBody()->write($body);
        return $response->withStatus(404);
    }
);

$app->run();
