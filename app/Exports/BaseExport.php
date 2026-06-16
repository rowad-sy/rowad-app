<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BaseExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected Collection $records,
        protected array $headings,
        protected array $columns,
        protected ?array $callbacks = null,
    ) {}

    public function collection(): Collection
    {
        return $this->records;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function map($row): array
    {
        $data = [];
        foreach ($this->columns as $i => $column) {
            if ($column instanceof \Closure) {
                $value = $column($row);
            } else {
                $value = data_get($row, $column);
            }
            $cb = $this->callbacks[$i] ?? null;
            $data[] = $cb ? $cb($value, $row) : $value;
        }
        return $data;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
