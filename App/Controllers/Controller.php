<?php


namespace App\Controllers;

use Colibri\App;
use Colibri\AppException;
use Colibri\Events\EventsContainer;
use Colibri\IO\FileSystem\File;
use Colibri\IO\FileSystem\Finder;
use Colibri\IO\Request\Request;
use Colibri\IO\Request\Type;
use Colibri\Utils\Debug;
use Colibri\Utils\ExtendedObject;
use Colibri\Web\Controller as WebController;
use Colibri\Web\Templates\PhpTemplate;
use Colibri\Web\View;
use Exception;
use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\OutputStyle;
use Colibri\Utils\Cache\Bundle;
use VaultApiClient\Client;
use Colibri\Web\RequestCollection;
use Colibri\Web\PayloadCopy;
use Colibri\Data\Storages\Storages;
use ReflectionClass;

/**
 * Контроллер по умолчанию
 * @author Vahan P. Grigoryan
 * @package App\Controllers
 */
class Controller extends WebController
{

    /**
     * Метод по умолчанию
     * @param mixed $get
     * @param mixed $post
     * @param mixed $payload
     * @return object
     */
    public function Index(RequestCollection $get, RequestCollection $post, ?PayloadCopy $payload): object
    {

        // Обработка scss
        App::$instance->HandleEvent(EventsContainer::BundleComplete, function ($event, $args) {
            if (in_array('scss', $args->exts)) {
                try {
                    $scss = new Compiler();
                    $scss->setOutputStyle(App::$isDev ? OutputStyle::EXPANDED : OutputStyle::COMPRESSED);
                    $args->content = $scss->compileString($args->content)->getCss();
                } catch (\Throwable $e) {
                    Debug::Out($e->getMessage());
                }
            }
            return true;
        });

        // если есть бандлер то нужно запустить генерация javascript из шаблонов
        App::$instance->HandleEvent(EventsContainer::BundleFile, function ($event, $args) {
            $file = new File($args->file);
            if ($file->extension == 'html') {
                // компилируем html в javascript
                $componentName = $file->filename;
                $res = preg_match('/ComponentName="([^"]*)"/i', $args->content, $matches);
                if($res > 0) {
                    $componentName = $matches[1];
                }
                $compiledContent = str_replace('\'', '\\\'', str_replace("\n", "", str_replace("\r", "", $args->content)));
                $compiledContent = str_replace('ComponentName="'.$componentName.'"', '', $compiledContent);
                $args->content = 'Colibri.UI.AddTemplate(\'' . $componentName . '\', \'' . $compiledContent . '\');' . "\n";
            }
            return true;
        });

        // создаем класс View
        $view = View::Create();

        // создаем обьект шаблона
        $template = PhpTemplate::Create(App::$appRoot . '/templates/index');

        // собираем аргументы
        $args = new ExtendedObject([
            'get' => $get,
            'post' => $post,
            'payload' => $payload
        ]);

        try {
            // пытаемся сгенерировать страницу
            $html = $view->Render($template,  $args);
            return $this->Finish(200, $html);
        } catch (Exception $e) {

            $redirectAddress = App::$config->Query('settings.errors.' . $e->getCode(), '')->getValue();
            if(!$redirectAddress) {
                $redirectAddress = App::$config->Query('settings.errors.0', '')->getValue();
            }
            if($redirectAddress) {
                $req = new Request(App::$request->address . $redirectAddress, Type::Get);
                $req->timeout = 3;
                $req->sslVerify = false;
                $res = $req->Execute();
                if($res->status == 200) {
                    $html = $res->data;
                }
                else {
                    $html = $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine();
                }
            }
            else {
                $html = $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine();
            }

            $code = $e->getCode();
            if(!$code) {
                $code = 404;
            }

            return $this->Finish($code, $html);
            
        }
        
    }

    /**
     * Настройки
     * @param RequestCollection $get данные GET
     * @param RequestCollection $post данные POST
     * @param mixed $payload данные payload обьекта переданного через POST/PUT
     * @return object
     */
    public function Settings(RequestCollection $get, RequestCollection $post, ?PayloadCopy $payload = null): object
    {

        $appConfig = App::$config;
        $result = array_merge($appConfig->Query('settings')->AsArray(), [
            'hosts' => $appConfig->Query('hosts')->AsArray(),
            'current' => App::$domainKey,
            'comet' => $appConfig->Query('comet')->AsArray(),
            'res' => '/'.$appConfig->Query('res')->GetValue()
        ]);
        return $this->Finish(200, 'Settings', $result);

    }
    
    /**
     * Метод по умолчанию
     * @param mixed $get
     * @param mixed $post
     * @param mixed $payload
     * @return \stdClass
     */
    public function Vault($get, $post, $payload)
    {

        if(App::$request->server->commandline) {
            $fi = new Finder();
            $files =  $fi->Files(App::$appRoot.'config/');
            foreach($files as $file) {
                Client::Vault($file->path);
            }
        }
        else {
            throw new AppException('This command is allowed only in commandline mode');
        }


        return $this->Finish(200, 'ok', []);
        
    }
    
}
