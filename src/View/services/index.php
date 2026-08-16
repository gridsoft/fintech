<div class="d-flex justify-content-end mb-3">
    <a href="/services/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Нова услуга
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Назив</th>
                    <th>Категорија</th>
                    <th class="text-end">Цена</th>
                    <th>Статус</th>
                    <th class="text-end">Акции</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($services)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Нема внесени услуги.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($services as $service): ?>
                    <?php $category = $categoriesById[$service->categoryId] ?? null; ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($service->name) ?></td>
                        <td class="text-muted"><?= $category ? htmlspecialchars($category->name) : '—' ?></td>
                        <td class="text-end"><?= number_format((float) $service->price, 2) ?></td>
                        <td>
                            <?php if ($service->isActive): ?>
                                <span class="badge text-bg-success-subtle text-success-emphasis">активен</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary-subtle text-secondary-emphasis">неактивен</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="/services/<?= $service->id ?>/edit" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="/services/<?= $service->id ?>/delete" method="post" class="d-inline"
                                  onsubmit="return confirm('Да се избрише услугата <?= htmlspecialchars(addslashes($service->name)) ?>?');">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
