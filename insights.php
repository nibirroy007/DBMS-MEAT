<?php
// insights.php (Nutrition Analysts)
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
    header('Location: insights.php?msg=' . urlencode($msg));
    exit();
}

/** -------------------------------
 *  Options (Insights for dropdown)
 * --------------------------------*/
$insightOptions = $pdo->query('SELECT insight_id, region, demographics FROM insights ORDER BY insight_id DESC')->fetchAll(PDO::FETCH_ASSOC);

/** -------------------------------
 *  Delete (GET + CSRF)
 * --------------------------------*/
if (isset($_GET['delete'])) {
    if (!hash_equals($CSRF, $_GET['csrf'] ?? '')) redirect_with_msg('Invalid request token.');
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare('DELETE FROM nutrition_analyst WHERE nutritionist_id = ?');
    $stmt->execute([$id]);
    redirect_with_msg('Analyst deleted.');
}

/** -------------------------------
 *  Insert/Update (POST + CSRF)
 * --------------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($CSRF, $_POST['csrf'] ?? '')) redirect_with_msg('Invalid request token.');

    $id          = isset($_POST['nutritionist_id']) ? (int)$_POST['nutritionist_id'] : 0;
    $insightId   = ($_POST['insight_id'] ?? '') !== '' ? (int)$_POST['insight_id'] : null;
    $name        = trim($_POST['name'] ?? '');
    $role        = trim($_POST['role'] ?? '');
    $workingArea = trim($_POST['working_area'] ?? '');

    $errors = [];
    if ($name === '') $errors[] = 'Name is required.';
    if ($role !== '' && mb_strlen($role) > 80) $errors[] = 'Role is too long (max 80).';
    if ($workingArea !== '' && mb_strlen($workingArea) > 160) $errors[] = 'Working area is too long (max 160).';

    if ($errors) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data']   = $_POST;
        header('Location: insights.php?showModal=1' . ($id ? ('&edit=' . $id) : ''));
        exit();
    }

    if ($id > 0) {
        $sql = 'UPDATE nutrition_analyst SET insight_id=?, name=?, role=?, working_area=? WHERE nutritionist_id=?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$insightId, $name, $role, $workingArea, $id]);
        redirect_with_msg('Analyst updated.');
    } else {
        $sql = 'INSERT INTO nutrition_analyst (insight_id, name, role, working_area) VALUES (?,?,?,?)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$insightId, $name, $role, $workingArea]);
        redirect_with_msg('Analyst added.');
    }
}

/** -------------------------------
 *  Edit record
 * --------------------------------*/
$editRecord = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM nutrition_analyst WHERE nutritionist_id=?');
    $stmt->execute([$id]);
    $editRecord = $stmt->fetch(PDO::FETCH_ASSOC);
}

/** -------------------------------
 *  Fetch all analysts (with insight join)
 * --------------------------------*/
