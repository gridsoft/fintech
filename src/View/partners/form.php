<?php $action = $partner !== null ? "/partners/{$partner->id}" : '/partners'; ?>

<div class="card">
    <div class="card-body">
        <form method="post" action="<?= $action ?>" class="row g-3" style="max-width: 640px;">
            <div class="col-md-8">
                <label for="name" class="form-label">Назив</label>
                <input type="text" id="name" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($partner->name ?? '') ?>" required>
                <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['name']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-4">
                <label for="type" class="form-label">Тип</label>
                <select id="type" name="type" class="form-select <?= isset($errors['type']) ? 'is-invalid' : '' ?>">
                    <?php foreach (\App\Domain\Partners\Partner::TYPE_LABELS as $value => $label): ?>
                        <option value="<?= $value ?>" <?= ($partner->type ?? 'customer') === $value ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['type'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['type']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-4">
                <label for="tax_number" class="form-label">ЕДБ</label>
                <input type="text" id="tax_number" name="tax_number" class="form-control"
                       value="<?= htmlspecialchars($partner->taxNumber ?? '') ?>">
            </div>

            <div class="col-md-8">
                <label for="address" class="form-label">Адреса</label>
                <input type="text" id="address" name="address" class="form-control"
                       value="<?= htmlspecialchars($partner->address ?? '') ?>">
            </div>

            <div class="col-12">
                <label for="contact" class="form-label">Контакт (тел./емаил)</label>
                <input type="text" id="contact" name="contact" class="form-control"
                       value="<?= htmlspecialchars($partner->contact ?? '') ?>">
            </div>

            <div class="col-12">
                <div class="form-check">
                    <input type="checkbox" id="is_active" name="is_active" value="1" class="form-check-input"
                           <?= ($partner->isActive ?? true) ? 'checked' : '' ?>>
                    <label for="is_active" class="form-check-label">Активен</label>
                </div>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">Зачувај</button>
                <a href="/partners" class="btn btn-outline-secondary">Откажи</a>
            </div>
        </form>
    </div>
</div>
