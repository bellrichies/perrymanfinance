<?php

declare(strict_types=1);

use PerrymanFinance\Config\Environment;
use PerrymanFinance\Database\PdoConnectionManager;

require dirname(__DIR__) . '/vendor/autoload.php';
$basePath = dirname(__DIR__);
$config = Environment::load($basePath);
return ['base_path' => $basePath, 'config' => $config, 'connections' => new PdoConnectionManager($config)];
