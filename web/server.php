<?php

set_time_limit(0);

require_once __DIR__ . '/../vendor/autoload.php';

use Colibri\App;
use Colibri\Common\Encoding;
use Colibri\Common\ErrorHelper;
use Colibri\Common\HtmlHelper;
use Colibri\Common\MimeType;
use Colibri\Common\NoLangHelper;
use Colibri\Common\StringHelper;
use Colibri\Common\VariableHelper;
use Colibri\Common\XmlHelper;
use Colibri\IO\FileSystem\File;
use Colibri\Data\Storages\Fields\DateTimeField;
use Colibri\Events\Event;
use Colibri\Events\EventDispatcher;
use Colibri\Events\EventsContainer;
use Colibri\Web\Request;
use Colibri\Web\RequestCollection;
use Colibri\Web\Response;
use Colibri\Web\StringStream;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Loop;
use React\Http\HttpServer;
use React\Http\Message\Response as MessageResponse;
use React\Socket\SecureServer;
use React\Socket\SocketServer;

DateTimeField::$defaultLocale = 'RU_ru';

/**
 * List of types
 */
const JSON = 'json';
const XML = 'xml';
const HTML = 'html';
const Text = 'txt';
const CSS = 'css';
const JS = 'js';
const Stream = 'stream';


/**
* List of errors
*/
const IncorrectCommandObject = 1;
const UnknownMethodInObject = 2;


/**
 * Gets the full controller class name with namespace.
 *
 * @param string $class The class name.
 * @return string The full class name.
 */
function GetControllerFullName(string $class): string
{
    $class = StringHelper::UrlToNamespace($class);
    if (strpos($class, 'Modules') === 0) {

        // это модуль, значит должно быть modules/«название модуля»[/«название контроллера»]
        $parts = explode('\\', $class);
        if (count($parts) >= 3) {
            array_splice($parts, 2, 0, 'Controllers');
        } else {
            $parts[] = 'Controllers\\';
        }
        $class = implode('\\', $parts);

        return '\\App\\' . $class . 'Controller';
    }
    return '\\App\\Controllers\\' . $class . 'Controller';
}


/**
 * Parses command URL to determine type, class, and method.
 *
 * @param string $cmd The command URL.
 * @return array An array containing type, class, and method.
 */
function ParseCommand(string $cmd): array
{
    $cmd = explode('?', $cmd);
    $cmd = reset($cmd);

    $isRequestTyped = true;
    $method = 'index';
    $type = HTML;
    $class = $cmd;
    if (preg_match('/\/([^\/]+)\.([^\?]+)/', $cmd, $matches) > 0) {
        $method = $matches[1];
        $type = $matches[2];
        $class = str_replace($method . '.' . $type, '', $cmd);
    } elseif (preg_match('/\/([^\/]+)$/', $cmd, $matches) > 0) {
        $method = $matches[1];
        $type = JSON;
        $class = preg_replace('/' . $method . '$/', '', $cmd);
        $isRequestTyped = false;
    }

    $class = GetControllerFullName($class);
    $method = StringHelper::ToCamelCaseAttr($method, true);

    return [$type, $class, $method, $isRequestTyped];
}

