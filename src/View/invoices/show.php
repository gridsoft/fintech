<?php
$badgeClass = [
    'draft' => 'text-bg-secondary-subtle text-secondary-emphasis',
    'issued' => 'text-bg-primary-subtle text-primary-emphasis',
    'paid' => 'text-bg-success-subtle text-success-emphasis',
    'cancelled' => 'text-bg-danger-subtle text-danger-emphasis',
][$invoice->status] ?? 'text-bg-light';

$einvoiceBadgeClass = [
    'not_sent' => 'text-bg-light border',
    'sent' => 'text-bg-info-subtle text-info-emphasis',
    'accepted' => 'text-bg-success-subtle text-success-emphasis',
    'rejected' => 'text-bg-danger-subtle text-danger-emphasis',
    'error' => 'text-bg-warning-subtle text-warning-emphasis',
][$invoice->einvoiceStatus] ?? 'text-bg-light';
?>

<div class="d-flex justify-content-between align-items-start mb-3 no-print">
    <div>
        <span class="badge <?= $badgeClass ?> fs-6"><?= htmlspecialchars($invoice->statusLabel()) ?></span>
        <?php if ($invoice->journalEntryId): ?>
            <a href="/journal/<?= $invoice->journalEntryId ?>" class="ms-2 small">Преглед на книжење →</a>
        <?php endif; ?>
        <?php if (in_array($invoice->status, ['issued', 'paid'], true)): ?>
            <span class="badge <?= $einvoiceBadgeClass ?> ms-2">е-фактура: <?= htmlspecialchars($invoice->einvoiceStatusLabel()) ?></span>
            <?php if ($invoice->einvoiceEuid): ?>
                <span class="text-muted small ms-1">euid: <?= htmlspecialchars($invoice->einvoiceEuid) ?></span>
            <?php endif; ?>
            <?php if ($invoice->einvoiceStatus === 'error' && $invoice->einvoiceError): ?>
                <div class="text-danger small mt-1"><?= htmlspecialchars($invoice->einvoiceError) ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <div class="d-flex align-items-start gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
            <i class="bi bi-printer"></i> Печати
        </button>
        <?php if ($invoice->status === 'draft'): ?>
            <form action="/invoices/<?= $invoice->id ?>/issue" method="post" class="d-inline"
                  onsubmit="return confirm('Да се издаде фактурата? Ова автоматски ќе создаде книжење во главната книга.');">
                <button type="submit" class="btn btn-primary btn-sm">Издај фактура</button>
            </form>
            <form action="/invoices/<?= $invoice->id ?>/cancel" method="post" class="d-inline"
                  onsubmit="return confirm('Да се откаже нацртот?');">
                <button type="submit" class="btn btn-outline-danger btn-sm">Откажи</button>
            </form>
        <?php elseif ($invoice->status === 'issued'): ?>
            <form action="/invoices/<?= $invoice->id ?>/mark-paid" method="post" class="d-inline"
                  onsubmit="return confirm('Да се означи фактурата како платена?');">
                <button type="submit" class="btn btn-success btn-sm">Означи како платена</button>
            </form>
        <?php endif; ?>
        <?php if (in_array($invoice->status, ['issued', 'paid'], true) && in_array($invoice->einvoiceStatus, ['not_sent', 'error', 'rejected'], true)): ?>
            <form action="/invoices/<?= $invoice->id ?>/send-einvoice" method="post" class="d-inline"
                  onsubmit="return confirm('Да се прати фактурата како е-фактура до УЈП? Ова не може да се повлече, само сторно/корекција.');">
                <button type="submit" class="btn btn-outline-primary btn-sm">Прати како е-фактура</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="card printable-invoice">
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-6">
                <h5 class="mb-1">Фактура <?= htmlspecialchars($invoice->number) ?></h5>
                <div class="text-muted">Датум: <?= htmlspecialchars($invoice->date) ?></div>
                <div class="text-muted">Рок на плаќање: <?= htmlspecialchars($invoice->dueDate) ?></div>
                <?php if ($currency && !$currency->isBase): ?>
                    <div class="text-muted">Валута: <?= htmlspecialchars($currency->code) ?>, курс <?= htmlspecialchars($invoice->exchangeRate) ?></div>
                <?php endif; ?>
            </div>
            <div class="col-6 text-end">
                <strong>Партнер</strong><br>
                <?= $partner ? htmlspecialchars($partner->name) : '—' ?><?= ($partner && $partner->isForeign()) ? ' <span class="badge text-bg-light border">странски</span>' : '' ?><br>
                <?php if ($partner && $partner->taxNumber): ?>ЕДБ: <?= htmlspecialchars($partner->taxNumber) ?><br><?php endif; ?>
                <?php if ($partner && $partner->addressSummary()): ?><?= htmlspecialchars($partner->addressSummary()) ?><?php endif; ?>
            </div>
        </div>

        <table class="table align-middle">
            <thead class="table-light">
                <tr>
                    <th>Производ / Услуга</th>
                    <th>Забелешка</th>
                    <th class="text-end">Количина</th>
                    <th class="text-end">Ед. цена</th>
                    <th class="text-end">ДДВ %</th>
                    <th class="text-end">Нето</th>
                    <th class="text-end">ДДВ износ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoice->lines as $line): ?>
                    <?php
                        $itemName = '—';
                        if ($line->productId && isset($productsById[$line->productId])) {
                            $itemName = $productsById[$line->productId]->name;
                        } elseif ($line->serviceId && isset($servicesById[$line->serviceId])) {
                            $itemName = $servicesById[$line->serviceId]->name;
                        }
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($itemName) ?></td>
                        <td class="text-muted"><?= htmlspecialchars($line->description ?? '') ?></td>
                        <td class="text-end"><?= number_format((float) $line->quantity, 2) ?></td>
                        <td class="text-end"><?= number_format((float) $line->unitPrice, 2) ?></td>
                        <td class="text-end"><?= number_format((float) $line->vatRate, 0) ?>%</td>
                        <td class="text-end"><?= number_format((float) $line->lineTotal, 2) ?></td>
                        <td class="text-end"><?= number_format($line->vatAmount(), 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="text-end fw-semibold">Вкупно нето:</td>
                    <td class="text-end fw-semibold" colspan="2"><?= number_format((float) $invoice->totalNet, 2) ?></td>
                </tr>
                <tr>
                    <td colspan="5" class="text-end fw-semibold">Вкупно ДДВ:</td>
                    <td class="text-end fw-semibold" colspan="2"><?= number_format((float) $invoice->totalVat, 2) ?></td>
                </tr>
                <tr class="table-light">
                    <td colspan="5" class="text-end fw-bold">За плаќање:</td>
                    <td class="text-end fw-bold" colspan="2"><?= number_format((float) $invoice->totalGross, 2) ?><?= ($currency && !$currency->isBase) ? ' ' . htmlspecialchars($currency->code) : '' ?></td>
                </tr>
                <?php if ($currency && !$currency->isBase): ?>
                    <tr>
                        <td colspan="5" class="text-end text-muted small">Во денари (по курс <?= htmlspecialchars($invoice->exchangeRate) ?>):</td>
                        <td class="text-end text-muted small" colspan="2"><?= number_format((float) $invoice->grossInBaseCurrency(), 2) ?> MKD</td>
                    </tr>
                <?php endif; ?>
            </tfoot>
        </table>
    </div>
</div>
