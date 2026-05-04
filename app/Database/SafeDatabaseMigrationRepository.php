<?php

namespace App\Database;

use Illuminate\Database\Migrations\DatabaseMigrationRepository;

class SafeDatabaseMigrationRepository extends DatabaseMigrationRepository
{
    public function getNextBatchNumber()
    {
        return $this->getLastBatchNumber() + 1;
    }

    public function getLastBatchNumber()
    {
        $maxBatch = 0;

        foreach ($this->table()->pluck('batch')->all() as $batch) {
            $batch = is_string($batch) ? trim($batch) : $batch;

            if ($batch === null || $batch === '' || ! is_numeric($batch)) {
                continue;
            }

            $maxBatch = max($maxBatch, (int) $batch);
        }

        return $maxBatch;
    }

    public function log($file, $batch)
    {
        parent::log($file, (int) $batch);
    }
}
