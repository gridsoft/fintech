<?php $action = $vatRate !== null ? "/vat-rates/{$vatRate->id}" : '/vat-rates'; ?>

<div class="card">
    <div class="card-body">
        <form method="post" action="<?= $action ?>" class="row g-3" style="max-width: 640px;">
            <div class="col-md-6">
                <label for="name" class="form-label">Назив</label>
                <input type="text" id="name" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($vatRate->name ?? '') ?>" required>
                <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['name']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-2">
                <label for="rate" class="form-label">Стапка (%)</label>
                <input type="number" step="0.01" min="0" id="rate" name="rate" class="form-control <?= isset($errors['rate']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($vatRate->rate ?? '') ?>" required>
                <?php if (isset($errors['rate'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['rate']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-4">
                <label for="type" class="form-label">Тип</label>
                <select id="type" name="type" class="form-select <?= isset($errors['type']) ? 'is-invalid' : '' ?>">
                    <?php foreach (\App\Domain\Accounting\VatRate::TYPE_LABELS as $value => $label): ?>
                        <option value="<?= $value ?>" <?= ($vatRate->type ?? 'standard') === $value ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['type'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['type']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-8">
                <label for="payable_account_id" class="form-label">Сметка за обврска за ДДВ (излезен)</label>
                <select id="payable_account_id" name="payable_account_id" class="form-select">
                    <option value="">— нема (0%/ослободено) —</option>
                    <?php foreach ($accounts as $account): ?>
                        <option value="<?= $account->id ?>" <?= (int) ($vatRate->payableAccountId ?? 0) === $account->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($account->code . ' — ' . $account->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">На оваа сметка се книжи собраниот ДДВ при издавање (продажна) фактура со оваа стапка.</div>
            </div>

            <div class="col-md-8">
                <label for="receivable_account_id" class="form-label">Сметка за побарување за ДДВ (влезен, одбивен)</label>
                <select id="receivable_account_id" name="receivable_account_id" class="form-select">
                    <option value="">— нема (0%/неодбивно) —</option>
                    <?php foreach ($accounts as $account): ?>
                        <option value="<?= $account->id ?>" <?= (int) ($vatRate->receivableAccountId ?? 0) === $account->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($account->code . ' — ' . $account->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">На оваа сметка се книжи одбивниот влезен ДДВ при заведување влезна фактура со оваа стапка.</div>
            </div>

            <div class="col-md-6">
                <label for="ujp_tax_indicator_code" class="form-label">УЈП даночен индикатор (е-фактура)</label>
                <input type="text" id="ujp_tax_indicator_code" name="ujp_tax_indicator_code" class="form-control"
                       value="<?= htmlspecialchars($vatRate->ujpTaxIndicatorCode ?? '') ?>" placeholder="пр. DDV-A">
                <div class="form-text">Точен код од шифрарникот на УЈП (DDV-A, DDV-B, DDV-V, DDV-G, DDV-9...). Задолжително пред фактура со оваа стапка да се испрати како е-фактура — не се погодува автоматски.</div>
            </div>

            <div class="col-12">
                <div class="form-check">
                    <input type="checkbox" id="is_active" name="is_active" value="1" class="form-check-input"
                           <?= ($vatRate->isActive ?? true) ? 'checked' : '' ?>>
                    <label for="is_active" class="form-check-label">Активна</label>
                </div>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">Зачувај</button>
                <a href="/vat-rates" class="btn btn-outline-secondary">Откажи</a>
            </div>
        </form>
    </div>
</div>
