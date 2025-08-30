<?php
// meat_products.php
session_start();
require 'config.php';
include 'header.php';

/** -------------------------------
 *  CSRF Protection
 * --------------------------------*/
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
$CSRF = $_SESSION['csrf_token'];

/** -------------------------------
 *  Helpers
 * --------------------------------*/
function redirect_with_msg($msg)
{
    header('Location: meat_products.php?msg=' . urlencode($msg));
    exit();
}
function validate_enum_type($val)
{
    $allowed = ['beef', 'chicken', 'mutton', 'pork', 'other'];
    return in_array($val, $allowed, true) ? $val : null;
}

/** -------------------------------
 *  Handle Deletion (GET w/ CSRF)
 * --------------------------------*/
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    $token = $_GET['csrf'] ?? '';
    if (!hash_equals($CSRF, $token)) {
        redirect_with_msg('Invalid request token.');
    }
    $stmt = $pdo->prepare('DELETE FROM meat_product WHERE product_id = ?');
    $stmt->execute([$deleteId]);
    redirect_with_msg('Product deleted successfully.');
}

/** -------------------------------
 *  Handle Insert/Update (POST)
 * --------------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF
    if (!isset($_POST['csrf']) || !hash_equals($CSRF, $_POST['csrf'])) {
        redirect_with_msg('Invalid request token.');
    }

    // Sanitize/validate
    $id        = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $typeIn    = $_POST['type'] ?? '';
    $type      = validate_enum_type(strtolower(trim($typeIn)));
    $breed     = trim($_POST['breed'] ?? '');
    $avgWeight = $_POST['average_weight_at_slaughter'] !== '' ? (float)$_POST['average_weight_at_slaughter'] : null;
    $feedRatio = $_POST['feed_conversion_ratio'] !== '' ? (float)$_POST['feed_conversion_ratio'] : null;
    $rearing   = $_POST['rearing_period'] !== '' ? (int)$_POST['rearing_period'] : null;

    // Basic server-side guardrails
    $errors = [];
    if (!$type) $errors[] = 'Type must be one of beef/chicken/mutton/pork/other.';
    if ($avgWeight !== null && $avgWeight < 0) $errors[] = 'Average weight cannot be negative.';
    if ($feedRatio !== null && $feedRatio < 0) $errors[] = 'Feed conversion ratio cannot be negative.';
    if ($rearing !== null && $rearing < 0) $errors[] = 'Rearing period cannot be negative.';

    if (!empty($errors)) {
        // stash in session to show in modal
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
        if ($id > 0) {
            header('Location: meat_products.php?edit=' . $id . '&showModal=1');
        } else {
            header('Location: meat_products.php?showModal=1');
        }
        exit();
    }

    if ($id > 0) {
        $sql = 'UPDATE meat_product 
                   SET type=?, breed=?, average_weight_at_slaughter=?, feed_conversion_ratio=?, rearing_period=? 
                 WHERE product_id=?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$type, $breed, $avgWeight, $feedRatio, $rearing, $id]);
        redirect_with_msg('Product updated successfully.');
    } else {
        $sql = 'INSERT INTO meat_product (type, breed, average_weight_at_slaughter, feed_conversion_ratio, rearing_period) 
                VALUES (?,?,?,?,?)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$type, $breed, $avgWeight, $feedRatio, $rearing]);
        redirect_with_msg('Product added successfully.');
    }
}

/** -------------------------------
 *  Fetch record for editing
 * --------------------------------*/
$editRecord = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM meat_product WHERE product_id = ?');
    $stmt->execute([$editId]);
    $editRecord = $stmt->fetch(PDO::FETCH_ASSOC);
}

/** -------------------------------
 *  Retrieve all records
 * --------------------------------*/
