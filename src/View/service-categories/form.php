<?php
$action = $category !== null ? "/service-categories/{$category->id}" : '/service-categories';
$validAccountIds = array_map(fn ($a) => $a->id, $accounts);
$hasInvalidAccount = $category !== null && (
    !in_array($category->domesticAccountId, $validAccountIds, true)
    || ($category->foreignAccountId && !in_array($category->foreignAccountId, $validAccountIds, true))
);
?>

<?php if ($hasInvalidAccount): ?>
    <div class="alert alert-warning">
        Оваа категорија моментално покажува на сметка што не е приходна (веројатно поставена пред да се воведе ова ограничување).
        Падот подолу не ја прикажува старата вредност — избери приходна сметка и зачувај за да се поправи.
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="post" action="<?= $action ?>" style="max-width: 760px;">
            <div class="row g-3 mb-2">
                <div class="col-md-8">
                    <label for="name" class="form-label">Назив на категорија</label>
                    <input type="text" id="name" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($category->name ?? '') ?>" required>
                    <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['name']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check">
                        <input type="checkbox" id="is_active" name="is_active" value="1" class="form-check-input"
                               <?= ($category->isActive ?? true) ? 'checked' : '' ?>>
                        <label for="is_active" class="form-check-label">Активна</label>
                    </div>
                </div>
            </div>

            <div class="card bg-body-tertiary mb-3">
                <div class="card-body">
                    <h6 class="mb-3">Домашен промет</h6>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="domestic_account_id" class="form-label">Сметка</label>
                            <select id="domestic_account_id" name="domestic_account_id" class="form-select <?= isset($errors['domestic_account_id']) ? 'is-invalid' : '' ?>">
                                <option value="">— избери сметка —</option>
                                <?php foreach ($accounts as $account): ?>
                                    <option value="<?= $account->id ?>" <?= (int) ($category->domesticAccountId ?? 0) === $account->id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($account->code . ' — ' . $account->name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['domestic_account_id'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['domestic_account_id']) ?></div>
                            <?php else: ?>
                                <div class="form-text">Само приходни сметки (класа 7, група 75-79) — на неа секогаш се книжи кредит при издавање фактура.</div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label for="domestic_vat_rate_id" class="form-label">ДДВ стапка</label>
                            <select id="domestic_vat_rate_id" name="domestic_vat_rate_id" class="form-select <?= isset($errors['domestic_vat_rate_id']) ? 'is-invalid' : '' ?>">
                                <option value="">— избери —</option>
                                <?php foreach ($vatRates as $vatRate): ?>
                                    <option value="<?= $vatRate->id ?>" <?= (int) ($category->domesticVatRateId ?? 0) === $vatRate->id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($vatRate->name . ' (' . $vatRate->rate . '%)') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['domestic_vat_rate_id'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['domestic_vat_rate_id']) ?></div><?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card bg-body-tertiary mb-3">
                <div class="card-body">
                    <h6 class="mb-3">Странски промет <span class="text-muted small">(опционално — ако е празно, се користи домашниот мапинг)</span></h6>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="foreign_account_id" class="form-label">Сметка</label>
                            <select id="foreign_account_id" name="foreign_account_id" class="form-select <?= isset($errors['foreign_account_id']) ? 'is-invalid' : '' ?>">
                                <option value="">— нема —</option>
                                <?php foreach ($accounts as $account): ?>
                                    <option value="<?= $account->id ?>" <?= (int) ($category->foreignAccountId ?? 0) === $account->id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($account->code . ' — ' . $account->name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['foreign_account_id'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['foreign_account_id']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label for="foreign_vat_rate_id" class="form-label">ДДВ стапка</label>
                            <select id="foreign_vat_rate_id" name="foreign_vat_rate_id" class="form-select">
                                <option value="">— нема —</option>
                                <?php foreach ($vatRates as $vatRate): ?>
                                    <option value="<?= $vatRate->id ?>" <?= (int) ($category->foreignVatRateId ?? 0) === $vatRate->id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($vatRate->name . ' (' . $vatRate->rate . '%)') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Зачувај</button>
            <a href="/service-categories" class="btn btn-outline-secondary">Откажи</a>
        </form>
    </div>
</div>
