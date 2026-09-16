<?php

namespace App\Console\Commands;

use App\Models\Admin\Logistics\PurchaseRequest;
use App\Models\Admin\MediaPlan;
use App\Models\Admin\MovementPlan;
use Illuminate\Console\Command;

/*
 * ملء حركة إحالات لقاعدة البيانات القديمة من أعمدة refer_to_*:
 * فقط الخطوة الحالية تُسجَّل إحالة نشطة (حسب الحالة)، والباقي يُسجَّل
 * كإحالات منجزة للتدقيق. الأوامر تكرارية (idempotent).
 */
class BackfillReferrals extends Command
{
    protected $signature = 'referrals:backfill';

    protected $description = 'وضع إحالات نشطة من بيانات refer_to_* القديمة للوحدات الثلاث';

    public function handle(): int
    {
        $this->backfillPurchaseRequests();
        $this->backfillMovementPlans();
        $this->backfillMediaPlans();

        $this->info('تمت مزامنة الإحالات القديمة.');

        return self::SUCCESS;
    }

    private function backfillPurchaseRequests(): void
    {
        $stepByStatus = [
            'pending' => ['logistics', 'direct_manager'],
            'priced' => 'direct_manager',
            'pm_approved' => 'pm2',
            'pm2_approved' => 'finance',
            'finance_approved' => 'executive',
        ];

        PurchaseRequest::query()
            ->select('id', 'status', 'refer_to_logistics_id', 'refer_to_direct_manager_id', 'refer_to_pm2_id', 'refer_to_finance_id', 'refer_to_executive_id')
            ->orderBy('id')
            ->chunk(200, function ($requests) use ($stepByStatus) {
                foreach ($requests as $r) {
                    if (in_array($r->status, ['approved', 'executed', 'rejected'], true)) {
                        $this->seedDone($r, ['logistics', 'direct_manager', 'pm2', 'finance', 'executive']);
                        continue;
                    }

                    $columnMap = [
                        'logistics' => $r->refer_to_logistics_id,
                        'direct_manager' => $r->refer_to_direct_manager_id,
                        'pm2' => $r->refer_to_pm2_id,
                        'finance' => $r->refer_to_finance_id,
                        'executive' => $r->refer_to_executive_id,
                    ];
                    $this->seedCurrent($r, $stepByStatus[$r->status] ?? null, $columnMap);
                }
            });
    }

    private function backfillMovementPlans(): void
    {
        MovementPlan::query()
            ->select('id', 'status', 'refer_to_pm2_id', 'refer_to_movement_officer_id')
            ->with('recipients')
            ->orderBy('id')
            ->chunk(200, function ($plans) {
                foreach ($plans as $p) {
                    if (in_array($p->status, ['completed', 'rejected', 'cancelled'], true)) {
                        $this->seedDone($p, ['pm2', 'movement_officer']);
                        continue;
                    }

                    if ($p->status === 'assigned') {
                        $recipientIds = $p->recipients->pluck('user_id')->filter()->values()->all();
                        $p->referToMany($recipientIds, 'recipient');
                        $this->seedDone($p, ['pm2', 'movement_officer']);
                    } elseif ($p->status === 'approved') {
                        $this->seedDone($p, ['pm2']);
                        $p->refer_to_movement_officer_id and $p->referTo($p->refer_to_movement_officer_id, 'movement_officer');
                    } else {
                        $this->seedCurrent($p, 'pm2', ['pm2' => $p->refer_to_pm2_id]);
                    }
                }
            });
    }

    private function backfillMediaPlans(): void
    {
        $stepByStatus = [
            'review' => 'direct_manager',
            'manager_approved' => 'pm2',
            'pm2_approved' => 'media_manager',
            'media_manager_approved' => 'media_officer',
            'executing' => 'media_officer',
        ];

        MediaPlan::query()
            ->select('id', 'status', 'refer_to_direct_manager_id', 'refer_to_pm2_id', 'refer_to_media_manager_id', 'refer_to_media_officer_id')
            ->orderBy('id')
            ->chunk(200, function ($plans) use ($stepByStatus) {
                foreach ($plans as $p) {
                    if (in_array($p->status, ['executed', 'rejected'], true)) {
                        $this->seedDone($p, ['direct_manager', 'pm2', 'media_manager', 'media_officer']);
                        continue;
                    }

                    $columnMap = [
                        'direct_manager' => $p->refer_to_direct_manager_id,
                        'pm2' => $p->refer_to_pm2_id,
                        'media_manager' => $p->refer_to_media_manager_id,
                        'media_officer' => $p->refer_to_media_officer_id,
                    ];
                    $this->seedCurrent($p, $stepByStatus[$p->status] ?? null, $columnMap);
                }
            });
    }

    private function seedCurrent($model, array|string|null $currentSteps, array $columnMap): void
    {
        $currentSteps = $currentSteps === null ? [] : (array) $currentSteps;

        foreach ($columnMap as $step => $userId) {
            if (! $userId) {
                continue;
            }

            $exists = $model->referrals()->where('step', $step)->exists();

            if (! $exists) {
                $model->referrals()->create([
                    'from_user_id' => null,
                    'to_user_id' => $userId,
                    'step' => $step,
                    'status' => in_array($step, $currentSteps, true) ? 'active' : 'done',
                ]);
            }
        }
    }

    private function seedDone($model, array $steps): void
    {
        foreach ($steps as $step) {
            if (! $model->referrals()->where('step', $step)->exists()) {
                $model->referrals()->create([
                    'from_user_id' => null,
                    'to_user_id' => null,
                    'step' => $step,
                    'status' => 'done',
                ]);
            }
        }
    }
}