<?php
// supply_demand.php
session_start();
require 'config.php';
include 'header.php';

/** --------------------------------
 *  CSRF
 * --------------------------------*/
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
$CSRF = $_SESSION['csrf_token'];

function redirect_with_msg($msg)
{
    header('Location: supply_demand.php?msg=' . urlencode($msg));
    exit();
}

/** --------------------------------
 *  Options
 * --------------------------------*/
$customerOptions = $pdo->query('SELECT customer_id, name FROM customer ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$supplierOptions = $pdo->query('SELECT supplier_id, name FROM supplier ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$houseOptions    = $pdo->query('SELECT slaughter_house_id, name FROM slaughter_house ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$STATUSES = ['pending', 'confirmed', 'in_transit', 'delivered', 'cancelled'];

/** --------------------------------
 *  ORDER CRUD (with CSRF)
 * --------------------------------*/
if (isset($_GET['delete_order'])) {
    if (!hash_equals($CSRF, $_GET['csrf'] ?? '')) redirect_with_msg('Invalid request token.');
    $id = (int)$_GET['delete_order'];
    $stmt = $pdo->prepare('DELETE FROM `order` WHERE order_id = ?');
    $stmt->execute([$id]);
    redirect_with_msg('Order deleted.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['entity'] ?? '') === 'order') {
    if (!hash_equals($CSRF, $_POST['csrf'] ?? '')) redirect_with_msg('Invalid request token.');
    $orderId     = (int)($_POST['id'] ?? 0);
    $customerId  = (int)($_POST['customer_id'] ?? 0);
    $quantity    = $_POST['order_quantity'] !== '' ? (float)$_POST['order_quantity'] : null;
    $orderDate   = $_POST['order_date'] ?? null;
    $deliveryDate = $_POST['delivery_date'] !== '' ? $_POST['delivery_date'] : null;
    $status      = $_POST['order_status'] ?? 'pending';

    $errors = [];
    if ($customerId <= 0) $errors[] = 'Customer is required.';
    if ($quantity === null || $quantity < 0) $errors[] = 'Order quantity (kg) must be ≥ 0.';
    if (!$orderDate) $errors[] = 'Order date is required.';
    if (!in_array($status, $STATUSES, true)) $errors[] = 'Invalid status.';

    if ($errors) {
        $_SESSION['form_errors_order'] = $errors;
        $_SESSION['form_data_order'] = $_POST;
        header('Location: supply_demand.php#orders&showOrder=1' . ($orderId ? '&edit_order=' . $orderId : ''));
        exit();
    }

    if ($orderId > 0) {
        $sql = 'UPDATE `order` SET customer_id=?, order_quantity=?, order_date=?, delivery_date=?, order_status=? WHERE order_id=?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$customerId, $quantity, $orderDate, $deliveryDate, $status, $orderId]);
        redirect_with_msg('Order updated.');
    } else {
        $sql = 'INSERT INTO `order` (customer_id, order_quantity, order_date, delivery_date, order_status) VALUES (?,?,?,?,?)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$customerId, $quantity, $orderDate, $deliveryDate, $status]);
        redirect_with_msg('Order added.');
    }
}

$editOrder = null;
if (isset($_GET['edit_order'])) {
    $id = (int)$_GET['edit_order'];
    $stmt = $pdo->prepare('SELECT * FROM `order` WHERE order_id=?');
    $stmt->execute([$id]);
    $editOrder = $stmt->fetch(PDO::FETCH_ASSOC);
}

$orders = $pdo->query('SELECT o.*, c.name AS customer_name 
                         FROM `order` o 
                         JOIN customer c ON o.customer_id=c.customer_id 
                     ORDER BY o.order_id DESC')->fetchAll(PDO::FETCH_ASSOC);

/** --------------------------------
 *  DELIVERY CRUD (with CSRF)
 * --------------------------------*/
if (isset($_GET['delete_delivery'])) {
    if (!hash_equals($CSRF, $_GET['csrf'] ?? '')) redirect_with_msg('Invalid request token.');
    $id = (int)$_GET['delete_delivery'];
    $stmt = $pdo->prepare('DELETE FROM delivery WHERE delivery_id = ?');
    $stmt->execute([$id]);
    redirect_with_msg('Delivery deleted.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['entity'] ?? '') === 'delivery') {
    if (!hash_equals($CSRF, $_POST['csrf'] ?? '')) redirect_with_msg('Invalid request token.');
    $deliveryId   = (int)($_POST['id'] ?? 0);
    $orderQuantity = $_POST['order_quantity'] !== '' ? (float)$_POST['order_quantity'] : null;
    $pricePerUnit = $_POST['price_per_unit'] !== '' ? (float)$_POST['price_per_unit'] : null;
    $orderDate    = $_POST['order_date'] ?? null;
    $deliveryDate = $_POST['delivery_date'] !== '' ? $_POST['delivery_date'] : null;
    $status       = $_POST['order_status'] ?? 'pending';
    $houseId      = (int)($_POST['slaughter_house_id'] ?? 0);
    $supplierId   = (int)($_POST['supplier_id'] ?? 0);

    $errors = [];
    if ($orderQuantity === null || $orderQuantity < 0) $errors[] = 'Delivery quantity (kg) must be ≥ 0.';
    if ($pricePerUnit === null || $pricePerUnit < 0) $errors[] = 'Price per unit must be ≥ 0.';
    if (!$orderDate) $errors[] = 'Order date is required.';
    if (!in_array($status, $STATUSES, true)) $errors[] = 'Invalid status.';
    if ($houseId <= 0) $errors[] = 'Slaughter house is required.';
    if ($supplierId <= 0) $errors[] = 'Supplier is required.';

    if ($errors) {
        $_SESSION['form_errors_delivery'] = $errors;
        $_SESSION['form_data_delivery'] = $_POST;
        header('Location: supply_demand.php#deliveries&showDelivery=1' . ($deliveryId ? '&edit_delivery=' . $deliveryId : ''));
        exit();
    }

    if ($deliveryId > 0) {
        $sql = 'UPDATE delivery 
               SET order_quantity=?, price_per_unit=?, order_date=?, delivery_date=?, order_status=?, slaughter_house_id=?, supplier_id=? 
             WHERE delivery_id=?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$orderQuantity, $pricePerUnit, $orderDate, $deliveryDate, $status, $houseId, $supplierId, $deliveryId]);
        redirect_with_msg('Delivery updated.');
    } else {
        $sql = 'INSERT INTO delivery (order_quantity, price_per_unit, order_date, delivery_date, order_status, slaughter_house_id, supplier_id) 
            VALUES (?,?,?,?,?,?,?)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$orderQuantity, $pricePerUnit, $orderDate, $deliveryDate, $status, $houseId, $supplierId]);
        redirect_with_msg('Delivery added.');
    }
}

$editDelivery = null;
if (isset($_GET['edit_delivery'])) {
    $id = (int)$_GET['edit_delivery'];
    $stmt = $pdo->prepare('SELECT * FROM delivery WHERE delivery_id=?');
    $stmt->execute([$id]);
    $editDelivery = $stmt->fetch(PDO::FETCH_ASSOC);
}

$deliveries = $pdo->query('SELECT d.*, s.name AS supplier_name, h.name AS house_name 
                             FROM delivery d 
                             JOIN supplier s ON d.supplier_id=s.supplier_id 
                             JOIN slaughter_house h ON d.slaughter_house_id=h.slaughter_house_id 
                         ORDER BY d.delivery_id DESC')->fetchAll(PDO::FETCH_ASSOC);

/** --------------------------------
 *  Analytics prep (PHP)
 *  - Demand = orders.order_quantity
 *  - Supply = deliveries.order_quantity
 *  - Revenue = sum(deliveries.order_quantity * price_per_unit) [delivered only]
 * --------------------------------*/
function ym($date)
{
    return $date ? substr($date, 0, 7) : null;
}

$totalDemand = 0.0;
$totalSupply = 0.0;
$revenue = 0.0;
$fulfillDeliveredQty = 0.0;
$ordersByMonth = [];
$deliveriesByMonth = [];
$statusMixOrders = array_fill_keys($STATUSES, 0);
$statusMixDeliveries = array_fill_keys($STATUSES, 0);
$ppuList = []; // for basic stats

foreach ($orders as $o) {
    $q = (float)$o['order_quantity'];
    $totalDemand += $q;
    $m = ym($o['order_date']);
    if ($m) $ordersByMonth[$m] = ($ordersByMonth[$m] ?? 0) + $q;
    $statusMixOrders[$o['order_status']] = ($statusMixOrders[$o['order_status']] ?? 0) + 1;
}

foreach ($deliveries as $d) {
    $q = (float)$d['order_quantity'];
    $totalSupply += $q;
    $m = ym($d['delivery_date'] ?: $d['order_date']);
    if ($m) $deliveriesByMonth[$m] = ($deliveriesByMonth[$m] ?? 0) + $q;
    $statusMixDeliveries[$d['order_status']] = ($statusMixDeliveries[$d['order_status']] ?? 0) + 1;
    if ($d['order_status'] === 'delivered') {
        $revenue += $q * (float)$d['price_per_unit'];
        $fulfillDeliveredQty += $q;
    }
}
ksort($ordersByMonth);
ksort($deliveriesByMonth);

$fulfillmentRate = $totalDemand > 0 ? min(100, ($fulfillDeliveredQty / $totalDemand) * 100) : 0;

// simple avg/median for price
foreach ($deliveries as $d) {
    if ($d['price_per_unit'] !== null) $ppuList[] = (float)$d['price_per_unit'];
}
sort($ppuList);
$avgPPU = $ppuList ? array_sum($ppuList) / count($ppuList) : 0;
$medianPPU = 0;
if ($ppuList) {
    $mid = intdiv(count($ppuList), 2);
    $medianPPU = count($ppuList) % 2 ? $ppuList[$mid] : (($ppuList[$mid - 1] + $ppuList[$mid]) / 2);
}

/** --------------------------------
 *  Repopulate forms after errors
 * --------------------------------*/
$orderFormData = [
    'id' => $editOrder['order_id'] ?? '',
    'customer_id' => $editOrder['customer_id'] ?? '',
    'order_quantity' => $editOrder['order_quantity'] ?? '',
    'order_date' => $editOrder['order_date'] ?? '',
    'delivery_date' => $editOrder['delivery_date'] ?? '',
    'order_status' => $editOrder['order_status'] ?? 'pending',
];
if (!empty($_SESSION['form_data_order'])) {
    $orderFormData = array_merge($orderFormData, $_SESSION['form_data_order']);
    unset($_SESSION['form_data_order']);
}
$orderErrors = $_SESSION['form_errors_order'] ?? [];
unset($_SESSION['form_errors_order']);

$deliveryFormData = [
    'id' => $editDelivery['delivery_id'] ?? '',
    'order_quantity' => $editDelivery['order_quantity'] ?? '',
    'price_per_unit' => $editDelivery['price_per_unit'] ?? '',
    'order_date' => $editDelivery['order_date'] ?? '',
    'delivery_date' => $editDelivery['delivery_date'] ?? '',
    'order_status' => $editDelivery['order_status'] ?? 'pending',
    'slaughter_house_id' => $editDelivery['slaughter_house_id'] ?? '',
    'supplier_id' => $editDelivery['supplier_id'] ?? '',
];
if (!empty($_SESSION['form_data_delivery'])) {
    $deliveryFormData = array_merge($deliveryFormData, $_SESSION['form_data_delivery']);
    unset($_SESSION['form_data_delivery']);
}
$deliveryErrors = $_SESSION['form_errors_delivery'] ?? [];
unset($_SESSION['form_errors_delivery']);

$msg = $_GET['msg'] ?? '';
$autoShowOrder   = isset($_GET['showOrder']);
$autoShowDelivery = isset($_GET['showDelivery']);
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

    .badge-status {
        text-transform: capitalize
    }

    .form-hint {
        font-size: .85rem;
        color: var(--bs-secondary-color)
    }
</style>

<div class="container-fluid py-3">
    <div class="page-header">
        <div>
            <h2 class="mb-0 fw-bold">Supply & Demand</h2>
            <div class="text-body-secondary">Orders (demand) versus Deliveries (supply), with analytics.</div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#orderModal"><i class="bi bi-plus-circle me-1"></i> Add Order</button>
            <button class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#deliveryModal"><i class="bi bi-truck me-1"></i> Add Delivery</button>
            <a href="supply_demand.php" class="btn btn-outline-secondary btn-lg"><i class="bi bi-arrow-clockwise me-1"></i> Refresh</a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row g-3 kpi">
        <div class="col-md-3">
            <div class="glass p-4 h-100 d-flex align-items-center gap-3">
                <div class="icon"><i class="bi bi-basket"></i></div>
                <div>
                    <div class="text-body-secondary">Total Demand (kg)</div>
                    <div class="h2 fw-bold mb-0"><?= number_format($totalDemand, 2) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass p-4 h-100 d-flex align-items-center gap-3">
                <div class="icon" style="background:#f0feff;color:#0ea5e9;"><i class="bi bi-truck"></i></div>
                <div>
                    <div class="text-body-secondary">Total Supply (kg)</div>
                    <div class="h2 fw-bold mb-0"><?= number_format($totalSupply, 2) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass p-4 h-100 d-flex align-items-center gap-3">
                <div class="icon" style="background:#fff7ed;color:#f97316;"><i class="bi bi-graph-up"></i></div>
                <div>
                    <div class="text-body-secondary">Revenue (delivered)</div>
                    <div class="h2 fw-bold mb-0">৳ <?= number_format($revenue, 2) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass p-4 h-100 d-flex align-items-center gap-3">
                <div class="icon" style="background:#ecfeff;color:#06b6d4;"><i class="bi bi-percent"></i></div>
                <div>
                    <div class="text-body-secondary">Fulfillment Rate</div>
                    <div class="h2 fw-bold mb-0"><?= number_format($fulfillmentRate, 1) ?>%</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters + Charts -->
    <div class="row g-3 mt-1">
        <div class="col-lg-8">
            <div class="glass p-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h5 class="mb-0"><i class="bi bi-bar-chart-line me-2"></i>Demand vs Supply (Monthly)</h5>
                    <div class="d-flex align-items-center gap-2">
                        <input type="month" id="fromMonth" class="form-control form-control-sm">
                        <span>–</span>
                        <input type="month" id="toMonth" class="form-control form-control-sm">
                        <select id="statusFilter" class="form-select form-select-sm">
                            <option value="">All statuses</option>
                            <?php foreach ($STATUSES as $st) echo '<option value="' . $st . '">' . ucfirst($st) . '</option>'; ?>
                        </select>
                        <button class="btn btn-sm btn-outline-secondary" id="applyFilter"><i class="bi bi-funnel"></i></button>
                    </div>
                </div>
                <canvas id="dsChart" height="180"></canvas>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="glass p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Status Mix</h5>
                    <span class="small text-body-secondary">Orders vs Deliveries</span>
                </div>
                <canvas id="statusChart" height="180"></canvas>
                <div class="small text-body-secondary mt-2">Avg PPU: ৳ <?= number_format($avgPPU, 2) ?> · Median: ৳ <?= number_format($medianPPU, 2) ?></div>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div id="orders" class="glass p-3 mt-3">
        <div class="d-flex align-items-center justify-content-between">
            <span class="fw-semibold"><i class="bi bi-list-ul me-2"></i>Orders</span>
            <div class="small text-body-secondary">Search, sort, export</div>
        </div>
        <div class="table-responsive mt-2">
            <table id="ordersTable" class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Quantity (kg)</th>
                        <th>Order Date</th>
                        <th>Delivery Date</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o):
                        $color = match ($o['order_status']) {
                            'pending' => 'secondary',
                            'confirmed' => 'info',
                            'in_transit' => 'warning',
                            'delivered' => 'success',
                            'cancelled' => 'danger',
                            default => 'secondary'
                        };
                    ?>
                        <tr>
                            <td><?= (int)$o['order_id'] ?></td>
                            <td><?= htmlspecialchars($o['customer_name']) ?></td>
                            <td><?= htmlspecialchars($o['order_quantity']) ?></td>
                            <td><?= htmlspecialchars($o['order_date']) ?></td>
                            <td><?= htmlspecialchars($o['delivery_date'] ?? '') ?></td>
                            <td><span class="badge bg-<?= $color ?> badge-status"><?= htmlspecialchars($o['order_status']) ?></span></td>
                            <td class="text-end">
                                <a class="btn btn-light btn-sm border" href="?edit_order=<?= (int)$o['order_id'] ?>&showOrder=1"><i class="bi bi-pencil-square"></i> Edit</a>
                                <a class="btn btn-light btn-sm border btn-del-order" href="?delete_order=<?= (int)$o['order_id'] ?>&csrf=<?= urlencode($CSRF) ?>" data-name="Order #<?= (int)$o['order_id'] ?>"><i class="bi bi-trash3"></i> Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Quantity (kg)</th>
                        <th>Order Date</th>
                        <th>Delivery Date</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Deliveries Table -->
    <div id="deliveries" class="glass p-3 mt-3">
        <div class="d-flex align-items-center justify-content-between">
            <span class="fw-semibold"><i class="bi bi-truck me-2"></i>Deliveries</span>
            <div class="small text-body-secondary">Search, sort, export</div>
        </div>
        <div class="table-responsive mt-2">
            <table id="deliveriesTable" class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Quantity (kg)</th>
                        <th>Price/Unit</th>
                        <th>Order Date</th>
                        <th>Delivery Date</th>
                        <th>Status</th>
                        <th>Slaughter House</th>
                        <th>Supplier</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deliveries as $d):
                        $color = match ($d['order_status']) {
                            'pending' => 'secondary',
                            'confirmed' => 'info',
                            'in_transit' => 'warning',
                            'delivered' => 'success',
                            'cancelled' => 'danger',
                            default => 'secondary'
                        };
                    ?>
                        <tr>
                            <td><?= (int)$d['delivery_id'] ?></td>
                            <td><?= htmlspecialchars($d['order_quantity']) ?></td>
                            <td><?= htmlspecialchars($d['price_per_unit']) ?></td>
                            <td><?= htmlspecialchars($d['order_date']) ?></td>
                            <td><?= htmlspecialchars($d['delivery_date'] ?? '') ?></td>
                            <td><span class="badge bg-<?= $color ?> badge-status"><?= htmlspecialchars($d['order_status']) ?></span></td>
                            <td><?= htmlspecialchars($d['house_name']) ?></td>
                            <td><?= htmlspecialchars($d['supplier_name']) ?></td>
                            <td class="text-end">
                                <a class="btn btn-light btn-sm border" href="?edit_delivery=<?= (int)$d['delivery_id'] ?>&showDelivery=1"><i class="bi bi-pencil-square"></i> Edit</a>
                                <a class="btn btn-light btn-sm border btn-del-delivery" href="?delete_delivery=<?= (int)$d['delivery_id'] ?>&csrf=<?= urlencode($CSRF) ?>" data-name="Delivery #<?= (int)$d['delivery_id'] ?>"><i class="bi bi-trash3"></i> Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th>ID</th>
                        <th>Quantity (kg)</th>
                        <th>Price/Unit</th>
                        <th>Order Date</th>
                        <th>Delivery Date</th>
                        <th>Status</th>
                        <th>Slaughter House</th>
                        <th>Supplier</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Order Modal -->
