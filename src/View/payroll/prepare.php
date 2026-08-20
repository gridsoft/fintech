<p class="text-muted small">
    Пред да се изврши плата за период <strong><?= htmlspecialchars(\App\Core\Dates::formatMk($periodDate)) ?></strong>,
    внеси го бројот денови боледување/сменска работа/празнична работа за секој вработен во овој период (остави 0 ако нема).
    Боледувањето се исплаќа <?= htmlspecialchars($settings->sickLeavePayRate) ?>% од дневната стапка, сменска работа носи
    +<?= htmlspecialchars($settings->shiftDayRate) ?>% по ден, празнична работа +<?= htmlspecialchars($settings->holidayDayRate) ?>% по ден
    (дневна стапка = бруто / <?= (int) $settings->dailyRateDivisor ?>).
</p>

<?php if (isset($errors['days'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errors['days']) ?></div>
<?php endif; ?>

<form method="post" action="/payroll/run">
    <input type="hidden" name="period" value="<?= htmlspecialchars($period) ?>">

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Вработен</th>
                        <th class="text-end">Бруто (пред варијабли)</th>
                        <th style="width: 140px;">Денови боледување</th>
                        <th style="width: 140px;">Денови сменска работа</th>
                        <th style="width: 140px;">Денови празнична работа</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($employees)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Нема вработени опфатени со овој период.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($employees as $employee): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($employee->name) ?></td>
                            <td class="text-end"><?= number_format((float) $employee->baseGrossSalary, 2) ?></td>
                            <td>
                                <input type="number" min="0" max="31" step="1" name="days[<?= $employee->id ?>][sick]" class="form-control form-control-sm" value="0">
                            </td>
                            <td>
                                <input type="number" min="0" max="31" step="1" name="days[<?= $employee->id ?>][shift]" class="form-control form-control-sm" value="0">
                            </td>
                            <td>
                                <input type="number" min="0" max="31" step="1" name="days[<?= $employee->id ?>][holiday]" class="form-control form-control-sm" value="0">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        <button type="submit" class="btn btn-primary" <?= empty($employees) ? 'disabled' : '' ?>>
            <i class="bi bi-calculator"></i> Потврди и изврши плата
        </button>
        <a href="/payroll" class="btn btn-outline-secondary">Откажи</a>
    </div>
</form>
