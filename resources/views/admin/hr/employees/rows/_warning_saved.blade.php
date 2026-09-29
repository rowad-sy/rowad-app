{{-- تنبيه محفوظ: للقراءة فقط (السجلات إضافية فقط ولا تُعاد عند الحفظ). $warning نموذج Warning --}}
<tr class="saved-row">
    <td>{{ $warning->date?->format('Y-m-d') ?? '—' }}</td>
    <td>{{ $warning->reason }}</td>
    <td>{{ ['verbal' => 'شفهي', 'written' => 'كتابي', 'termination' => 'إنذار بالفصل'][$warning->level] ?? $warning->level }}</td>
    <td class="text-center">
        <x-status-badge :tone="$warning->is_folded ? 'success' : 'warning'">{{ $warning->is_folded ? 'مطوي' : 'غير مطوي' }}</x-status-badge>
        @if ($warning->is_folded && $warning->fold_reason)<div class="small text-muted">{{ $warning->fold_reason }}</div>@endif
    </td>
    <td><span class="small text-muted">مسجّل</span></td>
</tr>
