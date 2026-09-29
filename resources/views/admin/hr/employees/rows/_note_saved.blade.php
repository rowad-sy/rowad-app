{{-- ملاحظة محفوظة: للقراءة فقط (صاحبها وتاريخها كما سُجّلا). $note نموذج EmployeeNote --}}
<tr class="saved-row">
    <td>
        <div style="white-space: pre-line">{{ $note->note }}</div>
        <small class="text-muted">{{ $note->user?->name ?? '—' }} - {{ $note->created_at?->locale('ar')->diffForHumans() }}</small>
    </td>
    <td><span class="small text-muted">مسجّلة</span></td>
</tr>
