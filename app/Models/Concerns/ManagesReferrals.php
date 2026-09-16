<?php

namespace App\Models\Concerns;

use App\Models\Referral;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/*
 * إدارة الإحالات الموحّدة عبر جدول referrals:
 * - referTo: إحالة لخطوة معينة (تُنهي الإحالات النشطة السابقة لنفس الخطوة).
 * - completeReferral: إنهاء إحالة الخطوة بعد أداء مَن وصلها (موافقة/رفض/تنفيذ).
 * - cancelActiveReferrals: إلغاء الإحالات النشطة عند إنهاء الكيان/رفضه.
 * - currentRecipientIds: مستلمو الكيان الحاليون (الأشخاص الذين يجب أن
 *   يروه/يعتمدوه فقط + المنشئ + السوبر أدمن).
 */
trait ManagesReferrals
{
    public function referrals(): MorphMany
    {
        return $this->morphMany(Referral::class, 'workable');
    }

    public function activeReferrals()
    {
        return $this->referrals()->where('status', 'active');
    }

    public function currentRecipientIds(): array
    {
        return $this->activeReferrals()
            ->pluck('to_user_id')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function currentRecipients(): array
    {
        return $this->activeReferrals()
            ->with('toUser')
            ->get()
            ->pluck('toUser')
            ->filter()
            ->unique('id')
            ->all();
    }

    public function referTo(int $toUserId, string $step, ?string $note = null): Referral
    {
        $this->activeReferrals()->where('step', $step)->update(['status' => 'cancelled']);

        return $this->referrals()->create([
            'from_user_id' => auth()->id(),
            'to_user_id' => $toUserId,
            'step' => $step,
            'status' => 'active',
            'note' => $note,
        ]);
    }

    public function referToMany(array $toUserIds, string $step, ?string $note = null): void
    {
        $this->activeReferrals()->where('step', $step)->update(['status' => 'cancelled']);

        foreach (array_unique(array_filter($toUserIds)) as $toUserId) {
            $this->referrals()->create([
                'from_user_id' => auth()->id(),
                'to_user_id' => $toUserId,
                'step' => $step,
                'status' => 'active',
                'note' => $note,
            ]);
        }
    }

    public function completeReferral(?string $step = null, ?string $toUserId = null): void
    {
        $query = $this->activeReferrals();

        if ($step !== null) {
            $query->where('step', $step);
        }

        if ($toUserId !== null) {
            $query->where('to_user_id', $toUserId);
        }

        $query->update(['status' => 'done']);
    }

    public function cancelActiveReferrals(?string $step = null): void
    {
        $query = $this->activeReferrals();

        if ($step !== null) {
            $query->where('step', $step);
        }

        $query->update(['status' => 'cancelled']);
    }

    /*
     * هل المستخدم هو المستلَم الحالي لإحدى الخطوات النشطة؟
     */
    public function isCurrentRecipient(?int $userId): bool
    {
        if ($userId === null) {
            return false;
        }

        return $this->activeReferrals()->where('to_user_id', $userId)->exists();
    }
}