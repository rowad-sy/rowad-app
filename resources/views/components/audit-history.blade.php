@props(['model', 'modelId'])

<button type="button"
        class="btn btn-sm btn-outline-secondary"
        onclick="loadAuditHistory('{{ addslashes($model) }}', {{ $modelId }})"
        title="سجل التغييرات">
    <i class="bi bi-clock-history"></i>
</button>
