<div class="mb-3">
    <a href="/partners" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Назад кон партнери</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0 data-table">
            <thead class="table-light">
                <tr>
                    <th>Датум</th>
                    <th>Сметка</th>
                    <th>Опис</th>
                    <th class="text-end">Дебит</th>
                    <th class="text-end">Кредит</th>
                    <th class="text-end" data-no-filter>Салдо</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Нема книжења за овој партнер.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($rows as $row): ?>
                    <tr class="cursor-pointer" onclick="location.href='/journal/<?= $row['entry']->id ?>'" style="cursor:pointer;">
                        <td data-order="<?= htmlspecialchars($row['entry']->date) ?>"><?= htmlspecialchars(\App\Core\Dates::formatMk($row['entry']->date)) ?></td>
                        <td><?= htmlspecialchars($row['account_code'] . ' — ' . $row['account_name']) ?></td>
                        <td>
                            <?= htmlspecialchars($row['entry']->description) ?>
                            <?php if ($row['line']->description): ?>
                                <div class="small text-muted"><?= htmlspecialchars($row['line']->description) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-end"><?= $row['line']->debit > 0 ? number_format((float) $row['line']->debit, 2) : '' ?></td>
                        <td class="text-end"><?= $row['line']->credit > 0 ? number_format((float) $row['line']->credit, 2) : '' ?></td>
                        <td class="text-end fw-semibold"><?= number_format($row['balance'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <?php if (!empty($rows)): ?>
                <tfoot>
                    <tr class="table-light fw-semibold">
                        <td colspan="5" class="text-end">Крајно салдо:</td>
                        <td class="text-end"><?= number_format($closingBalance, 2) ?></td>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>
