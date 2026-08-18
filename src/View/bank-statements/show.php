<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h5 class="mb-1">Извод <?= htmlspecialchars($statement->date) ?></h5>
        <div class="text-muted"><?= $account ? htmlspecialchars($account->code . ' — ' . $account->name) : '—' ?></div>
        <div class="text-muted">Почетно салдо: <?= number_format((float) $statement->openingBalance, 2) ?></div>
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
                    <th>Шифра</th>
                    <th>Партнер</th>
                    <th>Конто</th>
                    <th>Насока</th>
                    <th class="text-end">Износ</th>
                    <th class="text-end">Салдо</th>
                    <th>Статус</th>
                    <th>Фактура</th>
                    <th data-no-filter></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($statement->transactions)): ?>
                    <tr>
                        <td colspan="11" class="text-center text-muted py-4">Нема внесени трансакции.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($statement->transactions as $transaction): ?>
                    <?php
                        $partner = $transaction->partnerId ? ($partnersById[$transaction->partnerId] ?? null) : null;
                        $glAccount = $transaction->glAccountId ? ($accountsById[$transaction->glAccountId] ?? null) : null;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($transaction->date) ?></td>
                        <td class="text-muted"><?= htmlspecialchars($transaction->description ?? '') ?></td>
                        <td class="text-muted"><?= htmlspecialchars($transaction->code ?? '') ?></td>
                        <td><?= $partner ? htmlspecialchars($partner->name) : '—' ?></td>
                        <td class="text-muted small"><?= $glAccount ? htmlspecialchars($glAccount->code . ' — ' . $glAccount->name) : '—' ?></td>
                        <td>
                            <span class="badge <?= $transaction->direction === 'in' ? 'text-bg-success-subtle text-success-emphasis' : 'text-bg-danger-subtle text-danger-emphasis' ?>">
                                <?= htmlspecialchars($transaction->directionLabel()) ?>
                            </span>
                        </td>
                        <td class="text-end"><?= number_format((float) $transaction->amount, 2) ?></td>
                        <td class="text-end"><?= $transaction->balanceAfter !== null ? number_format((float) $transaction->balanceAfter, 2) : '—' ?></td>
                        <td>
                            <?php if ($transaction->matchedStatus === 'matched'): ?>
                                <span class="badge text-bg-success-subtle text-success-emphasis">книжена</span>
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

