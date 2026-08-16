<?php if (!empty($errors['lines']) && is_string($errors['lines'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errors['lines']) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="post" action="/invoices">
            <div class="row g-3 mb-3">
                <div class="col-md-5">
                    <label for="partner_id" class="form-label">Партнер</label>
                    <select id="partner_id" name="partner_id" class="form-select <?= isset($errors['partner_id']) ? 'is-invalid' : '' ?>">
                        <option value="">— избери партнер —</option>
                        <?php foreach ($partners as $partner): ?>
                            <option value="<?= $partner->id ?>" <?= (string) $old['partner_id'] === (string) $partner->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($partner->name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['partner_id'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['partner_id']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label for="date" class="form-label">Датум</label>
                    <input type="date" id="date" name="date" class="form-control <?= isset($errors['date']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($old['date']) ?>" required>
                    <?php if (isset($errors['date'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['date']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label for="due_date" class="form-label">Рок на плаќање</label>
                    <input type="date" id="due_date" name="due_date" class="form-control <?= isset($errors['due_date']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($old['due_date']) ?>" required>
                    <?php if (isset($errors['due_date'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['due_date']) ?></div><?php endif; ?>
                </div>
            </div>

            <div class="table-responsive">
            <table class="table align-middle" id="lines-table">
                <thead class="table-light">
                    <tr>
                        <th style="width: 34%">Опис</th>
                        <th style="width: 13%">Количина</th>
                        <th style="width: 16%">Ед. цена</th>
                        <th style="width: 13%">ДДВ %</th>
                        <th style="width: 16%">Вкупно (нето)</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($old['lines'] as $line): ?>
                        <tr class="line-row">
                            <td><input type="text" name="line_description[]" class="form-control" value="<?= htmlspecialchars($line['description'] ?? '') ?>"></td>
                            <td><input type="number" step="0.01" min="0.01" name="line_quantity[]" class="form-control line-qty" value="<?= htmlspecialchars($line['quantity'] ?? '1') ?>"></td>
                            <td><input type="number" step="0.01" min="0" name="line_unit_price[]" class="form-control line-price" value="<?= htmlspecialchars($line['unit_price'] ?? '') ?>"></td>
                            <td>
                                <select name="line_vat_rate[]" class="form-select line-vat">
                                    <?php foreach (['18', '10', '5', '0'] as $rate): ?>
                                        <option value="<?= $rate ?>" <?= (string) ($line['vat_rate'] ?? '18') === $rate ? 'selected' : '' ?>><?= $rate ?>%</option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="line-total text-end">0.00</td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger remove-line"><i class="bi bi-x-lg"></i></button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-end fw-semibold">Нето:</td>
                        <td class="text-end fw-semibold" id="sum-net">0.00</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-end fw-semibold">ДДВ:</td>
                        <td class="text-end fw-semibold" id="sum-vat">0.00</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-end fw-semibold">Вкупно:</td>
                        <td class="text-end fw-semibold" id="sum-gross">0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            </div>

            <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="add-line">
                <i class="bi bi-plus-lg"></i> Додади ставка
            </button>

            <div>
                <button type="submit" class="btn btn-primary">Зачувај нацрт</button>
                <a href="/invoices" class="btn btn-outline-secondary">Откажи</a>
            </div>
        </form>
    </div>
</div>

<template id="line-template">
    <tr class="line-row">
        <td><input type="text" name="line_description[]" class="form-control"></td>
        <td><input type="number" step="0.01" min="0.01" name="line_quantity[]" class="form-control line-qty" value="1"></td>
        <td><input type="number" step="0.01" min="0" name="line_unit_price[]" class="form-control line-price"></td>
        <td>
            <select name="line_vat_rate[]" class="form-select line-vat">
                <option value="18" selected>18%</option>
                <option value="10">10%</option>
                <option value="5">5%</option>
                <option value="0">0%</option>
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
    var sumVatEl = document.getElementById('sum-vat');
    var sumGrossEl = document.getElementById('sum-gross');

    function recalc() {
        var net = 0, vat = 0;

        tbody.querySelectorAll('.line-row').forEach(function (row) {
            var qty = parseFloat(row.querySelector('.line-qty').value) || 0;
            var price = parseFloat(row.querySelector('.line-price').value) || 0;
            var rate = parseFloat(row.querySelector('.line-vat').value) || 0;
            var lineTotal = Math.round(qty * price * 100) / 100;
            var lineVat = Math.round(lineTotal * rate) / 100;

            row.querySelector('.line-total').textContent = lineTotal.toFixed(2);

            net += lineTotal;
            vat += lineVat;
        });

        sumNetEl.textContent = net.toFixed(2);
        sumVatEl.textContent = vat.toFixed(2);
        sumGrossEl.textContent = (net + vat).toFixed(2);
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

    recalc();
})();
</script>
