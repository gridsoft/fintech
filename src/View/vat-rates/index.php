<?php ob_start(); ?>
<a href="/vat-rates/create" class="btn btn-primary">
    <i class="bi bi-plus-lg"></i> Нова ставка
</a>
<?php $headerActions = ob_get_clean(); ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 data-table">
            <thead class="table-light">
                <tr>
                    <th>Назив</th>
                    <th>Стапка</th>
                    <th>Тип</th>
                    <th>Сметка (излезен ДДВ)</th>
                    <th>Сметка (влезен ДДВ)</th>
                    <th>УЈП код</th>
                    <th>Статус</th>
                    <th class="text-end" data-no-filter>Акции</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($vatRates)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Нема дефинирани ДДВ стапки.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($vatRates as $vatRate): ?>
                    <?php $payableAccount = $vatRate->payableAccountId ? ($accountsById[$vatRate->payableAccountId] ?? null) : null; ?>
                    <?php $receivableAccount = $vatRate->receivableAccountId ? ($accountsById[$vatRate->receivableAccountId] ?? null) : null; ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($vatRate->name) ?></td>
                        <td><?= number_format((float) $vatRate->rate, 2) ?>%</td>
                        <td><span class="badge text-bg-light border"><?= htmlspecialchars($vatRate->typeLabel()) ?></span></td>
                        <td class="text-muted"><?= $payableAccount ? htmlspecialchars($payableAccount->code . ' — ' . $payableAccount->name) : '—' ?></td>
                        <td class="text-muted"><?= $receivableAccount ? htmlspecialchars($receivableAccount->code . ' — ' . $receivableAccount->name) : '—' ?></td>
                        <td>
                            <?php if ($vatRate->ujpTaxIndicatorCode): ?>
                                <code><?= htmlspecialchars($vatRate->ujpTaxIndicatorCode) ?></code>
                            <?php else: ?>
                                <span class="badge text-bg-warning-subtle text-warning-emphasis">немапирано</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($vatRate->isActive): ?>
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
                                    <li><a class="dropdown-item" href="/vat-rates/<?= $vatRate->id ?>/edit"><i class="bi bi-pencil me-2"></i>Уреди</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="/vat-rates/<?= $vatRate->id ?>/delete" method="post"
                                              onsubmit="return confirm('Да се избрише ставката <?= htmlspecialchars(addslashes($vatRate->name)) ?>?');">
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
