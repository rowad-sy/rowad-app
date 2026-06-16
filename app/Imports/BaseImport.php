<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BaseImport implements ToCollection, WithHeadingRow
{
    public function __construct(
        protected string $modelClass,
        protected array $fieldMap,   // ['excel_column' => 'db_column']
        protected ?\Closure $afterCreate = null,
    ) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $data = [];
            foreach ($this->fieldMap as $excelCol => $dbCol) {
                $data[$dbCol] = $row[$excelCol] ?? null;
            }
            $record = $this->modelClass::create($data);

            if ($this->afterCreate) {
                ($this->afterCreate)($record, $row);
            }
        }
    }
}
