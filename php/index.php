<?php

require __DIR__ . '/vendor/autoload.php';

use Elasticsearch\ClientBuilder;
use MongoDB\Client as MongoClient;

// Функция для обработки ошибок и возврата HTTP-кода
function handleError($message, $code = 500) {
    http_response_code($code);
    echo json_encode([
        'status' => 'error',
        'message' => $message,
        'code' => $code,
    ]);
    exit;
}

try {
    // Настройка MongoDB
    $mongo = new MongoClient('mongodb://mongo:27017');
    $mongoCollection = $mongo->selectCollection('test', 'metrics');
} catch (Exception $e) {
    handleError('Ошибка подключения к MongoDB: ' . $e->getMessage(), 500);
}

try {
    // Настройка Elasticsearch
    $elasticsearch = ClientBuilder::create()
        ->setHosts(['http://elasticsearch:9200'])
        ->build();
} catch (Exception $e) {
    handleError('Ошибка подключения к Elasticsearch: ' . $e->getMessage(), 500);
}

// Определяем режим (CLI или Web)
$isCli = php_sapi_name() === 'cli';
$runtimeLimit = 5; // Лимит выполнения в веб-режиме (секунды)
$runtimeLimit_req = 1; // Лимит выполнения в CLI (секунды)
$startTime = microtime(true);

// Флаги для статистики
$ops = 0;
$mongoOps = 0;
$esOps = 0;

try {
    while (true) {
        if (!$isCli && (microtime(true) - $startTime > $runtimeLimit)) {
            break;
        }
        if ($isCli && (microtime(true) - $startTime > $runtimeLimit_req)) {
            break;
        }

        $time = random_int(100, 400);
        $types = ['search', 'book', 'login', 'logout'];
        $type = $types[random_int(0, 3)];
        $delta = random_int(1, 5);

        // MongoDB операция
        try {
            $mongoCollection->insertOne([
                'type' => $type,
                'delta' => $delta,
                'time' => $time,
                'created_at' => new MongoDB\BSON\UTCDateTime((new DateTime())->getTimestamp() * 1000),
            ]);
            ++$mongoOps;
        } catch (Exception $e) {
            handleError('Ошибка при работе с MongoDB: ' . $e->getMessage(), 500);
        }

        // Elasticsearch операция
        try {
            $elasticsearch->index([
                'index' => 'metrics',
                'body' => [
                    'type' => $type,
                    'delta' => $delta,
                    'time' => $time,
                    'timestamp' => date('c'),
                ],
            ]);
            ++$esOps;
        } catch (Exception $e) {
            handleError('Ошибка при работе с Elasticsearch: ' . $e->getMessage(), 500);
        }

        ++$ops;

        // Задержка между операциями (опционально)
        // usleep(random_int(5, 55) * 1000);
    }
} catch (Exception $e) {
    handleError('Неизвестная ошибка: ' . $e->getMessage(), 500);
}

// Если всё успешно или с ошибкой случайным образом
$randomError = random_int(1, 10); // Генерация случайного числа от 1 до 10

// if ($randomError <= 7) { // С вероятностью 70% возвращаем HTTP 200
http_response_code(200);
echo json_encode([
    'status' => 'ok',
    'ops' => $ops,
    'mongo_ops' => $mongoOps,
    'es_ops' => $esOps,
    'runtime' => microtime(true) - $startTime,
]);
// } else { // С вероятностью 30% возвращаем случайную ошибку
//     $errorCodes = [400, 401, 403, 404, 500, 502, 503]; // Список возможных ошибок
//     $errorCode = $errorCodes[array_rand($errorCodes)]; // Выбираем случайный код ошибки

//     http_response_code($errorCode);
//     echo json_encode([
//         'status' => 'error',
//         'message' => "Случайная ошибка с кодом $errorCode",
//         'code' => $errorCode,
//     ]);
// }

