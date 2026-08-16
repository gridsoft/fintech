<div class="d-flex justify-content-end mb-3">
    <a href="/nalozi/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Нов налог
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Назив</th>
                    <th>Статус</th>
                    <th class="text-end">Акции</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($nalozi)): ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted py-4">Нема дефинирани налози.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($nalozi as $nalog): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($nalog->name) ?></td>
                        <td>
                            <?php if ($nalog->isActive): ?>
                                <span class="badge text-bg-success-subtle text-success-emphasis">активен</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary-subtle text-secondary-emphasis">неактивен</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="/nalozi/<?= $nalog->id ?>/edit" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="/nalozi/<?= $nalog->id ?>/delete" method="post" class="d-inline"
                                  onsubmit="return confirm('Да се избрише налогот <?= htmlspecialchars(addslashes($nalog->name)) ?>?');">
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
