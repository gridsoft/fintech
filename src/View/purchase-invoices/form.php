<?php if (!empty($errors['lines']) && is_string($errors['lines'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errors['lines']) ?></div>
<?php endif; ?>

<?php if (!empty($repostWarning)): ?>
    <div class="alert alert-warning">
        Оваа фактура е веќе <strong>заведена</strong>. Зачувувањето ќе го сторнира постојното книжење и ќе книжи нов запис според изменетите ставки — стариот запис останува во главната книга (сторниран), не се брише.
    </div>
<?php endif; ?>

<?php
$categoryOptions = function ($selectedId = null) use ($expenseCategories) {
    ?>
    <option value="">— избери категорија —</option>
    <?php foreach ($expenseCategories as $category): ?>
        <option value="<?= $category->id ?>" <?= (string) $selectedId === (string) $category->id ? 'selected' : '' ?>>
            <?= htmlspecialchars($category->name) ?>
        </option>
    <?php endforeach; ?>
    <?php
};

$vatRateOptions = function ($selectedId = null) use ($vatRates) {
    ?>
    <option value="">— ДДВ —</option>
    <?php foreach ($vatRates as $vatRate): ?>
        <option value="<?= $vatRate->id ?>" <?= (string) $selectedId === (string) $vatRate->id ? 'selected' : '' ?>>
            <?= htmlspecialchars($vatRate->name . ' (' . $vatRate->rate . '%)') ?>
        </option>
    <?php endforeach; ?>
    <?php
};
?>

<?php if (empty($expenseCategories)): ?>
    <div class="alert alert-warning">
        Нема дефинирани категории на трошоци. <a href="/expense-categories/create">Додади категорија</a> пред да можеш да внесеш влезна фактура —
        сметката се резолвира автоматски од категоријата, не се бира рачно.
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="post" action="<?= htmlspecialchars($formAction ?? '/purchase-invoices') ?>"
              <?= !empty($repostWarning) ? 'onsubmit="return confirm(\'Да се зачуваат промените? Ова ќе го сторнира постојното книжење и ќе книжи нов запис.\');"' : '' ?>>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label for="partner_id" class="form-label">Добавувач</label>
                    <select id="partner_id" name="partner_id" class="form-select <?= isset($errors['partner_id']) ? 'is-invalid' : '' ?>">
                        <option value="">— избери партнер —</option>
                        <?php foreach ($partners as $partner): ?>
                            <option value="<?= $partner->id ?>" <?= (string) $old['partner_id'] === (string) $partner->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($partner->name) ?><?= $partner->isForeign() ? ' (странски)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['partner_id'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['partner_id']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-2">
                    <label for="supplier_number" class="form-label">Бр. фактура (добавувач)</label>
                    <input type="text" id="supplier_number" name="supplier_number" class="form-control <?= isset($errors['supplier_number']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($old['supplier_number']) ?>" required>
                    <?php if (isset($errors['supplier_number'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['supplier_number']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label for="date" class="form-label">Датум</label>
                    <input type="date" id="date" name="date" class="form-control <?= isset($errors['date']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($old['date']) ?>" required>
                    <?php if (isset($errors['date'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['date']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label for="due_days" class="form-label">Рок на плаќање (денови)</label>
                    <input type="number" step="1" min="0" id="due_days" name="due_days" class="form-control <?= isset($errors['due_days']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($old['due_days'] ?? '15') ?>" required>
                    <div class="form-text">Доспева на: <span id="due-date-preview">—</span></div>
                    <?php if (isset($errors['due_days'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['due_days']) ?></div><?php endif; ?>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label for="currency_id" class="form-label">Валута</label>
                    <select id="currency_id" name="currency_id" class="form-select">
                        <?php foreach ($currencies as $currency): ?>
                            <option value="<?= $currency->id ?>" data-is-base="<?= $currency->isBase ? '1' : '0' ?>"
                                    <?= (string) $old['currency_id'] === (string) $currency->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($currency->code . ' — ' . $currency->name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="exchange_rate" class="form-label">Курс (MKD за 1 единица)</label>
                    <input type="number" step="0.000001" min="0.000001" id="exchange_rate" name="exchange_rate" class="form-control"
                           value="<?= htmlspecialchars($old['exchange_rate']) ?>">
                    <div class="form-text">Само за странска валута — курсот од примената фактура/датумот. За MKD секогаш 1.</div>
                </div>
            </div>

            <div class="table-responsive">
            <table class="table align-middle" id="lines-table">
                <thead class="table-light">
                    <tr>
                        <th style="width: 22%">Категорија на трошок</th>
                        <th style="width: 18%">Забелешка</th>
                        <th style="width: 10%">Количина</th>
                        <th style="width: 12%">Ед. цена</th>
                        <th style="width: 14%">ДДВ стапка</th>
                        <th style="width: 14%">Вкупно (нето)</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($old['lines'] as $line): ?>
                        <tr class="line-row">
                            <td>
                                <select name="line_category_id[]" class="form-select line-category">
                                    <?php $categoryOptions($line['category_id'] ?? ''); ?>
                                </select>
                            </td>
                            <td><input type="text" name="line_description[]" class="form-control" value="<?= htmlspecialchars($line['description'] ?? '') ?>"></td>
                            <td><input type="number" step="0.01" min="0.01" name="line_quantity[]" class="form-control line-qty" value="<?= htmlspecialchars($line['quantity'] ?? '1') ?>"></td>
                            <td><input type="number" step="0.01" min="0" name="line_unit_price[]" class="form-control line-price" value="<?= htmlspecialchars($line['unit_price'] ?? '') ?>"></td>
                            <td>
                                <select name="line_vat_rate_id[]" class="form-select line-vat">
                                    <?php $vatRateOptions($line['vat_rate_id'] ?? ''); ?>
                                </select>
                            </td>
                            <td class="line-total text-end">0.00</td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger remove-line"><i class="bi bi-x-lg"></i></button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class="text-end fw-semibold">Нето (проценето):</td>
                        <td class="text-end fw-semibold" id="sum-net">0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            </div>
            <p class="small text-muted">Сметката се одредува автоматски од категоријата на трошокот и дали партнерот е домашен/странски. ДДВ стапката се внесува рачно — онаа што стои на примената фактура од добавувачот.</p>

            <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="add-line">
                <i class="bi bi-plus-lg"></i> Додади ставка
            </button>

            <div>
                <button type="submit" class="btn btn-primary"><?= htmlspecialchars($submitLabel ?? 'Зачувај нацрт') ?></button>
                <a href="<?= htmlspecialchars($cancelUrl ?? '/purchase-invoices') ?>" class="btn btn-outline-secondary">Откажи</a>
            </div>
        </form>
    </div>
</div>

<template id="line-template">
    <tr class="line-row">
        <td>
            <select name="line_category_id[]" class="form-select line-category">
                <?php $categoryOptions(); ?>
            </select>
        </td>
        <td><input type="text" name="line_description[]" class="form-control"></td>
        <td><input type="number" step="0.01" min="0.01" name="line_quantity[]" class="form-control line-qty" value="1"></td>
        <td><input type="number" step="0.01" min="0" name="line_unit_price[]" class="form-control line-price"></td>
        <td>
            <select name="line_vat_rate_id[]" class="form-select line-vat">
                <?php $vatRateOptions(); ?>
            </select>
        </td>
        <td class="line-total text-end">0.00</td>
        <td><button type="button" class="btn btn-sm btn-outline-danger remove-line"><i class="bi bi-x-lg"></i></button></td>
    </tr>
</template>

<script>
(function () {
    var tbody = document.querySelector('#lines-table tbody');
    var template = document.getElementById('line-template');
    var sumNetEl = document.getElementById('sum-net');

    function recalc() {
        var net = 0;

        tbody.querySelectorAll('.line-row').forEach(function (row) {
            var qty = parseFloat(row.querySelector('.line-qty').value) || 0;
            var price = parseFloat(row.querySelector('.line-price').value) || 0;

            var lineTotal = Math.round(qty * price * 100) / 100;
            row.querySelector('.line-total').textContent = lineTotal.toFixed(2);
            net += lineTotal;
        });

        sumNetEl.textContent = net.toFixed(2);
    }

    document.getElementById('add-line').addEventListener('click', function () {
        var row = template.content.cloneNode(true);
        tbody.appendChild(row);
    });

    tbody.addEventListener('click', function (e) {
        var btn = e.target.closest('.remove-line');
        if (btn) {
            btn.closest('tr').remove();
            recalc();
        }
    });

    tbody.addEventListener('input', recalc);
    tbody.addEventListener('change', recalc);

    recalc();

    // Курсот важи само за странска валута — за базната (MKD) секогаш е 1, полето се заклучува.
    var currencySelect = document.getElementById('currency_id');
    var exchangeRateInput = document.getElementById('exchange_rate');

    function syncExchangeRate() {
        var opt = currencySelect.options[currencySelect.selectedIndex];
        var isBase = opt && opt.getAttribute('data-is-base') === '1';
        exchangeRateInput.disabled = isBase;
        if (isBase) {
            exchangeRateInput.value = '1.000000';
        }
    }

    currencySelect.addEventListener('change', syncExchangeRate);
    syncExchangeRate();

    // Прикажува "Доспева на: д.м.гггг" — чисто информативно, датумот сепак
    // се пресметува на серверот (date + due_days) при зачувување.
    var dateInput = document.getElementById('date');
    var dueDaysInput = document.getElementById('due_days');
    var dueDatePreview = document.getElementById('due-date-preview');

    function updateDueDatePreview() {
        var base = dateInput.value;
        var days = parseInt(dueDaysInput.value, 10);
        if (!base || isNaN(days)) {
            dueDatePreview.textContent = '—';
            return;
        }
        var parts = base.split('-').map(Number);
        var d = new Date(parts[0], parts[1] - 1, parts[2]);
        d.setDate(d.getDate() + days);
        var dd = String(d.getDate()).padStart(2, '0');
        var mm = String(d.getMonth() + 1).padStart(2, '0');
        dueDatePreview.textContent = dd + '.' + mm + '.' + d.getFullYear();
    }

    dateInput.addEventListener('input', updateDueDatePreview);
    dueDaysInput.addEventListener('input', updateDueDatePreview);
    updateDueDatePreview();
})();
</script>
