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
use VaultApiClient\Client;
use Colibri\Web\RequestCollection;
use Colibri\Web\PayloadCopy;
use Colibri\Utils\Minifiers\Javascript as Minifier;

/**
 * Контроллер по умолчанию
 * @author Vahan P. Grigoryan
 * @package App\Controllers
 */
class Controller extends WebController
{

    private function _initDefaultBundleHandlers() 
    {
        // Обработка scss
        App::$instance->HandleEvent(EventsContainer::BundleComplete, function ($event, $args) {
            try {
                if (in_array('scss', $args->exts)) {
                    $scss = new Compiler();
                    $scss->setOutputStyle(App::$isDev ? OutputStyle::EXPANDED : OutputStyle::COMPRESSED);
                    $args->content = $scss->compileString($args->content)->getCss();
                } elseif (in_array('js', $args->exts) && !App::$isDev) {
                    $args->content = Minifier::Minify($args->content);
                }
            } catch (\Throwable $e) {
                App::$log->emergency($e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine());
            }
            return true;
        });

        // если есть бандлер то нужно запустить генерация javascript из шаблонов
        App::$instance->HandleEvent(EventsContainer::BundleFile, function ($event, $args) {
            $file = new File($args->file);
            if ($file->extension !== 'html') {
                return true;
            }
            
            // компилируем html в javascript
            $componentName = $file->filename;
            if( preg_match('/ComponentName="([^"]*)"/i', $args->content, $matches) > 0) {
                $componentName = $matches[1];
            }

            $compiledContent = str_replace(
                ['\'', "\n", "\r", 'ComponentName="'.$componentName.'"'], 
                ['\\\'', "' + \n'", "", 'namespace="'.$componentName.'"'], 
                $args->content
            );

            $args->content = 'Colibri.UI.AddTemplate(\'' . $componentName . '\', ' . "\n" . '\'' . $compiledContent . '\');' . "\n";
            
            return true;
        });
    }

    /**
     * Метод по умолчанию
     * @param mixed $get
     * @param mixed $post
     * @param mixed $payload
     * @return object
     */
    public function Index(RequestCollection $get, RequestCollection $post, ?PayloadCopy $payload): object
    {

        $this->_initDefaultBundleHandlers();

        // создаем класс View
        $view = View::Create();

        // создаем обьект шаблона
        $template = PhpTemplate::Create(App::$appRoot . '/templates/index');

        try {
            // пытаемся сгенерировать страницу
            $html = $view->Render($template,  new ExtendedObject([
                'get' => $get,
                'post' => $post,
                'payload' => $payload
            ]));
            $code = 200;
        } catch (\Throwable $e) {

            $html = $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine();
            $code = $e->getCode() ?: 404;

            if( ($redirectAddress = App::$config->Query(['settings.errors.' . $e->getCode(), 'settings.errors.0'], '')->getValue()) !== '' ) {
                $res = Request::Get(App::$request->address . $redirectAddress, 1, false);
                $html = $res->status === 200 ? $res->data : $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine();
            }

        }

        return $this->Finish($code, $html);
        
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
                /** @var File $file */
                Client::Vault($file->path);
            }
        }
        else {
            throw new AppException('This command is allowed only in commandline mode');
        }


        return $this->Finish(200, 'ok', []);
        
    }
    
}
