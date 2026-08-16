<?php if (!empty($errors['lines']) && is_string($errors['lines'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errors['lines']) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="post" action="/journal">
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label for="date" class="form-label">Датум</label>
                    <input type="date" id="date" name="date" class="form-control <?= isset($errors['date']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($old['date']) ?>" required>
                    <?php if (isset($errors['date'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['date']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label for="description" class="form-label">Опис</label>
                    <input type="text" id="description" name="description" class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($old['description']) ?>" required>
                    <?php if (isset($errors['description'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['description']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label for="reference" class="form-label">Референца</label>
                    <input type="text" id="reference" name="reference" class="form-control"
                           value="<?= htmlspecialchars($old['reference'] ?? '') ?>">
                </div>
            </div>

            <table class="table align-middle" id="lines-table">
                <thead class="table-light">
                    <tr>
                        <th style="width: 32%">Сметка</th>
                        <th style="width: 22%">Опис на ставка</th>
                        <th style="width: 15%">Дебит</th>
                        <th style="width: 15%">Кредит</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($old['lines'] as $line): ?>
                        <tr class="line-row">
                            <td>
                                <select name="line_account_id[]" class="form-select">
                                    <option value="">— избери сметка —</option>
                                    <?php foreach ($accounts as $account): ?>
                                        <option value="<?= $account->id ?>" <?= (string) ($line['account_id'] ?? '') === (string) $account->id ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($account->code . ' — ' . $account->name) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="text" name="line_description[]" class="form-control" value="<?= htmlspecialchars($line['description'] ?? '') ?>"></td>
                            <td><input type="number" step="0.01" min="0" name="line_debit[]" class="form-control line-debit" value="<?= htmlspecialchars($line['debit'] ?? '') ?>"></td>
                            <td><input type="number" step="0.01" min="0" name="line_credit[]" class="form-control line-credit" value="<?= htmlspecialchars($line['credit'] ?? '') ?>"></td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger remove-line"><i class="bi bi-x-lg"></i></button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="text-end fw-semibold">Вкупно:</td>
                        <td class="fw-semibold" id="total-debit">0.00</td>
                        <td class="fw-semibold" id="total-credit">0.00</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-end" id="balance-note"></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>

            <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="add-line">
                <i class="bi bi-plus-lg"></i> Додади ставка
            </button>

            <div>
                <button type="submit" class="btn btn-primary">Зачувај запис</button>
                <a href="/journal" class="btn btn-outline-secondary">Откажи</a>
            </div>
        </form>
    </div>
</div>

<template id="line-template">
    <tr class="line-row">
        <td>
            <select name="line_account_id[]" class="form-select">
                <option value="">— избери сметка —</option>
                <?php foreach ($accounts as $account): ?>
                    <option value="<?= $account->id ?>"><?= htmlspecialchars($account->code . ' — ' . $account->name) ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input type="text" name="line_description[]" class="form-control"></td>
        <td><input type="number" step="0.01" min="0" name="line_debit[]" class="form-control line-debit"></td>
        <td><input type="number" step="0.01" min="0" name="line_credit[]" class="form-control line-credit"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger remove-line"><i class="bi bi-x-lg"></i></button></td>
    </tr>
</template>

<script>
(function () {
    var tbody = document.querySelector('#lines-table tbody');
    var template = document.getElementById('line-template');
    var totalDebitEl = document.getElementById('total-debit');
    var totalCreditEl = document.getElementById('total-credit');
    var balanceNote = document.getElementById('balance-note');

    function recalc() {
        var debit = 0, credit = 0;
        tbody.querySelectorAll('.line-debit').forEach(function (el) { debit += parseFloat(el.value) || 0; });
        tbody.querySelectorAll('.line-credit').forEach(function (el) { credit += parseFloat(el.value) || 0; });
        totalDebitEl.textContent = debit.toFixed(2);
        totalCreditEl.textContent = credit.toFixed(2);

        var diff = Math.round((debit - credit) * 100) / 100;
        if (diff === 0 && debit > 0) {
            balanceNote.innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill"></i> Балансирано</span>';
        } else if (diff !== 0) {
            balanceNote.innerHTML = '<span class="text-danger">Разлика: ' + diff.toFixed(2) + '</span>';
        } else {
            balanceNote.textContent = '';
        }
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

    tbody.addEventListener('input', function (e) {
        if (e.target.classList.contains('line-debit') || e.target.classList.contains('line-credit')) {
            recalc();
        }
    });

    recalc();
})();
</script>
