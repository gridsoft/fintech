<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h5 class="mb-1">Извод <?= htmlspecialchars($statement->date) ?></h5>
        <div class="text-muted"><?= $account ? htmlspecialchars($account->code . ' — ' . $account->name) : '—' ?></div>
        <?php if ($statement->reference): ?><div class="text-muted">Референца: <?= htmlspecialchars($statement->reference) ?></div><?php endif; ?>
    </div>
</div>

<div class="card mb-4">
    <div class="table-responsive">
        <table class="table align-middle mb-0 data-table">
            <thead class="table-light">
                <tr>
                    <th>Датум</th>
                    <th>Опис</th>
                    <th>Партнер</th>
                    <th>Насока</th>
                    <th class="text-end">Износ</th>
                    <th>Статус</th>
                    <th>Фактура</th>
                    <th data-no-filter></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($statement->transactions)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Нема внесени трансакции.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($statement->transactions as $transaction): ?>
                    <?php $partner = $transaction->partnerId ? ($partnersById[$transaction->partnerId] ?? null) : null; ?>
                    <tr>
                        <td><?= htmlspecialchars($transaction->date) ?></td>
                        <td class="text-muted"><?= htmlspecialchars($transaction->description ?? '') ?></td>
                        <td><?= $partner ? htmlspecialchars($partner->name) : '—' ?></td>
                        <td>
                            <span class="badge <?= $transaction->direction === 'in' ? 'text-bg-success-subtle text-success-emphasis' : 'text-bg-danger-subtle text-danger-emphasis' ?>">
                                <?= htmlspecialchars($transaction->directionLabel()) ?>
                            </span>
                        </td>
                        <td class="text-end"><?= number_format((float) $transaction->amount, 2) ?></td>
                        <td>
                            <?php if ($transaction->matchedStatus === 'matched'): ?>
                                <span class="badge text-bg-success-subtle text-success-emphasis">поврзана</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary-subtle text-secondary-emphasis">неповрзана</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted">
                            <?php if ($transaction->invoiceType): ?>
                                <?= htmlspecialchars($transaction->invoiceTypeLabel()) ?> #<?= $transaction->invoiceId ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if ($transaction->matchedStatus === 'unmatched'): ?>
                                <?php if ($transaction->partnerId): ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#matchModal<?= $transaction->id ?>">Поврзи</button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Прво изберете партнер на трансакцијата">Поврзи</button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h6 class="mb-3">Додади трансакција</h6>
        <?php if (!empty($errors['lines']) && is_string($errors['lines'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errors['lines']) ?></div>
        <?php endif; ?>
        <form method="post" action="/bank-statements/<?= $statement->id ?>/transactions" class="row g-3">
            <div class="col-md-2">
                <label for="transaction_date" class="form-label">Датум</label>
                <input type="date" id="transaction_date" name="transaction_date" class="form-control" value="<?= htmlspecialchars($statement->date) ?>" required>
            </div>
            <div class="col-md-3">
                <label for="description" class="form-label">Опис</label>
                <input type="text" id="description" name="description" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="partner_id" class="form-label">Партнер <span class="text-muted small">(опционално)</span></label>
                <select id="partner_id" name="partner_id" class="form-select">
                    <option value="">— нема —</option>
                    <?php foreach ($partners as $partner): ?>
                        <option value="<?= $partner->id ?>"><?= htmlspecialchars($partner->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="direction" class="form-label">Насока</label>
                <select id="direction" name="direction" class="form-select">
                    <option value="in">Уплата (влез)</option>
                    <option value="out">Исплата (излез)</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="amount" class="form-label">Износ</label>
                <input type="number" step="0.01" min="0.01" id="amount" name="amount" class="form-control" required>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary btn-sm">Додади</button>
            </div>
        </form>
    </div>
</div>

<?php foreach ($statement->transactions as $transaction): ?>
    <?php if (!isset($matchData[$transaction->id])) continue; ?>
    <?php
        $md = $matchData[$transaction->id];
        $partner = $partnersById[$transaction->partnerId] ?? null;
    ?>
    <div class="modal fade" id="matchModal<?= $transaction->id ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post" action="/bank-transactions/<?= $transaction->id ?>/match">
                    <div class="modal-header">
                        <h5 class="modal-title">Поврзи трансакција со фактура — <?= $partner ? htmlspecialchars($partner->name) : '—' ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Затвори"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-4"><strong>Датум:</strong> <?= htmlspecialchars($transaction->date) ?></div>
                            <div class="col-md-4"><strong>Насока:</strong> <?= htmlspecialchars($transaction->directionLabel()) ?></div>
                            <div class="col-md-4"><strong>Износ:</strong> <?= number_format((float) $transaction->amount, 2) ?></div>
                        </div>

                        <?php if ($transaction->direction === 'in' && empty($md['openSalesInvoices'])): ?>
                            <div class="alert alert-warning mb-0">Нема отворени излезни фактури за овој партнер.</div>
                        <?php elseif ($transaction->direction === 'out' && empty($md['openPurchaseInvoices'])): ?>
                            <div class="alert alert-warning mb-0">Нема отворени влезни фактури за овој партнер.</div>
                        <?php else: ?>
                            <div class="table-responsive">
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
                                    <?php if ($transaction->direction === 'in'): ?>
                                        <?php foreach ($md['openSalesInvoices'] as $invoice): ?>
                                            <tr>
                                                <td><input type="radio" name="invoice_pick" value="sales:<?= $invoice->id ?>" required></td>
                                                <td class="fw-semibold"><?= htmlspecialchars($invoice->number) ?></td>
                                                <td><?= htmlspecialchars($invoice->date) ?></td>
                                                <td class="text-end"><?= number_format((float) $invoice->totalGross, 2) ?></td>
                                                <td class="text-end"><?= number_format((float) $md['outstandingByInvoiceId']['sales_' . $invoice->id], 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <?php foreach ($md['openPurchaseInvoices'] as $invoice): ?>
                                            <tr>
                                                <td><input type="radio" name="invoice_pick" value="purchase:<?= $invoice->id ?>" required></td>
                                                <td class="fw-semibold"><?= htmlspecialchars($invoice->supplierNumber) ?></td>
                                                <td><?= htmlspecialchars($invoice->date) ?></td>
                                                <td class="text-end"><?= number_format((float) $invoice->totalGross, 2) ?></td>
                                                <td class="text-end"><?= number_format((float) $md['outstandingByInvoiceId']['purchase_' . $invoice->id], 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            </div>
                        <?php endif; ?>

                        <input type="hidden" name="invoice_type" class="js-invoice-type">
                        <input type="hidden" name="invoice_id" class="js-invoice-id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Откажи</button>
                        <button type="submit" class="btn btn-primary match-submit">Поврзи</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script>
document.querySelectorAll('.match-submit').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
        var form = btn.closest('form');
        var picked = form.querySelector('input[name="invoice_pick"]:checked');
        if (!picked) {
            return;
        }
        var parts = picked.value.split(':');
        form.querySelector('.js-invoice-type').value = parts[0];
        form.querySelector('.js-invoice-id').value = parts[1];
    });
});
</script>
