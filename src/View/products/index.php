<?php ob_start(); ?>
<a href="/products/create" class="btn btn-primary">
    <i class="bi bi-plus-lg"></i> Нов производ
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
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Нема внесени производи.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($products as $product): ?>
                    <?php $category = $categoriesById[$product->categoryId] ?? null; ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($product->name) ?></td>
                        <td class="text-muted"><?= $category ? htmlspecialchars($category->name) : '—' ?></td>
                        <td class="text-end"><?= number_format((float) $product->price, 2) ?></td>
                        <td>
                            <?php if ($product->isActive): ?>
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
                                    <li><a class="dropdown-item" href="/products/<?= $product->id ?>/edit"><i class="bi bi-pencil me-2"></i>Уреди</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="/products/<?= $product->id ?>/delete" method="post"
                                              onsubmit="return confirm('Да се избрише производот <?= htmlspecialchars(addslashes($product->name)) ?>?');">
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
