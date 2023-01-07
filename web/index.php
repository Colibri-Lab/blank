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

DateTimeField::$defaultLocale = 'RU_ru';

$mode = App::$config->Query('mode')->GetValue();
$isDev = in_array($mode, [App::ModeDevelopment, App::ModeLocal]);


try {

    if ($isDev || (App::$request->server->commandline && App::$request->get->command === 'migrate')) {
        $logger = new ConsoleLogger(Logger::Debug);
        if (App::$request->get->log && File::Exists(App::$request->get->log)) {
            $logger = new FileLogger(Logger::Debug, App::$request->get->log);
        }

        Storages::Create()->Migrate($logger, $isDev);
        if (App::$request->server->commandline && App::$request->get->command === 'migrate') {
            exit;
        }
    }

    if ($isDev && (App::$request->server->commandline && App::$request->get->command === 'models-generate')) {
        $storage = Storages::Create()->Load(App::$request->get->storage);
        Generator::GenerateModelClasses($storage);
        Generator::GenerateModelTemplates($storage);
        exit;
    }

    $command = App::$request->server->request_uri;

    $server = new WebServer();
    $server->Run($command, '/');

} catch (\Throwable $e) {
    Debug::Out($e->getMessage(), $e->getLine(), $e->getFile());
}
