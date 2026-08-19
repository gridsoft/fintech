<?php if (!empty($errors['lines']) && is_string($errors['lines'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errors['lines']) ?></div>
<?php endif; ?>

<?php
$itemOptions = function ($selectedValue = null) use ($products, $services) {
    ?>
    <option value="">— избери производ/услуга —</option>
    <?php if (!empty($products)): ?>
        <optgroup label="Производи">
            <?php foreach ($products as $product): ?>
                <?php $value = "product:{$product->id}"; ?>
                <option value="<?= $value ?>" data-price="<?= htmlspecialchars($product->price) ?>" <?= $selectedValue === $value ? 'selected' : '' ?>>
                    <?= htmlspecialchars($product->name) ?>
                </option>
            <?php endforeach; ?>
        </optgroup>
    <?php endif; ?>
    <?php if (!empty($services)): ?>
        <optgroup label="Услуги">
            <?php foreach ($services as $service): ?>
                <?php $value = "service:{$service->id}"; ?>
                <option value="<?= $value ?>" data-price="<?= htmlspecialchars($service->price) ?>" <?= $selectedValue === $value ? 'selected' : '' ?>>
                    <?= htmlspecialchars($service->name) ?>
                </option>
            <?php endforeach; ?>
        </optgroup>
    <?php endif; ?>
    <?php
};
?>

<?php if (empty($products) && empty($services)): ?>
    <div class="alert alert-warning">
        Нема внесени производи или услуги. <a href="/products/create">Додади производ</a> или <a href="/services/create">додади услуга</a> пред да можеш да создадеш фактура —
        сметката и ДДВ стапката се резолвираат автоматски од категоријата, не се бираат рачно.
    </div>
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
                                <?= htmlspecialchars($partner->name) ?><?= $partner->isForeign() ? ' (странски)' : '' ?>
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

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label for="currency_id" class="form-label">Валута</label>
                    <select id="currency_id" name="currency_id" class="form-select">
                        <?php foreach ($currencies as $currency): ?>
                            <option value="<?= $currency->id ?>" data-is-base="<?= $currency->isBase ? '1' : '0' ?>"
                                    data-default-rate="<?= htmlspecialchars($currency->defaultExchangeRate ?? '') ?>"
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
                    <div class="form-text">Само за странска валута — на датумот на фактурата. За MKD секогаш 1.</div>
                </div>
            </div>

            <div class="table-responsive">
            <table class="table align-middle" id="lines-table">
                <thead class="table-light">
                    <tr>
                        <th style="width: 28%">Производ / Услуга</th>
                        <th style="width: 20%">Забелешка</th>
                        <th style="width: 12%">Количина</th>
                        <th style="width: 14%">Ед. цена</th>
                        <th style="width: 16%">Вкупно (нето)</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($old['lines'] as $line): ?>
                        <?php $itemValue = ($line['type'] ?? '') && ($line['item_id'] ?? '') ? "{$line['type']}:{$line['item_id']}" : ''; ?>
                        <tr class="line-row">
                            <td>
                                <select name="line_item[]" class="form-select line-item">
                                    <?php $itemOptions($itemValue); ?>
                                </select>
                            </td>
                            <td><input type="text" name="line_description[]" class="form-control" value="<?= htmlspecialchars($line['description'] ?? '') ?>"></td>
                            <td><input type="number" step="0.01" min="0.01" name="line_quantity[]" class="form-control line-qty" value="<?= htmlspecialchars($line['quantity'] ?? '1') ?>"></td>
                            <td><input type="number" step="0.01" min="0" name="line_unit_price[]" class="form-control line-price" placeholder="стандардна" value="<?= htmlspecialchars($line['unit_price'] ?? '') ?>"></td>
                            <td class="line-total text-end">0.00</td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger remove-line"><i class="bi bi-x-lg"></i></button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-end fw-semibold">Нето (проценето):</td>
                        <td class="text-end fw-semibold" id="sum-net">0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            </div>
            <p class="small text-muted">Сметката и ДДВ стапката се одредуваат автоматски од категоријата на производот/услугата и дали партнерот е домашен/странски — не се бираат тука. Ед. цена е опционална — ако е празна, се користи стандардната цена на производот/услугата.</p>

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
        <td>
            <select name="line_item[]" class="form-select line-item">
                <?php $itemOptions(); ?>
            </select>
        </td>
        <td><input type="text" name="line_description[]" class="form-control"></td>
        <td><input type="number" step="0.01" min="0.01" name="line_quantity[]" class="form-control line-qty" value="1"></td>
        <td><input type="number" step="0.01" min="0" name="line_unit_price[]" class="form-control line-price" placeholder="стандардна"></td>
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
            var select = row.querySelector('.line-item');
            var qty = parseFloat(row.querySelector('.line-qty').value) || 0;
            var priceInput = row.querySelector('.line-price');
            var price = parseFloat(priceInput.value);

            if (isNaN(price)) {
                var opt = select.options[select.selectedIndex];
                price = opt ? (parseFloat(opt.getAttribute('data-price')) || 0) : 0;
            }

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

    // Кога се избира производ/услуга, полето за цена се пополнува со
    // стандардната цена — видлива и уредлива, корисникот слободно ја менува.
    tbody.addEventListener('change', function (e) {
        if (e.target.classList.contains('line-item')) {
            var select = e.target;
            var opt = select.options[select.selectedIndex];
            var priceInput = select.closest('tr').querySelector('.line-price');
            var price = opt ? opt.getAttribute('data-price') : null;
            priceInput.value = price !== null ? parseFloat(price).toFixed(2) : '';
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

    // Кога корисникот АКТИВНО менува валута, курсот се предполнува со
    // стандардниот курс на таа валута (/currencies) — видлив и уредлив,
    // истиот образец како цената на производ/услуга. Не се случува на
    // почетно вчитување за да не се избрише вредност веќе внесена од
    // претходен обид (validation redisplay).
    function applyDefaultRate() {
        var opt = currencySelect.options[currencySelect.selectedIndex];
        var isBase = opt && opt.getAttribute('data-is-base') === '1';
        if (isBase) {
            return;
        }
        var defaultRate = opt.getAttribute('data-default-rate');
        if (defaultRate) {
            exchangeRateInput.value = parseFloat(defaultRate).toFixed(6);
        }
    }

    currencySelect.addEventListener('change', function () {
        syncExchangeRate();
        applyDefaultRate();
    });
    syncExchangeRate();
})();
</script>
