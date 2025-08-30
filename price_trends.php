<?php
// price_trends.php
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
    header('Location: price_trends.php?msg=' . urlencode($msg));
    exit();
}

/** -------------------------------
 *  Fetch supplier options
 * --------------------------------*/
$supplierOptions = $pdo->query('SELECT supplier_id, name FROM supplier ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);

/** -------------------------------
 *  RETAILER CRUD (with CSRF)
 * --------------------------------*/
if (isset($_GET['delete_retailer'])) {
    if (!hash_equals($CSRF, $_GET['csrf'] ?? '')) redirect_with_msg('Invalid request token.');
    $id = (int)$_GET['delete_retailer'];
    $stmt = $pdo->prepare('DELETE FROM retailer WHERE retailer_id=?');
    $stmt->execute([$id]);
    redirect_with_msg('Retailer row deleted.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['entity'] ?? '') === 'retailer') {
    if (!hash_equals($CSRF, $_POST['csrf'] ?? '')) redirect_with_msg('Invalid request token.');
    $retailerId = (int)($_POST['id'] ?? 0);
    $supplierId = (int)($_POST['supplier_id'] ?? 0);
    $price      = $_POST['retail_price_of_meat_products'] !== '' ? (float)$_POST['retail_price_of_meat_products'] : null;
    $sea        = trim($_POST['trend_seasonal_fluctuations'] ?? '');
    $reg        = trim($_POST['trend_regional_fluctuations'] ?? '');

    $errorsR = [];
    if ($supplierId <= 0) $errorsR[] = 'Supplier is required.';
    if ($price === null || $price < 0) $errorsR[] = 'Retail price must be ≥ 0.';
    if ($errorsR) {
        $_SESSION['errorsR'] = $errorsR;
        $_SESSION['formR'] = $_POST;
        header('Location: price_trends.php?showRetailer=1' . ($retailerId ? '&edit_retailer=' . $retailerId : ''));
        exit();
    }

    if ($retailerId > 0) {
        $stmt = $pdo->prepare('UPDATE retailer SET supplier_id=?, retail_price_of_meat_products=?, trend_seasonal_fluctuations=?, trend_regional_fluctuations=? WHERE retailer_id=?');
        $stmt->execute([$supplierId, $price, $sea, $reg, $retailerId]);
        redirect_with_msg('Retailer row updated.');
    } else {
        $stmt = $pdo->prepare('INSERT INTO retailer (supplier_id, retail_price_of_meat_products, trend_seasonal_fluctuations, trend_regional_fluctuations) VALUES (?,?,?,?)');
        $stmt->execute([$supplierId, $price, $sea, $reg]);
        redirect_with_msg('Retailer row added.');
    }
}
$editRetailer = null;
if (isset($_GET['edit_retailer'])) {
    $id = (int)$_GET['edit_retailer'];
    $stmt = $pdo->prepare('SELECT * FROM retailer WHERE retailer_id=?');
    $stmt->execute([$id]);
    $editRetailer = $stmt->fetch(PDO::FETCH_ASSOC);
}
$retailers = $pdo->query('SELECT r.*, s.name AS supplier_name FROM retailer r JOIN supplier s ON r.supplier_id=s.supplier_id ORDER BY r.retailer_id DESC')->fetchAll(PDO::FETCH_ASSOC);

/** -------------------------------
 *  WHOLESALER CRUD (with CSRF)
 * --------------------------------*/
if (isset($_GET['delete_wholesaler'])) {
    if (!hash_equals($CSRF, $_GET['csrf'] ?? '')) redirect_with_msg('Invalid request token.');
    $id = (int)$_GET['delete_wholesaler'];
    $stmt = $pdo->prepare('DELETE FROM wholesaler WHERE wholesaler_id=?');
    $stmt->execute([$id]);
    redirect_with_msg('Wholesaler row deleted.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['entity'] ?? '') === 'wholesaler') {
    if (!hash_equals($CSRF, $_POST['csrf'] ?? '')) redirect_with_msg('Invalid request token.');
    $whId      = (int)($_POST['id'] ?? 0);
    $supplierId = (int)($_POST['supplier_id'] ?? 0);
    $price     = $_POST['wholesale_price_of_meat_products'] !== '' ? (float)$_POST['wholesale_price_of_meat_products'] : null;
    $sea       = trim($_POST['trend_seasonal_fluctuations'] ?? '');
    $reg       = trim($_POST['trend_regional_fluctuations'] ?? '');

    $errorsW = [];
    if ($supplierId <= 0) $errorsW[] = 'Supplier is required.';
    if ($price === null || $price < 0) $errorsW[] = 'Wholesale price must be ≥ 0.';
    if ($errorsW) {
        $_SESSION['errorsW'] = $errorsW;
        $_SESSION['formW'] = $_POST;
        header('Location: price_trends.php?showWholesaler=1' . ($whId ? '&edit_wholesaler=' . $whId : ''));
        exit();
    }

    if ($whId > 0) {
        $stmt = $pdo->prepare('UPDATE wholesaler SET supplier_id=?, wholesale_price_of_meat_products=?, trend_seasonal_fluctuations=?, trend_regional_fluctuations=? WHERE wholesaler_id=?');
        $stmt->execute([$supplierId, $price, $sea, $reg, $whId]);
        redirect_with_msg('Wholesaler row updated.');
    } else {
        $stmt = $pdo->prepare('INSERT INTO wholesaler (supplier_id, wholesale_price_of_meat_products, trend_seasonal_fluctuations, trend_regional_fluctuations) VALUES (?,?,?,?)');
        $stmt->execute([$supplierId, $price, $sea, $reg]);
        redirect_with_msg('Wholesaler row added.');
    }
}
$editWholesaler = null;
if (isset($_GET['edit_wholesaler'])) {
    $id = (int)$_GET['edit_wholesaler'];
    $stmt = $pdo->prepare('SELECT * FROM wholesaler WHERE wholesaler_id=?');
    $stmt->execute([$id]);
    $editWholesaler = $stmt->fetch(PDO::FETCH_ASSOC);
}
$wholesalers = $pdo->query('SELECT w.*, s.name AS supplier_name FROM wholesaler w JOIN supplier s ON w.supplier_id=s.supplier_id ORDER BY w.wholesaler_id DESC')->fetchAll(PDO::FETCH_ASSOC);

/** -------------------------------
 *  Analytics (PHP side)
 * --------------------------------*/
function stats($arr)
{
    if (!$arr) return ['avg' => 0, 'min' => 0, 'max' => 0, 'median' => 0, 'count' => 0];
    $n = count($arr);
    $sum = array_sum($arr);
    sort($arr);
    $median = $n % 2 ? $arr[intdiv($n, 2)] : (($arr[$n / 2 - 1] + $arr[$n / 2]) / 2);
    return ['avg' => $sum / $n, 'min' => $arr[0], 'max' => $arr[$n - 1], 'median' => $median, 'count' => $n];
}
$retPrices = array_values(array_filter(array_map(fn($r) => $r['retail_price_of_meat_products'] !== null ? (float)$r['retail_price_of_meat_products'] : null, $retailers), fn($v) => $v !== null));
$whPrices  = array_values(array_filter(array_map(fn($w) => $w['wholesale_price_of_meat_products'] !== null ? (float)$w['wholesale_price_of_meat_products'] : null, $wholesalers), fn($v) => $v !== null));
$statR = stats($retPrices);
$statW = stats($whPrices);

// Supplier leaderboard (avg retail per supplier, avg wholesale per supplier, and margin)
$leader = []; // supplier => ['ret'=>avg, 'wh'=>avg]
foreach ($supplierOptions as $sup) {
    $sid = $sup['supplier_id'];
    $rList = array_values(array_filter(array_map(fn($r) => $r['supplier_id'] == $sid ? (float)$r['retail_price_of_meat_products'] : null, $retailers), fn($v) => $v !== null));
    $wList = array_values(array_filter(array_map(fn($w) => $w['supplier_id'] == $sid ? (float)$w['wholesale_price_of_meat_products'] : null, $wholesalers), fn($v) => $v !== null));
    $rAvg = $rList ? array_sum($rList) / count($rList) : null;
    $wAvg = $wList ? array_sum($wList) / count($wList) : null;
    if ($rAvg !== null || $wAvg !== null) $leader[$sup['name']] = ['ret' => $rAvg, 'wh' => $wAvg, 'margin' => ($rAvg !== null && $wAvg !== null ? $rAvg - $wAvg : null)];
}
// simple seasonal/regional keywords counter
function wordsCount($rows, $col)
{
    $counts = [];
    foreach ($rows as $r) {
        $txt = strtolower($r[$col] ?? '');
        if (!$txt) continue;
        $parts = preg_split('/[^a-zA-Z০-৯ঀ-৿-]+/u', $txt);
        foreach ($parts as $p) {
            if (mb_strlen($p) < 3) continue;
            $counts[$p] = ($counts[$p] ?? 0) + 1;
        }
    }
    arsort($counts);
    return array_slice($counts, 0, 20, true);
}
$seasonWords = wordsCount(array_merge($retailers, $wholesalers), 'trend_seasonal_fluctuations');
$regionWords = wordsCount(array_merge($retailers, $wholesalers), 'trend_regional_fluctuations');

$msg = $_GET['msg'] ?? '';
$autoShowRetailer  = isset($_GET['showRetailer']);
$autoShowWholesaler = isset($_GET['showWholesaler']);

// Repopulate forms after errors
$formR = [
    'id' => $editRetailer['retailer_id'] ?? '',
    'supplier_id' => $editRetailer['supplier_id'] ?? '',
    'retail_price_of_meat_products' => $editRetailer['retail_price_of_meat_products'] ?? '',
    'trend_seasonal_fluctuations' => $editRetailer['trend_seasonal_fluctuations'] ?? '',
    'trend_regional_fluctuations' => $editRetailer['trend_regional_fluctuations'] ?? '',
];
if (!empty($_SESSION['formR'])) {
    $formR = array_merge($formR, $_SESSION['formR']);
    unset($_SESSION['formR']);
}
$errorsR = $_SESSION['errorsR'] ?? [];
unset($_SESSION['errorsR']);

$formW = [
    'id' => $editWholesaler['wholesaler_id'] ?? '',
    'supplier_id' => $editWholesaler['supplier_id'] ?? '',
    'wholesale_price_of_meat_products' => $editWholesaler['wholesale_price_of_meat_products'] ?? '',
    'trend_seasonal_fluctuations' => $editWholesaler['trend_seasonal_fluctuations'] ?? '',
    'trend_regional_fluctuations' => $editWholesaler['trend_regional_fluctuations'] ?? '',
];
if (!empty($_SESSION['formW'])) {
    $formW = array_merge($formW, $_SESSION['formW']);
    unset($_SESSION['formW']);
}
$errorsW = $_SESSION['errorsW'] ?? [];
unset($_SESSION['errorsW']);
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

    .form-hint {
        font-size: .85rem;
        color: var(--bs-secondary-color)
    }
</style>

<div class="container-fluid py-3">
    <div class="page-header">
        <div>
            <h2 class="mb-0 fw-bold">Price Trends</h2>
            <div class="text-body-secondary">Retail vs Wholesale prices, supplier margins & trend signals.</div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#retailerModal"><i class="bi bi-shop me-1"></i> Add Retail</button>
            <button class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#wholesalerModal"><i class="bi bi-box-seam me-1"></i> Add Wholesale</button>
            <a href="price_trends.php" class="btn btn-outline-secondary btn-lg"><i class="bi bi-arrow-clockwise me-1"></i> Refresh</a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row g-3 kpi">
        <div class="col-lg-3">
            <div class="glass p-4 h-100 d-flex align-items-center gap-3">
                <div class="icon"><i class="bi bi-shop"></i></div>
                <div>
                    <div class="text-body-secondary">Retail Avg / Median</div>
                    <div class="h2 fw-bold mb-0">৳ <?= number_format($statR['avg'], 2) ?> / <?= number_format($statR['median'], 2) ?></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="glass p-4 h-100 d-flex align-items-center gap-3">
                <div class="icon" style="background:#ecfeff;color:#06b6d4;"><i class="bi bi-box-seam"></i></div>
                <div>
                    <div class="text-body-secondary">Wholesale Avg / Median</div>
                    <div class="h2 fw-bold mb-0">৳ <?= number_format($statW['avg'], 2) ?> / <?= number_format($statW['median'], 2) ?></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="glass p-4 h-100 d-flex align-items-center gap-3">
                <div class="icon" style="background:#fff7ed;color:#f97316;"><i class="bi bi-graph-up"></i></div>
                <div>
                    <div class="text-body-secondary">Retail Min–Max</div>
                    <div class="h2 fw-bold mb-0">৳ <?= number_format($statR['min'], 2) ?>–<?= number_format($statR['max'], 2) ?></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="glass p-4 h-100 d-flex align-items-center gap-3">
                <div class="icon" style="background:#f0feff;color:#0ea5e9;"><i class="bi bi-graph-down"></i></div>
                <div>
                    <div class="text-body-secondary">Wholesale Min–Max</div>
                    <div class="h2 fw-bold mb-0">৳ <?= number_format($statW['min'], 2) ?>–<?= number_format($statW['max'], 2) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row g-3 mt-1">
        <div class="col-lg-6">
            <div class="glass p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><i class="bi bi-trophy me-2"></i>Supplier Leaderboard (Avg Price)</h5>
                    <span class="small text-body-secondary">Retail vs Wholesale</span>
                </div>
                <canvas id="leaderChart" height="180"></canvas>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="glass p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><i class="bi bi-scatter-chart me-2"></i>Retail vs Wholesale (Margin)</h5>
                    <span class="small text-body-secondary">One point per supplier</span>
                </div>
                <canvas id="marginChart" height="180"></canvas>
                <div class="small text-body-secondary mt-2">Margin = Retail − Wholesale (৳/kg)</div>
            </div>
        </div>
    </div>

    <!-- Trend Keywords -->
    <div class="row g-3 mt-1">
        <div class="col-lg-6">
            <div class="glass p-3">
                <h6 class="mb-2"><i class="bi bi-brightness-high me-2"></i>Seasonal Trend Keywords</h6>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($seasonWords as $w => $c): ?>
                        <span class="badge text-bg-light border"><?= htmlspecialchars($w) ?> <span class="text-secondary">(<?= $c ?>)</span></span>
                    <?php endforeach;
                    if (!$seasonWords) echo '<span class="text-body-secondary">No data</span>'; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="glass p-3">
                <h6 class="mb-2"><i class="bi bi-geo-alt me-2"></i>Regional Trend Keywords</h6>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($regionWords as $w => $c): ?>
                        <span class="badge text-bg-light border"><?= htmlspecialchars($w) ?> <span class="text-secondary">(<?= $c ?>)</span></span>
                    <?php endforeach;
                    if (!$regionWords) echo '<span class="text-body-secondary">No data</span>'; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Retailers Table -->
    <div id="retailBox" class="glass p-3 mt-3">
        <div class="d-flex align-items-center justify-content-between">
            <span class="fw-semibold"><i class="bi bi-shop me-2"></i>Retail Prices</span>
            <div class="small text-body-secondary">Search, sort, export</div>
        </div>
        <div class="table-responsive mt-2">
            <table id="retailTable" class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Supplier</th>
                        <th>Retail Price (৳)</th>
                        <th>Seasonal Trend</th>
                        <th>Regional Trend</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($retailers as $ret): ?>
                        <tr>
                            <td><?= (int)$ret['retailer_id'] ?></td>
                            <td><?= htmlspecialchars($ret['supplier_name']) ?></td>
                            <td><?= htmlspecialchars($ret['retail_price_of_meat_products']) ?></td>
                            <td class="text-truncate" style="max-width:260px;"><code class="small"><?= htmlspecialchars($ret['trend_seasonal_fluctuations'] ?? '') ?></code></td>
                            <td class="text-truncate" style="max-width:260px;"><code class="small"><?= htmlspecialchars($ret['trend_regional_fluctuations'] ?? '') ?></code></td>
                            <td class="text-end">
                                <a class="btn btn-light btn-sm border" href="?edit_retailer=<?= (int)$ret['retailer_id'] ?>&showRetailer=1"><i class="bi bi-pencil-square"></i> Edit</a>
                                <a class="btn btn-light btn-sm border btn-del-ret" href="?delete_retailer=<?= (int)$ret['retailer_id'] ?>&csrf=<?= urlencode($CSRF) ?>" data-name="Retail #<?= (int)$ret['retailer_id'] ?>"><i class="bi bi-trash3"></i> Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th>ID</th>
                        <th>Supplier</th>
                        <th>Retail Price (৳)</th>
                        <th>Seasonal Trend</th>
                        <th>Regional Trend</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Wholesalers Table -->
    <div id="wholeBox" class="glass p-3 mt-3 mb-4">
        <div class="d-flex align-items-center justify-content-between">
            <span class="fw-semibold"><i class="bi bi-box-seam me-2"></i>Wholesale Prices</span>
            <div class="small text-body-secondary">Search, sort, export</div>
        </div>
        <div class="table-responsive mt-2">
            <table id="wholeTable" class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Supplier</th>
                        <th>Wholesale Price (৳)</th>
                        <th>Seasonal Trend</th>
                        <th>Regional Trend</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($wholesalers as $wh): ?>
                        <tr>
                            <td><?= (int)$wh['wholesaler_id'] ?></td>
                            <td><?= htmlspecialchars($wh['supplier_name']) ?></td>
                            <td><?= htmlspecialchars($wh['wholesale_price_of_meat_products']) ?></td>
                            <td class="text-truncate" style="max-width:260px;"><code class="small"><?= htmlspecialchars($wh['trend_seasonal_fluctuations'] ?? '') ?></code></td>
                            <td class="text-truncate" style="max-width:260px;"><code class="small"><?= htmlspecialchars($wh['trend_regional_fluctuations'] ?? '') ?></code></td>
                            <td class="text-end">
                                <a class="btn btn-light btn-sm border" href="?edit_wholesaler=<?= (int)$wh['wholesaler_id'] ?>&showWholesaler=1"><i class="bi bi-pencil-square"></i> Edit</a>
                                <a class="btn btn-light btn-sm border btn-del-wh" href="?delete_wholesaler=<?= (int)$wh['wholesaler_id'] ?>&csrf=<?= urlencode($CSRF) ?>" data-name="Wholesale #<?= (int)$wh['wholesaler_id'] ?>"><i class="bi bi-trash3"></i> Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th>ID</th>
                        <th>Supplier</th>
                        <th>Wholesale Price (৳)</th>
                        <th>Seasonal Trend</th>
                        <th>Regional Trend</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Retailer Modal -->
<div class="modal fade" id="retailerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow">
            <div class="modal-header">
                <h5 class="modal-title"><?= $formR['id'] ? 'Edit Retail Price' : 'Add Retail Price' ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" class="needs-validation" novalidate>
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                <input type="hidden" name="entity" value="retailer">
                <input type="hidden" name="id" value="<?= htmlspecialchars((string)$formR['id']) ?>">
                <div class="modal-body">
                    <?php if (!empty($errorsR)): ?><div class="alert alert-danger">
                            <ul class="mb-0"><?php foreach ($errorsR as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul>
                        </div><?php endif; ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <select class="form-select" name="supplier_id" id="r_supplier" required>
                                    <option value="">Select Supplier</option>
                                    <?php foreach ($supplierOptions as $sup): $sel = ((string)$formR['supplier_id'] === (string)$sup['supplier_id']) ? 'selected' : ''; ?>
                                        <option value="<?= (int)$sup['supplier_id'] ?>" <?= $sel ?>><?= htmlspecialchars($sup['name']) ?></option>
                                    <?php endforeach; ?>
                                </select><label for="r_supplier">Supplier<span class="text-danger">*</span></label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="number" step="0.01" min="0" class="form-control" name="retail_price_of_meat_products" id="r_price" value="<?= htmlspecialchars((string)$formR['retail_price_of_meat_products']) ?>" required>
                                <label for="r_price">Retail Price (৳/kg)<span class="text-danger">*</span></label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="trend_seasonal_fluctuations" id="r_season" placeholder="Eid surge, winter dip…" value="<?= htmlspecialchars((string)$formR['trend_seasonal_fluctuations']) ?>">
                                <label for="r_season">Seasonal Trend (keywords)</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="trend_regional_fluctuations" id="r_region" placeholder="Dhaka premium, coastal risk…" value="<?= htmlspecialchars((string)$formR['trend_regional_fluctuations']) ?>">
                                <label for="r_region">Regional Trend (keywords)</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i> Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save2 me-1"></i> <?= $formR['id'] ? 'Update' : 'Add' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Wholesaler Modal -->
<div class="modal fade" id="wholesalerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow">
            <div class="modal-header">
                <h5 class="modal-title"><?= $formW['id'] ? 'Edit Wholesale Price' : 'Add Wholesale Price' ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" class="needs-validation" novalidate>
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                <input type="hidden" name="entity" value="wholesaler">
                <input type="hidden" name="id" value="<?= htmlspecialchars((string)$formW['id']) ?>">
                <div class="modal-body">
                    <?php if (!empty($errorsW)): ?><div class="alert alert-danger">
                            <ul class="mb-0"><?php foreach ($errorsW as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul>
                        </div><?php endif; ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <select class="form-select" name="supplier_id" id="w_supplier" required>
                                    <option value="">Select Supplier</option>
                                    <?php foreach ($supplierOptions as $sup): $sel = ((string)$formW['supplier_id'] === (string)$sup['supplier_id']) ? 'selected' : ''; ?>
                                        <option value="<?= (int)$sup['supplier_id'] ?>" <?= $sel ?>><?= htmlspecialchars($sup['name']) ?></option>
                                    <?php endforeach; ?>
                                </select><label for="w_supplier">Supplier<span class="text-danger">*</span></label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="number" step="0.01" min="0" class="form-control" name="wholesale_price_of_meat_products" id="w_price" value="<?= htmlspecialchars((string)$formW['wholesale_price_of_meat_products']) ?>" required>
                                <label for="w_price">Wholesale Price (৳/kg)<span class="text-danger">*</span></label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="trend_seasonal_fluctuations" id="w_season" placeholder="Eid surge, winter dip…" value="<?= htmlspecialchars((string)$formW['trend_seasonal_fluctuations']) ?>">
                                <label for="w_season">Seasonal Trend (keywords)</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="trend_regional_fluctuations" id="w_region" placeholder="Dhaka premium, coastal risk…" value="<?= htmlspecialchars((string)$formW['trend_regional_fluctuations']) ?>">
                                <label for="w_region">Regional Trend (keywords)</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i> Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-save2 me-1"></i> <?= $formW['id'] ? 'Update' : 'Add' ?></button>
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
    // DataTables + CSV export buttons
    function wireTable(tableId, containerId) {
        const el = document.querySelector(tableId);
        if (!el) return null;
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
        const cont = document.querySelector(containerId);
        const btn = document.createElement('button');
        btn.className = 'btn btn-outline-primary btn-sm ms-2';
        btn.innerHTML = '<i class="bi bi-download me-1"></i>Export CSV';
        cont.querySelector('.small').appendChild(btn);
        btn.addEventListener('click', () => dt.export({
            type: 'csv',
            download: true,
            selection: true
        }));
        return dt;
    }
    wireTable('#retailTable', '#retailBox');
    wireTable('#wholeTable', '#wholeBox');

    // Validation
    document.querySelectorAll('form.needs-validation').forEach(f => {
        f.addEventListener('submit', e => {
            if (!f.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            f.classList.add('was-validated');
        });
    });

    // Delete confirmations
    document.querySelectorAll('.btn-del-ret').forEach(a => {
        a.addEventListener('click', e => {
            e.preventDefault();
            const url = a.getAttribute('href');
            const name = a.dataset.name || 'this record';
            Swal.fire({
                    icon: 'warning',
                    title: 'Delete retail price?',
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
    document.querySelectorAll('.btn-del-wh').forEach(a => {
        a.addEventListener('click', e => {
            e.preventDefault();
            const url = a.getAttribute('href');
            const name = a.dataset.name || 'this record';
            Swal.fire({
                    icon: 'warning',
                    title: 'Delete wholesale price?',
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

    // Auto-open modals if bouncing back
    <?php if ($autoShowRetailer): ?> new bootstrap.Modal('#retailerModal').show();
    <?php endif; ?>
    <?php if ($autoShowWholesaler): ?> new bootstrap.Modal('#wholesalerModal').show();
    <?php endif; ?>

    // Toast
    <?php if (!empty($msg)): ?>
        const toast = new bootstrap.Toast(document.getElementById('liveToast'), {
            delay: 2500
        });
        document.getElementById('toastMsg').textContent = <?= json_encode($msg) ?>;
        toast.show();
    <?php endif; ?>

    // Charts: Leaderboard & Margin
    const leader = <?= json_encode($leader, JSON_UNESCAPED_UNICODE) ?>;
    const names = Object.keys(leader);
    const retail = names.map(n => leader[n].ret ?? null);
    const whole = names.map(n => leader[n].wh ?? null);
    const margin = names.map(n => (leader[n].margin ?? null));

    const ctxLead = document.getElementById('leaderChart');
    const ctxMargin = document.getElementById('marginChart');

    if (ctxLead && names.length) {
        new Chart(ctxLead, {
            type: 'bar',
            data: {
                labels: names,
                datasets: [{
                        label: 'Retail (৳/kg)',
                        data: retail
                    },
                    {
                        label: 'Wholesale (৳/kg)',
                        data: whole
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: c => `${c.dataset.label}: ৳ ${Number(c.raw).toLocaleString(undefined,{maximumFractionDigits:2})}`
                        }
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            callback: v => '৳ ' + Number(v).toLocaleString()
                        }
                    }
                }
            }
        });
    }

    if (ctxMargin && names.length) {
        // Build scatter points: x = Wholesale, y = Retail (per supplier)
        const points = names.map((n, i) => {
            const x = whole[i];
            const y = retail[i];
            return (x != null && y != null) ? {
                x,
                y,
                supplier: n
            } : null;
        }).filter(Boolean);

        // Optional parity line y = x
        const allPrices = points.flatMap(p => [p.x, p.y]);
        const minVal = Math.min.apply(null, allPrices);
        const maxVal = Math.max.apply(null, allPrices);
        const parity = [{
            x: minVal,
            y: minVal
        }, {
            x: maxVal,
            y: maxVal
        }];

        new Chart(ctxMargin, {
            type: 'scatter',
            data: {
                datasets: [{
                        label: 'Suppliers',
                        data: points,
                        parsing: false,
                        pointRadius: 4,
                        borderWidth: 1
                    },
                    {
                        label: 'Parity (Retail = Wholesale)',
                        type: 'line',
                        data: parity,
                        parsing: false,
                        pointRadius: 0,
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => {
                                const p = ctx.raw;
                                if (p && typeof p === 'object' && 'supplier' in p) {
                                    return `${p.supplier}: Wholesale ৳ ${Number(p.x).toLocaleString(undefined,{maximumFractionDigits:2})}, Retail ৳ ${Number(p.y).toLocaleString(undefined,{maximumFractionDigits:2})}`;
                                }
                                return `${ctx.dataset.label}: ৳ ${Number(ctx.parsed.y ?? ctx.parsed.x).toLocaleString()}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Wholesale (৳/kg)'
                        },
                        ticks: {
                            callback: v => '৳ ' + Number(v).toLocaleString()
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Retail (৳/kg)'
                        },
                        ticks: {
                            callback: v => '৳ ' + Number(v).toLocaleString()
                        }
                    }
                }
            }
        });
    }
</script>

<?php include 'footer.php'; ?>