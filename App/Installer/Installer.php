<?php

namespace App\Installer;

use Composer\Installer\PackageEvent;
use Composer\DependencyResolver\Operation\InstallOperation;

class Installer
{

    /**
     * 
     * @param PackageEvent $event 
     * @return void 
     */
    public static function PostRootPackageInstall($event)
    {

        $envColibriMode = \getenv('COLIBRI_MODE');
        $envColibriWebRoot = \getenv('COLIBRI_WEBROOT');
        print_r("Установлен режим в COLIBRI_MODE: " . $envColibriMode . "\n");
        print_r("Установлена папка: " . $envColibriWebRoot . "\n");
        $mode = 'prod';
        if ($envColibriMode) {
            $mode = $envColibriMode;
        } elseif ($event->isDevMode()) {
            $mode = '';
            $modes = ['local', 'dev', 'test', 'prod'];
            while (!in_array($mode, $modes)) {
                $mode = $event->getIO()->ask('Выберите режим (local|test|prod) prod по умолчанию: ', 'prod');
            }
        }

        $webRoot = 'web';
        if ($envColibriWebRoot) {
            $webRoot = $envColibriWebRoot;
        } elseif ($event->isDevMode()) {
            $webRoot = $event->getIO()->ask('Введите папку для точки входа web по умолчанию: ', 'web');
        }

        if ($mode != 'local') {
            shell_exec('mv ./config-template/' . $mode . '/ ./config && rm -R ./config-template');
        } else {
            shell_exec('mkdir ./config');
            $path = './config-template/local/';
            $files = scandir($path);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && is_file($path . $file)) {
                    shell_exec('ln -s ' . realpath($path . $file) . ' ./config/' . $file);
                }
            }
        }

        // переименовываем папку
        if ($webRoot !== 'web') {
            shell_exec('mv ./web ./' . $webRoot);
        }
        // ставим права на кэш
        shell_exec('chmod -R 777 ./' . $webRoot . '/_cache');

    }

    /**
     * 
     * @param PackageEvent $event 
     * @return void 
     */
    public static function PostPackageInstall($event)
    {

        /** @var InstallOperation */
        $operation = $event->getOperation();
        $installedPackage = $operation->getPackage();
        $targetDir = $installedPackage->getName();
        $autoload = $installedPackage->getAutoload();
        $psr4 = isset($autoload['psr-4']) ? $autoload['psr-4'] : [];
        foreach ($psr4 as $classNamespace => $path) {

            if (file_exists('./vendor/' . $targetDir . '/' . $path . 'Installer.php')) {
                print_r('Пост установка пакета ' . $classNamespace . "\n");

                $class = $classNamespace . 'Installer';
                if(!class_exists($class)) {
                    require_once './vendor/' . $targetDir . '/' . $path . 'Installer.php';
                }

                print_r('Запускаем инсталлер ' . $classNamespace . "::PostPackageInstall\n");
                /** @var object $class */
                $class::PostPackageInstall($event);

            }
        }
    }
}