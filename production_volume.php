<?php
// production_volume.php
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
    header('Location: production_volume.php?msg=' . urlencode($msg));
    exit();
}

/** -------------------------------
 *  Fetch dropdown data
 * --------------------------------*/
$productsStmt = $pdo->query('SELECT product_id, type, COALESCE(breed, "") AS breed FROM meat_product ORDER BY type');
$productOptions = $productsStmt->fetchAll(PDO::FETCH_ASSOC);

$procStmt = $pdo->query('SELECT processing_id FROM processing ORDER BY processing_id');
$processingOptions = $procStmt->fetchAll(PDO::FETCH_ASSOC);

/** -------------------------------
 *  Handle Deletion (GET + CSRF)
 * --------------------------------*/
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    $token = $_GET['csrf'] ?? '';
    if (!hash_equals($CSRF, $token)) {
        redirect_with_msg('Invalid request token.');
    }
    $stmt = $pdo->prepare('DELETE FROM meat_production_batch WHERE batch_id = ?');
    $stmt->execute([$deleteId]);
    redirect_with_msg('Batch deleted successfully.');
}

/** -------------------------------
 *  Handle Insert/Update (POST)
 * --------------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf']) || !hash_equals($CSRF, $_POST['csrf'])) {
        redirect_with_msg('Invalid request token.');
    }

    $batchId        = isset($_POST['batch_id']) ? (int)$_POST['batch_id'] : 0;
    $productId      = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $processingId   = isset($_POST['processing_id']) && $_POST['processing_id'] !== '' ? (int)$_POST['processing_id'] : null;
    $districtVolume = $_POST['district_volume'] !== '' ? (float)$_POST['district_volume'] : null;
    $livestockCount = $_POST['livestock_count'] !== '' ? (int)$_POST['livestock_count'] : null;
    $slaughterRate  = $_POST['slaughter_rate'] !== '' ? (float)$_POST['slaughter_rate'] : null;
    $meatYield      = trim($_POST['meat_yield_over_time'] ?? '');

    $errors = [];
    if ($productId <= 0) $errors[] = 'Product is required.';
    if ($districtVolume === null || $districtVolume < 0) $errors[] = 'District volume (kg) must be ≥ 0.';
    if ($livestockCount !== null && $livestockCount < 0) $errors[] = 'Livestock count must be ≥ 0.';
    if ($slaughterRate !== null && $slaughterRate < 0) $errors[] = 'Slaughter rate must be ≥ 0.';
    if ($meatYield !== '') {
        // Validate JSON
        json_decode($meatYield, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errors[] = 'Meat Yield Over Time must be valid JSON (e.g., {"2025-01":1200.5}).';
        }
    }

    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
        if ($batchId > 0) {
            header('Location: production_volume.php?edit=' . $batchId . '&showModal=1');
        } else {
            header('Location: production_volume.php?showModal=1');
        }
        exit();
    }

    if ($batchId > 0) {
        $sql = 'UPDATE meat_production_batch 
                   SET product_id=?, processing_id=?, district_volume=?, livestock_count=?, slaughter_rate=?, meat_yield_over_time=? 
                 WHERE batch_id=?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$productId, $processingId, $districtVolume, $livestockCount, $slaughterRate, ($meatYield !== '' ? $meatYield : null), $batchId]);
        redirect_with_msg('Batch updated successfully.');
    } else {
        $sql = 'INSERT INTO meat_production_batch 
                   (product_id, processing_id, district_volume, livestock_count, slaughter_rate, meat_yield_over_time) 
                VALUES (?,?,?,?,?,?)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$productId, $processingId, $districtVolume, $livestockCount, $slaughterRate, ($meatYield !== '' ? $meatYield : null)]);
        redirect_with_msg('Batch added successfully.');
    }
}

/** -------------------------------
 *  Editing record
 * --------------------------------*/
