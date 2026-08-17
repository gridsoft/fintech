<?php ob_start(); ?>
<a href="/partners/create" class="btn btn-primary">
    <i class="bi bi-plus-lg"></i> Нов партнер
</a>
<?php $headerActions = ob_get_clean(); ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 data-table">
            <thead class="table-light">
                <tr>
                    <th>Назив</th>
                    <th>Тип</th>
                    <th>ЕДБ</th>
                    <th>Град</th>
                    <th>Контакт</th>
                    <th>Статус</th>
                    <th class="text-end" data-no-filter>Акции</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($partners)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Нема внесени партнери.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($partners as $partner): ?>
                    <?php $contact = $partner->phone ?? $partner->mobile ?? $partner->email ?? null; ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($partner->name) ?></td>
                        <td><span class="badge text-bg-light border"><?= htmlspecialchars($partner->typeLabel()) ?></span></td>
                        <td class="text-muted"><?= htmlspecialchars($partner->taxNumber ?? '—') ?></td>
                        <td class="text-muted"><?= htmlspecialchars($partner->city ?? '—') ?></td>
                        <td class="text-muted"><?= htmlspecialchars($contact ?? '—') ?></td>
                        <td>
                            <?php if ($partner->isActive): ?>
                                <span class="badge text-bg-success-subtle text-success-emphasis">активен</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary-subtle text-secondary-emphasis">неактивен</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="/partners/<?= $partner->id ?>/statement"><i class="bi bi-journal-text me-2"></i>Картица на партнер</a></li>
                                    <li><a class="dropdown-item" href="/partners/<?= $partner->id ?>/edit"><i class="bi bi-pencil me-2"></i>Уреди</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="/partners/<?= $partner->id ?>/delete" method="post"
                                              onsubmit="return confirm('Да се избрише партнерот <?= htmlspecialchars(addslashes($partner->name)) ?>?');">
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
