<?php

namespace App\Imports\Students\Sheets;

use App\Imports\Students\StudentFullImport;
use App\Models\Admin\Student\CertificateSigner;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithTitle;

class SignersSheetImport implements ToCollection, WithHeadingRow, WithTitle
{
    public function __construct(protected StudentFullImport $parent) {}

    public function title(): string
    {
        return 'signers';
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function collection(Collection $rows): void
    {
        $validRoles = array_keys(CertificateSigner::ROLES);
        $labelToRole = array_flip(CertificateSigner::ROLES);

        foreach ($rows as $row) {
            $name = trim((string) ($row['name_ar'] ?? ''));
            if ($name === '') {
                continue;
            }

            $role = trim((string) ($row['role'] ?? ''));
            if (!in_array($role, $validRoles, true)) {
                $role = $labelToRole[$role] ?? null;
            }
            if (!in_array((string) $role, $validRoles, true)) {
                continue;
            }

            $code = trim((string) ($row['signer_code'] ?? ''));

            $signer = null;
            if ($code !== '') {
                $signer = CertificateSigner::where('code', $code)->first();
            }
            if (!$signer) {
                $signer = CertificateSigner::where('name_ar', $name)->where('role', $role)->first();
            }

            $signer = CertificateSigner::updateOrCreate(
                ['id' => $signer?->id],
                array_filter([
                    'code' => $code !== '' ? $code : ($signer?->code),
                    'name_ar' => $name,
                    'role' => $role,
                ], fn($v) => $v !== null)
            );

            if ($code !== '') {
                $this->parent->registerSigner($code, $signer->id);
            }
            $this->parent->registerSigner($name . '|' . $role, $signer->id);
            $this->parent->signersImported++;
        }
    }
}
