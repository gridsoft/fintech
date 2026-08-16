<!DOCTYPE html>
<html lang="mk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Сметководство' ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<header class="topbar d-flex align-items-center">
    <div class="topbar-brand">
        <button class="btn btn-link text-white d-lg-none" id="sidebarToggle" type="button">
            <i class="bi bi-list fs-4"></i>
        </button>
        <a href="/" class="d-flex align-items-center text-white text-decoration-none">
            <i class="bi bi-journal-bookmark-fill me-2"></i>
            <span class="fw-semibold">Сметководство МК</span>
        </a>
    </div>
    <div class="ms-auto d-flex align-items-center text-white-50 pe-3">
        <i class="bi bi-person-circle fs-5 me-2"></i>
        <span class="small">Администратор</span>
    </div>
</header>

<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <nav class="sidebar-nav">
            <div class="sidebar-heading">Мени</div>
            <a href="/" class="sidebar-link <?= ($activeNav ?? '') === 'home' ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2-fill"></i> <span>Контролна табла</span>
            </a>
            <a href="/accounts" class="sidebar-link <?= ($activeNav ?? '') === 'accounts' ? 'active' : '' ?>">
                <i class="bi bi-diagram-3-fill"></i> <span>Контен план</span>
            </a>

            <div class="sidebar-heading">Наскоро</div>
            <span class="sidebar-link disabled"><i class="bi bi-journal-text"></i> <span>Дневник</span></span>
            <span class="sidebar-link disabled"><i class="bi bi-people-fill"></i> <span>Партнери</span></span>
            <span class="sidebar-link disabled"><i class="bi bi-receipt"></i> <span>Фактури</span></span>
            <span class="sidebar-link disabled"><i class="bi bi-bar-chart-fill"></i> <span>Извештаи</span></span>
        </nav>
    </aside>

    <main class="content">
        <div class="content-header">
            <h1><?= $pageTitle ?? 'Почетна' ?></h1>
            <?php if (!empty($breadcrumb)): ?>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <?php foreach ($breadcrumb as $label => $url): ?>
                            <?php if (is_int($label)): ?>
                                <li class="breadcrumb-item active"><?= htmlspecialchars($url) ?></li>
                            <?php else: ?>
                                <li class="breadcrumb-item"><a href="<?= htmlspecialchars($url) ?>"><?= htmlspecialchars($label) ?></a></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ol>
                </nav>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('sidebarToggle')?.addEventListener('click', function () {
        document.getElementById('sidebar').classList.toggle('show');
    });
</script>
</body>
</html>
