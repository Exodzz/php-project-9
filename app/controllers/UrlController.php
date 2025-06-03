<?php

namespace Hexlet\Code\Controllers;

use Hexlet\Code\Connection;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class UrlController
{
    private Environment $view;
    private Connection $db;

    public function __construct(Environment $view)
    {
        $this->view = $view;
        $this->db = new Connection();
    }

    public function index(Request $request, Response $response): Response
    {
        $body = $this->view->render('index.twig');
        $response->getBody()->write($body);
        return $response;
    }

    public function list(Request $request, Response $response): Response
    {
        $urls = $this->db->getAllUrls();
        foreach ($urls as &$url) {
            $url['check'] = $this->db->getLastUrlCheck($url['id']);
        }
        $body = $this->view->render('urls/index.twig', [
            'urls' => $urls,
            'title' => 'Сайты'
        ]);
        $response->getBody()->write($body);
        return $response;
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $url = $this->db->getUrlById($id);

        if (!$url) {
            return $this->notFound($request, $response);
        }

        $checks = $this->db->getUrlChecks($id);
        $body = $this->view->render('urls/detail.twig', [
            'url' => $url,
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
    public function notFound(Request $request, Response $response): Response
    {
        $response = $response->withStatus(404);
        $body = $this->view->render('404.twig');
        $response->getBody()->write($body);
        return $response;
    }
}
