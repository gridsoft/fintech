<div class="d-flex justify-content-end mb-3">
    <a href="/accounts/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Нова сметка
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Шифра</th>
                    <th>Назив</th>
                    <th>Тип</th>
                    <th>Родител</th>
                    <th>Статус</th>
                    <th class="text-end">Акции</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($accounts)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Нема внесени сметки.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($accounts as $account): ?>
                    <?php $parent = $account->parentId ? ($accountsById[$account->parentId] ?? null) : null; ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($account->code) ?></td>
                        <td><?= htmlspecialchars($account->name) ?></td>
                        <td><span class="badge text-bg-light border"><?= htmlspecialchars($account->typeLabel()) ?></span></td>
                        <td class="text-muted"><?= $parent ? htmlspecialchars($parent->code . ' — ' . $parent->name) : '—' ?></td>
                        <td>
                            <?php if ($account->isActive): ?>
                                <span class="badge text-bg-success-subtle text-success-emphasis">активна</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary-subtle text-secondary-emphasis">неактивна</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="/accounts/<?= $account->id ?>/ledger" class="btn btn-sm btn-outline-secondary" title="Картица на сметка">
                                <i class="bi bi-journal-text"></i>
                            </a>
                            <a href="/accounts/<?= $account->id ?>/edit" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="/accounts/<?= $account->id ?>/delete" method="post" class="d-inline"
                                  onsubmit="return confirm('Да се избрише сметката <?= htmlspecialchars(addslashes($account->code . ' — ' . $account->name)) ?>?');">
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
