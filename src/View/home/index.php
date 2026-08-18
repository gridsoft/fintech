<?php
$fmt = fn ($v) => number_format((float) $v, 2, '.', ',');
?>

<div class="row g-3 mb-3">
    <div class="col-sm-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="card-title text-muted fs-6 mb-2">Салдо на парични средства</h2>
                <p class="fs-3 fw-semibold mb-0"><?= $fmt($cashBalance) ?> <span class="fs-6 text-muted fw-normal">MKD</span></p>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="card-title text-muted fs-6 mb-2">Отворени побарувања</h2>
                <p class="fs-3 fw-semibold mb-0"><?= $fmt($openReceivables) ?> <span class="fs-6 text-muted fw-normal">MKD</span></p>
                <a href="/reports/open-items" class="small">Преглед →</a>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="card-title text-muted fs-6 mb-2">Отворени обврски</h2>
                <p class="fs-3 fw-semibold mb-0"><?= $fmt($openPayables) ?> <span class="fs-6 text-muted fw-normal">MKD</span></p>
                <a href="/reports/open-items" class="small">Преглед →</a>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="card-title text-muted fs-6 mb-2">Приход овој месец</h2>
                <p class="fs-3 fw-semibold mb-0"><?= $fmt($revenueThisMonth) ?> <span class="fs-6 text-muted fw-normal">MKD</span></p>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="card-title fs-6 mb-3">Приход и трошок по месец</h2>
                <canvas id="trendChart" height="240" role="img" aria-label="Приход и трошок по месец, последните 6 месеци"></canvas>
                <details class="mt-3 small">
                    <summary class="text-muted">Прикажи како табела</summary>
                    <div class="table-responsive mt-2">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr><th>Месец</th><th class="text-end">Приход</th><th class="text-end">Трошок</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($trend['labels'] as $i => $label): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($label) ?></td>
                                        <td class="text-end"><?= $fmt($trend['revenue'][$i]) ?></td>
                                        <td class="text-end"><?= $fmt($trend['expense'][$i]) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </details>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="card-title fs-6 mb-3">Остарување на побарувањата</h2>
                <?php if (array_sum(array_map('floatval', $arAging['values'])) <= 0): ?>
                    <p class="text-muted small mb-0">Нема отворени побарувања.</p>
                <?php else: ?>
                    <canvas id="agingChart" height="240" role="img" aria-label="Отворени побарувања по старост"></canvas>
                    <details class="mt-3 small">
                        <summary class="text-muted">Прикажи како табела</summary>
                        <div class="table-responsive mt-2">
                            <table class="table table-sm mb-0">
                                <tbody>
                                    <?php foreach ($arAging['labels'] as $i => $label): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($label) ?></td>
                                            <td class="text-end"><?= $fmt($arAging['values'][$i]) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </details>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
    var ink = { primary: '#0b0b0b', secondary: '#52514e', muted: '#898781', grid: '#e1e0d9' };

    Chart.defaults.font.family = 'system-ui, -apple-system, "Segoe UI", sans-serif';
    Chart.defaults.color = ink.muted;

    var trendCtx = document.getElementById('trendChart');
    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($trend['labels'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
                datasets: [
                    {
                        label: 'Приход',
                        data: <?= json_encode($trend['revenue']) ?>,
                        backgroundColor: '#2a78d6',
                        borderRadius: 4,
                        borderSkipped: false,
                        maxBarThickness: 24,
                    },
                    {
                        label: 'Трошок',
                        data: <?= json_encode($trend['expense']) ?>,
                        backgroundColor: '#eb6834',
                        borderRadius: 4,
                        borderSkipped: false,
                        maxBarThickness: 24,
                    },
                ],
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top', align: 'end', labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 8, color: ink.secondary, font: { size: 12 } } },
                    tooltip: {
                        backgroundColor: '#0b0b0b',
                        padding: 10,
                        callbacks: {
                            label: function (item) {
                                return item.dataset.label + ': ' + Number(item.parsed.y).toLocaleString('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' MKD';
                            },
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: ink.grid, drawTicks: false },
                        border: { display: false },
                        ticks: { color: ink.muted, callback: function (v) { return Number(v).toLocaleString('mk-MK'); } },
                    },
                    x: {
                        grid: { display: false },
                        border: { color: ink.grid },
                        ticks: { color: ink.muted },
                    },
                },
            },
        });
    }

    var agingCtx = document.getElementById('agingChart');
    if (agingCtx) {
        new Chart(agingCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($arAging['labels'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
                datasets: [{
                    data: <?= json_encode($arAging['values']) ?>,
                    backgroundColor: ['#86b6ef', '#5598e7', '#2a78d6', '#1c5cab'],
                    borderRadius: 4,
                    borderSkipped: false,
                    maxBarThickness: 24,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0b0b0b',
                        padding: 10,
                        callbacks: {
                            label: function (item) {
                                return Number(item.parsed.x).toLocaleString('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' MKD';
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: ink.grid, drawTicks: false },
                        border: { display: false },
                        ticks: { color: ink.muted, callback: function (v) { return Number(v).toLocaleString('mk-MK'); } },
                    },
                    y: {
                        grid: { display: false },
                        border: { color: ink.grid },
                        ticks: { color: ink.primary },
                    },
                },
            },
        });
    }
})();
</script>
