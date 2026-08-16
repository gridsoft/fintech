<div class="d-flex justify-content-end mb-3">
    <a href="/partners/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Нов партнер
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Назив</th>
                    <th>Тип</th>
                    <th>ЕДБ</th>
                    <th>Контакт</th>
                    <th>Статус</th>
                    <th class="text-end">Акции</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($partners)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Нема внесени партнери.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($partners as $partner): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($partner->name) ?></td>
                        <td><span class="badge text-bg-light border"><?= htmlspecialchars($partner->typeLabel()) ?></span></td>
                        <td class="text-muted"><?= htmlspecialchars($partner->taxNumber ?? '—') ?></td>
                        <td class="text-muted"><?= htmlspecialchars($partner->contact ?? '—') ?></td>
                        <td>
                            <?php if ($partner->isActive): ?>
                                <span class="badge text-bg-success-subtle text-success-emphasis">активен</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary-subtle text-secondary-emphasis">неактивен</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="/partners/<?= $partner->id ?>/statement" class="btn btn-sm btn-outline-secondary" title="Картица на партнер">
                                <i class="bi bi-journal-text"></i>
                            </a>
                            <a href="/partners/<?= $partner->id ?>/edit" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="/partners/<?= $partner->id ?>/delete" method="post" class="d-inline"
                                  onsubmit="return confirm('Да се избрише партнерот <?= htmlspecialchars(addslashes($partner->name)) ?>?');">
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
