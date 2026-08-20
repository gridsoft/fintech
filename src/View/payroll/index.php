<?php ob_start(); ?>
<form method="get" action="/payroll/prepare" class="d-flex align-items-center gap-2">
    <input type="month" name="period" class="form-control form-control-sm" value="<?= htmlspecialchars($defaultPeriod) ?>" required>
    <button type="submit" class="btn btn-primary btn-sm">
        <i class="bi bi-calculator"></i> Изврши плата
    </button>
    <a href="/payroll/settings" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-gear"></i> Поставки
    </a>
</form>
<?php $headerActions = ob_get_clean(); ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 data-table">
            <thead class="table-light">
                <tr>
                    <th>Период</th>
                    <th class="text-end">Бруто</th>
                    <th class="text-end">Додаток за стаж</th>
                    <th class="text-end">Боледување</th>
                    <th class="text-end">Смени</th>
                    <th class="text-end">Празници</th>
                    <th class="text-end">Нето</th>
                    <th class="text-end">Персонален данок</th>
                    <th class="text-end">ПИО</th>
                    <th class="text-end">Здравство</th>
                    <th class="text-end">Вработување</th>
                    <th>Journal</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($runs)): ?>
                    <tr>
                        <td colspan="12" class="text-center text-muted py-4">Сè уште нема извршена плата.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($runs as $run): ?>
                    <tr class="cursor-pointer" onclick="location.href='/payroll/<?= $run->id ?>'" style="cursor:pointer;">
                        <td class="fw-semibold" data-order="<?= htmlspecialchars($run->periodDate) ?>"><?= htmlspecialchars(\App\Core\Dates::formatMk($run->periodDate)) ?></td>
                        <td class="text-end"><?= number_format((float) $run->totalGross, 2) ?></td>
                        <td class="text-end"><?= number_format((float) $run->totalSenioritySupplement, 2) ?></td>
                        <td class="text-end">-<?= number_format((float) $run->totalSickDeduction, 2) ?></td>
                        <td class="text-end"><?= number_format((float) $run->totalShiftSupplement, 2) ?></td>
                        <td class="text-end"><?= number_format((float) $run->totalHolidaySupplement, 2) ?></td>
                        <td class="text-end"><?= number_format((float) $run->totalNet, 2) ?></td>
                        <td class="text-end"><?= number_format((float) $run->totalPit, 2) ?></td>
                        <td class="text-end"><?= number_format((float) $run->totalPension, 2) ?></td>
                        <td class="text-end"><?= number_format((float) $run->totalHealth, 2) ?></td>
                        <td class="text-end"><?= number_format((float) $run->totalEmployment, 2) ?></td>
                        <td><a href="/journal/<?= $run->journalEntryId ?>" onclick="event.stopPropagation();">#<?= $run->journalEntryId ?></a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
