@php $value = $block?->json_value; @endphp

@if ($section['type'] === 'fields')
    @foreach (($section['fields'] ?? []) as $field)
        <div class="mb-2">
            <label class="form-label small fw-semibold mb-1">{{ $field }}</label>
            <input type="text" name="blocks[{{ $section['key'] }}][fields][{{ $field }}]"
                   class="form-control form-control-sm"
                   value="{{ old('blocks.'.$section['key'].'.fields.'.$field, $value[$field] ?? '') }}">
        </div>
    @endforeach
@elseif ($section['type'] === 'table')
    <div class="table-responsive">
        <table class="table table-bordered table-sm table-edit align-middle">
            <thead>
                <tr>
                    @foreach (($section['columns'] ?? []) as $column)
                        <th>{{ $column }}</th>
                    @endforeach
                    <th style="width: 40px;"></th>
                </tr>
            </thead>
            <tbody class="table-edit-rows">
                @php $rows = (array) ($value ?? []); @endphp
                @forelse ($rows as $r => $row)
                    <tr class="table-edit-row">
                        @foreach (($section['columns'] ?? []) as $c => $column)
                            <td><input type="text" name="blocks[{{ $section['key'] }}][rows][{{ $r }}][{{ $c }}]"
                                       class="form-control form-control-sm" value="{{ $row[$c] ?? '' }}"></td>
                        @endforeach
                        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger table-edit-remove"><i class="bi bi-x-lg"></i></button></td>
                    </tr>
                @empty
                    <tr class="table-edit-row">
                        @foreach (($section['columns'] ?? []) as $c => $column)
                            <td><input type="text" name="blocks[{{ $section['key'] }}][rows][0][{{ $c }}]"
                                       class="form-control form-control-sm"></td>
                        @endforeach
                        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger table-edit-remove"><i class="bi bi-x-lg"></i></button></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary table-edit-add"
            data-key="{{ $section['key'] }}" data-cols="{{ count($section['columns'] ?? []) }}">
        <i class="bi bi-plus-lg"></i> إضافة صف
    </button>
@elseif ($section['type'] === 'list')
    <div class="list-edit-items">
        @php $items = (array) ($value ?? []); @endphp
        @forelse ($items as $item)
            <div class="input-group mb-1 list-edit-item">
                <input type="text" name="blocks[{{ $section['key'] }}][items][]" class="form-control form-control-sm" value="{{ $item }}">
                <button type="button" class="btn btn-outline-danger btn-sm list-edit-remove"><i class="bi bi-x-lg"></i></button>
            </div>
        @empty
            <div class="input-group mb-1 list-edit-item">
                <input type="text" name="blocks[{{ $section['key'] }}][items][]" class="form-control form-control-sm">
                <button type="button" class="btn btn-outline-danger btn-sm list-edit-remove"><i class="bi bi-x-lg"></i></button>
            </div>
        @endforelse
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary list-edit-add" data-key="{{ $section['key'] }}">
        <i class="bi bi-plus-lg"></i> إضافة بند
    </button>
@else
    <textarea name="blocks[{{ $section['key'] }}][paragraph]" rows="5"
              class="form-control" placeholder="اكتب محتوى القسم هنا...">{{ old('blocks.'.$section['key'].'.paragraph', $value ?? '') }}</textarea>
@endif