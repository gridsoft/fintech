<?php $action = $terk !== null ? "/terkovi/{$terk->id}" : '/terkovi'; ?>

<?php if (!empty($errors['lines'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errors['lines']) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="post" action="<?= $action ?>">
            <div class="row g-3 mb-3">
                <div class="col-md-5">
                    <label for="name" class="form-label">Назив</label>
                    <input type="text" id="name" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($old['name']) ?>" required>
                    <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['name']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-7">
                    <label for="description" class="form-label">Опис</label>
                    <input type="text" id="description" name="description" class="form-control" value="<?= htmlspecialchars($old['description']) ?>">
                </div>
            </div>

            <div class="table-responsive">
            <table class="table align-middle" id="lines-table">
                <thead class="table-light">
                    <tr>
                        <th style="width: 34%">Сметка</th>
                        <th style="width: 18%">Страна</th>
                        <th style="width: 20%">Износ од</th>
                        <th style="width: 16%">Тагирај партнер</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($old['lines'] as $i => $line): ?>
                        <tr class="line-row">
                            <td>
                                <select name="line_account_id[]" class="form-select">
                                    <option value="">— избери сметка —</option>
                                    <?php foreach ($accounts as $account): ?>
                                        <option value="<?= $account->id ?>" <?= (string) $line['account_id'] === (string) $account->id ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($account->code . ' — ' . $account->name) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select name="line_side[]" class="form-select">
                                    <option value="debit" <?= $line['side'] === 'debit' ? 'selected' : '' ?>>Должи</option>
                                    <option value="credit" <?= $line['side'] === 'credit' ? 'selected' : '' ?>>Побарува</option>
                                </select>
                            </td>
                            <td>
                                <select name="line_amount_source[]" class="form-select">
                                    <?php foreach (\App\Domain\Accounting\TerkLine::SOURCE_LABELS as $value => $label): ?>
                                        <option value="<?= $value ?>" <?= $line['amount_source'] === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input" name="line_tag_partner[]" value="<?= $i ?>" <?= $line['tag_partner'] === '1' ? 'checked' : '' ?>>
                            </td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger remove-line"><i class="bi bi-x-lg"></i></button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>

            <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="add-line">
                <i class="bi bi-plus-lg"></i> Додади ставка
            </button>

            <p class="small text-muted">
                При книжење, секоја ставка со износ 0 (на пр. ДДВ кога стапката е 0%) автоматски се прескокнува.
                "Тагирај партнер" значи дека таа ставка во книжењето ќе го носи партнерот од фактурата (обично сметката за побарувања).
            </p>

            <div>
                <button type="submit" class="btn btn-primary">Зачувај</button>
                <a href="/terkovi" class="btn btn-outline-secondary">Откажи</a>
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
        <td>
            <select name="line_side[]" class="form-select">
                <option value="debit">Должи</option>
                <option value="credit">Побарува</option>
            </select>
        </td>
        <td>
            <select name="line_amount_source[]" class="form-select">
                <?php foreach (\App\Domain\Accounting\TerkLine::SOURCE_LABELS as $value => $label): ?>
                    <option value="<?= $value ?>"><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td class="text-center">
            <input type="checkbox" class="form-check-input" name="line_tag_partner[]" value="__INDEX__">
        </td>
        <td><button type="button" class="btn btn-sm btn-outline-danger remove-line"><i class="bi bi-x-lg"></i></button></td>
    </tr>
</template>

<script>
(function () {
    var tbody = document.querySelector('#lines-table tbody');
    var template = document.getElementById('line-template');

    function reindexCheckboxes() {
        tbody.querySelectorAll('.line-row').forEach(function (row, i) {
            var cb = row.querySelector('input[type=checkbox]');
            cb.value = i;
        });
    }

    document.getElementById('add-line').addEventListener('click', function () {
        var row = template.content.cloneNode(true);
        tbody.appendChild(row);
        reindexCheckboxes();
    });

    tbody.addEventListener('click', function (e) {
        var btn = e.target.closest('.remove-line');
        if (btn) {
            btn.closest('tr').remove();
            reindexCheckboxes();
        }
    });
})();
</script>