$editRecord = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM meat_production_batch WHERE batch_id = ?');
    $stmt->execute([$editId]);
    $editRecord = $stmt->fetch(PDO::FETCH_ASSOC);
}

/** -------------------------------
 *  Fetch list of batches with product
 * --------------------------------*/
$stmt = $pdo->query(
    'SELECT b.batch_id, p.type AS product_type, p.product_id, b.processing_id, b.district_volume, 
            b.livestock_count, b.slaughter_rate, b.meat_yield_over_time
       FROM meat_production_batch b 
       JOIN meat_product p ON b.product_id = p.product_id 
   ORDER BY b.batch_id DESC'
);
$batches = $stmt->fetchAll(PDO::FETCH_ASSOC);

/** -------------------------------
 *  Prep analytics in PHP
 * --------------------------------*/
$totalVolume = 0.0;
$totalBatches = count($batches);
$srSum = 0.0;
$srCount = 0;
$byType = []; // ['beef'=>sum, ...]
$monthly = []; // ['YYYY-MM'=>sum]

foreach ($batches as $row) {
    $v = (float)($row['district_volume'] ?? 0);
    $totalVolume += $v;
    $t = $row['product_type'] ?? 'other';
    $byType[$t] = ($byType[$t] ?? 0) + $v;

    if ($row['slaughter_rate'] !== null) {
        $srSum += (float)$row['slaughter_rate'];
        $srCount++;
    }
    if (!empty($row['meat_yield_over_time'])) {
        $decoded = json_decode($row['meat_yield_over_time'], true);
        if (is_array($decoded)) {
            foreach ($decoded as $ym => $val) {
                $monthly[$ym] = ($monthly[$ym] ?? 0) + (float)$val;
            }
        }
    }
}
ksort($monthly);
$avgSR = $srCount ? $srSum / $srCount : 0.0;

/** -------------------------------
 *  Support form repopulation on error
 * --------------------------------*/
$formData = [
    'batch_id' => $editRecord['batch_id'] ?? '',
    'product_id' => $editRecord['product_id'] ?? '',
    'processing_id' => $editRecord['processing_id'] ?? '',
    'district_volume' => $editRecord['district_volume'] ?? '',
    'livestock_count' => $editRecord['livestock_count'] ?? '',
    'slaughter_rate' => $editRecord['slaughter_rate'] ?? '',
    'meat_yield_over_time' => $editRecord['meat_yield_over_time'] ?? ''
];
if (!empty($_SESSION['form_data'])) {
    $formData = array_merge($formData, $_SESSION['form_data']);
    unset($_SESSION['form_data']);
}
$formErrors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_errors']);
$msg = $_GET['msg'] ?? '';
$autoShowModal = isset($_GET['showModal']) ? true : false;
?>

<!-- UI libs -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simple-datatables@9.0.3/dist/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3"></script>

<style>
    /* Colorful gradient background */
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

    .badge-type {
        text-transform: capitalize;
    }

    .form-hint {
        font-size: .85rem;
        color: var(--bs-secondary-color);
    }
</style>

