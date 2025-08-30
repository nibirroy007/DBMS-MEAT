<?php
// consumption.php
session_start();
require 'config.php';
include 'header.php';

/** -------------------------------
 *  CSRF
 * --------------------------------*/
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
$CSRF = $_SESSION['csrf_token'];
function redirect_with_msg($msg)
{
    header('Location: consumption.php?msg=' . urlencode($msg));
    exit();
}

/** -------------------------------
 *  Handle deletion (GET + CSRF)
 * --------------------------------*/
if (isset($_GET['delete'])) {
    if (!hash_equals($CSRF, $_GET['csrf'] ?? '')) redirect_with_msg('Invalid request token.');
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare('DELETE FROM insights WHERE insight_id = ?');
    $stmt->execute([$id]);
    redirect_with_msg('Insight deleted.');
}

/** -------------------------------
 *  Handle insert/update (POST + CSRF)
 * --------------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($CSRF, $_POST['csrf'] ?? '')) redirect_with_msg('Invalid request token.');
    $id          = isset($_POST['insight_id']) ? (int)$_POST['insight_id'] : 0;
    $perCapita   = $_POST['per_capita_meat_consumption'] !== '' ? (float)$_POST['per_capita_meat_consumption'] : null;
    $region      = trim($_POST['region'] ?? '');
    $demographics = trim($_POST['demographics'] ?? '');
    $nutritional = trim($_POST['nutritional_intake'] ?? '');

    $errors = [];
    if ($perCapita !== null && $perCapita < 0) $errors[] = 'Per capita meat consumption (kg) must be ≥ 0.';
    if ($region === '') $errors[] = 'Region is required.';

    if ($errors) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data']   = $_POST;
        header('Location: consumption.php?showModal=1' . ($id ? ('&edit=' . $id) : ''));
        exit();
    }

    if ($id > 0) {
        $sql = 'UPDATE insights SET per_capita_meat_consumption=?, region=?, demographics=?, nutritional_intake=? WHERE insight_id=?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$perCapita, $region, $demographics, $nutritional, $id]);
        redirect_with_msg('Insight updated.');
    } else {
        $sql = 'INSERT INTO insights (per_capita_meat_consumption, region, demographics, nutritional_intake) VALUES (?,?,?,?)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$perCapita, $region, $demographics, $nutritional]);
        redirect_with_msg('Insight added.');
    }
}

/** -------------------------------
 *  Edit record
 * --------------------------------*/
$editRecord = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM insights WHERE insight_id=?');
    $stmt->execute([$id]);
    $editRecord = $stmt->fetch(PDO::FETCH_ASSOC);
}

/** -------------------------------
 *  Fetch all records
 * --------------------------------*/
$insights = $pdo->query('SELECT * FROM insights ORDER BY insight_id DESC')->fetchAll(PDO::FETCH_ASSOC);

/** -------------------------------
 *  Analytics prep
 * --------------------------------*/
$values = [];
$regions = [];
$demoWords = [];
$nutriWords = [];

foreach ($insights as $r) {
    if ($r['per_capita_meat_consumption'] !== null) $values[] = (float)$r['per_capita_meat_consumption'];
    if (!empty($r['region'])) $regions[$r['region']] = ($regions[$r['region']] ?? 0) + 1;

    // quick keyword breakdown (English/Bangla letters & digits)
    $d = strtolower($r['demographics'] ?? '');
    $n = strtolower($r['nutritional_intake'] ?? '');
    foreach (preg_split('/[^a-zA-Z০-৯ঀ-৿]+/u', $d) as $w) {
        if (mb_strlen($w) >= 3) $demoWords[$w] = ($demoWords[$w] ?? 0) + 1;
    }
    foreach (preg_split('/[^a-zA-Z০-৯ঀ-৿]+/u', $n) as $w) {
        if (mb_strlen($w) >= 3) $nutriWords[$w] = ($nutriWords[$w] ?? 0) + 1;
    }
}
arsort($regions);
arsort($demoWords);
arsort($nutriWords);

