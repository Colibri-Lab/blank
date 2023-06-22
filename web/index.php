<?php

/**
 * Стандартная точка входа для всех web запросов
 * ! НЕ ТРОГАТЬ И НЕ МОДИФИЦИРОВАТЬ
 *
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package App
 * @version 1.0.0
 *
 */

set_time_limit(0);

require_once __DIR__ . '/../vendor/autoload.php';

use Colibri\App;
use Colibri\Data\Storages\Models\Generator;
use Colibri\Data\Storages\Storages;
use Colibri\Utils\Debug;
use Colibri\Web\Server as WebServer;
use Colibri\IO\FileSystem\File;
use Colibri\Utils\Logs\ConsoleLogger;
use Colibri\Utils\Logs\Logger;
use Colibri\Utils\Logs\FileLogger;
use Colibri\Data\Storages\Fields\DateTimeField;
use Colibri\Queue\Manager as QueueManager;
use Colibri\Utils\Logs\MemoryLogger;

DateTimeField::$defaultLocale = 'RU_ru';

try {
    
    ob_start();

    $log = App::$request->get->{'log'} && App::$request->get->{'log'} !== 'no';
    $logger = new MemoryLogger();
    if ($log && File::Exists(App::$request->get->{'log'})) {
        $logger = new FileLogger(Logger::Debug, App::$request->get->{'log'});
    } elseif ($log) {
        $logger = new ConsoleLogger(Logger::Debug);
    }

    if (App::$isDev || (App::$request->server->{'commandline'} && App::$request->get->{'command'} === 'migrate')) {
        Storages::Create()->Migrate($logger, App::$isDev);
        QueueManager::Create()->Migrate($logger);
        if (App::$request->server->{'commandline'} && App::$request->get->{'command'} === 'migrate') {
            exit;
        }
    }

    if (App::$isDev && (App::$request->server->{'commandline'} && App::$request->get->{'command'} === 'models-generate')) {
        $logger->debug('Creating models for storage ' . App::$request->get->{'storage'});
        $storage = Storages::Create()->Load(App::$request->get->{'storage'});
        Generator::GenerateModelClasses($storage);
        Generator::GenerateModelTemplates($storage);
        $logger->debug('Generation complete');
        exit;
    }

    $command = App::$request->server->{'request_uri'};

    $server = new WebServer();
    $server->Run($command, '/');

    ob_end_flush();

} catch (\Throwable $e) {
    Debug::Out($e->getMessage(), $e->getLine(), $e->getFile());
}
