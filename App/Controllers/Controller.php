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
    public function Index($get, $post, $payload)
    {

        // Обработка scss
        App::$instance->HandleEvent(EventsContainer::BundleComplete, function ($event, $args) {
            if (in_array('scss', $args->exts)) {
                try {
                    $scss = new Compiler();
                    $scss->setOutputStyle(OutputStyle::EXPANDED);
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
                $args->content = 'Colibri.UI.AddTemplate(\'' . $componentName . '\', \'' . $compiledContent . '\');' . "\n";
            }
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
        } catch (Exception $e) {
            // если произошла ошибка, то выводим ее
            $html = $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine();
        }

        // финишируем контроллер
        return $this->Finish(200, $html);
    }

    /**
     * Метод по умолчанию
     * @param mixed $get
     * @param mixed $post
     * @param mixed $payload
     * @return object
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

    public function Comet($get, $post, $payload) 
    {

        // Do nothing
        /**
            $comet = App::$config->Query('comet.server')->GetValue()
            $request = new Request('https://' . $comet . '/api/CometServerApi.js', Type::Get)
            $request->sslVerify = falsу
            $request->timeout = 1
            $response = $request->Execute()
            
            App::$response->ContentType('text/javascript', 'utf-8')
            App::$response->Close(200, $response->data)
        */
    }
    

}
