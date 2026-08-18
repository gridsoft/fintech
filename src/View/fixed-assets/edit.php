<div class="card" style="max-width: 560px;">
    <div class="card-body">
        <form method="post" action="/fixed-assets/<?= $asset->id ?>">
            <div class="mb-3">
                <label for="name" class="form-label">Име</label>
                <input type="text" id="name" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($asset->name) ?>" required>
                <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['name']) ?></div><?php endif; ?>
            </div>
            <div class="mb-3">
                <label for="annual_rate" class="form-label">Стапка на амортизација (% годишно)</label>
                <input type="number" step="0.01" min="0.01" id="annual_rate" name="annual_rate" class="form-control <?= isset($errors['annual_rate']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($asset->annualRate) ?>" required>
                <?php if (isset($errors['annual_rate'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['annual_rate']) ?></div><?php endif; ?>
                <div class="form-text">
                    Стандардно преземена од категоријата при создавање, но уредлива по средство —
                    на пр. ако проценката за конкретното средство се разликува. Месечен износ = набавна
                    вредност × стапка / 100 / 12; ако веќе има извршена амортизација, промената важи само за идните месеци.
                </div>
            </div>
            <div class="mb-3 text-muted small">
                Набавна вредност (<?= number_format((float) $asset->purchaseValue, 2) ?>), датум на набавка
                (<?= htmlspecialchars($asset->purchaseDate) ?>) и конто се замрзнати од изворната влезна
                фактура — не се уредливи тука.
            </div>
            <button type="submit" class="btn btn-primary">Зачувај</button>
            <a href="/fixed-assets/<?= $asset->id ?>" class="btn btn-outline-secondary">Откажи</a>
        </form>
    </div>
</div>