$avg = $values ? array_sum($values) / count($values) : 0;
$min = $values ? min($values) : 0;
$max = $values ? max($values) : 0;

$formData = [
    'insight_id' => $editRecord['insight_id'] ?? '',
    'per_capita_meat_consumption' => $editRecord['per_capita_meat_consumption'] ?? '',
    'region' => $editRecord['region'] ?? '',
    'demographics' => $editRecord['demographics'] ?? '',
    'nutritional_intake' => $editRecord['nutritional_intake'] ?? '',
];
if (!empty($_SESSION['form_data'])) {
    $formData = array_merge($formData, $_SESSION['form_data']);
    unset($_SESSION['form_data']);
}
$formErrors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_errors']);
$msg = $_GET['msg'] ?? '';
$autoShow = isset($_GET['showModal']);
?>

<!-- UI libs -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simple-datatables@9.0.3/dist/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3"></script>

<style>
    body {
        background: linear-gradient(120deg, #eef2ff 0%, #e0f7ff 30%, #fef6ff 60%, #fff 100%) fixed !important;
    }

    .glass {
        backdrop-filter: blur(10px);
        background: rgba(255, 255, 255, .65);
        border: 1px solid rgba(255, 255, 255, .5);
        border-radius: 1.25rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .08);
    }

    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .kpi .icon {
        width: 52px;
        height: 52px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        background: #f4f6ff;
        color: #4f46e5;
        font-size: 24px;
    }

    .badge-word {
        font-weight: 500;
    }

    .table code {
        white-space: nowrap
    }

    .form-hint {
        font-size: .85rem;
        color: var(--bs-secondary-color)
    }
</style>

