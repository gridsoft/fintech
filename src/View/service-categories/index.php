<div class="d-flex justify-content-end mb-3">
    <a href="/service-categories/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Нова категорија
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Назив</th>
                    <th>Сметка (домашен)</th>
                    <th>ДДВ (домашен)</th>
                    <th>Сметка (странски)</th>
                    <th>ДДВ (странски)</th>
                    <th>Статус</th>
                    <th class="text-end">Акции</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Нема дефинирани категории.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($categories as $category): ?>
                    <?php
                        $domAcc = $accountsById[$category->domesticAccountId] ?? null;
                        $domVat = $vatRatesById[$category->domesticVatRateId] ?? null;
                        $forAcc = $category->foreignAccountId ? ($accountsById[$category->foreignAccountId] ?? null) : null;
                        $forVat = $category->foreignVatRateId ? ($vatRatesById[$category->foreignVatRateId] ?? null) : null;
                    ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($category->name) ?></td>
                        <td class="text-muted"><?= $domAcc ? htmlspecialchars($domAcc->code . ' — ' . $domAcc->name) : '—' ?></td>
                        <td class="text-muted"><?= $domVat ? htmlspecialchars($domVat->name . ' (' . $domVat->rate . '%)') : '—' ?></td>
                        <td class="text-muted"><?= $forAcc ? htmlspecialchars($forAcc->code . ' — ' . $forAcc->name) : '—' ?></td>
                        <td class="text-muted"><?= $forVat ? htmlspecialchars($forVat->name . ' (' . $forVat->rate . '%)') : '—' ?></td>
                        <td>
                            <?php if ($category->isActive): ?>
                                <span class="badge text-bg-success-subtle text-success-emphasis">активна</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary-subtle text-secondary-emphasis">неактивна</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="/service-categories/<?= $category->id ?>/edit" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="/service-categories/<?= $category->id ?>/delete" method="post" class="d-inline"
                                  onsubmit="return confirm('Да се избрише категоријата <?= htmlspecialchars(addslashes($category->name)) ?>?');">
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
