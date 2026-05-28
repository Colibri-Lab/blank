<?php

set_time_limit(0);

require_once __DIR__ . '/../vendor/autoload.php';

use Colibri\ReactServer;
use Colibri\Utils\Config\Config;

$config = \yaml_parse_file(__DIR__ . '/../config/app.yaml');
$reactserver = $config['reactserver'];
if(strstr($reactserver, 'include(') !== false) {
    $reactserver = str_replace(['include(', ')'], '', $reactserver);
    $reactserver = \yaml_parse_file(__DIR__ . '/../config/' . $reactserver);
    $config['reactserver'] = $reactserver;
}
$configArray = $config['reactserver'];

ReactServer::Initialize($configArray, __DIR__);