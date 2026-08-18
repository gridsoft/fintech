<!DOCTYPE html>
<html lang="mk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' · Fintech' : 'Fintech' ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="/assets/css/app.css">
    <script>
        if (localStorage.getItem('sidebar-collapsed') === '1') {
            document.documentElement.classList.add('sidebar-collapsed');
        }
    </script>
</head>
<body>

<header class="topbar d-flex align-items-center">
    <div class="topbar-brand">
        <button class="btn btn-link topbar-icon-btn d-lg-none" id="sidebarToggle" type="button">
            <i class="bi bi-list fs-4"></i>
        </button>
        <a href="/" class="d-flex align-items-center text-decoration-none">
            <span class="topbar-logo" aria-hidden="true">
                <svg width="19" height="19" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6 4H18V8H10V11H16V15H10V20H6V4Z" fill="#ffffff"/>
                </svg>
            </span>
            <span class="fw-semibold topbar-brand-text ms-3">Fintech</span>
        </a>
    </div>
    <div class="ms-auto d-flex align-items-center gap-1 pe-2">
        <button class="btn btn-link topbar-icon-btn" type="button" title="Известувања">
            <i class="bi bi-bell"></i>
        </button>
        <button class="btn btn-link topbar-icon-btn" type="button" title="Поставки">
            <i class="bi bi-gear"></i>
        </button>
        <span class="topbar-avatar ms-2">АД</span>
    </div>
</header>

<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <nav class="sidebar-nav">
            <div class="sidebar-heading">Мени</div>
            <a href="/" class="sidebar-link <?= ($activeNav ?? '') === 'home' ? 'active' : '' ?>" title="Контролна табла">
                <i class="bi bi-grid-1x2-fill"></i> <span>Контролна табла</span>
            </a>
            <a href="/accounts" class="sidebar-link <?= ($activeNav ?? '') === 'accounts' ? 'active' : '' ?>" title="Контен план">
                <i class="bi bi-diagram-3-fill"></i> <span>Контен план</span>
            </a>
            <a href="/journal" class="sidebar-link <?= ($activeNav ?? '') === 'journal' ? 'active' : '' ?>" title="Главна книга">
                <i class="bi bi-journal-text"></i> <span>Главна книга</span>
            </a>
            <a href="/partners" class="sidebar-link <?= ($activeNav ?? '') === 'partners' ? 'active' : '' ?>" title="Партнери">
                <i class="bi bi-people-fill"></i> <span>Партнери</span>
            </a>
            <a href="/invoices" class="sidebar-link <?= ($activeNav ?? '') === 'invoices' ? 'active' : '' ?>" title="Излезни фактури">
                <i class="bi bi-receipt"></i> <span>Излезни фактури</span>
            </a>
            <a href="/purchase-invoices" class="sidebar-link <?= ($activeNav ?? '') === 'purchase-invoices' ? 'active' : '' ?>" title="Влезни фактури">
                <i class="bi bi-receipt-cutoff"></i> <span>Влезни фактури</span>
            </a>
            <a href="/bank-statements" class="sidebar-link <?= ($activeNav ?? '') === 'bank-statements' ? 'active' : '' ?>" title="Изводи">
                <i class="bi bi-bank"></i> <span>Изводи</span>
            </a>
            <a href="/advances" class="sidebar-link <?= ($activeNav ?? '') === 'advances' ? 'active' : '' ?>" title="Аванси">
                <i class="bi bi-cash-coin"></i> <span>Аванси</span>
            </a>
            <a href="/fixed-assets" class="sidebar-link <?= ($activeNav ?? '') === 'fixed-assets' ? 'active' : '' ?>" title="Основни средства">
                <i class="bi bi-truck"></i> <span>Основни средства</span>
            </a>
            <a href="/reports" class="sidebar-link <?= ($activeNav ?? '') === 'reports' ? 'active' : '' ?>" title="Извештаи">
                <i class="bi bi-bar-chart-fill"></i> <span>Извештаи</span>
            </a>

            <div class="sidebar-heading">Каталог</div>
            <a href="/product-categories" class="sidebar-link <?= ($activeNav ?? '') === 'product-categories' ? 'active' : '' ?>" title="Категории производи">
                <i class="bi bi-tags-fill"></i> <span>Категории производи</span>
            </a>
            <a href="/products" class="sidebar-link <?= ($activeNav ?? '') === 'products' ? 'active' : '' ?>" title="Производи">
                <i class="bi bi-box-seam-fill"></i> <span>Производи</span>
            </a>
            <a href="/service-categories" class="sidebar-link <?= ($activeNav ?? '') === 'service-categories' ? 'active' : '' ?>" title="Категории услуги">
                <i class="bi bi-tags"></i> <span>Категории услуги</span>
            </a>
            <a href="/services" class="sidebar-link <?= ($activeNav ?? '') === 'services' ? 'active' : '' ?>" title="Услуги">
                <i class="bi bi-tools"></i> <span>Услуги</span>
            </a>
            <a href="/expense-categories" class="sidebar-link <?= ($activeNav ?? '') === 'expense-categories' ? 'active' : '' ?>" title="Категории трошоци">
                <i class="bi bi-tags"></i> <span>Категории трошоци</span>
            </a>

            <div class="sidebar-heading">Поставки</div>
            <a href="/vat-rates" class="sidebar-link <?= ($activeNav ?? '') === 'vat-rates' ? 'active' : '' ?>" title="ДДВ стапки">
                <i class="bi bi-percent"></i> <span>ДДВ стапки</span>
            </a>
            <a href="/currencies" class="sidebar-link <?= ($activeNav ?? '') === 'currencies' ? 'active' : '' ?>" title="Валути">
                <i class="bi bi-currency-exchange"></i> <span>Валути</span>
            </a>
            <a href="/fx-revaluations" class="sidebar-link <?= ($activeNav ?? '') === 'fx-revaluations' ? 'active' : '' ?>" title="Курсни разлики">
                <i class="bi bi-arrow-left-right"></i> <span>Курсни разлики</span>
            </a>
        </nav>

        <div class="sidebar-footer d-none d-lg-block">
            <button class="sidebar-link sidebar-collapse-toggle w-100" id="sidebarCollapseToggle" type="button" title="Стесни/прошири мени">
                <i class="bi bi-chevron-double-left"></i> <span>Собери мени</span>
            </button>
        </div>
    </aside>

    <main class="content">
        <?php $crumbs = !empty($breadcrumb) ? $breadcrumb : [$pageTitle ?? 'Почетна']; ?>
        <div class="content-header">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <?php foreach ($crumbs as $label => $url): ?>
                        <?php if (is_int($label)): ?>
                            <li class="breadcrumb-item active"><?= htmlspecialchars($url) ?></li>
                        <?php else: ?>
                            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($url) ?>"><?= htmlspecialchars($label) ?></a></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ol>
            </nav>

            <?php if (!empty($headerActions)): ?>
                <div class="content-header-actions"><?= $headerActions ?></div>
            <?php endif; ?>
        </div>

        <?php if (!empty($flash)): ?>
            <div class="alert alert-<?= $flash['type'] ?? 'success' ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?= $content ?>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.js"></script>
<script src="/assets/js/data-table.js"></script>
<script>
    document.getElementById('sidebarToggle')?.addEventListener('click', function () {
        document.getElementById('sidebar').classList.toggle('show');
    });

    document.getElementById('sidebarCollapseToggle')?.addEventListener('click', function () {
        var collapsed = document.documentElement.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebar-collapsed', collapsed ? '1' : '0');
    });
</script>
</body>
</html>
