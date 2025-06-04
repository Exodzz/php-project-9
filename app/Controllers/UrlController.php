<?php

namespace Hexlet\Code\Controllers;

use Hexlet\Code\Checker;
use Hexlet\Code\Connection;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Flash\Messages;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Valitron\Validator;

class UrlController
{
    public const ROUT_LIST = [
        'main'        => [
            'path'   => '/',
            'type'   => 'get',
            'method' => 'index',
        ],
        'add'         => [
            'path'   => '/urls',
            'type'   => 'post',
            'method' => 'add',
        ],
        'url.index'   => [
            'path'   => '/urls',
            'type'   => 'get',
            'method' => 'list',
        ],
        'urls.show'   => [
            'path'   => '/urls/{id}',
            'type'   => 'get',
            'method' => 'show',
        ],
        'urls.checks' => [
            'path'   => '/urls/{id}/checks',
            'type'   => 'post',
            'method' => 'check',
        ]
    ];
    /** @var App<ContainerInterface> */
    public App $app;
    private Environment $view;
    private Connection $db;
    private Messages $flash;

    /**
     * @param App<ContainerInterface> $app
     */
    public function __construct(Environment $view, App $app)
    {
        $this->view = $view;
        $this->app = $app;
        $this->db = new Connection();
        $container = $this->app->getContainer();
        if (!$container instanceof ContainerInterface) {
            throw new \RuntimeException('Container is not initialized');
        }
        $this->setFlash($container);
    }

    private function setFlash(ContainerInterface $container)
    {
        $this->flash = new Messages();
        $container->set('flash', function () {
            return $this->flash;
        });
    }

    private function render(string $template, array $data = []): string
    {
        $data['flash'] = $this->flash->getMessages();
        return $this->view->render($template, $data);
    }

    public function index(Request $request, Response $response): Response
    {
        $body = $this->render('index.twig', ['main' => true]);
        $response->getBody()->write($body);
        return $response;
    }

    private function redirectToRoute(string $routeName, array $params = [], int $status = 302): Response
    {
        $routeParser = $this->app->getRouteCollector()->getRouteParser();
        $url = $routeParser->urlFor($routeName, $params);
        return $this->app->getResponseFactory()
            ->createResponse()
            ->withHeader('Location', $url)
            ->withStatus($status);
    }

    public function add(Request $request, Response $response): Response
    {
        $parsedBody = $request->getParsedBody();
        if (!is_array($parsedBody) || !isset($parsedBody['url']) || !is_array($parsedBody['url'])) {
            $this->flash->addMessageNow('danger', 'Некорректные данные формы');
            $body = $this->render('index.twig', ['main' => true]);
            $response->getBody()->write($body);
            return $response->withStatus(422);
        }

        $urls = $parsedBody['url'];
        $validation = new Validator(
            [
                'name'  => $urls['name'] ?? '',
                'count' => strlen((string)($urls['name'] ?? ''))
            ]
        );
        $validation->rule('required', 'name')
            ->rule('lengthMax', 'count.*', 255)
            ->rule('url', 'name', '');
        if (!$validation->validate()) {
            $this->flash->addMessageNow('danger', 'Некорректный URL');
            $body = $this->render('index.twig', ['main' => true]);
            $response->getBody()->write($body);
            return $response->withStatus(422);
        }

        try {
            $id = $this->db->createUrl((string)$urls['name']);
            $this->flash->addMessage('success', 'Страница успешно добавлена');
            return $this->redirectToRoute('urls.show', ['id' => $id]);
        } catch (\Exception | \RuntimeException $exception) {
            $this->flash->addMessage('danger', 'Страница уже существует');
            $id = $exception->getMessage();
            return $this->redirectToRoute('urls.show', ['id' => $id]);
        }
    }

    public function list(Request $request, Response $response): Response
    {
        $urls = $this->db->getAllUrls();
        foreach ($urls as &$url) {
            $url['name'] = parse_url($url['name'])['host'];
            $url['check'] = $this->db->getLastUrlCheck($url['id']);
        }
        $body = $this->render('urls/index.twig', [
            'urls'  => $urls,
            'title' => 'Сайты'
        ]);
        $response->getBody()->write($body);
        return $response;
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $url = $this->db->getUrlById($id);
        if (!$url) {
            return $this->notFound($response);
        }
        $url['name'] = parse_url($url['name'])['host'];

        $checks = $this->db->getUrlChecks($id);
        $body = $this->render('urls/detail.twig', [
            'url'    => $url,
            'checks' => $checks
        ]);
        $response->getBody()->write($body);
        return $response;
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function notFound(Response $response): Response
    {
        $response = $response->withStatus(404);
        $body = $this->render('404.twig');
        $response->getBody()->write($body);
        return $response;
    }

    public function check(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $url = $this->db->getUrlById($id);
        if (!$url) {
            return $this->notFound($response);
        }

        $checker = new Checker();
        $checkData = $checker->check($url['name']);

        try {
            $this->db->createUrlCheck($id, $checkData);
            $this->flash->addMessage('success', 'Страница успешно проверена');
        } catch (\Exception $e) {
            $this->flash->addMessage('danger', 'Ошибка при проверке: ' . $e->getMessage());
        }

        $errors = $checker->getErrors();
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->flash->addMessage('warning', $error);
            }
        }

        return $this->redirectToRoute('urls.show', ['id' => $id]);
    }
}
