<?php $action = $nalog !== null ? "/nalozi/{$nalog->id}" : '/nalozi'; ?>

<div class="card">
    <div class="card-body">
        <form method="post" action="<?= $action ?>" class="row g-3" style="max-width: 640px;">
            <div class="col-md-8">
                <label for="name" class="form-label">Назив</label>
                <input type="text" id="name" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($nalog->name ?? '') ?>" required>
                <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['name']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-4">
                <label for="terk_id" class="form-label">Терк</label>
                <select id="terk_id" name="terk_id" class="form-select <?= isset($errors['terk_id']) ? 'is-invalid' : '' ?>">
                    <option value="">— избери терк —</option>
                    <?php foreach ($terkovi as $terk): ?>
                        <option value="<?= $terk->id ?>" <?= (int) ($nalog->terkId ?? 0) === $terk->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($terk->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['terk_id'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['terk_id']) ?></div>
                <?php else: ?>
                    <div class="form-text">Нема теркови? <a href="/terkovi/create" target="_blank">Создади еден →</a></div>
                <?php endif; ?>
            </div>

            <div class="col-12">
                <div class="form-check">
                    <input type="checkbox" id="is_active" name="is_active" value="1" class="form-check-input"
                           <?= ($nalog->isActive ?? true) ? 'checked' : '' ?>>
                    <label for="is_active" class="form-check-label">Активен</label>
                </div>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">Зачувај</button>
                <a href="/nalozi" class="btn btn-outline-secondary">Откажи</a>
            </div>
        </form>
    </div>
</div>
