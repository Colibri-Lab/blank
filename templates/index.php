<?php

use Colibri\App;
use Colibri\Utils\Cache\Bundle;
use Colibri\Web\Templates\PhpTemplate;
use App\Modules\Sites\Models\Domain;
use App\Modules\Sites\Models\Domains;
use Colibri\AppException;

$langModule = App::$moduleManager->lang;

$headers = [];
$headerTemplates = App::$moduleManager->GetTemplates('header');
foreach($headerTemplates as $template) {
    /** @var PhpTemplate $template */
    $headers[] = $template->Render($args);
}

$bodies = [];
$bodyTemplates = App::$moduleManager->GetTemplates('body');
foreach($bodyTemplates as $template) {
    /** @var PhpTemplate $template */
    $bodies[] = $template->Render($args);
}

$footers = [];
$footerTemplates = App::$moduleManager->GetTemplates('footer');
foreach($footerTemplates as $template) {
    /** @var PhpTemplate $template */
    $footers[] = $template->Render($args);
}

$themeFile = null;
$themeKey = '';
if(App::$moduleManager->tools) {
    $themeFile = App::$moduleManager->tools->Theme(App::$domainKey);
    $themeKey = md5($themeFile);
}

$web = str_replace(App::$appRoot, '', App::$webRoot);
$permanent = App::$config->Query('permanent')->GetValue();

?>
<!DOCTYPE html>
<html lang="<?=($langModule ? $langModule->current : '')?>">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE9" />
        <?php /* ?><style>body { display: none; }</style><?php */ ?>
        <?=implode('', $headers ?? [])?>
    </head>
    <body>
        <?=implode('', $bodies ?? [])?>
    </body>
    <link rel="stylesheet" href="<?=Bundle::Automate(App::$domainKey, ($langModule ? $langModule->current : '').($themeKey ? '.'.$themeKey : '').'.assets.css', 'scss', array_merge(
        [['path' => App::$appRoot.'vendor/colibri/ui/src/']], 
        [['path' => $themeFile]], 
        [['path' => App::$webRoot.$permanent.'css/']], 
        App::$moduleManager->GetPaths($web.'res/css/'),
        App::$moduleManager->GetPaths('.Bundle/'),
        App::$moduleManager->GetPaths('templates/')
    ))?>" type="text/css" />
    <script type="text/javascript" src="<?=Bundle::Automate(App::$domainKey, ($langModule ? $langModule->current : '').'.assets.js', 'js', array_merge(
        [['path' => App::$appRoot.'vendor/colibri/ui/src/', 'exts' => ['js', 'html']]], 
        App::$moduleManager->GetPaths('.Bundle/', ['exts' => ['js', 'html']]), 
        App::$moduleManager->GetPaths('templates/', ['exts' => ['js', 'html']]), 
    ))?>"></script>    
    <?=implode('', $footers ?? [])?>
</html>