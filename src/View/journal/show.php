<div class="card mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3"><strong>Датум:</strong> <?= htmlspecialchars($entry->date) ?></div>
            <div class="col-md-6"><strong>Опис:</strong> <?= htmlspecialchars($entry->description) ?></div>
            <div class="col-md-3"><strong>Референца:</strong> <?= htmlspecialchars($entry->reference ?? '—') ?></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Сметка</th>
                    <th>Партнер</th>
                    <th>Опис на ставка</th>
                    <th class="text-end">Дебит</th>
                    <th class="text-end">Кредит</th>
                </tr>
            </thead>
            <tbody>
                <?php $totalDebit = 0; $totalCredit = 0; ?>
                <?php foreach ($entry->lines as $line): ?>
                    <?php $account = $accountsById[$line->accountId] ?? null; ?>
                    <?php $partner = $line->partnerId ? ($partnersById[$line->partnerId] ?? null) : null; ?>
                    <?php $totalDebit += (float) $line->debit; $totalCredit += (float) $line->credit; ?>
                    <tr>
                        <td><?= $account ? htmlspecialchars($account->code . ' — ' . $account->name) : '#' . $line->accountId ?></td>
                        <td class="text-muted"><?= $partner ? htmlspecialchars($partner->name) : '—' ?></td>
                        <td class="text-muted"><?= htmlspecialchars($line->description ?? '') ?></td>
                        <td class="text-end"><?= $line->debit > 0 ? number_format((float) $line->debit, 2) : '' ?></td>
                        <td class="text-end"><?= $line->credit > 0 ? number_format((float) $line->credit, 2) : '' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="table-light fw-semibold">
                    <td colspan="3" class="text-end">Вкупно:</td>
                    <td class="text-end"><?= number_format($totalDebit, 2) ?></td>
                    <td class="text-end"><?= number_format($totalCredit, 2) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
