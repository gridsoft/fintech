<?php $action = $category !== null ? "/expense-categories/{$category->id}" : '/expense-categories'; ?>

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
                    <h6 class="mb-3">Сметки за книжење</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="domestic_account_id" class="form-label">Сметка (домашен добавувач)</label>
                            <select id="domestic_account_id" name="domestic_account_id" class="form-select <?= isset($errors['domestic_account_id']) ? 'is-invalid' : '' ?>">
                                <option value="">— избери сметка —</option>
                                <?php foreach ($accounts as $account): ?>
                                    <option value="<?= $account->id ?>" <?= (int) ($category->domesticAccountId ?? 0) === $account->id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($account->code . ' — ' . $account->name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['domestic_account_id'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['domestic_account_id']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="foreign_account_id" class="form-label">Сметка (странски добавувач) <span class="text-muted small">(опционално)</span></label>
                            <select id="foreign_account_id" name="foreign_account_id" class="form-select">
                                <option value="">— исто како домашен —</option>
                                <?php foreach ($accounts as $account): ?>
                                    <option value="<?= $account->id ?>" <?= (int) ($category->foreignAccountId ?? 0) === $account->id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($account->code . ' — ' . $account->name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card bg-body-tertiary mb-3">
                <div class="card-body">
                    <h6 class="mb-3">ДДВ и третман</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="vat_deductible" class="form-label">Одбивност на ДДВ</label>
                            <select id="vat_deductible" name="vat_deductible" class="form-select <?= isset($errors['vat_deductible']) ? 'is-invalid' : '' ?>">
                                <?php foreach (\App\Domain\Invoicing\ExpenseCategory::VAT_DEDUCTIBLE_LABELS as $value => $label): ?>
                                    <option value="<?= $value ?>" <?= ($category->vatDeductible ?? 'full') === $value ? 'selected' : '' ?> <?= $value === 'partial' ? 'disabled' : '' ?>>
                                        <?= htmlspecialchars($label) ?><?= $value === 'partial' ? ' (сè уште не е поддржано)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['vat_deductible'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['vat_deductible']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-4 d-flex align-items-center">
                            <div class="form-check">
                                <input type="checkbox" id="is_capitalizable" name="is_capitalizable" value="1" class="form-check-input" disabled
                                       <?= ($category->isCapitalizable ?? false) ? 'checked' : '' ?>>
                                <label for="is_capitalizable" class="form-check-label">Основно средство <span class="text-muted small">(сè уште не е поддржано)</span></label>
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-center">
                            <div class="form-check">
                                <input type="checkbox" id="reverse_charge_applicable" name="reverse_charge_applicable" value="1" class="form-check-input" disabled
                                       <?= ($category->reverseChargeApplicable ?? false) ? 'checked' : '' ?>>
                                <label for="reverse_charge_applicable" class="form-check-label">Обратно оданочување <span class="text-muted small">(сè уште не е поддржано)</span></label>
                            </div>
                        </div>
                    </div>
                    <p class="small text-muted mb-0 mt-2">Делумна одбивност, основни средства и обратно оданочување се идни чекори (POSTING_RULES_ADDENDUM.md) — категорија со таков атрибут ќе одбие книжење наместо да книжи погрешно.</p>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Зачувај</button>
            <a href="/expense-categories" class="btn btn-outline-secondary">Откажи</a>
        </form>
    </div>
</div>
