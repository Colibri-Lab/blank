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

    public function Comet(RequestCollection $get, RequestCollection $post, ?PayloadCopy $payload): object
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

        return $this->Finish(200, '', []);
    }
    
    public function File(RequestCollection $get, RequestCollection $post, ?PayloadCopy $payload): object
    {
        $storage = $get->storage;
        $field = $get->field;
        $guid = $get->guid;

        if(!$storage || !$field || !$guid) {
            return $this->Finish(400, 'Bad request', []);
        }

        $storage = Storages::Create()->Load($storage);
        $field = $storage->GetField($field);
        $params = $field->params;
    
        $remote = $params['remote'];
        $className = $remote['class'] ?? null;
        if(!$className) {
            return $this->Finish(400, 'Bad request', []);
        }

        $args = $remote['args'];
        $method1 = $remote['method'][0];

        $reflectionClass = new ReflectionClass($className);
        if(!$reflectionClass->hasMethod($method1)) {
            return $this->Finish(400, 'Bad request', []);
        }

        $classInstance = $reflectionClass->newInstanceArgs($args);
        try {
            $data = $classInstance->$method1($guid);
        }
        catch(\Throwable $e) {
            return $this->Finish(404, 'File not found', []);
        }

        return $this->Finish(200, 'file.stream', base64_encode($data)); // $stat->name
    }

}
