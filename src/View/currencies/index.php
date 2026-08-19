<?php ob_start(); ?>
<a href="/currencies/create" class="btn btn-primary">
    <i class="bi bi-plus-lg"></i> Нова валута
</a>
<?php $headerActions = ob_get_clean(); ?>

<p class="text-muted small">MKD е базната валута на системот (главната книга секогаш е во денари) и не се уредува. Странските валути овде се тие во кои смее да се издава/прима фактура — курсот се внесува по документ, со предполнет стандарден курс (ако е поставен) за да не се впишува секој пат рачно.</p>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 data-table">
            <thead class="table-light">
                <tr>
                    <th>Код</th>
                    <th>Назив</th>
                    <th>Тип</th>
                    <th>Стандарден курс</th>
                    <th>Статус</th>
                    <th class="text-end" data-no-filter>Акции</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($currencies as $currency): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($currency->code) ?></td>
                        <td><?= htmlspecialchars($currency->name) ?></td>
                        <td>
                            <?php if ($currency->isBase): ?>
                                <span class="badge text-bg-primary-subtle text-primary-emphasis">базна</span>
                            <?php else: ?>
                                <span class="badge text-bg-light border">странска</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted"><?= $currency->defaultExchangeRate !== null ? number_format((float) $currency->defaultExchangeRate, 6) : '—' ?></td>
                        <td>
                            <?php if ($currency->isActive): ?>
                                <span class="badge text-bg-success-subtle text-success-emphasis">активна</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary-subtle text-secondary-emphasis">неактивна</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end" data-no-filter>
                            <?php if (!$currency->isBase): ?>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                                        <i class="bi bi-three-dots"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="/currencies/<?= $currency->id ?>/edit"><i class="bi bi-pencil me-2"></i>Уреди</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="/currencies/<?= $currency->id ?>/delete" method="post"
                                                  onsubmit="return confirm('Да се избрише валутата <?= htmlspecialchars(addslashes($currency->code)) ?>?');">
                                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Избриши</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
