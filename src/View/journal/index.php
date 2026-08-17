<?php ob_start(); ?>
<a href="/journal/create" class="btn btn-primary">
    <i class="bi bi-plus-lg"></i> Нов запис
</a>
<?php $headerActions = ob_get_clean(); ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 data-table">
            <thead class="table-light">
                <tr>
                    <th>Датум</th>
                    <th>Опис</th>
                    <th>Референца</th>
                    <th class="text-end">Износ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($entries)): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">Нема внесени записи.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($entries as $entry): ?>
                    <tr class="cursor-pointer" onclick="location.href='/journal/<?= $entry->id ?>'" style="cursor:pointer;">
                        <td><?= htmlspecialchars($entry->date) ?></td>
                        <td><?= htmlspecialchars($entry->description) ?></td>
                        <td class="text-muted"><?= htmlspecialchars($entry->reference ?? '—') ?></td>
                        <td class="text-end"><?= number_format((float) $entry->total, 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
