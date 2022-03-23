<?php

use Colibri\App;
use Colibri\Utils\Cache\Bundle;
use Colibri\Web\Templates\PhpTemplate;

?>
<!DOCTYPE html>
<html lang="ru" class="gbsreps">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        
        <title>Бухсофт</title>
        <meta name="google" content="notranslate"/>
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE9">

        <?php
            $headerTemplates = App::$moduleManager->GetTemplates('header');
            foreach($headerTemplates as $template) {
                /** @var PhpTemplate $template */
                echo $template->Render($args);
            }
        ?>

        <link rel="stylesheet" href="<?=Bundle::Automate('web.assets.css', 'scss', array_merge(
            [['path' => App::$appRoot.'vendor/colibri/ui/src/']], 
            [['path' => App::$webRoot.'res/css/']], 
            App::$moduleManager->GetPaths('.Bundle/')
        ))?>" type="text/css">

        <script type="text/javascript" src="<?=Bundle::Automate('web.assets.js', 'js', array_merge(
            [['path' => App::$appRoot.'vendor/colibri/ui/src/', 'exts' => ['js', 'html']]], 
            App::$moduleManager->GetPaths('.Bundle/', ['exts' => ['js', 'html']]), 
        ))?>"></script>

    </head>
    <body>
    </body>
    <?php
        $footerTemplates = App::$moduleManager->GetTemplates('footer');
        foreach($footerTemplates as $template) {
            /** @var PhpTemplate $template */
            echo $template->Render($args);
        }
    ?>
</html>