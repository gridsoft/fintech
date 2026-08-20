<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <p class="text-muted small">Овие стапки се законски категории што периодично се менуваат — треба рачно да се
            ажурираат тука кога ќе се променат. Промена тука влијае само на идни извршувања на плата, никогаш на
            веќе извршени периоди (износите се замрзнуваат во соодветниот journal entry во моментот на извршување).</p>

        <form method="post" action="/payroll/settings" class="row g-3">
            <div class="col-md-4">
                <label for="pension_rate" class="form-label">ПИО стапка (%)</label>
                <input type="number" step="0.01" min="0" id="pension_rate" name="pension_rate"
                       class="form-control <?= isset($errors['pension_rate']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($settings->pensionRate) ?>" required>
                <?php if (isset($errors['pension_rate'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['pension_rate']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-4">
                <label for="health_rate" class="form-label">Здравство стапка (%)</label>
                <input type="number" step="0.01" min="0" id="health_rate" name="health_rate"
                       class="form-control <?= isset($errors['health_rate']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($settings->healthRate) ?>" required>
                <?php if (isset($errors['health_rate'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['health_rate']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-4">
                <label for="employment_rate" class="form-label">Вработување стапка (%)</label>
                <input type="number" step="0.01" min="0" id="employment_rate" name="employment_rate"
                       class="form-control <?= isset($errors['employment_rate']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($settings->employmentRate) ?>" required>
                <?php if (isset($errors['employment_rate'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['employment_rate']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-4">
                <label for="pit_rate" class="form-label">Персонален данок (%)</label>
                <input type="number" step="0.01" min="0" id="pit_rate" name="pit_rate"
                       class="form-control <?= isset($errors['pit_rate']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($settings->pitRate) ?>" required>
                <?php if (isset($errors['pit_rate'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['pit_rate']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-4">
                <label for="seniority_rate_per_year" class="form-label">Додаток за стаж (% по година)</label>
                <input type="number" step="0.01" min="0" id="seniority_rate_per_year" name="seniority_rate_per_year"
                       class="form-control <?= isset($errors['seniority_rate_per_year']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($settings->seniorityRatePerYear) ?>" required>
                <?php if (isset($errors['seniority_rate_per_year'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['seniority_rate_per_year']) ?></div><?php endif; ?>
                <div class="form-text">Законски минимум 0,5% од основната плата по година вкупен работен стаж (чл. 106 ЗРО).</div>
            </div>

            <div class="col-md-3">
                <label for="sick_leave_pay_rate" class="form-label">Боледување (% од дневна стапка)</label>
                <input type="number" step="0.01" min="0" max="100" id="sick_leave_pay_rate" name="sick_leave_pay_rate"
                       class="form-control <?= isset($errors['sick_leave_pay_rate']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($settings->sickLeavePayRate) ?>" required>
                <?php if (isset($errors['sick_leave_pay_rate'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['sick_leave_pay_rate']) ?></div><?php endif; ?>
                <div class="form-text">На товар на работодавач, без побарување од ФЗОМ.</div>
            </div>

            <div class="col-md-3">
                <label for="shift_day_rate" class="form-label">Сменска работа (% по ден)</label>
                <input type="number" step="0.01" min="0" id="shift_day_rate" name="shift_day_rate"
                       class="form-control <?= isset($errors['shift_day_rate']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($settings->shiftDayRate) ?>" required>
                <?php if (isset($errors['shift_day_rate'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['shift_day_rate']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-3">
                <label for="holiday_day_rate" class="form-label">Празнична работа (% по ден)</label>
                <input type="number" step="0.01" min="0" id="holiday_day_rate" name="holiday_day_rate"
                       class="form-control <?= isset($errors['holiday_day_rate']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($settings->holidayDayRate) ?>" required>
                <?php if (isset($errors['holiday_day_rate'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['holiday_day_rate']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-3">
                <label for="daily_rate_divisor" class="form-label">Делител за дневна стапка</label>
                <input type="number" step="1" min="1" id="daily_rate_divisor" name="daily_rate_divisor"
                       class="form-control <?= isset($errors['daily_rate_divisor']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars((string) $settings->dailyRateDivisor) ?>" required>
                <?php if (isset($errors['daily_rate_divisor'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['daily_rate_divisor']) ?></div><?php endif; ?>
                <div class="form-text">Дневна стапка = бруто / овој број.</div>
            </div>

            <div class="col-md-8">
                <label for="personal_exemption" class="form-label">Лично ослобување (МКД, месечно)</label>
                <input type="number" step="0.01" min="0" id="personal_exemption" name="personal_exemption"
                       class="form-control <?= isset($errors['personal_exemption']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($settings->personalExemption) ?>" required>
                <?php if (isset($errors['personal_exemption'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['personal_exemption']) ?></div><?php endif; ?>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">Зачувај</button>
                <a href="/payroll" class="btn btn-outline-secondary">Откажи</a>
            </div>
        </form>
    </div>
</div>
