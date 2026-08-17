<?php $action = $partner !== null ? "/partners/{$partner->id}" : '/partners'; ?>

<div class="card mb-4">
    <div class="card-body">
        <form method="post" action="<?= $action ?>" class="row g-3">
            <div class="col-md-6">
                <label for="name" class="form-label">Назив</label>
                <input type="text" id="name" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($partner->name ?? '') ?>" required>
                <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['name']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-3">
                <label for="type" class="form-label">Тип</label>
                <select id="type" name="type" class="form-select <?= isset($errors['type']) ? 'is-invalid' : '' ?>">
                    <?php foreach (\App\Domain\Partners\Partner::TYPE_LABELS as $value => $label): ?>
                        <option value="<?= $value ?>" <?= ($partner->type ?? 'customer') === $value ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['type'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['type']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-3 d-flex align-items-end">
                <div class="form-check">
                    <input type="checkbox" id="is_active" name="is_active" value="1" class="form-check-input"
                           <?= ($partner->isActive ?? true) ? 'checked' : '' ?>>
                    <label for="is_active" class="form-check-label">Активен</label>
                </div>
            </div>

            <div class="col-md-4">
                <label for="tax_number" class="form-label">ЕДБ</label>
                <input type="text" id="tax_number" name="tax_number" class="form-control"
                       value="<?= htmlspecialchars($partner->taxNumber ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label for="vat_number" class="form-label">ДДВ број</label>
                <input type="text" id="vat_number" name="vat_number" class="form-control"
                       value="<?= htmlspecialchars($partner->vatNumber ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label for="country" class="form-label">Земја</label>
                <input type="text" id="country" name="country" class="form-control" maxlength="2" style="text-transform: uppercase;"
                       value="<?= htmlspecialchars($partner->country ?? 'MK') ?>">
                <div class="form-text">ISO код (MK = домашен)</div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-lg-6">
                    <div class="card bg-body-tertiary h-100">
                        <div class="card-body">
                            <h6 class="mb-3"><i class="bi bi-geo-alt me-1"></i> Адреса</h6>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="address_line1" class="form-label">Адреса — улица 1</label>
                                    <input type="text" id="address_line1" name="address_line1" class="form-control"
                                           value="<?= htmlspecialchars($partner->addressLine1 ?? '') ?>">
                                </div>
                                <div class="col-12">
                                    <label for="address_line2" class="form-label">Адреса — улица 2</label>
                                    <input type="text" id="address_line2" name="address_line2" class="form-control"
                                           value="<?= htmlspecialchars($partner->addressLine2 ?? '') ?>">
                                </div>
                                <div class="col-md-5">
                                    <label for="postal_code" class="form-label">Поштенски код</label>
                                    <input type="text" id="postal_code" name="postal_code" class="form-control"
                                           value="<?= htmlspecialchars($partner->postalCode ?? '') ?>">
                                </div>
                                <div class="col-md-7">
                                    <label for="city" class="form-label">Град</label>
                                    <input type="text" id="city" name="city" class="form-control"
                                           value="<?= htmlspecialchars($partner->city ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card bg-body-tertiary h-100">
                        <div class="card-body">
                            <h6 class="mb-3"><i class="bi bi-telephone me-1"></i> Контакт информации</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Телефонски број</label>
                                    <input type="text" id="phone" name="phone" class="form-control"
                                           value="<?= htmlspecialchars($partner->phone ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="fax" class="form-label">Факс</label>
                                    <input type="text" id="fax" name="fax" class="form-control"
                                           value="<?= htmlspecialchars($partner->fax ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="mobile" class="form-label">Мобилен</label>
                                    <input type="text" id="mobile" name="mobile" class="form-control"
                                           value="<?= htmlspecialchars($partner->mobile ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" id="email" name="email" class="form-control"
                                           value="<?= htmlspecialchars($partner->email ?? '') ?>">
                                </div>
                                <div class="col-12">
                                    <label for="website" class="form-label">Web</label>
                                    <input type="text" id="website" name="website" class="form-control"
                                           value="<?= htmlspecialchars($partner->website ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card bg-body-tertiary">
                        <div class="card-body">
                            <h6 class="mb-3"><i class="bi bi-info-circle me-1"></i> Останати информации</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="bank_account" class="form-label">Сметка</label>
                                    <input type="text" id="bank_account" name="bank_account" class="form-control"
                                           value="<?= htmlspecialchars($partner->bankAccount ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="iban" class="form-label">IBAN</label>
                                    <input type="text" id="iban" name="iban" class="form-control"
                                           value="<?= htmlspecialchars($partner->iban ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="swift" class="form-label">Swift</label>
                                    <input type="text" id="swift" name="swift" class="form-control"
                                           value="<?= htmlspecialchars($partner->swift ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="timocom_id" class="form-label">Тимоком ID</label>
                                    <input type="text" id="timocom_id" name="timocom_id" class="form-control"
                                           value="<?= htmlspecialchars($partner->timocomId ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($partner === null): ?>
                <div class="row g-3 mt-1">
                    <div class="col-lg-6">
                        <div class="card bg-body-tertiary h-100">
                            <div class="card-body">
                                <h6 class="mb-3"><i class="bi bi-people me-1"></i> Вработени</h6>
                                <div class="table-responsive">
                                    <table class="table align-middle mb-2" id="employees-table">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Име и презиме</th>
                                                <th>Позиција</th>
                                                <th>Телефон</th>
                                                <th>Email</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($oldEmployees as $employee): ?>
                                                <tr class="dyn-row">
                                                    <td><input type="text" name="employee_name[]" class="form-control form-control-sm" value="<?= htmlspecialchars($employee['name'] ?? '') ?>"></td>
                                                    <td><input type="text" name="employee_job_title[]" class="form-control form-control-sm" value="<?= htmlspecialchars($employee['job_title'] ?? '') ?>"></td>
                                                    <td><input type="text" name="employee_phone[]" class="form-control form-control-sm" value="<?= htmlspecialchars($employee['phone'] ?? '') ?>"></td>
                                                    <td><input type="email" name="employee_email[]" class="form-control form-control-sm" value="<?= htmlspecialchars($employee['email'] ?? '') ?>"></td>
                                                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-dyn-row"><i class="bi bi-x-lg"></i></button></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="add-employee-row">
                                    <i class="bi bi-plus-lg"></i> Додади вработен
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card bg-body-tertiary h-100">
                            <div class="card-body">
                                <h6 class="mb-1"><i class="bi bi-plus-square-dotted me-1"></i> Дополнителни полиња</h6>
                                <p class="small text-muted">За полиња што не се дел од стандардниот образец погоре.</p>
                                <div class="table-responsive">
                                    <table class="table align-middle mb-2" id="custom-fields-table">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Назив на поле</th>
                                                <th>Вредност</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($oldCustomFields as $field): ?>
                                                <tr class="dyn-row">
                                                    <td><input type="text" name="custom_field_key[]" class="form-control form-control-sm" value="<?= htmlspecialchars($field['key'] ?? '') ?>"></td>
                                                    <td><input type="text" name="custom_field_value[]" class="form-control form-control-sm" value="<?= htmlspecialchars($field['value'] ?? '') ?>"></td>
                                                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-dyn-row"><i class="bi bi-x-lg"></i></button></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="add-custom-field-row">
                                    <i class="bi bi-plus-lg"></i> Додади поле
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <template id="employee-row-template">
                    <tr class="dyn-row">
                        <td><input type="text" name="employee_name[]" class="form-control form-control-sm"></td>
                        <td><input type="text" name="employee_job_title[]" class="form-control form-control-sm"></td>
                        <td><input type="text" name="employee_phone[]" class="form-control form-control-sm"></td>
                        <td><input type="email" name="employee_email[]" class="form-control form-control-sm"></td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger remove-dyn-row"><i class="bi bi-x-lg"></i></button></td>
                    </tr>
                </template>

                <template id="custom-field-row-template">
                    <tr class="dyn-row">
                        <td><input type="text" name="custom_field_key[]" class="form-control form-control-sm"></td>
                        <td><input type="text" name="custom_field_value[]" class="form-control form-control-sm"></td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger remove-dyn-row"><i class="bi bi-x-lg"></i></button></td>
                    </tr>
                </template>

                <script>
                (function () {
                    function wireDynamicTable(addButtonId, templateId, tableId) {
                        var addButton = document.getElementById(addButtonId);
                        var template = document.getElementById(templateId);
                        var tbody = document.querySelector('#' + tableId + ' tbody');

                        if (!addButton || !template || !tbody) {
                            return;
                        }

                        addButton.addEventListener('click', function () {
                            tbody.appendChild(template.content.cloneNode(true));
                        });

                        tbody.addEventListener('click', function (e) {
                            var btn = e.target.closest('.remove-dyn-row');
                            if (btn) {
                                btn.closest('tr').remove();
                            }
                        });
                    }

                    wireDynamicTable('add-employee-row', 'employee-row-template', 'employees-table');
                    wireDynamicTable('add-custom-field-row', 'custom-field-row-template', 'custom-fields-table');
                })();
                </script>
            <?php endif; ?>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">Зачувај</button>
                <a href="/partners" class="btn btn-outline-secondary">Откажи</a>
            </div>
        </form>
    </div>
</div>

<?php if ($partner !== null): ?>
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="mb-3"><i class="bi bi-people me-1"></i> Вработени</h6>

                    <?php if (empty($employees)): ?>
                        <p class="text-muted small">Нема внесени вработени.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush mb-3">
                            <?php foreach ($employees as $employee): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-start px-0">
                                    <div>
                                        <div class="fw-semibold"><?= htmlspecialchars($employee->name) ?></div>
                                        <div class="small text-muted">
                                            <?= htmlspecialchars($employee->jobTitle ?? '') ?>
                                            <?= $employee->phone ? ' · ' . htmlspecialchars($employee->phone) : '' ?>
                                            <?= $employee->email ? ' · ' . htmlspecialchars($employee->email) : '' ?>
                                        </div>
                                    </div>
                                    <form action="/partners/<?= $partner->id ?>/employees/<?= $employee->id ?>/delete" method="post"
                                          onsubmit="return confirm('Да се избрише вработениот <?= htmlspecialchars(addslashes($employee->name)) ?>?');">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <form method="post" action="/partners/<?= $partner->id ?>/employees" class="row g-2">
                        <div class="col-md-6">
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="Име и презиме" required>
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="job_title" class="form-control form-control-sm" placeholder="Позиција">
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="phone" class="form-control form-control-sm" placeholder="Телефон">
                        </div>
                        <div class="col-md-6">
                            <input type="email" name="email" class="form-control form-control-sm" placeholder="Email">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg"></i> Додади вработен</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="mb-1"><i class="bi bi-plus-square-dotted me-1"></i> Дополнителни полиња</h6>
                    <p class="small text-muted">За полиња што не се дел од стандардниот образец погоре.</p>

                    <?php if (empty($customFields)): ?>
                        <p class="text-muted small">Нема додадени дополнителни полиња.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush mb-3">
                            <?php foreach ($customFields as $field): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <span class="fw-semibold"><?= htmlspecialchars($field->key) ?>:</span>
                                        <span class="text-muted"><?= htmlspecialchars($field->value ?? '') ?></span>
                                    </div>
                                    <form action="/partners/<?= $partner->id ?>/custom-fields/<?= $field->id ?>/delete" method="post"
                                          onsubmit="return confirm('Да се избрише полето „<?= htmlspecialchars(addslashes($field->key)) ?>“?');">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <form method="post" action="/partners/<?= $partner->id ?>/custom-fields" class="row g-2">
                        <div class="col-md-5">
                            <input type="text" name="field_key" class="form-control form-control-sm" placeholder="Назив на поле" required>
                        </div>
                        <div class="col-md-5">
                            <input type="text" name="field_value" class="form-control form-control-sm" placeholder="Вредност">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm btn-outline-primary w-100"><i class="bi bi-plus-lg"></i></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
