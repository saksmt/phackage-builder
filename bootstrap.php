<?php

use Smt\PhackageBuilder\Application\PhackageBuilderApp;

require __DIR__ . '/vendor/autoload.php';

$app = new PhackageBuilderApp();
$app->run();