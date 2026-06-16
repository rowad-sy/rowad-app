@props(['perPage' => 10, 'sizes' => [10, 25, 50, 100]])

<div class="d-flex align-items-center gap-2">
    <label class="form-label mb-0 small text-nowrap">عدد الصفوف:</label>
    <select name="per_page" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
        @foreach ($sizes as $size)
            <option value="{{ $size }}" {{ (int)$perPage === $size ? 'selected' : '' }}>{{ $size }}</option>
        @endforeach
    </select>
</div>
