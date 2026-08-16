<div class="mb-3">
    <a href="/reports" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Назад кон извештаи</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Шифра</th>
                    <th>Сметка</th>
                    <th class="text-end">Дебит</th>
                    <th class="text-end">Кредит</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($report['rows'])): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">Нема книжења.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($report['rows'] as $row): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($row['account']->code) ?></td>
                        <td><?= htmlspecialchars($row['account']->name) ?></td>
                        <td class="text-end"><?= number_format((float) $row['debit'], 2) ?></td>
                        <td class="text-end"><?= number_format((float) $row['credit'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <?php if (!empty($report['rows'])): ?>
                <tfoot>
                    <tr class="table-light fw-semibold">
                        <td colspan="2" class="text-end">Вкупно:</td>
                        <td class="text-end"><?= number_format((float) $report['totalDebit'], 2) ?></td>
                        <td class="text-end"><?= number_format((float) $report['totalCredit'], 2) ?></td>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<?php if (!empty($report['rows'])): ?>
    <div class="mt-3">
        <?php if ($report['balanced']): ?>
            <span class="text-success"><i class="bi bi-check-circle-fill"></i> Балансирано — вкупниот дебит е еднаков на вкупниот кредит.</span>
        <?php else: ?>
            <span class="text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Небалансирано! Провери ги книжењата.</span>
        <?php endif; ?>
    </div>
<?php endif; ?>
