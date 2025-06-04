<?php

namespace Hexlet\Code;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\GuzzleException;
use DOMDocument;
use DOMXPath;

class Checker
{
    private Client $client;
    private array $errors = [];

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 5,
            'connect_timeout' => 5,
            'http_errors' => false,
            'verify' => false
        ]);
    }

    public function check(string $url): array
    {
        try {
            $response = $this->client->get($url);
            $statusCode = $response->getStatusCode();
            $body = (string)$response->getBody();

            return [
                'status_code' => $statusCode,
                'h1' => $this->getH1($body),
                'title' => $this->getTitle($body),
                'description' => $this->getDescription($body)
            ];
        } catch (ConnectException $e) {
            $this->errors[] = "Не удалось подключиться к сайту";
        } catch (RequestException $e) {
            $this->errors[] = "Ошибка при запросе";
        } catch (GuzzleException $e) {
            $this->errors[] = "Произошла ошибка";
        }

        return [
            'status_code' => 500,
            'h1' => null,
            'title' => null,
            'description' => null
        ];
    }

    private function getH1(string $html): ?string
    {
        try {
            $dom = new DOMDocument();
            @$dom->loadHTML($html, LIBXML_NOERROR);
            $xpath = new DOMXPath($dom);
            $h1 = $xpath->query('//h1')->item(0);
            return $h1 ? trim($h1->textContent) : null;
        } catch (\Exception $e) {
            $this->errors[] = "Ошибка при получении h1: {$e->getMessage()}";
            return null;
        }
    }

    private function getTitle(string $html): ?string
    {
        try {
            $dom = new DOMDocument();
            @$dom->loadHTML($html, LIBXML_NOERROR);
            $xpath = new DOMXPath($dom);
            $title = $xpath->query('//title')->item(0);
            return $title ? trim($title->textContent) : null;
        } catch (\Exception $e) {
            $this->errors[] = "Ошибка при получении title: {$e->getMessage()}";
            return null;
        }
    }

    private function getDescription(string $html): ?string
    {
        try {
            $dom = new DOMDocument();
            @$dom->loadHTML($html, LIBXML_NOERROR);
            $xpath = new DOMXPath($dom);
            $meta = $xpath->query('//meta[@name="description"]')->item(0);
            return $meta ? trim($meta->getAttribute('content')) : null;
        } catch (\Exception $e) {
            $this->errors[] = "Ошибка при получении description: {$e->getMessage()}";
            return null;
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
