<?php

namespace Hexlet\Code;

use Google_Client;
use Google_Service_Sheets;

class GoogleSheets
{
    private $client;
    private $service;
    private $spreadsheetId;

    public function __construct($credentialsPath, $spreadsheetId)
    {
        $this->client = new Google_Client();
        $this->client->setApplicationName('URL Analyzer');
        $this->client->setScopes([Google_Service_Sheets::SPREADSHEETS]);
        $this->client->setAuthConfig($credentialsPath);
        $this->service = new Google_Service_Sheets($this->client);
        $this->spreadsheetId = $spreadsheetId;
    }

    public function appendRow($range, $values)
    {
        $body = new \Google_Service_Sheets_ValueRange([
            'values' => [$values]
        ]);

        $params = [
            'valueInputOption' => 'RAW'
        ];

        return $this->service->spreadsheets_values->append(
            $this->spreadsheetId,
            $range,
            $body,
            $params
        );
    }

    public function getValues($range)
    {
        $response = $this->service->spreadsheets_values->get(
            $this->spreadsheetId,
            $range
        );

        return $response->getValues();
    }

    public function updateCell($range, $value)
    {
        $body = new \Google_Service_Sheets_ValueRange([
            'values' => [[$value]]
        ]);

        $params = [
            'valueInputOption' => 'RAW'
        ];

        return $this->service->spreadsheets_values->update(
            $this->spreadsheetId,
            $range,
            $body,
            $params
        );
    }
} 