<?php $action = $nalog !== null ? "/nalozi/{$nalog->id}" : '/nalozi'; ?>

<div class="card">
    <div class="card-body">
        <form method="post" action="<?= $action ?>" class="row g-3" style="max-width: 480px;">
            <div class="col-12">
                <label for="name" class="form-label">Назив</label>
                <input type="text" id="name" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($nalog->name ?? '') ?>" required>
                <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['name']) ?></div><?php endif; ?>
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
