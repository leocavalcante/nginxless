<?php declare(strict_types=1);

namespace Nginxless;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Swoole\Constant;
use Swoole\Coroutine\FastCGI\Client\Exception;
use Swoole\Coroutine\FastCGI\Proxy;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

require_once __DIR__ . '/vendor/autoload.php';

$host = getenv('HOST') ?: '0.0.0.0';
$port = getenv('PORT') ?: 80;
$php_fpm = getenv('PHP_FPM') ?: '127.0.0.1:9000';
$document_root = getenv('DOCUMENT_ROOT') ?: '/var/www/html';

$logger = new Logger('Nginxless', [new StreamHandler(STDOUT)]);
$server = new Server($host, $port, SWOOLE_BASE, SWOOLE_SOCK_TCP);
$proxy = new Proxy($php_fpm, $document_root);

$server->set([
    Constant::OPTION_WORKER_NUM => swoole_cpu_num() * 2,
    Constant::OPTION_ENABLE_STATIC_HANDLER => true,
    Constant::OPTION_STATIC_HANDLER_LOCATIONS => [$document_root],
]);

$server->on('request', static function (Request $request, Response $response) use ($logger, $proxy): void {
    try {
        $proxy->pass($request, $response);
    } catch (\Throwable $err) {
        $logger->error('Internal server error', ['exception' => $err]);
    }
});

$logger->info('Start', [
    'host' => $server->host,
    'port' => $server->port,
    'php_fpm' => $php_fpm,
    'document_root' => $document_root
]);

$server->start();
