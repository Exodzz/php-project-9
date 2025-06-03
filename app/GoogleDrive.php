<?php

namespace Hexlet\Code;

use Google_Client;
use Google_Service_Drive;

class GoogleDrive
{
    private $client;
    private $service;

    public function __construct($credentialsPath)
    {
        $this->client = new Google_Client();
        $this->client->setApplicationName('Price Downloader');
        $this->client->setScopes([
            Google_Service_Drive::DRIVE_READONLY
        ]);
        $this->client->setAuthConfig($credentialsPath);
        $this->service = new Google_Service_Drive($this->client);
    }

    public function downloadFile($fileName, $savePath)
    {
        try {
            // Поиск файла по имени
            $query = "name = '{$fileName}' and trashed = false";
            $files = $this->service->files->listFiles([
                'q' => $query,
                'fields' => 'files(id, name)'
            ]);

            if (empty($files->getFiles())) {
                throw new \Exception("Файл {$fileName} не найден");
            }

            $file = $files->getFiles()[0];
            $fileId = $file->getId();

            // Скачивание файла
            $content = $this->service->files->get($fileId, [
                'alt' => 'media'
            ]);

            // Сохранение файла
            file_put_contents($savePath, $content->getBody()->getContents());

            return true;
        } catch (\Exception $e) {
            throw new \Exception("Ошибка при скачивании файла: " . $e->getMessage());
        }
    }

    public function listFiles($folderId = null)
    {
        try {
            $query = "trashed = false";
            if ($folderId) {
                $query .= " and '{$folderId}' in parents";
            }

            $files = $this->service->files->listFiles([
                'q' => $query,
                'fields' => 'files(id, name, mimeType, modifiedTime)'
            ]);

            return $files->getFiles();
        } catch (\Exception $e) {
            throw new \Exception("Ошибка при получении списка файлов: " . $e->getMessage());
        }
    }
} 