<div class="modal fade" id="orderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow">
            <div class="modal-header">
                <h5 class="modal-title"><?= $orderFormData['id'] ? 'Edit Order' : 'Add Order' ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" class="needs-validation" novalidate>
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                <input type="hidden" name="entity" value="order">
                <input type="hidden" name="id" value="<?= htmlspecialchars((string)$orderFormData['id']) ?>">
                <div class="modal-body">
                    <?php if ($orderErrors): ?><div class="alert alert-danger">
                            <ul class="mb-0"><?php foreach ($orderErrors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul>
                        </div><?php endif; ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <select class="form-select" name="customer_id" id="customer_id" required>
                                    <option value="">Select Customer</option>
                                    <?php foreach ($customerOptions as $c):
                                        $sel = ((string)$orderFormData['customer_id'] === (string)$c['customer_id']) ? 'selected' : ''; ?>
                                        <option value="<?= (int)$c['customer_id'] ?>" <?= $sel ?>><?= htmlspecialchars($c['name']) ?></option>
                                    <?php endforeach; ?>
                                </select><label for="customer_id">Customer<span class="text-danger">*</span></label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="number" step="0.001" min="0" class="form-control" name="order_quantity" id="order_quantity" value="<?= htmlspecialchars((string)$orderFormData['order_quantity']) ?>" required>
                                <label for="order_quantity">Order Quantity (kg)<span class="text-danger">*</span></label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="date" class="form-control" name="order_date" id="order_date" value="<?= htmlspecialchars((string)$orderFormData['order_date']) ?>" required>
                                <label for="order_date">Order Date<span class="text-danger">*</span></label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="date" class="form-control" name="delivery_date" id="delivery_date" value="<?= htmlspecialchars((string)$orderFormData['delivery_date']) ?>">
                                <label for="delivery_date">Delivery Date</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <select class="form-select" name="order_status" id="order_status">
                                    <?php foreach ($STATUSES as $st):
                                        $sel = ($orderFormData['order_status'] === $st) ? 'selected' : ''; ?>
                                        <option value="<?= $st ?>" <?= $sel ?>><?= ucfirst($st) ?></option>
                                    <?php endforeach; ?>
                                </select><label for="order_status">Status</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i> Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save2 me-1"></i> <?= $orderFormData['id'] ? 'Update' : 'Add' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delivery Modal -->
<div class="modal fade" id="deliveryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow">
            <div class="modal-header">
                <h5 class="modal-title"><?= $deliveryFormData['id'] ? 'Edit Delivery' : 'Add Delivery' ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" class="needs-validation" novalidate>
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                <input type="hidden" name="entity" value="delivery">
                <input type="hidden" name="id" value="<?= htmlspecialchars((string)$deliveryFormData['id']) ?>">
                <div class="modal-body">
                    <?php if ($deliveryErrors): ?><div class="alert alert-danger">
                            <ul class="mb-0"><?php foreach ($deliveryErrors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul>
                        </div><?php endif; ?>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="form-floating">
                                <input type="number" step="0.001" min="0" class="form-control" name="order_quantity" id="d_order_quantity" value="<?= htmlspecialchars((string)$deliveryFormData['order_quantity']) ?>" required>
                                <label for="d_order_quantity">Quantity (kg)<span class="text-danger">*</span></label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-floating">
                                <input type="number" step="0.01" min="0" class="form-control" name="price_per_unit" id="price_per_unit" value="<?= htmlspecialchars((string)$deliveryFormData['price_per_unit']) ?>" required>
                                <label for="price_per_unit">Price per Unit (৳)<span class="text-danger">*</span></label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-floating">
                                <input type="date" class="form-control" name="order_date" id="d_order_date" value="<?= htmlspecialchars((string)$deliveryFormData['order_date']) ?>" required>
                                <label for="d_order_date">Order Date<span class="text-danger">*</span></label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-floating">
                                <input type="date" class="form-control" name="delivery_date" id="d_delivery_date" value="<?= htmlspecialchars((string)$deliveryFormData['delivery_date']) ?>">
                                <label for="d_delivery_date">Delivery Date</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <select class="form-select" name="order_status" id="d_status">
                                    <?php foreach ($STATUSES as $st):
                                        $sel = ($deliveryFormData['order_status'] === $st) ? 'selected' : ''; ?>
                                        <option value="<?= $st ?>" <?= $sel ?>><?= ucfirst($st) ?></option>
                                    <?php endforeach; ?>
                                </select><label for="d_status">Status</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <select class="form-select" name="slaughter_house_id" id="slaughter_house_id" required>
                                    <option value="">Select House</option>
                                    <?php foreach ($houseOptions as $h):
                                        $sel = ((string)$deliveryFormData['slaughter_house_id'] === (string)$h['slaughter_house_id']) ? 'selected' : ''; ?>
                                        <option value="<?= (int)$h['slaughter_house_id'] ?>" <?= $sel ?>><?= htmlspecialchars($h['name']) ?></option>
                                    <?php endforeach; ?>
                                </select><label for="slaughter_house_id">Slaughter House<span class="text-danger">*</span></label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <select class="form-select" name="supplier_id" id="supplier_id" required>
                                    <option value="">Select Supplier</option>
                                    <?php foreach ($supplierOptions as $s):
                                        $sel = ((string)$deliveryFormData['supplier_id'] === (string)$s['supplier_id']) ? 'selected' : ''; ?>
                                        <option value="<?= (int)$s['supplier_id'] ?>" <?= $sel ?>><?= htmlspecialchars($s['name']) ?></option>
                                    <?php endforeach; ?>
                                </select><label for="supplier_id">Supplier<span class="text-danger">*</span></label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i> Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-save2 me-1"></i> <?= $deliveryFormData['id'] ? 'Update' : 'Add' ?></button>
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
    // DataTables + CSV export
    function wireTable(tableId, containerSelector) {
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
        const cont = document.querySelector(containerSelector);
        if (cont) {
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
        return dt;
    }
    const dtOrders = wireTable('#ordersTable', '#orders');
    const dtDeliveries = wireTable('#deliveriesTable', '#deliveries');

    // Bootstrap validation
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
    document.querySelectorAll('.btn-del-order').forEach(a => {
        a.addEventListener('click', e => {
            e.preventDefault();
            const url = a.getAttribute('href');
            const name = a.dataset.name || 'this order';
            Swal.fire({
                    icon: 'warning',
                    title: 'Delete order?',
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
    document.querySelectorAll('.btn-del-delivery').forEach(a => {
        a.addEventListener('click', e => {
            e.preventDefault();
            const url = a.getAttribute('href');
            const name = a.dataset.name || 'this delivery';
            Swal.fire({
                    icon: 'warning',
                    title: 'Delete delivery?',
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

    // Auto open modals on validation bounce/edit
    <?php if ($autoShowOrder): ?> new bootstrap.Modal('#orderModal').show();
    <?php endif; ?>
    <?php if ($autoShowDelivery): ?> new bootstrap.Modal('#deliveryModal').show();
    <?php endif; ?>

    // Toast for ?msg=
    <?php if (!empty($msg)): ?>
        const toastEl = document.getElementById('liveToast');
        const toastMsg = document.getElementById('toastMsg');
        toastMsg.textContent = <?= json_encode($msg) ?>;
        new bootstrap.Toast(toastEl, {
            delay: 2500
        }).show();
    <?php endif; ?>

    // Charts
    const ordersByMonth = <?= json_encode($ordersByMonth) ?>;
    const deliveriesByMonth = <?= json_encode($deliveriesByMonth) ?>;
    const statusMixOrders = <?= json_encode($statusMixOrders) ?>;
    const statusMixDeliveries = <?= json_encode($statusMixDeliveries) ?>;

    const dsCtx = document.getElementById('dsChart');
    const stCtx = document.getElementById('statusChart');

    // Build base labels union
    const labels = Array.from(new Set([...Object.keys(ordersByMonth), ...Object.keys(deliveriesByMonth)])).sort();

    let dsChart;

    function buildDSChart(filteredLabels, oMap, dMap) {
        const oData = filteredLabels.map(m => oMap[m] || 0);
        const dData = filteredLabels.map(m => dMap[m] || 0);
        if (dsChart) dsChart.destroy();
        dsChart = new Chart(dsCtx, {
            type: 'bar',
            data: {
                labels: filteredLabels,
                datasets: [{
                        label: 'Demand (kg)',
                        data: oData
                    },
                    {
                        label: 'Supply (kg)',
                        data: dData
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
                            label: c => `${c.dataset.label}: ${Number(c.raw).toLocaleString()} kg`
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

    let stChart;

    function buildStatusChart() {
        const s1 = Object.values(statusMixOrders);
        const s2 = Object.values(statusMixDeliveries);
        if (stChart) stChart.destroy();
        stChart = new Chart(stCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(statusMixOrders),
                datasets: [{
                        label: 'Orders',
                        data: s1
                    },
                    {
                        label: 'Deliveries',
                        data: s2
                    }
                ]
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
    }

    // Initial render
    buildDSChart(labels, ordersByMonth, deliveriesByMonth);
    buildStatusChart();

    // Filters (client-side)
    function monthInRange(m, fromM, toM) {
        if (fromM && m < fromM) return false;
        if (toM && m > toM) return false;
        return true;
    }
    document.getElementById('applyFilter').addEventListener('click', () => {
        const fromM = document.getElementById('fromMonth').value || null;
        const toM = document.getElementById('toMonth').value || null;
        // For simplicity, status filter affects donut counts only (table filters are via search box)
        const status = document.getElementById('statusFilter').value || '';

        const flabels = labels.filter(m => monthInRange(m, fromM, toM));

        // Rebuild DS chart with month filter (status agnostic)
        buildDSChart(flabels, ordersByMonth, deliveriesByMonth);

        // Rebuild status donut (status selection here acts as highlight by narrowing to just that status in both series)
        if (status) {
            const o = {};
            const d = {};
            o[status] = statusMixOrders[status] || 0;
            d[status] = statusMixDeliveries[status] || 0;
            // temporary single-slice charts
            if (stChart) stChart.destroy();
            stChart = new Chart(stCtx, {
                type: 'doughnut',
                data: {
                    labels: [status],
                    datasets: [{
                        label: 'Orders',
                        data: [o[status]]
                    }, {
                        label: 'Deliveries',
                        data: [d[status]]
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
        } else {
            buildStatusChart();
        }
    });
</script>

<?php include 'footer.php'; ?>