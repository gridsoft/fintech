<?php ob_start(); ?>
<a href="/accounts/create" class="btn btn-primary">
    <i class="bi bi-plus-lg"></i> Нова сметка
</a>
<?php $headerActions = ob_get_clean(); ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 data-table">
            <thead class="table-light">
                <tr>
                    <th>Шифра</th>
                    <th>Назив</th>
                    <th>Тип</th>
                    <th>Родител</th>
                    <th>Статус</th>
                    <th class="text-end" data-no-filter>Акции</th>
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
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="/accounts/<?= $account->id ?>/ledger"><i class="bi bi-journal-text me-2"></i>Картица на сметка</a></li>
                                    <li><a class="dropdown-item" href="/accounts/<?= $account->id ?>/edit"><i class="bi bi-pencil me-2"></i>Уреди</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="/accounts/<?= $account->id ?>/delete" method="post"
                                              onsubmit="return confirm('Да се избрише сметката <?= htmlspecialchars(addslashes($account->code . ' — ' . $account->name)) ?>?');">
                                            <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Избриши</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
