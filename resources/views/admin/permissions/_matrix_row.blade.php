{{-- صف من مصفوفة الصلاحيات. يُستخدم مع $ri (رقم الصف أو '__ROW__' في القالب) و$row --}}
<tr data-row-index="{{ $ri }}">
    <td class="row-col">
        <div class="d-flex align-items-start gap-2">
            <div class="flex-grow-1">
                <div class="d-flex gap-2 mb-1">
                    <div class="form-check form-check-inline mb-0">
                        <input type="radio" class="form-check-input" name="rows[{{ $ri }}][assign_to]" value="user"
                               data-assign="user" id="assign_user_{{ $ri }}" onchange="setRowAssign(this)"
                               {{ ($row['assign_to'] ?? 'user') === 'user' ? 'checked' : '' }}>
                        <label class="form-check-label" for="assign_user_{{ $ri }}">مستخدم</label>
                    </div>
                    <div class="form-check form-check-inline mb-0">
                        <input type="radio" class="form-check-input" name="rows[{{ $ri }}][assign_to]" value="group"
                               data-assign="group" id="assign_group_{{ $ri }}" onchange="setRowAssign(this)"
                               {{ ($row['assign_to'] ?? 'user') === 'group' ? 'checked' : '' }}>
                        <label class="form-check-label" for="assign_group_{{ $ri }}">مجموعة</label>
                    </div>
                </div>
                <div data-field="user" style="{{ ($row['assign_to'] ?? 'user') === 'user' ? '' : 'display:none;' }}">
                    <select name="rows[{{ $ri }}][user_id]" aria-label="المستخدم في الصف" class="form-select form-select-sm mb-1 @error("rows.$ri.user_id") is-invalid @enderror">
                        <option value="">اختر مستخدم</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" {{ old("rows.$ri.user_id", $row['user_id'] ?? '') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    @error("rows.$ri.user_id") <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div data-field="group" style="{{ ($row['assign_to'] ?? 'user') === 'group' ? '' : 'display:none;' }}">
                    <select name="rows[{{ $ri }}][group_id]" aria-label="المجموعة في الصف" class="form-select form-select-sm mb-1 @error("rows.$ri.group_id") is-invalid @enderror">
                        <option value="">اختر مجموعة</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}" {{ old("rows.$ri.group_id", $row['group_id'] ?? '') == $group->id ? 'selected' : '' }}>
                                {{ $group->name }}
                            </option>
                        @endforeach
                    </select>
                    @error("rows.$ri.group_id") <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="row-actions">
                <button type="button" class="btn btn-link btn-sm p-0 row-select-all" onclick="setRowAll(this, true)">تحديد كل الصف</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="duplicateRow(this)"
                        title="نسخ الصف بقيمه لتعديله">
                    <i class="bi bi-files me-1" aria-hidden="true"></i> نسخ
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)" aria-label="حذف الصف" title="حذف الصف">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </td>
    @foreach ($modelGroups as $category => $models)
        @php $catIndex = $loop->index; @endphp
        @foreach ($models as $model => $label)
            @php $modelKey = $modelKeys[$model] ?? null; @endphp
            @if ($modelKey !== null)
                <td class="perm-cell" data-cat="{{ $catIndex }}" data-row="{{ $ri }}">
                    @foreach ($flags as $flag => $flagLabel)
                        <div class="perm-flag">
                            <input type="checkbox" class="form-check-input" id="perm_{{ $ri }}_m{{ $modelKey }}_{{ $flag }}"
                                   aria-label="{{ $label }} — {{ $flagLabel }}"
                                   name="perms[{{ $ri }}][{{ $modelKey }}][{{ $flag }}]"
                                   value="1" data-cat="{{ $catIndex }}" data-row="{{ $ri }}"
                                   {{ ($row['perms'][$modelKey][$flag] ?? false) ? 'checked' : '' }}>
                            <label for="perm_{{ $ri }}_m{{ $modelKey }}_{{ $flag }}">{{ $flagLabel }}</label>
                        </div>
                    @endforeach
                </td>
            @endif
        @endforeach
    @endforeach
</tr>