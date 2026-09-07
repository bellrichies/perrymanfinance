<?php

declare(strict_types=1);

namespace PerrymanFinance\Database;

use PDO;

interface ConnectionInterface
{
    public function connection(): PDO;
}
