<?php $action = $currency !== null ? "/currencies/{$currency->id}" : '/currencies'; ?>

<div class="card">
    <div class="card-body">
        <form method="post" action="<?= $action ?>" class="row g-3" style="max-width: 480px;">
            <div class="col-md-4">
                <label for="code" class="form-label">Код (ISO 4217)</label>
                <?php if ($currency !== null): ?>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($currency->code) ?>" readonly tabindex="-1">
                <?php else: ?>
                    <input type="text" id="code" name="code" maxlength="3" class="form-control text-uppercase <?= isset($errors['code']) ? 'is-invalid' : '' ?>"
                           value="" placeholder="EUR" required>
                    <?php if (isset($errors['code'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['code']) ?></div><?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="col-md-8">
                <label for="name" class="form-label">Назив</label>
                <input type="text" id="name" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($currency->name ?? '') ?>" required>
                <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['name']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-6">
                <label for="default_exchange_rate" class="form-label">Стандарден курс <span class="text-muted small">(опционално)</span></label>
                <input type="number" step="0.000001" min="0.000001" id="default_exchange_rate" name="default_exchange_rate"
                       class="form-control <?= isset($errors['default_exchange_rate']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($currency->defaultExchangeRate ?? '') ?>" placeholder="пр. 61.500000">
                <?php if (isset($errors['default_exchange_rate'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['default_exchange_rate']) ?></div><?php endif; ?>
                <div class="form-text">Само предполнува курс на фактури/изводи — секогаш видлив и уредлив по документ, никогаш не се книжи автоматски без потврда.</div>
            </div>

            <div class="col-12">
                <div class="form-check">
                    <input type="checkbox" id="is_active" name="is_active" value="1" class="form-check-input"
                           <?= ($currency->isActive ?? true) ? 'checked' : '' ?>>
                    <label for="is_active" class="form-check-label">Активна</label>
                </div>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">Зачувај</button>
                <a href="/currencies" class="btn btn-outline-secondary">Откажи</a>
            </div>
        </form>
    </div>
</div>
