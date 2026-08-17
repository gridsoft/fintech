<?php ob_start(); ?>
<a href="/expense-categories/create" class="btn btn-primary">
    <i class="bi bi-plus-lg"></i> Нова категорија
</a>
<?php $headerActions = ob_get_clean(); ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 data-table">
            <thead class="table-light">
                <tr>
                    <th>Назив</th>
                    <th>Сметка (домашен)</th>
                    <th>Сметка (странски)</th>
                    <th>ДДВ</th>
                    <th>Статус</th>
                    <th class="text-end" data-no-filter>Акции</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Нема дефинирани категории.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($categories as $category): ?>
                    <?php
                        $domAcc = $accountsById[$category->domesticAccountId] ?? null;
                        $forAcc = $category->foreignAccountId ? ($accountsById[$category->foreignAccountId] ?? null) : null;
                    ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($category->name) ?></td>
                        <td class="text-muted"><?= $domAcc ? htmlspecialchars($domAcc->code . ' — ' . $domAcc->name) : '—' ?></td>
                        <td class="text-muted"><?= $forAcc ? htmlspecialchars($forAcc->code . ' — ' . $forAcc->name) : '—' ?></td>
                        <td><span class="badge text-bg-light border"><?= htmlspecialchars($category->vatDeductibleLabel()) ?></span></td>
                        <td>
                            <?php if ($category->isActive): ?>
                                <span class="badge text-bg-success-subtle text-success-emphasis">активна</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary-subtle text-secondary-emphasis">неактивна</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="/expense-categories/<?= $category->id ?>/edit"><i class="bi bi-pencil me-2"></i>Уреди</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="/expense-categories/<?= $category->id ?>/delete" method="post"
                                              onsubmit="return confirm('Да се избрише категоријата <?= htmlspecialchars(addslashes($category->name)) ?>?');">
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
