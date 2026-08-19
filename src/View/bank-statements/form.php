<div class="card">
    <div class="card-body">
        <form method="post" action="/bank-statements" class="row g-3" style="max-width: 640px;">
            <div class="col-md-6">
                <label for="account_id" class="form-label">Парична сметка</label>
                <select id="account_id" name="account_id" class="form-select <?= isset($errors['account_id']) ? 'is-invalid' : '' ?>">
                    <option value="">— избери сметка —</option>
                    <?php foreach ($cashAccounts as $account): ?>
                        <option value="<?= $account->id ?>" <?= (string) $old['account_id'] === (string) $account->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($account->code . ' — ' . $account->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['account_id'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['account_id']) ?></div><?php endif; ?>
                <div class="form-text"><a href="/accounts/create" target="_blank">+ Нова сметка</a> (нова банка/валута — аналитичка сметка под 10x во контниот план)</div>
            </div>
            <div class="col-md-3">
                <label for="date" class="form-label">Датум</label>
                <input type="date" id="date" name="date" class="form-control <?= isset($errors['date']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($old['date']) ?>" required>
                <?php if (isset($errors['date'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['date']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-3">
                <label for="opening_balance" class="form-label">Почетно салдо</label>
                <input type="number" step="0.01" id="opening_balance" name="opening_balance" class="form-control" value="<?= htmlspecialchars($old['opening_balance'] ?? '0.00') ?>">
            </div>
            <div class="col-md-3">
                <label for="currency_id" class="form-label">Валута</label>
                <select id="currency_id" name="currency_id" class="form-select">
                    <?php foreach ($currencies as $currency): ?>
                        <option value="<?= $currency->id ?>" <?= (string) $old['currency_id'] === (string) $currency->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($currency->code . ' — ' . $currency->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Девизен извод (EUR/USD/...) — сите трансакции во него се внесуваат во оваа валута, со курс по трансакција.</div>
            </div>
            <div class="col-md-6">
                <label for="reference" class="form-label">Референца <span class="text-muted small">(опционално)</span></label>
                <input type="text" id="reference" name="reference" class="form-control" value="<?= htmlspecialchars($old['reference'] ?? '') ?>">
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">Создај извод</button>
                <a href="/bank-statements" class="btn btn-outline-secondary">Откажи</a>
            </div>
        </form>
    </div>
</div>