<div class="container-fluid py-3">
    <div class="page-header">
        <div>
            <h2 class="mb-0 fw-bold">Consumption & Nutritional Insights</h2>
            <div class="text-body-secondary">Per-capita consumption by region with demographic & nutrition notes.</div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#insightModal">
                <i class="bi bi-plus-circle me-1"></i> Add Insight
            </button>
            <a href="consumption.php" class="btn btn-outline-secondary btn-lg"><i class="bi bi-arrow-clockwise me-1"></i> Refresh</a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row g-3 kpi">
        <div class="col-md-4">
            <div class="glass p-4 h-100 d-flex align-items-center gap-3">
                <div class="icon"><i class="bi bi-people"></i></div>
                <div>
                    <div class="text-body-secondary">Average Per-Capita (kg)</div>
                    <div class="h2 fw-bold mb-0"><?= number_format($avg, 3) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass p-4 h-100 d-flex align-items-center gap-3">
                <div class="icon" style="background:#fff7ed;color:#f97316;"><i class="bi bi-rulers"></i></div>
                <div>
                    <div class="text-body-secondary">Range (kg)</div>
                    <div class="h2 fw-bold mb-0"><?= number_format($min, 3) ?>–<?= number_format($max, 3) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass p-4 h-100 d-flex align-items-center gap-3">
                <div class="icon" style="background:#ecfeff;color:#06b6d4;"><i class="bi bi-geo-alt"></i></div>
                <div>
                    <div class="text-body-secondary">Regions Covered</div>
                    <div class="h2 fw-bold mb-0"><?= number_format(count($regions)) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row g-3 mt-1">
        <div class="col-lg-8">
            <div class="glass p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><i class="bi bi-bar-chart-line me-2"></i>Per-Capita by Region</h5>
                    <span class="small text-body-secondary">Higher bars = more consumption</span>
                </div>
                <canvas id="regionChart" height="180"></canvas>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="glass p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><i class="bi bi-card-text me-2"></i>Top Keywords</h5>
                    <span class="small text-body-secondary">Demographics & Nutrition</span>
                </div>
                <div class="mt-2">
                    <div class="fw-semibold small text-uppercase text-secondary mb-1">Demographics</div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php $i = 0;
                        foreach (array_slice($demoWords, 0, 15, true) as $w => $c): $i++; ?>
                            <span class="badge text-bg-light border badge-word"><?= htmlspecialchars($w) ?> <span class="text-secondary">(<?= $c ?>)</span></span>
                        <?php endforeach;
                        if ($i === 0) echo '<span class="text-body-secondary">No data</span>'; ?>
                    </div>
                    <div class="fw-semibold small text-uppercase text-secondary mt-3 mb-1">Nutrition</div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php $i = 0;
                        foreach (array_slice($nutriWords, 0, 15, true) as $w => $c): $i++; ?>
                            <span class="badge text-bg-light border badge-word"><?= htmlspecialchars($w) ?> <span class="text-secondary">(<?= $c ?>)</span></span>
                        <?php endforeach;
                        if ($i === 0) echo '<span class="text-body-secondary">No data</span>'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="glass p-3 mt-3">
        <div class="d-flex align-items-center justify-content-between">
            <span class="fw-semibold"><i class="bi bi-table me-2"></i>Insights</span>
            <div class="small text-body-secondary">Search, sort, export</div>
        </div>
        <div class="table-responsive mt-2">
            <table id="insightsTable" class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Per Capita (kg)</th>
                        <th>Region</th>
                        <th>Demographics</th>
                        <th>Nutritional Intake</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($insights as $row): ?>
                        <tr>
                            <td><?= (int)$row['insight_id'] ?></td>
                            <td><?= htmlspecialchars($row['per_capita_meat_consumption'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['region'] ?? '') ?></td>
                            <td class="text-truncate" style="max-width:260px;"><code class="small"><?= htmlspecialchars($row['demographics'] ?? '') ?></code></td>
                            <td class="text-truncate" style="max-width:260px;"><code class="small"><?= htmlspecialchars($row['nutritional_intake'] ?? '') ?></code></td>
                            <td class="text-end">
                                <a class="btn btn-light btn-sm border" href="?edit=<?= (int)$row['insight_id'] ?>&showModal=1"><i class="bi bi-pencil-square"></i> Edit</a>
                                <a class="btn btn-light btn-sm border btn-del" href="?delete=<?= (int)$row['insight_id'] ?>&csrf=<?= urlencode($CSRF) ?>" data-name="Insight #<?= (int)$row['insight_id'] ?>"><i class="bi bi-trash3"></i> Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th>ID</th>
                        <th>Per Capita (kg)</th>
                        <th>Region</th>
                        <th>Demographics</th>
                        <th>Nutritional Intake</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Add/Edit Insight -->
<div class="modal fade" id="insightModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow">
            <div class="modal-header">
                <h5 class="modal-title"><?= $formData['insight_id'] ? 'Edit Insight' : 'Add Insight' ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="post" novalidate class="needs-validation" id="insightForm">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                <input type="hidden" name="insight_id" value="<?= htmlspecialchars((string)$formData['insight_id']) ?>">
                <div class="modal-body">
                    <?php if (!empty($formErrors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($formErrors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="number" step="0.001" min="0" class="form-control" id="perCapita" name="per_capita_meat_consumption"
                                    value="<?= htmlspecialchars((string)$formData['per_capita_meat_consumption']) ?>">
                                <label for="perCapita">Per Capita (kg)</label>
                            </div>
                            <div class="form-hint mt-1">≥ 0</div>
                        </div>

                        <div class="col-md-8">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="region" name="region" required
                                    value="<?= htmlspecialchars((string)$formData['region']) ?>">
                                <label for="region">Region<span class="text-danger">*</span></label>
                            </div>
                            <div class="form-hint mt-1">e.g., Dhaka, Chattogram, Sylhet…</div>
                        </div>

                        <div class="col-12">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="demographics" name="demographics"
                                    placeholder="age groups, income, urban/rural…" value="<?= htmlspecialchars((string)$formData['demographics']) ?>">
                                <label for="demographics">Demographics (keywords)</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="nutrition" name="nutritional_intake"
                                    placeholder="protein, fat, micronutrients…" value="<?= htmlspecialchars((string)$formData['nutritional_intake']) ?>">
                                <label for="nutrition">Nutritional Intake (keywords)</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i> Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save2 me-1"></i> <?= $formData['insight_id'] ? 'Update' : 'Add' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:1080">
    <div id="liveToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="toastMsg">Saved.</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@9.0.3"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // DataTable + CSV export
    const tableEl = document.querySelector('#insightsTable');
    if (tableEl) {
        const dt = new simpleDatatables.DataTable(tableEl, {
            searchable: true,
            fixedHeight: false,
            perPage: 10,
            perPageSelect: [10, 20, 50, 100],
            labels: {
                placeholder: "Search…",
                perPage: "{select} rows per page",
                noRows: "No data",
                info: "Showing {start} to {end} of {rows}"
            }
        });
        const cont = tableEl.closest('.glass');
        const btn = document.createElement('button');
        btn.className = 'btn btn-outline-primary btn-sm ms-2';
        btn.innerHTML = '<i class="bi bi-download me-1"></i>Export CSV';
        cont.querySelector('.small').appendChild(btn);
        btn.addEventListener('click', () => dt.export({
            type: 'csv',
            download: true,
            selection: true
        }));
    }

    // Form validation
    (function() {
        const form = document.getElementById('insightForm');
        if (!form) return;
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    })();

    // SweetAlert delete confirm
    document.querySelectorAll('.btn-del').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');
            const name = this.getAttribute('data-name') || 'this insight';
            Swal.fire({
                icon: 'warning',
                title: 'Delete insight?',
                text: `Delete ${name}? This cannot be undone.`,
                showCancelButton: true,
                confirmButtonText: 'Yes, delete',
                reverseButtons: true,
                confirmButtonColor: '#dc3545'
            }).then(res => {
                if (res.isConfirmed) window.location.href = url;
            });
        });
    });

    // Auto open modal if ?showModal=1
    <?php if ($autoShow): ?> new bootstrap.Modal('#insightModal').show();
    <?php endif; ?>

    // Toast for ?msg=
    <?php if (!empty($msg)): ?>
        const toastEl = document.getElementById('liveToast');
        document.getElementById('toastMsg').textContent = <?= json_encode($msg) ?>;
        new bootstrap.Toast(toastEl, {
            delay: 2500
        }).show();
    <?php endif; ?>

        // Chart: Per-capita by Region
        (function() {
            const ctx = document.getElementById('regionChart');
            if (!ctx) return;
            const dataByRegion = {};
            <?php
            // average per region (if multiple rows per region)
            $agg = [];
            foreach ($insights as $r) {
                $reg = $r['region'] ?? '';
                if ($reg === '' || $r['per_capita_meat_consumption'] === null) continue;
                $agg[$reg] = $agg[$reg] ?? ['sum' => 0.0, 'n' => 0];
                $agg[$reg]['sum'] += (float)$r['per_capita_meat_consumption'];
                $agg[$reg]['n'] += 1;
            }
            $labels = [];
            $vals = [];
            foreach ($agg as $reg => $a) {
                $labels[] = $reg;
                $vals[] = $a['sum'] / $a['n'];
            }
            ?>
            const labels = <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>;
            const values = <?= json_encode($vals) ?>;

            if (labels.length) {
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Per Capita (kg)',
                            data: values
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            },
                            tooltip: {
                                callbacks: {
                                    label: c => `${Number(c.raw).toLocaleString(undefined,{maximumFractionDigits:3})} kg`
                                }
                            }
                        },
                        scales: {
                            y: {
                                ticks: {
                                    callback: v => `${Number(v).toLocaleString()} kg`
                                }
                            }
                        }
                    }
                });
            }
        })();
</script>

<?php include 'footer.php'; ?>