<div class="card mb-4">
    <div class="card-body">
        <h6 class="mb-3">Внеси трансакции</h6>
        <p class="small text-muted">Изберете партнер и фактура ако трансакцијата плаќа фактура (цената/преостанатото, датата на фактурата и прилив/одлив се пополнуваат автоматски, сепак уредливо за делумно плаќање) — или оставете ги партнер/фактура празни за директно книжење наспроти избраното конто (пр. банкарски провизии, плати).</p>
        <?php if (!empty($errors['lines']) && is_string($errors['lines'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errors['lines']) ?></div>
        <?php endif; ?>

        <form method="post" action="/bank-statements/<?= $statement->id ?>/transactions">
            <div class="table-responsive">
            <table class="table align-middle" id="matched-lines-table">
                <thead class="table-light">
                    <tr>
                        <th style="min-width: 180px">Партнер <span class="text-muted small">(опц.)</span></th>
                        <th style="min-width: 220px">Фактура <span class="text-muted small">(опц.)</span></th>
                        <th style="min-width: 180px">Конто</th>
                        <th style="min-width: 110px">Цена (преостанато)</th>
                        <th style="min-width: 110px">Дата на фактура</th>
                        <th style="min-width: 110px">Прилив</th>
                        <th style="min-width: 110px">Одлив</th>
                        <th style="min-width: 110px">Салдо (ново)</th>
                        <th style="min-width: 160px">Забелешка</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="matched-line-row">
                        <td>
                            <select name="partner_id[]" class="form-select matched-partner">
                                <option value="">— нема —</option>
                                <?php foreach ($partners as $partner): ?>
                                    <option value="<?= $partner->id ?>"><?= htmlspecialchars($partner->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <select name="invoice_pick[]" class="form-select matched-invoice">
                                <option value="">— без фактура —</option>
                            </select>
                        </td>
                        <td>
                            <select name="gl_account_id[]" class="form-select matched-konto">
                                <option value="">— конто —</option>
                                <?php foreach ($glAccounts as $glAccount): ?>
                                    <option value="<?= $glAccount->id ?>"><?= htmlspecialchars($glAccount->code . ' — ' . $glAccount->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="text" class="form-control matched-remaining" readonly tabindex="-1"></td>
                        <td><input type="text" class="form-control matched-invoice-date" readonly tabindex="-1"></td>
                        <td><input type="number" step="0.01" min="0" name="amount_in[]" class="form-control matched-amount-in"></td>
                        <td><input type="number" step="0.01" min="0" name="amount_out[]" class="form-control matched-amount-out"></td>
                        <td><input type="text" class="form-control matched-balance" readonly tabindex="-1"></td>
                        <td><input type="text" name="description[]" class="form-control"></td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger remove-matched-line"><i class="bi bi-x-lg"></i></button></td>
                    </tr>
                </tbody>
            </table>
            </div>

            <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="add-matched-line">
                <i class="bi bi-plus-lg"></i> Додади ред
            </button>

            <div>
                <button type="submit" class="btn btn-primary btn-sm">Зачувај</button>
            </div>
        </form>
    </div>
</div>

<template id="matched-line-template">
    <tr class="matched-line-row">
        <td>
            <select name="partner_id[]" class="form-select matched-partner">
                <option value="">— нема —</option>
                <?php foreach ($partners as $partner): ?>
                    <option value="<?= $partner->id ?>"><?= htmlspecialchars($partner->name) ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td>
            <select name="invoice_pick[]" class="form-select matched-invoice">
                <option value="">— без фактура —</option>
            </select>
        </td>
        <td>
            <select name="gl_account_id[]" class="form-select matched-konto">
                <option value="">— конто —</option>
                <?php foreach ($glAccounts as $glAccount): ?>
                    <option value="<?= $glAccount->id ?>"><?= htmlspecialchars($glAccount->code . ' — ' . $glAccount->name) ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input type="text" class="form-control matched-remaining" readonly tabindex="-1"></td>
        <td><input type="text" class="form-control matched-invoice-date" readonly tabindex="-1"></td>
        <td><input type="number" step="0.01" min="0" name="amount_in[]" class="form-control matched-amount-in"></td>
        <td><input type="number" step="0.01" min="0" name="amount_out[]" class="form-control matched-amount-out"></td>
        <td><input type="text" class="form-control matched-balance" readonly tabindex="-1"></td>
        <td><input type="text" name="description[]" class="form-control"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger remove-matched-line"><i class="bi bi-x-lg"></i></button></td>
    </tr>
</template>

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

                        <div class="mb-3">
                            <label class="form-label">Конто</label>
                            <select name="account_id" class="form-select" required>
                                <option value="">— избери конто —</option>
                                <?php foreach ($glAccounts as $glAccount): ?>
                                    <option value="<?= $glAccount->id ?>"><?= htmlspecialchars($glAccount->code . ' — ' . $glAccount->name) ?></option>
                                <?php endforeach; ?>
                            </select>
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

<?php
$invoicesByPartnerForJs = [];
foreach ($openSalesInvoicesByPartner as $partnerId => $invoices) {
    foreach ($invoices as $invoice) {
        $invoicesByPartnerForJs[$partnerId][] = [
            'type' => 'sales',
            'id' => $invoice['id'],
            'number' => $invoice['number'],
            'date' => $invoice['date'],
            'outstanding' => $invoice['outstanding'],
        ];
    }
}
foreach ($openPurchaseInvoicesByPartner as $partnerId => $invoices) {
    foreach ($invoices as $invoice) {
        $invoicesByPartnerForJs[$partnerId][] = [
            'type' => 'purchase',
            'id' => $invoice['id'],
            'number' => $invoice['number'],
            'date' => $invoice['date'],
            'outstanding' => $invoice['outstanding'],
        ];
    }
}
?>
<script>
window.openInvoicesByPartner = <?= json_encode($invoicesByPartnerForJs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

(function () {
    var openingBalance = <?= json_encode((float) $statement->openingBalance) ?>;
    var tbody = document.querySelector('#matched-lines-table tbody');
    var template = document.getElementById('matched-line-template');

    function formatMoney(n) {
        return (Math.round(n * 100) / 100).toFixed(2);
    }

    function populateInvoiceSelect(row) {
        var partnerSelect = row.querySelector('.matched-partner');
        var invoiceSelect = row.querySelector('.matched-invoice');
        var partnerId = partnerSelect.value;
        var invoices = (partnerId && window.openInvoicesByPartner[partnerId]) || [];

        invoiceSelect.innerHTML = '';

        if (!partnerId) {
            invoiceSelect.appendChild(new Option('— без фактура —', ''));
            return;
        }

        if (!invoices.length) {
            invoiceSelect.appendChild(new Option('— нема отворени фактури —', ''));
            return;
        }

        invoiceSelect.appendChild(new Option('— без фактура —', ''));

        invoices.forEach(function (inv) {
            var opt = new Option(inv.number + ' — преостанато ' + formatMoney(inv.outstanding), inv.type + ':' + inv.id);
            opt.setAttribute('data-remaining', inv.outstanding);
            opt.setAttribute('data-date', inv.date);
            opt.setAttribute('data-direction', inv.type === 'sales' ? 'in' : 'out');
            invoiceSelect.appendChild(opt);
        });
    }

    function applyInvoiceSelection(row) {
        var invoiceSelect = row.querySelector('.matched-invoice');
        var opt = invoiceSelect.options[invoiceSelect.selectedIndex];
        var remainingInput = row.querySelector('.matched-remaining');
        var invoiceDateInput = row.querySelector('.matched-invoice-date');
        var amountIn = row.querySelector('.matched-amount-in');
        var amountOut = row.querySelector('.matched-amount-out');

        if (!opt || !opt.value) {
            remainingInput.value = '';
            invoiceDateInput.value = '';
            return;
        }

        var remaining = opt.getAttribute('data-remaining');
        var direction = opt.getAttribute('data-direction');

        remainingInput.value = formatMoney(parseFloat(remaining));
        invoiceDateInput.value = opt.getAttribute('data-date');

        if (direction === 'in') {
            amountIn.value = formatMoney(parseFloat(remaining));
            amountOut.value = '';
        } else {
            amountOut.value = formatMoney(parseFloat(remaining));
            amountIn.value = '';
        }

        recalcBalances();
    }

    function recalcBalances() {
        var running = openingBalance;

        tbody.querySelectorAll('.matched-line-row').forEach(function (row) {
            var amountIn = parseFloat(row.querySelector('.matched-amount-in').value) || 0;
            var amountOut = parseFloat(row.querySelector('.matched-amount-out').value) || 0;
            running = running + amountIn - amountOut;
            row.querySelector('.matched-balance').value = formatMoney(running);
        });
    }

    tbody.addEventListener('change', function (e) {
        var row = e.target.closest('.matched-line-row');
        if (!row) {
            return;
        }
        if (e.target.classList.contains('matched-partner')) {
            populateInvoiceSelect(row);
            applyInvoiceSelection(row);
        } else if (e.target.classList.contains('matched-invoice')) {
            applyInvoiceSelection(row);
        }
    });

    tbody.addEventListener('input', function (e) {
        if (e.target.classList.contains('matched-amount-in') || e.target.classList.contains('matched-amount-out')) {
            recalcBalances();
        }
    });

    tbody.addEventListener('click', function (e) {
        var btn = e.target.closest('.remove-matched-line');
        if (btn) {
            btn.closest('tr').remove();
            recalcBalances();
        }
    });

    document.getElementById('add-matched-line').addEventListener('click', function () {
        var row = template.content.cloneNode(true);
        tbody.appendChild(row);
    });

    recalcBalances();
})();
</script>
