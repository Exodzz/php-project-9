<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Hexlet\Code\GoogleSheets;

// Путь к файлу с учетными данными
$credentialsPath = __DIR__ . '/../credentials.json';

// ID вашей таблицы (можно получить из URL таблицы)
$spreadsheetId = 'YOUR_SPREADSHEET_ID';

try {
    $sheets = new GoogleSheets($credentialsPath, $spreadsheetId);

    // Добавление новой строки
    $values = ['URL', 'Status Code', 'Title', 'H1', 'Description', 'Created At'];
    $sheets->appendRow('A1:F1', $values);

    // Чтение данных
    $range = 'A1:F10';
    $data = $sheets->getValues($range);
    
    // Обновление ячейки
    $sheets->updateCell('A2', 'https://example.com');

} catch (Exception $e) {
    echo 'Ошибка: ' . $e->getMessage();
} 