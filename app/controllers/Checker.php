<?php

namespace Hexlet\Code\Controllers;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;

class Checker
{
    private int $urlId;
    private string $error;

    /**
     * @param int $urlId
     */
    public function __construct(int $urlId)
    {
        $this->urlId = $urlId;
    }


    public function check()
    {
        try {
            $client = new Client();
            $res = $client->request('GET', $name[0]['name']);
            $checkUrl['status'] = $res->getStatusCode();
        } catch (ConnectException $e) {
            $this->get('flash')->addMessage('failure', 'Произошла ошибка при проверке, не удалось подключиться');

            $url = $this->get('router')->urlFor('urls.show', ['id' => $url_id]);
        } catch (ClientException $e) {
        }
    }
}
