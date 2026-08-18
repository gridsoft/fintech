<?php ob_start(); ?>
<form method="post" action="/fixed-assets/run-depreciation" class="d-flex align-items-center gap-2">
    <input type="month" name="period" class="form-control form-control-sm" value="<?= htmlspecialchars($defaultPeriod) ?>" required>
    <button type="submit" class="btn btn-primary btn-sm">
        <i class="bi bi-calculator"></i> Изврши амортизација
    </button>
</form>
<?php $headerActions = ob_get_clean(); ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 data-table">
            <thead class="table-light">
                <tr>
                    <th>Име</th>
                    <th>Конто</th>
                    <th>Датум на набавка</th>
                    <th class="text-end">Вредност</th>
                    <th class="text-end">Амортизирано</th>
                    <th class="text-end">Книговодствена вредност</th>
                    <th>Статус</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($assets)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Нема внесени основни средства.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($assets as $asset): ?>
                    <?php
                        $account = $accountsById[$asset->accountId] ?? null;
                        $accumulated = $accumulatedByAsset[$asset->id] ?? '0.00';
                        $bookValue = bcsub($asset->purchaseValue, $accumulated, 2);
                    ?>
                    <tr class="cursor-pointer" onclick="location.href='/fixed-assets/<?= $asset->id ?>'" style="cursor:pointer;">
                        <td class="fw-semibold"><?= htmlspecialchars($asset->name) ?></td>
                        <td class="text-muted small"><?= $account ? htmlspecialchars($account->code . ' — ' . $account->name) : '—' ?></td>
                        <td><?= htmlspecialchars($asset->purchaseDate) ?></td>
                        <td class="text-end"><?= number_format((float) $asset->purchaseValue, 2) ?></td>
                        <td class="text-end"><?= number_format((float) $accumulated, 2) ?></td>
                        <td class="text-end fw-semibold"><?= number_format((float) $bookValue, 2) ?></td>
                        <td>
                            <span class="badge <?= $asset->status === 'active' ? 'text-bg-success-subtle text-success-emphasis' : 'text-bg-secondary-subtle text-secondary-emphasis' ?>">
                                <?= htmlspecialchars($asset->statusLabel()) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
