<div class="d-flex justify-content-end mb-3">
    <a href="/terkovi/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Нов терк
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Назив</th>
                    <th>Опис</th>
                    <th>Ставки</th>
                    <th class="text-end">Акции</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($terkovi)): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">Нема дефинирани теркови.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($terkovi as $terk): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($terk->name) ?></td>
                        <td class="text-muted"><?= htmlspecialchars($terk->description ?? '—') ?></td>
                        <td class="text-muted"><a href="/terkovi/<?= $terk->id ?>/edit"><?= $lineCounts[$terk->id] ?? 0 ?> ставки</a></td>
                        <td class="text-end">
                            <a href="/terkovi/<?= $terk->id ?>/edit" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="/terkovi/<?= $terk->id ?>/delete" method="post" class="d-inline"
                                  onsubmit="return confirm('Да се избрише теркот <?= htmlspecialchars(addslashes($terk->name)) ?>?');">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
