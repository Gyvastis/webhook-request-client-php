<?php

require_once __DIR__ . '/../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->load();

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;

$exchange = 'webhook';
$queue = 'webhook_response';
$routingKey = 'response';
$prefetchCount = 10;

$connection = new AMQPStreamConnection(
    $_ENV['RABBITMQ_HOST'],
    $_ENV['RABBITMQ_PORT'],
    $_ENV['RABBITMQ_USERNAME'],
    $_ENV['RABBITMQ_PASSWORD'],
    $_ENV['RABBITMQ_VHOST']
);
$channel = $connection->channel();
$channel->basic_qos(null, $prefetchCount, null);
$channel->queue_declare($queue, false, true, false, false);
$channel->exchange_declare($exchange, AMQPExchangeType::DIRECT, false, true, false);
$channel->queue_bind($queue, $exchange, $routingKey);

$channel->basic_consume($queue, 'xxxx', false, false, false, false, function($msg) {
    var_dump($msg->body); // use body
    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
});

function shutdown($channel, $connection)
{
    $channel->close();
    $connection->close();
}
register_shutdown_function('shutdown', $channel, $connection);

while ($channel ->is_consuming()) {
    var_dump('Consuming...');
    $channel->wait();
}