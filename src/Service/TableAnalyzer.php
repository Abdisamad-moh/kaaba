<?php 
// src/Service/TableAnalyzer.php
namespace App\Service;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

class TableAnalyzer
{
    private $connection;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->connection = $entityManager->getConnection();
    }

    public function analyzeUserDetails(int $userId): array
    {
        $tableName = 'user_details';
        $schemaManager = $this->connection->createSchemaManager();
        $columns = $schemaManager->listTableColumns($tableName);
        $totalColumns = count($columns);

        $emptyColumnsCount = 0;
        foreach ($columns as $column) {
            $columnName = $column->getName();
            $emptyCount = $this->connection->fetchOne(
                "SELECT COUNT(*) FROM $tableName WHERE user_id = :userId AND ($columnName IS NULL OR $columnName = '')",
                ['userId' => $userId]
            );
            if ($emptyCount > 0) {
                $emptyColumnsCount++;
            }
        }

        $percentageEmpty = ($emptyColumnsCount / $totalColumns) * 100;

        return [
            'total_columns' => $totalColumns,
            'empty_columns' => $emptyColumnsCount,
            'percentage_empty' => $percentageEmpty
        ];
    }
}
