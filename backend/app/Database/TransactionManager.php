<?php

declare(strict_types=1);

namespace PerrymanFinance\Database;

use PDO;
use Throwable;

final readonly class TransactionManager
{
    public function __construct(private ConnectionInterface $connections)
    {
    }

    /**
     * @template T
     * @param callable(PDO): T $operation
     * @return T
     */
    public function run(callable $operation): mixed
    {
        $pdo = $this->connections->connection();
        $pdo->beginTransaction();
        try {
            $result = $operation($pdo);
            $pdo->commit();
            return $result;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }
}
