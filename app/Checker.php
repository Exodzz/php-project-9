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
    public const FIELDS_LENGTH = [
        'h1'          => 255,
        'title'       => 255,
        'description' => 255,
    ];
    private Client $client;
    private array $errors = [];
    private string $encoding = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';

    public function __construct()
    {
        $this->client = new Client([
            'timeout'         => 5,
            'connect_timeout' => 5,
            'http_errors'     => false,
            'verify'          => false
        ]);
    }

    public function check(string $url): array
    {
        try {
            $response = $this->client->get($url);
            $statusCode = $response->getStatusCode();
            $body = (string)$response->getBody();

            return [
                'status_code' => $statusCode ?: 200,
                'h1'          => $this->getH1($body),
                'title'       => $this->getTitle($body),
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
            'status_code' => 404,
            'h1'          => null,
            'title'       => null,
            'description' => null
        ];
    }

    private function getH1(string $html): ?string
    {
        try {
            $dom = new DOMDocument();
            @$dom->loadHTML($html . $this->encoding, LIBXML_NOERROR);
            $xpath = new DOMXPath($dom);
            $h1List = $xpath->query('//h1');
            if ($h1List === false) {
                return null;
            }
            $h1 = $h1List->item(0);
            return $h1 instanceof \DOMNode ? trim($h1->textContent) : null;
        } catch (\Exception $e) {
            $this->errors[] = "Ошибка при получении h1";
            return null;
        }
    }

    private function getTitle(string $html): ?string
    {
        try {
            $dom = new DOMDocument();
            @$dom->loadHTML($html . $this->encoding, LIBXML_NOERROR);
            $xpath = new DOMXPath($dom);
            $titleList = $xpath->query('//title');
            if ($titleList === false) {
                return null;
            }
            $title = $titleList->item(0);
            return $title instanceof \DOMNode ? trim($title->textContent) : null;
        } catch (\Exception $e) {
            $this->errors[] = "Ошибка при получении title";
            return null;
        }
    }

    private function getDescription(string $html): ?string
    {
        try {
            $dom = new DOMDocument();
            @$dom->loadHTML($html . $this->encoding, LIBXML_NOERROR);
            $xpath = new DOMXPath($dom);
            $metaList = $xpath->query('//meta[@name="description"]');
            if ($metaList === false) {
                return null;
            }
            $meta = $metaList->item(0);
            if (!$meta instanceof \DOMElement) {
                return null;
            }
            return trim($meta->getAttribute('content'));
        } catch (\Exception $e) {
            $this->errors[] = "Ошибка при получении description";
            return null;
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
