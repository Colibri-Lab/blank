<?php

set_time_limit(0);

require_once __DIR__ . '/../vendor/autoload.php';

use Colibri\ReactServer;
use Colibri\Utils\Config\Config;

$config = Config::LoadFile('app.yaml');
$configArray = $config->Query('reactserver')->AsArray();

ReactServer::Initialize($configArray);