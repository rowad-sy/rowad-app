@if($logs->isEmpty())
    <div class="text-center py-4 text-muted">
        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
        لا توجد سجلات تغييرات بعد
    </div>
@else
    <div class="audit-timeline">
        @foreach($logs as $log)
            <div class="audit-entry mb-3 p-3 border rounded bg-light">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="badge bg-{{ match($log->event) {
                            'created' => 'success',
                            'updated' => 'primary',
                            'deleted' => 'danger',
                            'restored' => 'warning',
                            'login' => 'info',
                            'logout' => 'secondary',
                            'failed' => 'danger',
                            'import' => 'primary',
                            'export' => 'info',
                            'approve' => 'success',
                            'reject' => 'danger',
                            default => 'secondary',
                        } }}">
                            {{ match($log->event) {
                                'created' => 'إنشاء',
                                'updated' => 'تعديل',
                                'deleted' => 'حذف',
                                'restored' => 'استعادة',
                                'login' => 'دخول',
                                'logout' => 'خروج',
                                'failed' => 'محاولة فاشلة',
                                'import' => 'استيراد',
                                'export' => 'تصدير',
                                'approve' => 'موافقة',
                                'reject' => 'رفض',
                                default => $log->event,
                            } }}
                        </span>
                        @if($log->user)
                            <span class="text-muted ms-2">{{ $log->user->name }} <small>({{ $log->user->email }})</small></span>
                        @else
                            <span class="text-muted ms-2">نظام</span>
                        @endif
                    </div>
                    <small class="text-muted">{{ $log->created_at->format('Y-m-d H:i:s') }}</small>
                </div>

                @if($log->description)
                    <div class="text-muted small mb-2">{{ $log->description }}</div>
                @endif

                @if($log->changes->count())
                    <table class="table table-sm table-bordered mb-0 mt-2">
                        <thead class="table-light">
                            <tr>
                                <th style="width:25%">الحقل</th>
                                <th style="width:37.5%">القيمة السابقة</th>
                                <th style="width:37.5%">القيمة الجديدة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($log->changes as $change)
                                <tr>
                                    <td>
                                        <span class="fw-medium">{{ $change->label }}</span>
                                        <br><small class="text-muted">{{ $change->field }}</small>
                                    </td>
                                    <td dir="auto">
                                        @if($change->is_masked)
                                            <span class="text-muted">***</span>
                                        @else
                                            <span class="text-danger text-decoration-line-through">{{ $change->old_value ?? 'فارغ' }}</span>
                                        @endif
                                    </td>
                                    <td dir="auto">
                                        @if($change->is_masked)
                                            <span class="text-muted">***</span>
                                        @else
                                            <span class="text-success fw-medium">{{ $change->new_value ?? 'فارغ' }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @elseif(in_array($log->event, ['created', 'deleted']))
                    <div class="small text-muted mt-1">
                        @if($log->event === 'created' && $log->new_values)
                            تم الإنشاء بـ {{ count($log->new_values) }} حقل
                        @elseif($log->event === 'deleted' && $log->old_values)
                            تم الحذف واحتوى على {{ count($log->old_values) }} حقل
                        @endif
                    </div>
                @endif

                @if($log->ip_address)
                    <div class="small text-muted mt-2">
                        <i class="bi bi-globe2"></i> {{ $log->ip_address }}
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endif
