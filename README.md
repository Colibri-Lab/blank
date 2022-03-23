# Балванка проекта Colibri

```
composer create-project colibri/blank ./#path-to-application#/ --repository="{\"url\": \"https://gitlab.repeatme.online/colibrilab/blank.git\", \"type\": \"vcs\"}" --stability=dev --remove-vcs
```
при установке будет задан вопрос Выберите режим (local|dev|test|prod)
необходимо выбрать какой набор конфигурации установить, по умолчанию будет установлен режим prod

для установки на прод-стенд необходимо выполнить 
```
composer create-project colibri/blank-project ./#path-to-application#/ --repository="{\"url\": \"https://gitlab.repeatme.online/colibrilab/blank.git\", \"type\": \"vcs\"}" --stability=dev --remove-vcs --no-dev
```

режим будет выбран prod автоматически

# дальше добавляем нужные модули (в модуле должен присутствовать postInstall)

# например
```
composer require colibri/lk --repository="{\"url\": \"https://git:V5P3EvURpCR4yS_aecbh@gitlab.repeatme.online/colibrilab/modules/lk.git\", \"type\": \"vcs\"}"
```

# далее 2 варианта
- nginx/php-fpm

```
server {

    # домен (должен заканчиваться local.bsft.loc)
    server_name <project name>.local.bsft.loc;

    listen 443 ssl;

    # включить сертификаты за сертификатами сходить к @grigorjan
    # include snippets/wildcard-root.conf;

    root <path-to-project>/web;
    index index.php;

    access_log <path-to-logs>/<project name>-access.log;
    error_log <path-to-logs>/<project name>-error.log notice;

    client_body_buffer_size 1m;
    client_max_body_size 100M;

    location / {
        if (!-e $request_filename) {
            rewrite .* /index.php;
        }
    }

    location ~* ^.+\.(xml|html) {
        if (!-e $request_filename) {
            rewrite .* /index.php;
        }
        if (-e $request_filename) {
            expires     modified +168h;
            add_header  Cache-Control  private;
        }

    }

    location ~* ^.+\.(jpg|jpeg|gif|png|ico|mp3|css|zip|tgz|gz|rar|bz2|doc|xls|exe|pdf|dat|avi|ppt|txt|tar|mid|midi|wav|bmp|rtf|wmv|mpeg|mpg|tbz|js|woff|ttf|eot|svg|swf)$ {
        if (-e $request_filename) {
            expires     modified +168h;
            add_header  Cache-Control  private;
            add_header  Access-Control-Allow-Origin *;
        }
    }


    location ~ \.php {

        fastcgi_pass unix:/run/php/php8.0-fpm.sock;
        include snippets/fastcgi-php.conf;
        fastcgi_intercept_errors on;
        fastcgi_read_timeout 300;
        fastcgi_ignore_client_abort on;
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;

    }


}
```
