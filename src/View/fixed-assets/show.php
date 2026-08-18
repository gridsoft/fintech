<?php $bookValue = bcsub($asset->purchaseValue, $accumulated, 2); ?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h5 class="mb-1"><?= htmlspecialchars($asset->name) ?></h5>
        <div class="text-muted"><?= $account ? htmlspecialchars($account->code . ' — ' . $account->name) : '—' ?></div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge <?= $asset->status === 'active' ? 'text-bg-success-subtle text-success-emphasis' : 'text-bg-secondary-subtle text-secondary-emphasis' ?> fs-6">
            <?= htmlspecialchars($asset->statusLabel()) ?>
        </span>
        <a href="/fixed-assets/<?= $asset->id ?>/edit" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-pencil"></i> Уреди
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Датум на набавка</div>
            <div class="fw-semibold"><?= htmlspecialchars($asset->purchaseDate) ?></div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Набавна вредност</div>
            <div class="fw-semibold"><?= number_format((float) $asset->purchaseValue, 2) ?></div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Стапка на амортизација</div>
            <div class="fw-semibold"><?= number_format((float) $asset->annualRate, 2) ?>% годишно <span class="text-muted small">(<?= number_format((float) $asset->monthlyDepreciation(), 2) ?>/месец)</span></div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Книговодствена вредност</div>
            <div class="fw-semibold"><?= number_format((float) $bookValue, 2) ?> <span class="text-muted small">(амортизирано <?= number_format((float) $accumulated, 2) ?>)</span></div>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="card-header">Амортизациски план</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Период</th>
                    <th class="text-end">Износ</th>
                    <th>Journal</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($entries)): ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted py-4">Сè уште нема извршена амортизација.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($entries as $entry): ?>
                    <tr>
                        <td><?= htmlspecialchars($entry->periodDate) ?></td>
                        <td class="text-end"><?= number_format((float) $entry->amount, 2) ?></td>
                        <td><a href="/journal/<?= $entry->journalEntryId ?>">#<?= $entry->journalEntryId ?></a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
