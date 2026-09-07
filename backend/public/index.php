<?php

declare(strict_types=1);

use PerrymanFinance\Core\Application;
use PerrymanFinance\Http\Request;

/** @var Application $application */
$application = require dirname(__DIR__) . '/bootstrap/app.php';
$rawBody = file_get_contents('php://input');
$application->handle(Request::fromGlobals($_SERVER, $_GET, $_POST, $_FILES, $rawBody === false ? '' : $rawBody))->send();
