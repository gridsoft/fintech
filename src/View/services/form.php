<?php $action = $service !== null ? "/services/{$service->id}" : '/services'; ?>

<div class="card">
    <div class="card-body">
        <form method="post" action="<?= $action ?>" class="row g-3" style="max-width: 640px;">
            <div class="col-md-6">
                <label for="name" class="form-label">Назив</label>
                <input type="text" id="name" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($service->name ?? '') ?>" required>
                <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['name']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-4">
                <label for="category_id" class="form-label">Категорија</label>
                <select id="category_id" name="category_id" class="form-select <?= isset($errors['category_id']) ? 'is-invalid' : '' ?>">
                    <option value="">— избери категорија —</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category->id ?>" <?= (int) ($service->categoryId ?? 0) === $category->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['category_id'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['category_id']) ?></div>
                <?php else: ?>
                    <div class="form-text">Нема категории? <a href="/service-categories/create" target="_blank">Создади една →</a></div>
                <?php endif; ?>
            </div>

            <div class="col-md-2">
                <label for="price" class="form-label">Цена</label>
                <input type="number" step="0.01" min="0" id="price" name="price" class="form-control <?= isset($errors['price']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($service->price ?? '') ?>">
                <?php if (isset($errors['price'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['price']) ?></div><?php endif; ?>
            </div>

            <div class="col-12">
                <div class="form-check">
                    <input type="checkbox" id="is_active" name="is_active" value="1" class="form-check-input"
                           <?= ($service->isActive ?? true) ? 'checked' : '' ?>>
                    <label for="is_active" class="form-check-label">Активен</label>
                </div>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">Зачувај</button>
                <a href="/services" class="btn btn-outline-secondary">Откажи</a>
            </div>
        </form>
    </div>
</div>
