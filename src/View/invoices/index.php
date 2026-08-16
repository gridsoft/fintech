<div class="d-flex justify-content-end mb-3">
    <a href="/invoices/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Нова фактура
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Број</th>
                    <th>Партнер</th>
                    <th>Датум</th>
                    <th>Рок</th>
                    <th>Статус</th>
                    <th class="text-end">Износ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invoices)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Нема внесени фактури.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($invoices as $invoice): ?>
                    <?php $partner = $partnersById[$invoice->partnerId] ?? null; ?>
                    <tr class="cursor-pointer" onclick="location.href='/invoices/<?= $invoice->id ?>'" style="cursor:pointer;">
                        <td class="fw-semibold"><?= htmlspecialchars($invoice->number) ?></td>
                        <td><?= $partner ? htmlspecialchars($partner->name) : '—' ?></td>
                        <td><?= htmlspecialchars($invoice->date) ?></td>
                        <td><?= htmlspecialchars($invoice->dueDate) ?></td>
                        <td>
                            <?php
                            $badgeClass = [
                                'draft' => 'text-bg-secondary-subtle text-secondary-emphasis',
                                'issued' => 'text-bg-primary-subtle text-primary-emphasis',
                                'paid' => 'text-bg-success-subtle text-success-emphasis',
                                'cancelled' => 'text-bg-danger-subtle text-danger-emphasis',
                            ][$invoice->status] ?? 'text-bg-light';
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($invoice->statusLabel()) ?></span>
                        </td>
                        <td class="text-end"><?= number_format((float) $invoice->totalGross, 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
