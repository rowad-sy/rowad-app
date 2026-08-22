<?php

namespace App\Console\Commands;

use App\Services\AuditAnchorService;
use App\Services\AuditLogger;
use Illuminate\Console\Command;

class AuditVerifyCommand extends Command
{
    protected $signature = 'audit:verify
                            {--from= : Start audit log ID (inclusive)}
                            {--to= : End audit log ID (inclusive)}
                            {--fix-anchor : Rebuild the latest anchor after verification}';

    protected $description = 'Verify the integrity of the audit log hash chain';

    public function handle(): int
    {
        $from = $this->option('from') ? (int) $this->option('from') : null;
        $to = $this->option('to') ? (int) $this->option('to') : null;

        $this->newLine();
        $this->line('<info>═══════════════════════════════════════════</info>');
        $this->line('<info>  التحقق من سلامة سجل التدقيق</info>');
        $this->line('<info>═══════════════════════════════════════════</info>');
        $this->newLine();

        $totalRecords = \App\Models\AuditLog::count();

        if ($totalRecords === 0) {
            $this->warn('  لا توجد سجلات في سجل التدقيق بعد.');
            return self::SUCCESS;
        }

        $this->line("  إجمالي السجلات: <comment>{$totalRecords}</comment>");

        if ($from || $to) {
            $rangeLabel = ($from ?? '1') . ' → ' . ($to ?? $totalRecords);
            $this->line("  النطاق المحدد: <comment>{$rangeLabel}</comment>");
        }

        $this->newLine();
        $this->line('  جاري التحقق من السلسلة...');
        $this->newLine();

        $start = microtime(true);
        $result = $this->verifyRange($from, $to);
        $elapsed = round(microtime(true) - $start, 2);

        if ($result['valid']) {
            $this->line("  <fg=green>✓ سلسلة السمات سليمة بالكامل</>");
            $this->line("    السجلات المُتحقق منها: <info>{$result['total_verified']}</info>");
        } else {
            $this->error("  ✗ تم اكتشاف تلاعب في السجلات!");
            $this->error("    آخر سجل سليم: #{$result['last_valid_id']}");
            $this->error("    أول سجل مُخترق: #{$result['broken_at_id']}");
            $this->line("    السجلات المُتحقق منها: <comment>{$result['total_verified']}</comment> / {$result['total_records']}");
        }

        $this->line("    الوقت: {$elapsed} ثانية");
        $this->newLine();

        if ($result['anchor_valid'] === false) {
            $this->warn('  ⚠ لا يوجد م.Anchor صالح يطابق السجلات.');
        } elseif ($result['anchor_valid'] === true) {
            $this->line("  <fg=green>✓ Anchor hash يطابق آخر م Anchor مسجل</>");
        }

        $this->newLine();

        if ($this->option('fix-anchor') && $result['valid']) {
            $this->line('  جاري تحديث Anchor...');
            AuditAnchorService::createAnchor();
            $this->line('  <fg=green>✓ تم إنشاء Anchor جديد بنجاح</>');
            $this->newLine();
        }

        return $result['valid'] ? self::SUCCESS : self::FAILURE;
    }

    protected function verifyRange(?int $from, ?int $to): array
    {
        $query = \App\Models\AuditLog::orderBy('id', 'asc');

        if ($from) {
            $query->where('id', '>=', $from);
        }
        if ($to) {
            $query->where('id', '<=', $to);
        }

        $logs = $query->get();

        $previousHash = null;
        if ($from && $from > 1) {
            $previousLog = \App\Models\AuditLog::where('id', '<', $from)->latest('id')->first();
            $previousHash = $previousLog?->hash;
        }

        $brokenAt = null;
        $lastValidId = null;
        $totalVerified = 0;

        foreach ($logs as $log) {
            $expectedHash = hash('sha256', implode('|', [
                $previousHash,
                $log->request_id,
                (string) $log->user_id,
                $log->model,
                (string) $log->model_id,
                $log->event,
                json_encode($log->old_values ?? []),
                json_encode($log->new_values ?? []),
                $log->created_at->toDateTimeString(),
            ]));

            if ($expectedHash !== $log->hash) {
                $brokenAt = $log->id;
                break;
            }

            $previousHash = $log->hash;
            $lastValidId = $log->id;
            $totalVerified++;
        }

        $anchorValid = null;
        $latestAnchor = AuditAnchorService::getLatestAnchor();
        if ($latestAnchor) {
            $anchorValid = ($latestAnchor['last_hash'] === $previousHash);
        }

        return [
            'valid' => $brokenAt === null,
            'total_verified' => $totalVerified,
            'broken_at_id' => $brokenAt,
            'last_valid_id' => $lastValidId,
            'total_records' => $logs->count(),
            'anchor_valid' => $anchorValid,
        ];
    }
}
