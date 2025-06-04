<?php

namespace Hexlet\Code\Controllers;

use Hexlet\Code\Checker;
use Hexlet\Code\Connection;
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
    public App $app;
    private Environment $view;
    private Connection $db;

    public function __construct(Environment $view, App $app)
    {
        $this->view = $view;
        $this->app = $app;
        $this->db = new Connection();
        $this->app->getContainer()->set('flash', function () {
            return new Messages();
        });
    }

    private function render(string $template, array $data = []): string
    {
        $flash = $this->app->getContainer()->get('flash');
        $data['flash'] = $flash->getMessages();
        return $this->view->render($template, $data);
    }

    public function index(Request $request, Response $response): Response
    {
        $body = $this->render('index.twig', ['main' => true]);
        $response->getBody()->write($body);
        $this->app->getContainer()->get('flash');

        return $response;
    }

    private function redirectToRoute(string $routeName, array $params = []): Response
    {
        $routeParser = $this->app->getRouteCollector()->getRouteParser();
        $url = $routeParser->urlFor($routeName, $params);
        return $this->app->getResponseFactory()
            ->createResponse()
            ->withHeader('Location', $url)
            ->withStatus(302);
    }

    public function add(Request $request, Response $response): Response
    {
        $urls = $request->getParsedBody()['url'];
        $validation = new Validator(
            [
                'name'  => $urls['name'] ?? '',
                'count' => strlen((string)$urls['name'])
            ]
        );
        $validation->rule('required', 'name')
            ->rule('lengthMax', 'count.*', 255)
            ->rule('url', 'name');
        if (!$validation->validate()) {
            foreach ($validation->errors('name') as $message) {
                $this->app->getContainer()->get('flash')
                    ->addMessageNow('danger', $message);
            }
            $body = $this->render('index.twig', [
                'main'   => true,
            ]);
            $response->getBody()->write($body);
        } else {
            try {
                $this->db->createUrl((string)$urls['name']);
                $this->app->getContainer()->get('flash')
                    ->addMessage('success', 'Страница успешно добавлена');
                return $this->redirectToRoute('url.index');
            } catch (\Exception | \RuntimeException $exception) {
                $this->app->getContainer()->get('flash')
                    ->addMessageNow('danger', $exception->getMessage());
                $body = $this->render('index.twig', [
                    'main'   => true,
                ]);

                $response->getBody()->write($body);
            }
        }
        return $response;
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
            $this->app->getContainer()->get('flash')
                ->addMessage('success', 'Страница успешно проверена');
        } catch (\Exception $e) {
            $this->app->getContainer()->get('flash')
                ->addMessage('danger', 'Ошибка при проверке: ' . $e->getMessage());
        }

        $errors = $checker->getErrors();
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->app->getContainer()->get('flash')
                    ->addMessage('warning', $error);
            }
        }

        return $this->redirectToRoute('urls.show', ['id' => $id]);
    }
}