$stmt = $pdo->query('SELECT * FROM meat_product ORDER BY product_id ASC');
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare form fill (supports validation bounce-back)
$formData = [
    'id' => $editRecord['product_id'] ?? '',
    'type' => $editRecord['type'] ?? '',
    'breed' => $editRecord['breed'] ?? '',
    'average_weight_at_slaughter' => $editRecord['average_weight_at_slaughter'] ?? '',
    'feed_conversion_ratio' => $editRecord['feed_conversion_ratio'] ?? '',
    'rearing_period' => $editRecord['rearing_period'] ?? ''
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

<!-- Page-specific CSS/JS (safe to include even if header already loads Bootstrap) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simple-datatables@9.0.3/dist/style.css">

<style>
    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .card-hero {
        border: none;
        border-radius: 1.25rem;
        background: radial-gradient(1200px 600px at -10% -20%, #f4f8ff, #ffffff 60%);
        box-shadow: 0 10px 30px rgba(0, 0, 0, .06);
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
            <h2 class="mb-0 fw-bold">Meat Products</h2>
            <div class="text-body-secondary">Manage breeds, weights, FCR, and rearing periods with style.</div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#productModal">
                <i class="bi bi-plus-circle me-1"></i> Add Meat Product
            </button>
            <a href="meat_products.php" class="btn btn-outline-secondary btn-lg">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh
            </a>
        </div>
    </div>

    <div class="card card-hero mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-basket2-fill display-5 text-primary"></i>
                <div>
                    <div class="fs-5">Total Products</div>
                    <div class="display-6 fw-bold"><?= number_format(count($products)) ?></div>
                </div>
                <div class="vr mx-3"></div>
                <div>
                    <div class="fs-5">Types Present</div>
                    <?php
                    $types = array_unique(array_map(fn($r) => $r['type'], $products));
                    echo '<div class="h3 m-0">' . count($types) . '</div>';
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <span class="fw-semibold"><i class="bi bi-table me-2"></i>Product List</span>
                <div class="text-body-secondary small">Search, sort, paginate</div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="productsTable" class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Breed</th>
                            <th>Avg Weight (kg)</th>
                            <th>FCR</th>
                            <th>Rearing (days)</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $prod): ?>
                            <tr>
                                <td><?= (int)$prod['product_id']; ?></td>
                                <td>
                                    <?php
                                    $type = htmlspecialchars($prod['type']);
                                    $color = match ($type) {
                                        'beef' => 'danger',
                                        'mutton' => 'warning',
                                        'chicken' => 'success',
                                        'pork' => 'secondary',
                                        default => 'info'
                                    };
                                    echo '<span class="badge bg-' . $color . ' badge-type">' . $type . '</span>';
                                    ?>
                                </td>
                                <td><?= htmlspecialchars($prod['breed'] ?? ''); ?></td>
                                <td><?= htmlspecialchars($prod['average_weight_at_slaughter'] ?? ''); ?></td>
                                <td><?= htmlspecialchars($prod['feed_conversion_ratio'] ?? ''); ?></td>
                                <td><?= htmlspecialchars($prod['rearing_period'] ?? ''); ?></td>
                                <td class="text-end">
                                    <a class="btn btn-light btn-sm border" href="?edit=<?= (int)$prod['product_id']; ?>&showModal=1">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </a>
                                    <a class="btn btn-light btn-sm border btn-delete"
                                        href="?delete=<?= (int)$prod['product_id']; ?>&csrf=<?= urlencode($CSRF) ?>"
                                        data-name="<?= htmlspecialchars($prod['breed'] ?: $prod['type']) ?>">
                                        <i class="bi bi-trash3"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Breed</th>
                            <th>Avg Weight (kg)</th>
                            <th>FCR</th>
                            <th>Rearing (days)</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add/Edit Product -->
<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow">
            <div class="modal-header">
                <h5 class="modal-title">
                    <?= $formData['id'] ? 'Edit Meat Product' : 'Add Meat Product' ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form method="post" novalidate class="needs-validation" id="productForm">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                <input type="hidden" name="id" value="<?= htmlspecialchars($formData['id']) ?>">

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
                        <div class="col-md-4">
                            <div class="form-floating">
                                <select class="form-select" id="type" name="type" required>
                                    <?php
                                    $opts = ['beef' => 'Beef', 'chicken' => 'Chicken', 'mutton' => 'Mutton', 'pork' => 'Pork', 'other' => 'Other'];
                                    $selType = strtolower((string)$formData['type']);
                                    foreach ($opts as $val => $label) {
                                        $sel = $selType === $val ? 'selected' : '';
                                        echo "<option value=\"$val\" $sel>$label</option>";
                                    }
                                    ?>
                                </select>
                                <label for="type">Type<span class="text-danger">*</span></label>
                            </div>
                            <div class="form-hint mt-1">Matches DB enum.</div>
                        </div>

                        <div class="col-md-8">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="breed" name="breed"
                                    placeholder="Breed (optional)" value="<?= htmlspecialchars((string)$formData['breed']) ?>">
                                <label for="breed">Breed</label>
                            </div>
                            <div class="form-hint mt-1">E.g., “Local Red Chittagong”, “Cobb 500”.</div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="number" step="0.001" min="0" class="form-control" id="avgW" name="average_weight_at_slaughter"
                                    placeholder="Avg Weight" value="<?= htmlspecialchars((string)$formData['average_weight_at_slaughter']) ?>">
                                <label for="avgW">Average Weight at Slaughter (kg)</label>
                            </div>
                            <div class="form-hint mt-1">≥ 0; cattle ~200–350kg, broiler ~2kg.</div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="number" step="0.001" min="0" class="form-control" id="fcr" name="feed_conversion_ratio"
                                    placeholder="FCR" value="<?= htmlspecialchars((string)$formData['feed_conversion_ratio']) ?>">
                                <label for="fcr">Feed Conversion Ratio</label>
                            </div>
                            <div class="form-hint mt-1">Lower is better; broiler ~1.6–1.8.</div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="number" step="1" min="0" class="form-control" id="rearing" name="rearing_period"
                                    placeholder="Rearing Days" value="<?= htmlspecialchars((string)$formData['rearing_period']) ?>">
                                <label for="rearing">Rearing Period (days)</label>
                            </div>
                            <div class="form-hint mt-1">Broiler ~40–50 days; beef ~700+ days.</div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save2 me-1"></i> <?= $formData['id'] ? 'Update' : 'Add' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Toast container -->
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
    const tableEl = document.querySelector('#productsTable');
    if (tableEl) {
        const dataTable = new simpleDatatables.DataTable(tableEl, {
            searchable: true,
            fixedHeight: false,
            perPage: 10,
            perPageSelect: [10, 20, 50, 100],
            labels: {
                placeholder: "Search…",
                perPage: "{select} rows per page",
                noRows: "No products to display",
                info: "Showing {start} to {end} of {rows} products"
            }
        });
    }

    // Bootstrap form validation
    (function() {
        const form = document.getElementById('productForm');
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
            const name = this.getAttribute('data-name') || 'this product';
            Swal.fire({
                icon: 'warning',
                title: 'Delete product?',
                text: `Are you sure you want to delete “${name}”? This action cannot be undone.`,
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

    // Auto open modal if ?showModal=1
    <?php if ($autoShowModal): ?>
        const modal = new bootstrap.Modal('#productModal');
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
</script>

<?php include 'footer.php'; ?>