{{--
    The craft-category picker for one company.

    ALL FROM THE HIERARCHY. A selection is a CATEGORY
    (`kraf_bidangs` where `code` IS NULL), a sub-category under it, and any of
    that sub-category's classifications and products, ticked. The craft
    CODE a draw matches on is NOT picked here — it is set per event on the draw
    list, because a company can be in two events under two codes.

    HASIL LOGAM is the one category whose sub-categories are picked several at a
    time; every other is one. The select switches between single and multiple
    below, and the controller enforces the same rule on the far side.

    Expects:
      $company  — the company being edited
      $picker   — App\Support\KrafPicker::data($company)
--}}
<div class="mac-card mac-card-padded mb-3" id="craft-categories" style="scroll-margin-top: 80px">
    <h3 class="mac-card-title">Craft categories</h3>

    @if(session('anchor') === 'craft-categories')
        @include('partials.admin-alerts')
    @endif
    <p class="mac-muted mac-text-sm">
        What this company makes. The first one added is the primary.
    </p>

    @if($picker['selections']->isEmpty())
        <div class="mac-empty py-3">
            <p class="mac-empty-text mb-0">Nothing picked yet.</p>
        </div>
    @else
        <div class="table-responsive mb-3">
            <table class="mac-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Sub-category</th>
                        <th>Classification</th>
                        <th>Products</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($picker['selections'] as $selection)
                        <tr>
                            <td>
                                {{ $selection->bidang?->name ?? '—' }}
                                @if($selection->is_primary)
                                    <span class="mac-badge mac-badge-blue ms-1">Primary</span>
                                @endif
                            </td>
                            <td>{{ $selection->subBidang?->name ?? '—' }}</td>
                            <td class="mac-text-sm">{{ $selection->pengelasan->pluck('name')->join(', ') ?: '—' }}</td>
                            <td class="mac-text-sm">{{ $selection->produks->pluck('name')->join(', ') ?: '—' }}</td>
                            <td class="text-end">
                                <form method="POST"
                                      action="{{ route('admin.companies.kraf.destroy', [$company, $selection]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="mac-action-btn mac-action-btn-danger"
                                            title="Remove"
                                            data-confirm="Remove this craft selection?">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.companies.kraf.store', $company) }}">
        @csrf
        <div class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="mac-label" for="bidang_id">Category</label>
                <select id="bidang_id" name="bidang_id" class="mac-select" required
                        data-multi="{{ \App\Support\KrafPicker::MULTI_SUB_CATEGORY }}">
                    <option value="">— Pick a category —</option>
                    @foreach($picker['bidangs'] as $bidang)
                        <option value="{{ $bidang->id }}" data-name="{{ $bidang->name }}">{{ $bidang->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-5">
                <label class="mac-label" for="sub_bidang_id">Sub-category</label>
                <select id="sub_bidang_id" name="sub_bidang_id[]" class="mac-select" required>
                    <option value="">— Pick a category first —</option>
                </select>
            </div>

            <div class="col-md-2">
                <button type="submit" class="mac-btn-primary w-100">Add</button>
            </div>

            <fieldset class="col-12" id="kraf-pengelasan" hidden>
                <legend class="mac-label">Classification <span class="mac-muted">— optional</span></legend>
                <div class="kraf-ticks"></div>
            </fieldset>

            <fieldset class="col-12" id="kraf-produk" hidden>
                <legend class="mac-label">Products <span class="mac-muted">— optional</span></legend>
                <div class="kraf-ticks"></div>
            </fieldset>

            <div class="col-12">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="is_primary" name="is_primary" value="1">
                    <label class="form-check-label mac-text-sm" for="is_primary">Make this the primary category</label>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
/*
 * The sub-category list, filtered by the chosen category, from data already on
 * the page. No fetch: the whole hierarchy is two short lists, and a round trip
 * per keystroke would be slower than shipping them.
 */
document.addEventListener('DOMContentLoaded', function () {
    const subsByBidang = @json($picker['subBidangs']->map->pluck('name', 'id'));
    const category = document.getElementById('bidang_id');
    const sub = document.getElementById('sub_bidang_id');

    if (! category || ! sub) return;

    category.addEventListener('change', function () {
        const picked = category.selectedOptions[0];
        const subs = subsByBidang[category.value] || {};

        // HASIL LOGAM is stored one row per sub-category, so it is the one that
        // takes several at once. The controller applies the same rule.
        sub.multiple = picked.dataset.name === category.dataset.multi;
        sub.size = sub.multiple ? 6 : 0;

        sub.innerHTML = '';

        if (! sub.multiple) {
            sub.add(new Option('— Pick a sub-category —', ''));
        }

        Object.entries(subs).forEach(([id, name]) => sub.add(new Option(name, id)));
        drawTicks();
    });

    /*
     * Tick boxes for the classifications and the products of every sub-category
     * picked, grouped under its name when there is more than one. The controller
     * takes only the ones under each sub-category; both lists are optional.
     */
    const groups = [
        { box: document.getElementById('kraf-pengelasan'), bySub: @json($picker['pengelasanBySub']), name: 'pengelasan_id[]', prefix: 'pengelasan-' },
        { box: document.getElementById('kraf-produk'), bySub: @json($picker['produksBySub']), name: 'produk_id[]', prefix: 'produk-' },
    ];

    function drawTicks() {
        groups.forEach(drawGroup);
    }

    function drawGroup({ box, bySub, name: fieldName, prefix }) {
        const picked = [...sub.selectedOptions].filter(o => o.value);
        const list = box.querySelector('.kraf-ticks');
        list.innerHTML = '';

        picked.forEach(option => {
            const items = bySub[option.value] || {};
            if (! Object.keys(items).length) return;

            const group = document.createElement('div');
            group.className = 'mb-2';
            if (picked.length > 1) {
                const title = document.createElement('div');
                title.className = 'mac-text-sm fw-semibold';
                title.textContent = option.text;
                group.append(title);
            }
            Object.entries(items).forEach(([id, name]) => {
                const row = document.createElement('div');
                row.className = 'form-check';
                const input = Object.assign(document.createElement('input'), {
                    type: 'checkbox', className: 'form-check-input', name: fieldName,
                    value: id, id: prefix + id,
                });
                const label = Object.assign(document.createElement('label'), {
                    className: 'form-check-label mac-text-sm', htmlFor: input.id, textContent: name,
                });
                row.append(input, label);
                group.append(row);
            });
            list.append(group);
        });

        box.hidden = ! list.children.length;
    }

    sub.addEventListener('change', drawTicks);
});
</script>
@endpush