<div class="container-fluid py-3">
    <div class="page-header">
        <div>
            <h2 class="mb-0 fw-bold">Production Volume</h2>
            <div class="text-body-secondary">Batches, volumes, slaughter rates & monthly yields — with insights.</div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#batchModal">
                <i class="bi bi-plus-circle me-1"></i> Add Batch
            </button>
            <a href="production_volume.php" class="btn btn-outline-secondary btn-lg">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh
            </a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row g-3 kpi">
        <div class="col-md-4">
            <div class="glass p-4 h-100 d-flex align-items-center gap-3">
                <div class="icon"><i class="bi bi-archive-fill"></i></div>
                <div>
                    <div class="text-body-secondary">Total Batches</div>
                    <div class="h2 fw-bold mb-0"><?= number_format($totalBatches) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass p-4 h-100 d-flex align-items-center gap-3">
                <div class="icon" style="background:#f0feff;color:#0ea5e9;"><i class="bi bi-speedometer"></i></div>
                <div>
                    <div class="text-body-secondary">Total District Volume (kg)</div>
                    <div class="h2 fw-bold mb-0"><?= number_format($totalVolume, 2) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass p-4 h-100 d-flex align-items-center gap-3">
                <div class="icon" style="background:#fff7ed;color:#f97316;"><i class="bi bi-sliders"></i></div>
                <div>
                    <div class="text-body-secondary">Avg Slaughter Rate</div>
                    <div class="h2 fw-bold mb-0"><?= number_format($avgSR, 3) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row g-3 mt-1">
        <div class="col-lg-6">
            <div class="glass p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Volume by Product Type</h5>
                    <span class="small text-body-secondary">Distribution</span>
                </div>
                <canvas id="typeChart" height="180"></canvas>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="glass p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><i class="bi bi-graph-up-arrow me-2"></i>Monthly Yield Trend</h5>
                    <span class="small text-body-secondary">Aggregated from JSON</span>
                </div>
                <canvas id="monthlyChart" height="180"></canvas>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="glass p-3 mt-3">
        <div class="d-flex align-items-center justify-content-between">
            <span class="fw-semibold"><i class="bi bi-table me-2"></i>Batches</span>
            <div class="small text-body-secondary">Search, sort, export</div>
        </div>
        <div class="table-responsive mt-2">
            <table id="batchesTable" class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Batch ID</th>
                        <th>Product</th>
                        <th>Processing ID</th>
                        <th>District Volume (kg)</th>
                        <th>Livestock Count</th>
                        <th>Slaughter Rate</th>
                        <th>Yield JSON</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $typeBadge = function ($type) {
                        $color = match ($type) {
                            'beef' => 'danger',
                            'mutton' => 'warning',
                            'chicken' => 'success',
                            'pork' => 'secondary',
                            default => 'info'
                        };
                        return '<span class="badge bg-' . $color . ' badge-type">' . htmlspecialchars($type) . '</span>';
                    };
                    ?>
                    <?php foreach ($batches as $batch): ?>
                        <tr>
                            <td><?= (int)$batch['batch_id']; ?></td>
                            <td><?= $typeBadge($batch['product_type']); ?></td>
                            <td><?= htmlspecialchars($batch['processing_id'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($batch['district_volume']); ?></td>
                            <td><?= htmlspecialchars($batch['livestock_count'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($batch['slaughter_rate'] ?? ''); ?></td>
                            <td class="text-truncate" style="max-width:280px;">
                                <code class="small"><?= htmlspecialchars($batch['meat_yield_over_time'] ?? '') ?></code>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-light btn-sm border" href="?edit=<?= (int)$batch['batch_id']; ?>&showModal=1">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </a>
                                <a class="btn btn-light btn-sm border btn-delete"
                                    href="?delete=<?= (int)$batch['batch_id']; ?>&csrf=<?= urlencode($CSRF) ?>"
                                    data-name="Batch #<?= (int)$batch['batch_id']; ?>">
                                    <i class="bi bi-trash3"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th>Batch ID</th>
                        <th>Product</th>
                        <th>Processing ID</th>
                        <th>District Volume (kg)</th>
                        <th>Livestock Count</th>
                        <th>Slaughter Rate</th>
                        <th>Yield JSON</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Add/Edit Batch -->
<div class="modal fade" id="batchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow">
            <div class="modal-header">
                <h5 class="modal-title"><?= $formData['batch_id'] ? 'Edit Batch' : 'Add Batch' ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form method="post" novalidate class="needs-validation" id="batchForm">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                <input type="hidden" name="batch_id" value="<?= htmlspecialchars((string)$formData['batch_id']) ?>">

                <div class="modal-body">
                    <?php if (!empty($formErrors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($formErrors as $e): ?>
                                    <li><?= htmlspecialchars($e) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <select class="form-select" id="product_id" name="product_id" required>
                                    <option value="">Select Product</option>
                                    <?php foreach ($productOptions as $prod):
                                        $sel = ((string)$formData['product_id'] === (string)$prod['product_id']) ? 'selected' : '';
                                        $label = $prod['type'] . ($prod['breed'] ? (' — ' . $prod['breed']) : '');
                                    ?>
                                        <option value="<?= (int)$prod['product_id']; ?>" <?= $sel; ?>>
                                            <?= htmlspecialchars($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="product_id">Product<span class="text-danger">*</span></label>
                            </div>
                            <div class="form-hint mt-1">Required; maps to meat_product.</div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating">
                                <select class="form-select" id="processing_id" name="processing_id">
                                    <option value="">None</option>
                                    <?php foreach ($processingOptions as $proc):
                                        $sel = ((string)$formData['processing_id'] === (string)$proc['processing_id']) ? 'selected' : '';
                                    ?>
                                        <option value="<?= (int)$proc['processing_id']; ?>" <?= $sel; ?>>
                                            <?= (int)$proc['processing_id']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="processing_id">Processing ID (optional)</label>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="number" step="0.01" min="0" class="form-control" id="district_volume"
                                    name="district_volume" placeholder="District Volume" required
                                    value="<?= htmlspecialchars((string)$formData['district_volume']) ?>">
                                <label for="district_volume">District Volume (kg)<span class="text-danger">*</span></label>
                            </div>
                            <div class="form-hint mt-1">≥ 0</div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="number" step="1" min="0" class="form-control" id="livestock_count"
                                    name="livestock_count" placeholder="Livestock Count"
                                    value="<?= htmlspecialchars((string)$formData['livestock_count']) ?>">
                                <label for="livestock_count">Livestock Count</label>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="number" step="0.001" min="0" class="form-control" id="slaughter_rate"
                                    name="slaughter_rate" placeholder="Slaughter Rate"
                                    value="<?= htmlspecialchars((string)$formData['slaughter_rate']) ?>">
                                <label for="slaughter_rate">Slaughter Rate</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-floating">
                                <textarea class="form-control" id="meat_yield_over_time" name="meat_yield_over_time"
                                    placeholder='{"2025-01": 1000.5, "2025-02": 1200}' style="height: 140px"><?= htmlspecialchars((string)$formData['meat_yield_over_time']) ?></textarea>
                                <label for="meat_yield_over_time">Meat Yield Over Time (JSON)</label>
                            </div>
                            <div class="form-hint mt-1">
                                Example: <code>{"2025-01": 1000.5, "2025-02": 1200}</code>. Keys should be <code>YYYY-MM</code>.
                                <button class="btn btn-sm btn-outline-secondary ms-2" type="button" id="btn-pretty-json">Pretty</button>
                                <span id="json-validity" class="ms-2"></span>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save2 me-1"></i> <?= $formData['batch_id'] ? 'Update' : 'Add' ?>
                    </button>
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
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@9.0.3"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // DataTable
    const tableEl = document.querySelector('#batchesTable');
    if (tableEl) {
        const dt = new simpleDatatables.DataTable(tableEl, {
            searchable: true,
            fixedHeight: false,
            perPage: 10,
            perPageSelect: [10, 20, 50, 100],
            labels: {
                placeholder: "Search…",
                perPage: "{select} rows per page",
                noRows: "No records found",
                info: "Showing {start} to {end} of {rows} batches"
            }
        });

        // Simple CSV export
        const exportBtn = document.createElement('button');
        exportBtn.className = 'btn btn-outline-primary btn-sm ms-2';
        exportBtn.innerHTML = '<i class="bi bi-download me-1"></i>Export CSV';
        tableEl.closest('.glass').querySelector('.d-flex .small').appendChild(exportBtn);
        exportBtn.addEventListener('click', () => {
            const rows = dt.export({
                type: 'csv',
                download: true,
                selection: true,
                lineDelimiter: "\n",
                columnDelimiter: ","
            });
        });
    }

    // Form validation
    (function() {
        const form = document.getElementById('batchForm');
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
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');
            const name = this.getAttribute('data-name') || 'this batch';
            Swal.fire({
                icon: 'warning',
                title: 'Delete batch?',
                text: `Are you sure you want to delete ${name}? This cannot be undone.`,
                showCancelButton: true,
                confirmButtonText: 'Yes, delete',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                confirmButtonColor: '#dc3545'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });
    });

    // Auto open modal on ?showModal=1
    <?php if ($autoShowModal): ?>
        const modal = new bootstrap.Modal('#batchModal');
        modal.show();
    <?php endif; ?>

    // Toast for ?msg=
    <?php if (!empty($msg)): ?>
        const toastEl = document.getElementById('liveToast');
        const toastMsg = document.getElementById('toastMsg');
        if (toastEl && toastMsg) {
            toastMsg.textContent = <?= json_encode($msg) ?>;
            const toast = new bootstrap.Toast(toastEl, {
                delay: 2500
            });
            toast.show();
        }
    <?php endif; ?>

    // JSON live validation + Pretty
    const jsonField = document.getElementById('meat_yield_over_time');
    const jsonValidity = document.getElementById('json-validity');
    const prettyBtn = document.getElementById('btn-pretty-json');

    function validateJSON() {
        if (!jsonField) return;
        const val = jsonField.value.trim();
        if (val === '') {
            jsonValidity.innerHTML = '';
            return;
        }
        try {
            const obj = JSON.parse(val);
            // Optional format check: keys as YYYY-MM
            const badKey = Object.keys(obj).find(k => !/^\d{4}-\d{2}$/.test(k));
            if (badKey) {
                jsonValidity.innerHTML = '<span class="text-warning"><i class="bi bi-exclamation-triangle-fill me-1"></i>Key "' + badKey + '" not in YYYY-MM</span>';
            } else {
                jsonValidity.innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Valid JSON</span>';
            }
        } catch (e) {
            jsonValidity.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle-fill me-1"></i>Invalid JSON</span>';
        }
    }
    if (jsonField) {
        jsonField.addEventListener('input', validateJSON);
        validateJSON();
    }
    if (prettyBtn && jsonField) {
        prettyBtn.addEventListener('click', () => {
            try {
                const obj = JSON.parse(jsonField.value.trim() || '{}');
                jsonField.value = JSON.stringify(obj, null, 2);
                validateJSON();
            } catch (e) {
                /*ignore*/ }
        });
    }

    // Charts
    (function() {
        const typeCtx = document.getElementById('typeChart');
        const monthlyCtx = document.getElementById('monthlyChart');
        const typeData = <?= json_encode($byType, JSON_UNESCAPED_UNICODE) ?>;
        const monthlyData = <?= json_encode($monthly, JSON_UNESCAPED_UNICODE) ?>;

        if (typeCtx && Object.keys(typeData).length) {
            new Chart(typeCtx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(typeData),
                    datasets: [{
                        data: Object.values(typeData)
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
                                label: ctx => `${ctx.label}: ${Number(ctx.parsed).toLocaleString()} kg`
                            }
                        }
                    },
                    cutout: '65%'
                }
            });
        }
        if (monthlyCtx && Object.keys(monthlyData).length) {
            new Chart(monthlyCtx, {
                type: 'line',
                data: {
                    labels: Object.keys(monthlyData),
                    datasets: [{
                        label: 'Yield (kg)',
                        data: Object.values(monthlyData),
                        tension: .35,
                        pointRadius: 3,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => `${Number(ctx.parsed.y).toLocaleString()} kg`
                            }
                        }
                    },
                    scales: {
                        y: {
                            ticks: {
                                callback: v => v.toLocaleString() + ' kg'
                            }
                        }
                    }
                }
            });
        }
    })();
</script>

<?php include 'footer.php'; ?>