function HandleRequest(ServerRequestInterface $psrRequest): MessageResponse
{
    $request = new Request($psrRequest);
    $response = new Response();
    App::Instance()->Initialize($request, $response, true);
    
    $cmd = $request->uri;
    [
        $type, 
        $class, 
        $method, 
        $isRequestTyped
    ] = ParseCommand($cmd);

    if ((!class_exists($class) || !method_exists($class, $method))) {

        // if the request is not exists and it's a file in web root then return it as file
        if(File::Exists(App::$webRoot . $cmd)) {
            return ServerFinish(
                $psrRequest, 
                Stream, 
                [
                    'code' => 200,
                    'result' => File::Read(App::$webRoot . $cmd),
                    'message' => basename($cmd)
                ]
            );
        }
        // если не нашли чего делать то пробуем по умолчанию
        [$type, $class, $method, $isRequestTyped] = ParseCommand('/');
    }

    $headers = App::$request->headers;
    $requestMethod = App::$request->server->{'request_method'} ?? 'GET';
    $get = App::$request->get;
    $post = App::$request->post;
    $payload = App::$request->GetPayloadCopy();

    if(App::HasCsfrInRequest($headers) && !App::CsfrIsCorrect($headers)) {

        $message = 'CSFR token is incorrect';

        EventDispatcher::Instance()->Dispatch(new Event(App::Instance(), EventsContainer::RpcRequestError), (object) [
            'class' => $class,
            'method' => $method,
            'get' => $get,
            'post' => $post,
            'payload' => $payload,
            'message' => $message
        ]);

        return ServerFinish($psrRequest, $type, (object) [
            'code' => 403,
            'message' => $message
        ]);

    }

    $args = (object) [
        'class' => $class,
        'method' => $method,
        'get' => $get,
        'post' => $post,
        'payload' => $payload
    ];
    EventDispatcher::Instance()->Dispatch(new Event(App::Instance(), EventsContainer::RpcGotRequest), $args);
    if (isset($args->cancel) && $args->cancel === true) {
        $result = isset($args->result) ? $args->result : (object) [];
        return ServerFinish($psrRequest, $type, $result);
    }

    if (!class_exists($class)) {
    
        
        $message = 'Unknown class ' . $class;
        EventDispatcher::Instance()->Dispatch(new Event(App::Instance(), EventsContainer::RpcRequestError), (object) [
            'class' => $class,
            'method' => $method,
            'get' => $get,
            'post' => $post,
            'payload' => $payload,
            'message' => $message
        ]);

        return ResponseWithError($psrRequest, $type, $message, IncorrectCommandObject, $cmd, [
            'message' => $message,
            'code' => IncorrectCommandObject,
            'get' => $get,
            'post' => $post,
            'payload' => $payload
        ]);
        
    }

    if (!method_exists($class, $method)) {
        $message = 'Unknown method ' . $method . ' in object ' . $class;
        EventDispatcher::Instance()->Dispatch(new Event(App::Instance(), EventsContainer::RpcRequestError), (object) [
            'class' => $class,
            'method' => $method,
            'get' => $get,
            'post' => $post,
            'payload' => $payload,
            'message' => $message
        ]);

        return ResponseWithError(
            $psrRequest,
            $type,
            $message,
            UnknownMethodInObject,
            $cmd,
            [
                'message' => $message,
                'code' => UnknownMethodInObject,
                'get' => $get,
                'post' => $post,
                'payload' => $payload
            ]
        );
    }

    if ($requestMethod === 'OPTIONS') {
        // если это запрос на опции то вернуть
        return ServerFinish($psrRequest, $type, (object) ['code' => 200, 'message' => 'ok', 'options' => true]);
    } else {

        

        try {
            $obj = new $class($type, $isRequestTyped);
            // ! нужно понять как выпустить его в мир
            // if(!$obj->waitForAnswer) {

            //     $payload->Cache();

            //     App::$response->Origin();
            //     App::$response->FinishRequest();

            // }

            $result = (object) $obj->Invoke($method, $get, $post, $payload);
        } catch (\Throwable $e) {

            $errorResult = [
                'exception' => get_class($e),
                'message' => $e->getMessage()
            ];

            if(method_exists($e, 'getExceptionDataAsArray')) {
                $errorResult['data'] = $e->{'getExceptionDataAsArray'}();
            }

            if(App::$isDev || App::$isLocal) {
                $errorResult['line'] = $e->getLine();
                $errorResult['file'] = $e->getFile();
                $errorResult['trace'] = $e->getTrace();
            }

            // если что то не так то выводим ошибку
            $result = (object)[
                'code' => $e->getCode() ?: 500,
                'result' => $errorResult
            ];

            $code = $e->getCode() ?: 500;
            $message = $e->getMessage();

            App::$log->debug($code . ': ' . $message);
            App::$log->debug($e->getTraceAsString());

            $sendToTelegram = true;
            $arrayExclude = App::$config->Query('errors.exclude', [])->ToArray();
            if($arrayExclude && !empty($arrayExclude)) {
                foreach($arrayExclude as $classFilter) {
                    if(strstr(get_class($e), $classFilter) !== false) {
                        $sendToTelegram = false;
                        break;
                    }
                }
            }

            $sendToTelegram && ErrorHelper::Telegram(
                '@colibri_core_errors',
                '<b style="color: red">' . $class . '\\' . $method . '.' . $type . "</b>\n".
                '<b>Server:</b> ' . App::$request->host . "\n\n" .
                '<b>Trace:</b> ' . $e->getTraceAsString() . "\n\n" .
                '<b>Params:</b> ' . json_encode([$get->ToArray(), $post->ToArray(), $payload->ToArray()]) . "\n" .
                '<b>Response:</b> ' . $code . ', ' . $message . "\n" .
                '<b>Result:</b> ' . json_encode($result) . "\n"
            );

        }

        $args = (object) [
            'object' => $obj,
            'class' => $class,
            'method' => $method,
            'get' => $get,
            'post' => $post,
            'payload' => $payload,
            'result' => $result,
            'type' => ($result?->type ?? null) ?: $type
        ];
        EventDispatcher::Instance()->Dispatch(new Event(App::Instance(), EventsContainer::RpcRequestProcessed), $args);

        if((($result?->type ?? null) ?: $type) !== Stream) {
            $args->result = NoLangHelper::ParseArray($args->result);
        }

        return ServerFinish($psrRequest, (($result?->type ?? null) ?: $type), $args->result);
    }

}

/**
 * Finishes the process and sends response.
 *
 * @param string $type The response type.
 * @param mixed $result The result to send.
 * @return MessageResponse
 */
