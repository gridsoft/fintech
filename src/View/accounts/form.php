<?php
$action = $account !== null ? "/accounts/{$account->id}" : '/accounts';
?>

<div class="card">
    <div class="card-body">
        <form method="post" action="<?= $action ?>" class="row g-3" style="max-width: 640px;">
            <div class="col-md-4">
                <label for="code" class="form-label">Шифра</label>
                <input type="text" id="code" name="code" class="form-control <?= isset($errors['code']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($account->code ?? '') ?>" required>
                <?php if (isset($errors['code'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['code']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-8">
                <label for="name" class="form-label">Назив</label>
                <input type="text" id="name" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($account->name ?? '') ?>" required>
                <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['name']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-6">
                <label for="type" class="form-label">Тип</label>
                <select id="type" name="type" class="form-select <?= isset($errors['type']) ? 'is-invalid' : '' ?>">
                    <option value="">— избери —</option>
                    <?php foreach (\App\Domain\Accounting\Account::TYPE_LABELS as $value => $label): ?>
                        <option value="<?= $value ?>" <?= ($account->type ?? '') === $value ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['type'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['type']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-6">
                <label for="parent_id" class="form-label">Родителска сметка</label>
                <select id="parent_id" name="parent_id" class="form-select <?= isset($errors['parent_id']) ? 'is-invalid' : '' ?>">
                    <option value="">— нема —</option>
                    <?php foreach ($parentOptions as $id => $label): ?>
                        <option value="<?= $id ?>" <?= (int) ($account->parentId ?? 0) === $id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['parent_id'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['parent_id']) ?></div><?php endif; ?>
            </div>

            <div class="col-12">
                <div class="form-check">
                    <input type="checkbox" id="is_active" name="is_active" value="1" class="form-check-input"
                           <?= ($account->isActive ?? true) ? 'checked' : '' ?>>
                    <label for="is_active" class="form-check-label">Активна</label>
                </div>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">Зачувај</button>
                <a href="/accounts" class="btn btn-outline-secondary">Откажи</a>
            </div>
        </form>
    </div>
</div>
