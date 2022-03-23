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
        
        $envColibriMode = \getenv('AKTIONDIGITAL_MODE');
        print_r("Установлен режим в AKTIONDIGITAL_MODE: " . $envColibriMode . "\n");
        if($envColibriMode) {
            $mode = $envColibriMode;
        }
        else if($event->isDevMode()) {
            $mode = '';
            $modes = ['local', 'dev', 'test', 'prod'];
            while(!in_array($mode, $modes)) {
                $mode = $event->getIO()->ask('Выберите режим (local|dev|test|prod|demo) prod по умолчанию: ', 'prod');
            }
        }
        else {
            $mode = 'prod';
        }

        shell_exec('mv ./config-template/'.$mode.'/ ./config && rm -R ./config-template');
        shell_exec('chmod -R 777 ./web/_cache');
        
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
        foreach($psr4 as $classNamespace => $path) {

            if(file_exists('./vendor/'.$targetDir.'/'.$path.'Installer.php')) {
                print_r('Пост установка пакета '.$classNamespace."\n");

                require_once './vendor/'.$targetDir.'/'.$path.'Installer.php';
                $class = $classNamespace.'Installer';

                print_r('Запускаем инсталлер '.$classNamespace."::PostPackageInstall\n");
                $class::PostPackageInstall($event);

            }
        }
    }
}