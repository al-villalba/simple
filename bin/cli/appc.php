<?php

require __DIR__ . '/../../mvc/src/bootstrap.php';

$app = Simple\Application::getInstance();
exit($app->run());
