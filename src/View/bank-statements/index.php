<?php ob_start(); ?>
<a href="/bank-statements/create" class="btn btn-primary">
    <i class="bi bi-plus-lg"></i> Нов извод
</a>
<?php $headerActions = ob_get_clean(); ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 data-table">
            <thead class="table-light">
                <tr>
                    <th>Датум</th>
                    <th>Сметка</th>
                    <th>Референца</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($statements)): ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted py-4">Нема внесени изводи.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($statements as $statement): ?>
                    <?php $account = $accountsById[$statement->accountId] ?? null; ?>
                    <tr class="cursor-pointer" onclick="location.href='/bank-statements/<?= $statement->id ?>'" style="cursor:pointer;">
                        <td class="fw-semibold" data-order="<?= htmlspecialchars($statement->date) ?>"><?= htmlspecialchars(\App\Core\Dates::formatMk($statement->date)) ?></td>
                        <td><?= $account ? htmlspecialchars($account->code . ' — ' . $account->name) : '—' ?></td>
                        <td class="text-muted"><?= htmlspecialchars($statement->reference ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
