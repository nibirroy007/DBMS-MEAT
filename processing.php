<?php
// processing.php
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
    header('Location: processing.php?msg=' . urlencode($msg));
    exit();
}

/** -------------------------------
 *  Options
 * --------------------------------*/
$livestockOptions = $pdo->query('SELECT livestock_id FROM livestock ORDER BY livestock_id')->fetchAll(PDO::FETCH_ASSOC);
$houseOptions     = $pdo->query('SELECT slaughter_house_id, name FROM slaughter_house ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);

/** -------------------------------
 *  Delete (GET + CSRF)
 * --------------------------------*/
if (isset($_GET['delete'])) {
    if (!hash_equals($CSRF, $_GET['csrf'] ?? '')) redirect_with_msg('Invalid request token.');
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare('DELETE FROM processing WHERE processing_id = ?');
    $stmt->execute([$id]);
    redirect_with_msg('Processing record deleted.');
}

/** -------------------------------
 *  Insert/Update (POST + CSRF)
 * --------------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($CSRF, $_POST['csrf'] ?? '')) redirect_with_msg('Invalid request token.');

    $id            = isset($_POST['processing_id']) ? (int)$_POST['processing_id'] : 0;
    $livestockId   = isset($_POST['livestock_id']) ? (int)$_POST['livestock_id'] : 0;
    $houseId       = $_POST['slaughter_house_id'] !== '' ? (int)$_POST['slaughter_house_id'] : null;
    $slaughterDate = $_POST['slaughter_date'] ?? null;

    $errors = [];
    if ($livestockId <= 0) $errors[] = 'Livestock is required.';
    if (!$slaughterDate)   $errors[] = 'Slaughter date is required.';

    if ($errors) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data']   = $_POST;
        header('Location: processing.php?showModal=1' . ($id ? ('&edit=' . $id) : ''));
        exit();
    }

    if ($id > 0) {
        $sql = 'UPDATE processing SET livestock_id=?, slaughter_house_id=?, slaughter_date=? WHERE processing_id=?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$livestockId, $houseId, $slaughterDate, $id]);
        redirect_with_msg('Processing record updated.');
    } else {
        $sql = 'INSERT INTO processing (livestock_id, slaughter_house_id, slaughter_date) VALUES (?,?,?)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$livestockId, $houseId, $slaughterDate]);
        redirect_with_msg('Processing record added.');
    }
}

/** -------------------------------
 *  Edit record
 * --------------------------------*/
$editRecord = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM processing WHERE processing_id=?');
    $stmt->execute([$id]);
    $editRecord = $stmt->fetch(PDO::FETCH_ASSOC);
}

/** -------------------------------
 *  Fetch all with names (latest first)
 * --------------------------------*/
$records = $pdo->query(
    'SELECT pr.processing_id, pr.livestock_id, pr.slaughter_house_id, pr.slaughter_date,
         sh.name AS house_name
    FROM processing pr
    LEFT JOIN slaughter_house sh ON pr.slaughter_house_id = sh.slaughter_house_id
ORDER BY pr.slaughter_date DESC, pr.processing_id DESC'
)->fetchAll(PDO::FETCH_ASSOC);

/** -------------------------------
 *  Analytics prep
 * --------------------------------*/
$totalProcessed = count($records);
$housesSet = [];
$latestDate = '';
$byDay = [];             // YYYY-MM => daily? here we’ll use YYYY-MM-DD for more fidelity
$houseLeaderboard = [];  // name => count

foreach ($records as $r) {
    if ($r['slaughter_date']) {
        $d = $r['slaughter_date'];
        $byDay[$d] = ($byDay[$d] ?? 0) + 1;
        if ($d > $latestDate) $latestDate = $d;
    }
    $hn = $r['house_name'] ?: 'Unassigned';
    $houseLeaderboard[$hn] = ($houseLeaderboard[$hn] ?? 0) + 1;
    if (!empty($r['slaughter_house_id'])) $housesSet[(string)$r['slaughter_house_id']] = true;
}
arsort($houseLeaderboard);
ksort($byDay);

$housesCount = count($housesSet);

/** -------------------------------
 *  Form repop
 * --------------------------------*/