$analysts = $pdo->query('
  SELECT na.*, i.region, i.demographics
    FROM nutrition_analyst na
    LEFT JOIN insights i ON na.insight_id = i.insight_id
ORDER BY na.nutritionist_id DESC')->fetchAll(PDO::FETCH_ASSOC);

/** -------------------------------
 *  Analytics prep
 * --------------------------------*/
$totalAnalysts = count($analysts);
$linkedCount = 0;
$roleCounts = [];
$regionCounts = [];
foreach ($analysts as $a) {
    if (!empty($a['insight_id'])) $linkedCount++;
    $r = trim($a['role'] ?? '');
    if ($r !== '') $roleCounts[$r] = ($roleCounts[$r] ?? 0) + 1;

    $reg = trim($a['region'] ?? '');
    if ($reg !== '') $regionCounts[$reg] = ($regionCounts[$reg] ?? 0) + 1;
}
arsort($roleCounts);
arsort($regionCounts);

$uniqueRoles = count($roleCounts);
$uniqueRegions = count($regionCounts);

/** -------------------------------
 *  Form repop
 * --------------------------------*/
$formData = [
    'nutritionist_id' => $editRecord['nutritionist_id'] ?? '',
    'insight_id' => $editRecord['insight_id'] ?? '',
    'name' => $editRecord['name'] ?? '',
    'role' => $editRecord['role'] ?? '',
    'working_area' => $editRecord['working_area'] ?? '',
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
            <h2 class="mb-0 fw-bold">Nutrition Analysts</h2>
            <div class="text-body-secondary">People linked to consumption insights across regions.</div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#analystModal"><i class="bi bi-person-plus me-1"></i> Add Analyst</button>
            <a href="insights.php" class="btn btn-outline-secondary btn-lg"><i class="bi bi-arrow-clockwise me-1"></i> Refresh</a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row g-3 kpi">
        <div class="col-lg-3">
            <div class="glass p-4 h-100 d-flex align-items-center gap-3">
                <div class="icon"><i class="bi bi-people"></i></div>
                <div>
                    <div class="text-body-secondary">Total Analysts</div>
                    <div class="h2 fw-bold mb-0"><?= number_format($totalAnalysts) ?></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="glass p-4 h-100 d-flex align-items-center gap-3">
                <div class="icon" style="background:#ecfeff;color:#06b6d4;"><i class="bi bi-link-45deg"></i></div>
                <div>
                    <div class="text-body-secondary">Linked to Insight</div>
                    <div class="h2 fw-bold mb-0"><?= number_format($linkedCount) ?></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="glass p-4 h-100 d-flex align-items-center gap-3">
                <div class="icon" style="background:#fff7ed;color:#f97316;"><i class="bi bi-briefcase"></i></div>
                <div>
                    <div class="text-body-secondary">Unique Roles</div>
                    <div class="h2 fw-bold mb-0"><?= number_format($uniqueRoles) ?></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="glass p-4 h-100 d-flex align-items-center gap-3">
                <div class="icon" style="background:#f0feff;color:#0ea5e9;"><i class="bi bi-geo-alt"></i></div>
                <div>
                    <div class="text-body-secondary">Regions Covered</div>
                    <div class="h2 fw-bold mb-0"><?= number_format($uniqueRegions) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row g-3 mt-1">
        <div class="col-lg-7">
            <div class="glass p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><i class="bi bi-bar-chart-line me-2"></i>Analysts by Role</h5>
                    <span class="small text-body-secondary">Top roles</span>
                </div>
                <canvas id="roleChart" height="180"></canvas>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="glass p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Regions Covered</h5>
                    <span class="small text-body-secondary">Distribution</span>
                </div>
                <canvas id="regionChart" height="180"></canvas>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="glass p-3 mt-3">
        <div class="d-flex align-items-center justify-content-between">
            <span class="fw-semibold"><i class="bi bi-table me-2"></i>Analyst Directory</span>
            <div class="small text-body-secondary">Search, sort, export</div>
        </div>
        <div class="table-responsive mt-2">
            <table id="analystTable" class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Insight ID</th>
                        <th>Region</th>
                        <th>Demographics</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Working Area</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($analysts as $an): ?>
                        <tr>
                            <td><?= (int)$an['nutritionist_id'] ?></td>
                            <td><?= htmlspecialchars($an['insight_id'] ?? '') ?></td>
                            <td><?= htmlspecialchars($an['region'] ?? '') ?></td>
                            <td class="text-truncate" style="max-width:260px;"><code class="small"><?= htmlspecialchars($an['demographics'] ?? '') ?></code></td>
                            <td><?= htmlspecialchars($an['name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($an['role'] ?? '') ?></td>
                            <td><?= htmlspecialchars($an['working_area'] ?? '') ?></td>
                            <td class="text-end">
                                <a class="btn btn-light btn-sm border" href="?edit=<?= (int)$an['nutritionist_id'] ?>&showModal=1"><i class="bi bi-pencil-square"></i> Edit</a>
                                <a class="btn btn-light btn-sm border btn-del" href="?delete=<?= (int)$an['nutritionist_id'] ?>&csrf=<?= urlencode($CSRF) ?>" data-name="Analyst #<?= (int)$an['nutritionist_id'] ?>"><i class="bi bi-trash3"></i> Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th>ID</th>
                        <th>Insight ID</th>
                        <th>Region</th>
                        <th>Demographics</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Working Area</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Add/Edit Analyst -->
<div class="modal fade" id="analystModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow">
            <div class="modal-header">
                <h5 class="modal-title"><?= $formData['nutritionist_id'] ? 'Edit Analyst' : 'Add Analyst' ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" class="needs-validation" novalidate id="analystForm">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                <input type="hidden" name="nutritionist_id" value="<?= htmlspecialchars((string)$formData['nutritionist_id']) ?>">
                <div class="modal-body">
                    <?php if (!empty($formErrors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0"><?php foreach ($formErrors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul>
                        </div>
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <select class="form-select" id="insight_id" name="insight_id">
                                    <option value="">None</option>
                                    <?php foreach ($insightOptions as $ins):
                                        $sel = ((string)$formData['insight_id'] === (string)$ins['insight_id']) ? 'selected' : ''; ?>
                                        <option value="<?= (int)$ins['insight_id'] ?>" <?= $sel ?>>
                                            <?= 'ID ' . $ins['insight_id'] . ' - ' . htmlspecialchars($ins['region']) . ' (' . htmlspecialchars($ins['demographics']) . ')' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="insight_id">Insight (optional)</label>
                            </div>
                            <div class="form-hint mt-1">Link analysts to relevant regional insights.</div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="name" name="name" required
                                    value="<?= htmlspecialchars((string)$formData['name']) ?>">
                                <label for="name">Name<span class="text-danger">*</span></label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="role" name="role"
                                    value="<?= htmlspecialchars((string)$formData['role']) ?>">
                                <label for="role">Role</label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="working_area" name="working_area"
                                    value="<?= htmlspecialchars((string)$formData['working_area']) ?>">
                                <label for="working_area">Working Area</label>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i> Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save2 me-1"></i> <?= $formData['nutritionist_id'] ? 'Update' : 'Add' ?></button>
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
        const el = document.getElementById('analystTable');
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
        const form = document.getElementById('analystForm');
        if (!form) return;
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    })();

    // Delete confirmation
    document.querySelectorAll('.btn-del').forEach(a => {
        a.addEventListener('click', e => {
            e.preventDefault();
            const url = a.getAttribute('href');
            const name = a.dataset.name || 'this analyst';
            Swal.fire({
                    icon: 'warning',
                    title: 'Delete analyst?',
                    text: `Delete ${name}?`,
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete',
                    reverseButtons: true,
                    confirmButtonColor: '#dc3545'
                })
                .then(res => {
                    if (res.isConfirmed) window.location.href = url;
                });
        });
    });

    // Auto open modal on bounce/edit
    <?php if ($autoShow): ?> new bootstrap.Modal('#analystModal').show();
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
    const roleCounts = <?= json_encode($roleCounts, JSON_UNESCAPED_UNICODE) ?>;
    const regionCounts = <?= json_encode($regionCounts, JSON_UNESCAPED_UNICODE) ?>;

    (function() {
        const ctx = document.getElementById('roleChart');
        if (!ctx) return;
        const entries = Object.entries(roleCounts).slice(0, 10); // top 10 roles
        const labels = entries.map(([k]) => k);
        const data = entries.map(([, v]) => v);
        if (!labels.length) return;
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Analysts',
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

    (function() {
        const ctx = document.getElementById('regionChart');
        if (!ctx) return;
        const labels = Object.keys(regionCounts);
        const data = labels.map(k => regionCounts[k]);
        if (!labels.length) return;
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    label: 'Analysts',
                    data
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                cutout: '60%'
            }
        });
    })();
</script>

<?php include 'footer.php'; ?>