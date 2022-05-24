<?php

use Colibri\App;
use Colibri\Utils\Cache\Bundle;
use Colibri\Web\Templates\PhpTemplate;
use App\Modules\Sites\Models\Domain;
use App\Modules\Sites\Models\Domains;

$langModule = App::$moduleManager->lang;

?>
<!DOCTYPE html>
<html lang="ru">
    <head>

        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    
        <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE9">

        <link rel="stylesheet" href="<?=Bundle::Automate(App::$domainKey.($langModule ? '.'.$langModule->current : '').'.assets.css', 'scss', array_merge(
            [['path' => App::$appRoot.'vendor/colibri/ui/src/']], 
            [['path' => App::$webRoot.'res/css/']], 
            App::$moduleManager->GetPaths('.Bundle/'),
            App::$moduleManager->GetPaths('templates/')
        ))?>" type="text/css">

        <script type="text/javascript" src="<?=Bundle::Automate(App::$domainKey.($langModule ? '.'.$langModule->current : '').'.assets.js', 'js', array_merge(
            [['path' => App::$appRoot.'vendor/colibri/ui/src/', 'exts' => ['js', 'html']]], 
            App::$moduleManager->GetPaths('.Bundle/', ['exts' => ['js', 'html']]), 
            App::$moduleManager->GetPaths('templates/', ['exts' => ['js', 'html']]), 
        ))?>"></script>


        <?php
            $headerTemplates = App::$moduleManager->GetTemplates('header');
            foreach($headerTemplates as $template) {
                /** @var PhpTemplate $template */
                echo $template->Render($args);
            }
        ?>

    </head>
    <body>
        <?php
            $headerTemplates = App::$moduleManager->GetTemplates('body');
            foreach($headerTemplates as $template) {
                /** @var PhpTemplate $template */
                echo $template->Render($args);
            }
        ?>
    </body>
    <?php
        $footerTemplates = App::$moduleManager->GetTemplates('footer');
        foreach($footerTemplates as $template) {
            /** @var PhpTemplate $template */
            echo $template->Render($args);
        }
    ?>

</html>