$formData = [
    'processing_id' => $editRecord['processing_id'] ?? '',
    'livestock_id' => $editRecord['livestock_id'] ?? '',
    'slaughter_house_id' => $editRecord['slaughter_house_id'] ?? '',
    'slaughter_date' => $editRecord['slaughter_date'] ?? '',
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

    .table code {
        white-space: nowrap
    }
</style>

<div class="container-fluid py-3">
    <div class="page-header">
        <div>
            <h2 class="mb-0 fw-bold">Processing</h2>
            <div class="text-body-secondary">Track slaughter events by date & house.</div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#processingModal"><i class="bi bi-plus-circle me-1"></i> Add Processing</button>
            <a href="processing.php" class="btn btn-outline-secondary btn-lg"><i class="bi bi-arrow-clockwise me-1"></i> Refresh</a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row g-3 kpi">
        <div class="col-md-4">
            <div class="glass p-4 h-100 d-flex align-items-center gap-3">
                <div class="icon"><i class="bi bi-123"></i></div>
                <div>
                    <div class="text-body-secondary">Total Processed</div>
                    <div class="h2 fw-bold mb-0"><?= number_format($totalProcessed) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass p-4 h-100 d-flex align-items-center gap-3">
                <div class="icon" style="background:#ecfeff;color:#06b6d4;"><i class="bi bi-building"></i></div>
                <div>
                    <div class="text-body-secondary">Active Houses</div>
                    <div class="h2 fw-bold mb-0"><?= number_format($housesCount) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass p-4 h-100 d-flex align-items-center gap-3">
                <div class="icon" style="background:#fff7ed;color:#f97316;"><i class="bi bi-calendar-date"></i></div>
                <div>
                    <div class="text-body-secondary">Latest Date</div>
                    <div class="h2 fw-bold mb-0"><?= $latestDate ? htmlspecialchars($latestDate) : '—' ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row g-3 mt-1">
        <div class="col-lg-8">
            <div class="glass p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><i class="bi bi-bar-chart-line me-2"></i>Throughput Over Time</h5>
                    <span class="small text-body-secondary">Count of animals processed per day</span>
                </div>
                <canvas id="throughputChart" height="180"></canvas>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="glass p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><i class="bi bi-trophy me-2"></i>Top Slaughter Houses</h5>
                    <span class="small text-body-secondary">By total processed</span>
                </div>
                <canvas id="houseChart" height="180"></canvas>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="glass p-3 mt-3">
        <div class="d-flex align-items-center justify-content-between">
            <span class="fw-semibold"><i class="bi bi-list-ul me-2"></i>Processing Records</span>
            <div class="small text-body-secondary">Search, sort, export</div>
        </div>
        <div class="table-responsive mt-2">
            <table id="procTable" class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Livestock ID</th>
                        <th>Slaughter House</th>
                        <th>Slaughter Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $row): ?>
                        <tr>
                            <td><?= (int)$row['processing_id'] ?></td>
                            <td><?= (int)$row['livestock_id'] ?></td>
                            <td><?= htmlspecialchars($row['house_name'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($row['slaughter_date']) ?></td>
                            <td class="text-end">
                                <a class="btn btn-light btn-sm border" href="?edit=<?= (int)$row['processing_id'] ?>&showModal=1"><i class="bi bi-pencil-square"></i> Edit</a>
                                <a class="btn btn-light btn-sm border btn-del" href="?delete=<?= (int)$row['processing_id'] ?>&csrf=<?= urlencode($CSRF) ?>" data-name="Processing #<?= (int)$row['processing_id'] ?>"><i class="bi bi-trash3"></i> Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th>ID</th>
                        <th>Livestock ID</th>
                        <th>Slaughter House</th>
                        <th>Slaughter Date</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Add/Edit -->
<div class="modal fade" id="processingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow">
            <div class="modal-header">
                <h5 class="modal-title"><?= $formData['processing_id'] ? 'Edit Processing' : 'Add Processing' ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" novalidate class="needs-validation" id="procForm">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                <input type="hidden" name="processing_id" value="<?= htmlspecialchars((string)$formData['processing_id']) ?>">
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
                                <select class="form-select" id="livestock_id" name="livestock_id" required>
                                    <option value="">Select Livestock</option>
                                    <?php foreach ($livestockOptions as $liv): $sel = ((string)$formData['livestock_id'] === (string)$liv['livestock_id']) ? 'selected' : ''; ?>
                                        <option value="<?= (int)$liv['livestock_id'] ?>" <?= $sel ?>><?= (int)$liv['livestock_id'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="livestock_id">Livestock<span class="text-danger">*</span></label>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-floating">
                                <select class="form-select" id="slaughter_house_id" name="slaughter_house_id">
                                    <option value="">None</option>
                                    <?php foreach ($houseOptions as $h): $sel = ((string)$formData['slaughter_house_id'] === (string)$h['slaughter_house_id']) ? 'selected' : ''; ?>
                                        <option value="<?= (int)$h['slaughter_house_id'] ?>" <?= $sel ?>><?= htmlspecialchars($h['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="slaughter_house_id">Slaughter House</label>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="date" class="form-control" id="slaughter_date" name="slaughter_date" required
                                    value="<?= htmlspecialchars((string)$formData['slaughter_date']) ?>">
                                <label for="slaughter_date">Slaughter Date<span class="text-danger">*</span></label>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i> Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save2 me-1"></i> <?= $formData['processing_id'] ? 'Update' : 'Add' ?></button>
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
    (function() {
        const el = document.getElementById('procTable');
        if (!el) return;
        const dt = new simpleDatatables.DataTable(el, {
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
        const cont = el.closest('.glass');
        const btn = document.createElement('button');
        btn.className = 'btn btn-outline-primary btn-sm ms-2';
        btn.innerHTML = '<i class="bi bi-download me-1"></i>Export CSV';
        cont.querySelector('.small').appendChild(btn);
        btn.addEventListener('click', () => dt.export({
            type: 'csv',
            download: true,
            selection: true
        }));
    })();

    // Validation
    (function() {
        const form = document.getElementById('procForm');
        if (!form) return;
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    })();

    // Delete confirm
    document.querySelectorAll('.btn-del').forEach(a => {
        a.addEventListener('click', e => {
            e.preventDefault();
            const url = a.getAttribute('href');
            const name = a.dataset.name || 'this record';
            Swal.fire({
                    icon: 'warning',
                    title: 'Delete processing record?',
                    text: `Delete ${name}?`,
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete',
                    reverseButtons: true,
                    confirmButtonColor: '#dc3545'
                })
                .then(r => {
                    if (r.isConfirmed) window.location.href = url;
                });
        });
    });

    // Auto open modal on bounce/edit
    <?php if ($autoShow): ?> new bootstrap.Modal('#processingModal').show();
    <?php endif; ?>

    // Toast for ?msg=
    <?php if (!empty($msg)): ?>
        const toastEl = document.getElementById('liveToast');
        document.getElementById('toastMsg').textContent = <?= json_encode($msg) ?>;
        new bootstrap.Toast(toastEl, {
            delay: 2500
        }).show();
    <?php endif; ?>

    // Charts
    const throughput = <?= json_encode($byDay) ?>;
    const houseBoard = <?= json_encode($houseLeaderboard, JSON_UNESCAPED_UNICODE) ?>;

    // Throughput over time (line)
    (function() {
        const ctx = document.getElementById('throughputChart');
        if (!ctx) return;
        const labels = Object.keys(throughput).sort();
        const data = labels.map(k => throughput[k] || 0);
        if (!labels.length) return;
        new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Processed (count)',
                    data
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
                            label: c => `${c.dataset.label}: ${Number(c.raw).toLocaleString()}`
                        }
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            callback: v => Number(v).toLocaleString()
                        }
                    }
                }
            }
        });
    })();

    // Top houses (horizontal bar)
    (function() {
        const ctx = document.getElementById('houseChart');
        if (!ctx) return;
        const entries = Object.entries(houseBoard).slice(0, 10); // top 10
        const labels = entries.map(([k]) => k);
        const data = entries.map(([, v]) => v);
        if (!labels.length) return;
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Processed (count)',
                    data
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: c => `${c.dataset.label}: ${Number(c.raw).toLocaleString()}`
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            callback: v => Number(v).toLocaleString()
                        }
                    }
                }
            }
        });
    })();
</script>

<?php include 'footer.php'; ?>