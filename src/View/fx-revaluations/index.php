<p class="text-muted small">
    Превалоризацијата ги усогласува MKD книговодствените вредности на отворените фактури во странска валута со нов курс (на пр. крај на период/година) — не ги менува документите, ниту "преостанатото во валута" на фактурата, само книжи GL-прилагодување наспроти сметките за курсни разлики (7750 добивка / 4750 загуба). За разлика на порамнување при уплата/исплата, види ги фактурите/изводите директно.
</p>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="/fx-revaluations" class="row g-3 align-items-end mb-3">
            <div class="col-md-4">
                <label for="currency_id" class="form-label">Валута</label>
                <select id="currency_id" name="currency_id" class="form-select" onchange="this.form.submit()">
                    <?php foreach ($foreignCurrencies as $currency): ?>
                        <option value="<?= $currency->id ?>" <?= $selectedCurrencyId === $currency->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($currency->code . ' — ' . $currency->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <?php if (empty($foreignCurrencies)): ?>
            <div class="alert alert-warning mb-0">Нема дефинирани странски валути. <a href="/currencies/create">Додади валута</a>.</div>
        <?php elseif (empty($openInvoices)): ?>
            <div class="alert alert-info mb-0">Нема отворени фактури во оваа валута.</div>
        <?php else: ?>
            <div class="table-responsive mb-3">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Тип</th>
                            <th>Фактура</th>
                            <th>Партнер</th>
                            <th class="text-end">Преостанато (валута)</th>
                            <th class="text-end">Книгувано (MKD)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($openInvoices as $row): ?>
                            <?php $partner = $partnersById[$row['partnerId']] ?? null; ?>
                            <tr>
                                <td><?= $row['type'] === 'sales' ? 'Излезна' : 'Влезна' ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($row['label']) ?></td>
                                <td><?= $partner ? htmlspecialchars($partner->name) : '—' ?></td>
                                <td class="text-end"><?= number_format((float) $row['remainingForeign'], 2) ?></td>
                                <td class="text-end"><?= number_format((float) $row['currentBookMkd'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($errors['lines']) && is_string($errors['lines'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($errors['lines']) ?></div>
            <?php endif; ?>

            <form method="post" action="/fx-revaluations" class="row g-3 align-items-end">
                <input type="hidden" name="currency_id" value="<?= $selectedCurrencyId ?>">
                <div class="col-md-3">
                    <label for="date" class="form-label">Датум на превалоризација</label>
                    <input type="date" id="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-3">
                    <label for="new_rate" class="form-label">Нов курс (MKD за 1 единица)</label>
                    <input type="number" step="0.000001" min="0.000001" id="new_rate" name="new_rate" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary" onclick="return confirm('Да се изврши превалоризацијата? Ова автоматски книжи курсна разлика во главната книга.');">
                        Изврши превалоризација
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">Историја на превалоризации</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0 data-table">
            <thead class="table-light">
                <tr>
                    <th>Датум</th>
                    <th>Валута</th>
                    <th class="text-end">Курс</th>
                    <th>Книжење</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($history)): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">Нема извршени превалоризации.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($history as $revaluation): ?>
                    <?php $currency = $currenciesById[$revaluation->currencyId] ?? null; ?>
                    <tr>
                        <td data-order="<?= htmlspecialchars($revaluation->date) ?>"><?= htmlspecialchars(\App\Core\Dates::formatMk($revaluation->date)) ?></td>
                        <td><?= $currency ? htmlspecialchars($currency->code) : '—' ?></td>
                        <td class="text-end"><?= htmlspecialchars($revaluation->newRate) ?></td>
                        <td>
                            <?php if ($revaluation->journalEntryId): ?>
                                <a href="/journal/<?= $revaluation->journalEntryId ?>">Преглед →</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
