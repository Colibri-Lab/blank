<?php

set_time_limit(0);
require_once __DIR__.'/../vendor/autoload.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE9" />
    <title><?=$code?>: <?=$message?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>

<body>
</body>

    <h1>Something wrong with page you tried to request</h1>

    <p>
        <strong>Error: <?=$code?></strong>
    </p>
    <p>
        <?=$message?>
    </p>
    <p>
        Error data: <br />
        File: <strong><?=$file?></strong>, Line: <strong><?=$line?></strong>
    </p>
    <p>
        Trace: <br />
        <?=str_replace("\n", '<br />', $trace ?? '')?>
    </p>


</html>
