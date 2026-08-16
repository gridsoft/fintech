<div class="mb-3">
    <a href="/reports" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Назад кон извештаи</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="get" action="/reports/vat" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="from" class="form-label">Од датум</label>
                <input type="date" id="from" name="from" class="form-control" value="<?= htmlspecialchars($report['from']) ?>">
            </div>
            <div class="col-md-3">
                <label for="to" class="form-label">До датум</label>
                <input type="date" id="to" name="to" class="form-control" value="<?= htmlspecialchars($report['to']) ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">Прикажи</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted">Излезен ДДВ (од фактури)</h6>
                <p class="fs-4 fw-bold mb-0"><?= number_format((float) $report['outputVat'], 2) ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted">Влезен ДДВ</h6>
                <p class="fs-4 fw-bold mb-0"><?= number_format((float) $report['inputVat'], 2) ?></p>
                <p class="small text-muted mb-0">Модул за влезни фактури/трошоци сè уште не постои (идна фаза) — секогаш 0.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted">За уплата кон УЈП</h6>
                <p class="fs-4 fw-bold mb-0"><?= number_format((float) $report['netPayable'], 2) ?></p>
            </div>
        </div>
    </div>
</div>
