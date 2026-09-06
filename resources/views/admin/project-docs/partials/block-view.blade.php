@php
    $value = $block?->json_value;
    $filled = $block && $document->isBlockComplete($block);
@endphp

<div class="block-body">
    @if ($section['type'] === 'fields')
        @php $fields = $section['fields'] ?? []; @endphp
        @forelse ($fields as $field)
            <div class="row mb-2">
                <label class="col-sm-4 text-muted">{{ $field }}</label>
                <div class="col-sm-8">{{ $value[$field] ?? '—' }}</div>
            </div>
        @empty
            <span class="text-muted">لا حقول</span>
        @endforelse
    @elseif ($section['type'] === 'table')
        @php $columns = $section['columns'] ?? []; @endphp
        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead>
                    <tr>
                        @foreach ($columns as $column)
                            <th>{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse (($value ?? []) as $row)
                        <tr>
                            @foreach ($columns as $i => $column)
                                <td>{{ $row[$i] ?? '' }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($columns) }}" class="text-muted text-center">لا صفوف بعد</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @elseif ($section['type'] === 'list')
        <ul class="mb-0">
            @forelse (($value ?? []) as $item)
                <li>{{ $item }}</li>
            @empty
                <li class="text-muted">لا بنود بعد</li>
            @endforelse
        </ul>
    @else
        <div>{{ $value ?: '—' }}</div>
    @endif
</div>