function ServerFinish(ServerRequestInterface $request, string $type, mixed $result): MessageResponse
{
    $result = (object) $result;
    if (!isset($result->headers)) {
        $result->headers = [];
    }
    if (!isset($result->cookies)) {
        $result->cookies = [];
    }

    $serverParams = new RequestCollection($request->getServerParams());

    $headers = [
        'Access-Control-Allow-Origin' => $serverParams->{'http_origin'} ?? '*',
        'Access-Control-Allow-Credentials' => 'true',
        'Access-Control-Allow-Headers' => $request->getHeaderLine('access-control-request-headers') ?: '*',
        'Access-Control-Allow-Method' => $request->getHeaderLine('access-control-request-method') ?: '*'
    ];

    $mime = new MimeType($type);

    // if we responsing with file
    if (
        $type == Stream && $result?->result &&
        (\is_string($result->result) && \is_string($result->message))
    ) {

        $mime = MimeType::Create($result->message);
        return new MessageResponse($result->code ?: 200, VariableHelper::Extend($headers, [
            'Content-Description' => 'File Transfer',
            'Content-Disposition' => 'attachment; filename="' . $result->message . '"',
            'Content-Transfer-Encoding' => 'binary',
            'Expires' => '0',
            'Cache-Control' => 'must-revalidate',
            'Content-Length' => \strlen($result->result),
            'Content-Type' => $mime->data ?: 'application/octet-stream'
        ]), new StringStream($result->result));

    }

    $content = $result?->message ?? $result?->result ?? '';
    if ($type == JSON || $type == Stream) {
        $content = json_encode($result?->result ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } elseif ($type === XML) {
        $content = XmlHelper::Encode($result?->result ?? []);
    } elseif ($type == HTML) {
        $content = $result?->message ?: HtmlHelper::Encode($result?->result ?? []);
    } elseif ($type == CSS) {
        $content = $result?->message ?? [];
    }

    $setCookies = [];
    $cookies = $result?->cookies ?? [];
    $cookies[] = (object)[
        'name' => App::$config->Query('session.name', 'sid')->GetValue(),
        'value' => App::$session->sid,
        'expire' => time() + 365 * 86400,
        'domain' => App::$session->ttl,
        'path' => '/',
        'secure' => true,
        'samesite' => 'None'
    ];

    foreach($cookies as $cookie) {
        $cookie = (object)$cookie;
        $setCookies[] = '' . $cookie->name . '=' . $cookie->value . '; ' .
            'Expires=' . (isset($cookie->expire) ? gmdate('D, d M Y H:i:s T', $cookie->expire) : 'Session') . '; ' .
            'Path=' . ($cookie->path ?? '/') . '; ' .
            'Domain=' . ($cookie->domain ?? $request->getUri()->getHost()) . '; ' .
            (($cookie->secure ?? false) ? 'Secure; ' : '') .
            (($cookie->httponly ?? false) ? 'HttpOnly; ' : '');
    }

    $encoding = ($result?->charset ?? 'utf-8');
    $headers['Set-Cookie'] = $setCookies;
    $headers['Content-Type'] = ($mime->data ?? 'application/octet-stream') . '; charset=' . $encoding;
    $content = Encoding::Convert($content, $encoding, Encoding::UTF8);

    return new MessageResponse(
        $result->code ?: 200, 
        VariableHelper::Extend($headers, $result?->headers ?? []), 
        $content
    );
    
}

function ResponseWithError(
    ServerRequestInterface $request,
    string $type,
    string $message,
    int $code = -1,
    string $cmd = '',
    mixed $data = null
): MessageResponse {

    ErrorHelper::Telegram('@colibri_core_errors', 'Code: 404\nMessage: ' . $message . '\nResult: ' . ddrx([
        'code' => $code,
        'command' => $cmd,
        'data' => $data
    ]));

    return ServerFinish($request, $type, (object) [
        'code' => 404,
        'message' => $message,
        'result' => (object) [
            'code' => $code,
            'command' => $cmd,
            'data' => $data
        ]
    ]);
}

$loop = Loop::get();

$webPath = __DIR__;
$http = new HttpServer(function (ServerRequestInterface $psrRequest) use ($webPath) {
    $request = new Request($psrRequest);
    $response = new Response();
    App::Instance()->Initialize($request, $response, $webPath, true);

    echo "New request received: " . App::$request->address . ' ' . App::$request->uri . "\n";
    flush();
    $waitForAnswer = (App::$server->{'http_waitforanswer'} ?? 'true') === 'true';
    if(!$waitForAnswer) {
        Loop::futureTick(function () use ($psrRequest) {
            HandleRequest($psrRequest);
        });
    } else {
        return HandleRequest($psrRequest);
    }
});

$socket443 = new SocketServer('0.0.0.0:443', [], $loop);
$socket80 = new SocketServer('0.0.0.0:80', [], $loop);

$secure = new SecureServer($socket443, $loop, [
    'local_cert' => '/etc/nginx/ssl/server.crt',
    'local_pk'   => '/etc/nginx/ssl/server.key',
    'allow_self_signed' => true,
    'verify_peer' => false,
]);

$http->listen($secure);
$http->listen($socket80);


$loop->run();
