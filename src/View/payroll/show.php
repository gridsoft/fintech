<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h5 class="mb-1">Плата за период <?= htmlspecialchars(\App\Core\Dates::formatMk($run->periodDate)) ?></h5>
        <div class="text-muted">Journal <a href="/journal/<?= $run->journalEntryId ?>">#<?= $run->journalEntryId ?></a></div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Бруто</div>
            <div class="fw-semibold"><?= number_format((float) $run->totalGross, 2) ?></div>
        </div></div>
    </div>
    <div class="col-md-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Додаток за стаж</div>
            <div class="fw-semibold"><?= number_format((float) $run->totalSenioritySupplement, 2) ?></div>
        </div></div>
    </div>
    <div class="col-md-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Боледување (одбиток)</div>
            <div class="fw-semibold">-<?= number_format((float) $run->totalSickDeduction, 2) ?></div>
        </div></div>
    </div>
    <div class="col-md-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Смени / Празници</div>
            <div class="fw-semibold"><?= number_format((float) $run->totalShiftSupplement, 2) ?> / <?= number_format((float) $run->totalHolidaySupplement, 2) ?></div>
        </div></div>
    </div>
    <div class="col-md-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Нето</div>
            <div class="fw-semibold"><?= number_format((float) $run->totalNet, 2) ?></div>
        </div></div>
    </div>
    <div class="col-md-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Персонален данок</div>
            <div class="fw-semibold"><?= number_format((float) $run->totalPit, 2) ?></div>
        </div></div>
    </div>
    <div class="col-md-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">ПИО</div>
            <div class="fw-semibold"><?= number_format((float) $run->totalPension, 2) ?></div>
        </div></div>
    </div>
    <div class="col-md-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Здравство</div>
            <div class="fw-semibold"><?= number_format((float) $run->totalHealth, 2) ?></div>
        </div></div>
    </div>
    <div class="col-md-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Вработување</div>
            <div class="fw-semibold"><?= number_format((float) $run->totalEmployment, 2) ?></div>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="card-header">Платни листи</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Вработен</th>
                    <th class="text-end">Основна плата</th>
                    <th>Стаж</th>
                    <th class="text-end">Додаток за стаж</th>
                    <th class="text-end">Дневна стапка</th>
                    <th>Боледување</th>
                    <th>Смени</th>
                    <th>Празници</th>
                    <th class="text-end">Бруто</th>
                    <th class="text-end">ПИО</th>
                    <th class="text-end">Здравство</th>
                    <th class="text-end">Вработување</th>
                    <th class="text-end">Основа за данок</th>
                    <th class="text-end">Персонален данок</th>
                    <th class="text-end">Нето</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payslips)): ?>
                    <tr>
                        <td colspan="15" class="text-center text-muted py-4">Нема платни листи за овој период.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($payslips as $payslip): ?>
                    <?php $employee = $employeesById[$payslip->employeeId] ?? null; ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($employee ? $employee->name : '—') ?></td>
                        <td class="text-end"><?= number_format((float) $payslip->baseSalary, 2) ?></td>
                        <td class="text-muted small"><?= $payslip->seniorityYears() ?> год.</td>
                        <td class="text-end"><?= number_format((float) $payslip->senioritySupplement, 2) ?></td>
                        <td class="text-end"><?= number_format((float) $payslip->dailyRate, 2) ?></td>
                        <td class="text-muted small"><?= $payslip->sickDays ?> д. (-<?= number_format((float) $payslip->sickDeduction, 2) ?>)</td>
                        <td class="text-muted small"><?= $payslip->shiftDays ?> д. (+<?= number_format((float) $payslip->shiftSupplement, 2) ?>)</td>
                        <td class="text-muted small"><?= $payslip->holidayDays ?> д. (+<?= number_format((float) $payslip->holidaySupplement, 2) ?>)</td>
                        <td class="text-end"><?= number_format((float) $payslip->grossSalary, 2) ?></td>
                        <td class="text-end"><?= number_format((float) $payslip->pensionContribution, 2) ?></td>
                        <td class="text-end"><?= number_format((float) $payslip->healthContribution, 2) ?></td>
                        <td class="text-end"><?= number_format((float) $payslip->employmentContribution, 2) ?></td>
                        <td class="text-end"><?= number_format((float) $payslip->taxableBase, 2) ?></td>
                        <td class="text-end"><?= number_format((float) $payslip->pit, 2) ?></td>
                        <td class="text-end fw-semibold"><?= number_format((float) $payslip->netSalary, 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
