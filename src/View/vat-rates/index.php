<div class="d-flex justify-content-end mb-3">
    <a href="/vat-rates/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Нова ставка
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Назив</th>
                    <th>Стапка</th>
                    <th>Тип</th>
                    <th>Сметка за обврска</th>
                    <th>Статус</th>
                    <th class="text-end">Акции</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($vatRates)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Нема дефинирани ДДВ стапки.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($vatRates as $vatRate): ?>
                    <?php $payableAccount = $vatRate->payableAccountId ? ($accountsById[$vatRate->payableAccountId] ?? null) : null; ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($vatRate->name) ?></td>
                        <td><?= number_format((float) $vatRate->rate, 2) ?>%</td>
                        <td><span class="badge text-bg-light border"><?= htmlspecialchars($vatRate->typeLabel()) ?></span></td>
                        <td class="text-muted"><?= $payableAccount ? htmlspecialchars($payableAccount->code . ' — ' . $payableAccount->name) : '—' ?></td>
                        <td>
                            <?php if ($vatRate->isActive): ?>
                                <span class="badge text-bg-success-subtle text-success-emphasis">активна</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary-subtle text-secondary-emphasis">неактивна</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="/vat-rates/<?= $vatRate->id ?>/edit" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="/vat-rates/<?= $vatRate->id ?>/delete" method="post" class="d-inline"
                                  onsubmit="return confirm('Да се избрише ставката <?= htmlspecialchars(addslashes($vatRate->name)) ?>?');">
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
