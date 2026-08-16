<div class="d-flex justify-content-end mb-3">
    <a href="/products/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Нов производ
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
                            <a href="/products/<?= $product->id ?>/edit" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="/products/<?= $product->id ?>/delete" method="post" class="d-inline"
                                  onsubmit="return confirm('Да се избрише производот <?= htmlspecialchars(addslashes($product->name)) ?>?');">
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
