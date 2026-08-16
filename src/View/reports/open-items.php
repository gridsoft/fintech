<div class="mb-3">
    <a href="/reports" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Назад кон извештаи</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Партнер</th>
                    <th class="text-end">Дебит</th>
                    <th class="text-end">Кредит</th>
                    <th class="text-end">Салдо</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">Нема отворени ставки.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($rows as $row): ?>
                    <tr class="cursor-pointer" onclick="location.href='/partners/<?= $row['partner']->id ?>/statement'" style="cursor:pointer;">
                        <td class="fw-semibold"><?= htmlspecialchars($row['partner']->name) ?></td>
                        <td class="text-end"><?= number_format((float) $row['debit'], 2) ?></td>
                        <td class="text-end"><?= number_format((float) $row['credit'], 2) ?></td>
                        <td class="text-end fw-semibold <?= $row['balance'] > 0 ? 'text-success' : 'text-danger' ?>">
                            <?= number_format((float) $row['balance'], 2) ?>
                            <?= $row['balance'] > 0 ? ' (ни должи)' : ' (му должиме)' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
