<?php

require_once __DIR__ . '/../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->load();

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;

$exchange = 'webhook';
$queue = 'webhook_request';
$routingKey = 'request';

$connection = new AMQPStreamConnection(
    $_ENV['RABBITMQ_HOST'],
    $_ENV['RABBITMQ_PORT'],
    $_ENV['RABBITMQ_USERNAME'],
    $_ENV['RABBITMQ_PASSWORD'],
    $_ENV['RABBITMQ_VHOST']
);
$channel = $connection->channel();

$channel->queue_declare($queue, false, true, false, false);
$channel->exchange_declare($exchange, AMQPExchangeType::DIRECT, false, true, false);
$channel->queue_bind($queue, $exchange, $routingKey);

$messageBody = \json_encode([
    'request' => [
        'method' => 'GET',
        'url' => 'https://api.ipify.org',
        'headers' => null,
        'body' => null,
        'query' => null,
    ],
    'response' => [
        'method' => 'POST',
        'url' => '',
        'headers' => [],
        'body' => [],
        'query' => [],
    ],
    'requeue' => [
        'routingKey' => 'response',
    ]
]);

$message = new AMQPMessage($messageBody, [
    'content_type' => 'text/plain',
    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
]);

$channel->basic_publish($message, $exchange, $routingKey);

$channel->close();
$connection->close();
