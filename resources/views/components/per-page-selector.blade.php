@props(['perPage' => 10, 'sizes' => [10, 25, 50, 100], 'auto' => true])

<div class="d-flex align-items-center gap-2">
    <label class="form-label mb-0 small text-nowrap" for="per_page_select">عدد الصفوف:</label>
    <select id="per_page_select" name="per_page" class="form-select form-select-sm" style="width:auto;" @if ($auto) onchange="this.form.submit()" @endif>
        @foreach ($sizes as $size)
            <option value="{{ $size }}" {{ (int)$perPage === $size ? 'selected' : '' }}>{{ $size }}</option>
        @endforeach
    </select>
</div>
