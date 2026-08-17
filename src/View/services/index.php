<?php ob_start(); ?>
<a href="/services/create" class="btn btn-primary">
    <i class="bi bi-plus-lg"></i> Нова услуга
</a>
<?php $headerActions = ob_get_clean(); ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 data-table">
            <thead class="table-light">
                <tr>
                    <th>Назив</th>
                    <th>Категорија</th>
                    <th class="text-end">Цена</th>
                    <th>Статус</th>
                    <th class="text-end" data-no-filter>Акции</th>
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
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="/services/<?= $service->id ?>/edit"><i class="bi bi-pencil me-2"></i>Уреди</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="/services/<?= $service->id ?>/delete" method="post"
                                              onsubmit="return confirm('Да се избрише услугата <?= htmlspecialchars(addslashes($service->name)) ?>?');">
                                            <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Избриши</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
