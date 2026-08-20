<?php ob_start(); ?>
<a href="/employees/create" class="btn btn-primary">
    <i class="bi bi-plus-lg"></i> Нов вработен
</a>
<?php $headerActions = ob_get_clean(); ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 data-table">
            <thead class="table-light">
                <tr>
                    <th>Име</th>
                    <th>ЕМБГ</th>
                    <th>Датум на вработување</th>
                    <th>Датум на престанок</th>
                    <th class="text-end">Основна бруто плата</th>
                    <th>Признат стаж</th>
                    <th>Статус</th>
                    <th class="text-end" data-no-filter>Акции</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($employees)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Нема внесени вработени.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($employees as $employee): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($employee->name) ?></td>
                        <td class="text-muted"><?= htmlspecialchars($employee->embg ?? '—') ?></td>
                        <td><?= htmlspecialchars(\App\Core\Dates::formatMk($employee->hireDate)) ?></td>
                        <td><?= htmlspecialchars(\App\Core\Dates::formatMk($employee->terminationDate)) ?></td>
                        <td class="text-end"><?= number_format((float) $employee->baseGrossSalary, 2) ?></td>
                        <td class="text-muted"><?= intdiv($employee->priorStazMonths, 12) ?> год. <?= $employee->priorStazMonths % 12 ?> мес.</td>
                        <td>
                            <?php if ($employee->isActive): ?>
                                <span class="badge text-bg-success-subtle text-success-emphasis">активен</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary-subtle text-secondary-emphasis">неактивен</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end" data-no-filter>
                            <a href="/employees/<?= $employee->id ?>/edit" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil"></i> Уреди
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
