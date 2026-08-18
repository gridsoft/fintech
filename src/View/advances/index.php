<?php
$renderTable = function (string $title, array $rows) use ($partnersById, $applyData) {
    ?>
    <div class="card mb-4">
        <div class="card-header"><?= htmlspecialchars($title) ?></div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Датум</th>
                        <th>Партнер</th>
                        <th class="text-end">Износ</th>
                        <th class="text-end">Преостанато</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Нема отворени аванси.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $row): ?>
                        <?php
                            $transaction = $row['transaction'];
                            $remaining = $row['remaining'];
                            $partner = $transaction->partnerId ? ($partnersById[$transaction->partnerId] ?? null) : null;
                            $md = $applyData[$transaction->id] ?? null;
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($transaction->date) ?></td>
                            <td><?= $partner ? htmlspecialchars($partner->name) : '—' ?></td>
                            <td class="text-end"><?= number_format((float) $transaction->amount, 2) ?></td>
                            <td class="text-end fw-semibold"><?= number_format((float) $remaining, 2) ?></td>
                            <td class="text-end">
                                <?php if ($partner && $md && !empty($md['openInvoices'])): ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#applyModal<?= $transaction->id ?>">Примени на фактура</button>
                                <?php elseif (!$partner): ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Авансот нема таговиран партнер">Примени на фактура</button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Нема отворени фактури за овој партнер">Примени на фактура</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
};

$renderTable('Примени аванси (од купувачи)', $received);
$renderTable('Дадени аванси (на добавувачи)', $given);
?>

<?php foreach (array_merge($received, $given) as $row): ?>
    <?php
        $transaction = $row['transaction'];
        $remaining = $row['remaining'];
        $md = $applyData[$transaction->id] ?? null;

        if (!$transaction->partnerId || !$md || empty($md['openInvoices'])) {
            continue;
        }

        $partner = $partnersById[$transaction->partnerId] ?? null;
        $invoiceType = $md['invoiceType'];
    ?>
    <div class="modal fade" id="applyModal<?= $transaction->id ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post" action="/advances/<?= $transaction->id ?>/apply" data-advance-remaining="<?= htmlspecialchars($remaining) ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">Примени аванс — <?= $partner ? htmlspecialchars($partner->name) : '—' ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Затвори"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6"><strong>Датум на аванс:</strong> <?= htmlspecialchars($transaction->date) ?></div>
                            <div class="col-md-6"><strong>Преостанато на авансот:</strong> <?= number_format((float) $remaining, 2) ?></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Конто <span class="text-muted small">(другата страна на книжењето)</span></label>
                            <select name="account_id" class="form-select" required>
                                <option value="">— избери конто —</option>
                                <?php foreach ($glAccounts as $glAccount): ?>
                                    <option value="<?= $glAccount->id ?>"><?= htmlspecialchars($glAccount->code . ' — ' . $glAccount->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="table-responsive mb-3">
                            <table class="table align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th></th>
                                        <th>Фактура</th>
                                        <th>Датум</th>
                                        <th class="text-end">Вкупно</th>
                                        <th class="text-end">Преостанато</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($md['openInvoices'] as $invoice): ?>
                                        <?php $number = $invoiceType === 'sales' ? $invoice->number : $invoice->supplierNumber; ?>
                                        <tr>
                                            <td><input type="radio" name="invoice_id" value="<?= $invoice->id ?>" data-outstanding="<?= htmlspecialchars($md['outstandingByInvoiceId'][$invoice->id]) ?>" required></td>
                                            <td class="fw-semibold"><?= htmlspecialchars($number) ?></td>
                                            <td><?= htmlspecialchars($invoice->date) ?></td>
                                            <td class="text-end"><?= number_format((float) $invoice->totalGross, 2) ?></td>
                                            <td class="text-end"><?= number_format((float) $md['outstandingByInvoiceId'][$invoice->id], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <input type="hidden" name="invoice_type" value="<?= htmlspecialchars($invoiceType) ?>">
                        <div class="col-md-4">
                            <label class="form-label">Износ за примена</label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control apply-amount" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Откажи</button>
                        <button type="submit" class="btn btn-primary">Примени</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script>
document.querySelectorAll('input[name="invoice_id"]').forEach(function (radio) {
    radio.addEventListener('change', function () {
        var form = radio.closest('form');
        var amountInput = form.querySelector('.apply-amount');
        var outstanding = parseFloat(radio.getAttribute('data-outstanding'));
        var advanceRemaining = parseFloat(form.getAttribute('data-advance-remaining'));
        var amount = Math.min(outstanding, advanceRemaining);
        amountInput.value = amount.toFixed(2);
        amountInput.max = amount.toFixed(2);
    });
});
</script>
