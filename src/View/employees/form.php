<?php $action = $employee !== null ? "/employees/{$employee->id}" : '/employees'; ?>

<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <form method="post" action="<?= $action ?>" class="row g-3">
            <div class="col-md-8">
                <label for="name" class="form-label">Име</label>
                <input type="text" id="name" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($employee->name ?? '') ?>" required>
                <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['name']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-4">
                <label for="embg" class="form-label">ЕМБГ <span class="text-muted small">(опционално)</span></label>
                <input type="text" id="embg" name="embg" maxlength="13" class="form-control <?= isset($errors['embg']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($employee->embg ?? '') ?>">
                <?php if (isset($errors['embg'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['embg']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-4">
                <label for="hire_date" class="form-label">Датум на вработување</label>
                <input type="date" id="hire_date" name="hire_date" class="form-control <?= isset($errors['hire_date']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($employee->hireDate ?? '') ?>" required>
                <?php if (isset($errors['hire_date'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['hire_date']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-4">
                <label for="termination_date" class="form-label">Датум на престанок <span class="text-muted small">(опционално)</span></label>
                <input type="date" id="termination_date" name="termination_date" class="form-control <?= isset($errors['termination_date']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($employee->terminationDate ?? '') ?>">
                <?php if (isset($errors['termination_date'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['termination_date']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-4">
                <label for="base_gross_salary" class="form-label">Основна бруто плата</label>
                <input type="number" step="0.01" min="0.01" id="base_gross_salary" name="base_gross_salary"
                       class="form-control <?= isset($errors['base_gross_salary']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($employee->baseGrossSalary ?? '') ?>" required>
                <?php if (isset($errors['base_gross_salary'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['base_gross_salary']) ?></div><?php endif; ?>
                <div class="form-text">Без додатокот за стаж — тој се пресметува автоматски при секое извршување на плата.</div>
            </div>

            <div class="col-md-4">
                <label class="form-label">Признат стаж пред вработување <span class="text-muted small">(опционално)</span></label>
                <div class="input-group">
                    <input type="number" step="1" min="0" id="prior_staz_years" name="prior_staz_years"
                           class="form-control <?= isset($errors['prior_staz_years']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars((string) intdiv($employee->priorStazMonths ?? 0, 12)) ?>">
                    <span class="input-group-text">год.</span>
                    <input type="number" step="1" min="0" max="11" id="prior_staz_months" name="prior_staz_months"
                           class="form-control <?= isset($errors['prior_staz_months']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars((string) (($employee->priorStazMonths ?? 0) % 12)) ?>">
                    <span class="input-group-text">мес.</span>
                </div>
                <?php if (isset($errors['prior_staz_years'])): ?><div class="text-danger small mt-1"><?= htmlspecialchars($errors['prior_staz_years']) ?></div><?php endif; ?>
                <?php if (isset($errors['prior_staz_months'])): ?><div class="text-danger small mt-1"><?= htmlspecialchars($errors['prior_staz_months']) ?></div><?php endif; ?>
                <div class="form-text">Работен стаж кај ПРЕТХОДНИ работодавци, како на денот на вработување тука — стажот кај оваа фирма се додава автоматски. Основа за додатокот на плата за стаж (0,5%/година, чл. 106 ЗРО).</div>
            </div>

            <div class="col-12">
                <div class="form-check">
                    <input type="checkbox" id="is_active" name="is_active" value="1" class="form-check-input"
                           <?= ($employee->isActive ?? true) ? 'checked' : '' ?>>
                    <label for="is_active" class="form-check-label">Активен</label>
                </div>
                <div class="form-text">Само активни вработени се вклучени во извршување на плата.</div>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">Зачувај</button>
                <a href="/employees" class="btn btn-outline-secondary">Откажи</a>
            </div>
        </form>
    </div>
</div>
