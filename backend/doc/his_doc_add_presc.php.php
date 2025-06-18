<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);

include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();
$pharmacist_id = $_SESSION['doc_id'];

$med_names = [];
$med_query = $mysqli->query("SELECT phar_bcode, phar_name FROM his_pharmaceuticals ORDER BY phar_name ASC");
while ($row = $med_query->fetch_assoc()) {
    $med_names[$row['phar_bcode']] = $row['phar_name'];
}

if (!isset($_SESSION['dispense_cart'])) {
    $_SESSION['dispense_cart'] = [];
}

function updateStockAndDispense($item, $pat_number, $pat_name, $pharmacist_id, $mysqli) {
    $phar_bcode = $item['phar_bcode'];
    $unit_of_measure = $item['unit_of_measure'];
    $quantity_dispensed = $item['quantity_dispensed'];
    $unit_price = $item['price_per_unit'];
    $total_price = $item['total_price'];
    $pattern = $item['dose_per_time'] . '*' . $item['times_per_day'];
    $duration = $item['duration'];
    $med_name = $item['med_name'];

    $stmt = $mysqli->prepare("SELECT stock_id, quantity FROM his_pharma_stock 
                              WHERE phar_bcode = ? AND unit_of_measure = ? AND status = 'active' 
                              ORDER BY expiry_date ASC");
    $stmt->bind_param('ss', $phar_bcode, $unit_of_measure);
    $stmt->execute();
    $result = $stmt->get_result();

    $stock_id = null;
    $available_qty = 0;

    while ($row = $result->fetch_assoc()) {
        if ($row['quantity'] >= $quantity_dispensed) {
            $stock_id = $row['stock_id'];
            $available_qty = $row['quantity'];
            break;
        }
    }
    $stmt->close();

  if ($stock_id === null || $available_qty < $quantity_dispensed) {
    return false; 
  }


    $stmt = $mysqli->prepare("INSERT INTO his_pharma_dispense (pat_number, pat_name, med_name, med_pattern, med_duration, quantity_dispensed, price_per_unit, total_price, pharmacist_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('ssssiiddi', $pat_number, $pat_name, $med_name, $pattern, $duration, $quantity_dispensed, $unit_price, $total_price, $pharmacist_id);
    $stmt->execute();
    $stmt->close();

    $new_qty = $available_qty - $quantity_dispensed;
    $stmt = $mysqli->prepare("UPDATE his_pharma_stock SET quantity = ? WHERE stock_id = ?");
    $stmt->bind_param('ii', $new_qty, $stock_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $mysqli->prepare("UPDATE his_pharmaceuticals SET phar_qty = phar_qty - ? WHERE phar_bcode = ?");
    $stmt->bind_param('is', $quantity_dispensed, $phar_bcode);
    $stmt->execute();
    $stmt->close();
    return true;
}

if (isset($_POST['add_to_cart'])) {
    $phar_bcode = $_POST['phar_bcode'];
    $unit_of_measure = $_POST['unit_of_measure'];
    $dose_per_time = (int)$_POST['dose_per_time'];
    $times_per_day = (int)$_POST['times_per_day'];
    $duration = (int)$_POST['med_duration'];
    $quantity = $dose_per_time * $times_per_day * $duration;
    $unit_price = (float)$_POST['unit_price'];
    $total_price = (float)$_POST['total_price'];

    $item = [
        'phar_bcode' => $phar_bcode,
        'med_name' => $med_names[$phar_bcode] ?? 'Unknown',
        'unit_of_measure' => $unit_of_measure,
        'dose_per_time' => $dose_per_time,
        'times_per_day' => $times_per_day,
        'duration' => $duration,
        'quantity_dispensed' => $quantity,
        'price_per_unit' => $unit_price,
        'total_price' => $total_price
    ];

    $found = false;
    foreach ($_SESSION['dispense_cart'] as &$existing) {
        if ($existing['phar_bcode'] === $phar_bcode && $existing['unit_of_measure'] === $unit_of_measure) {
            $existing['quantity_dispensed'] += $quantity;
            $existing['total_price'] += $total_price;
            $found = true;
            break;
        }
    }
    unset($existing);

    if (!$found) {
        $_SESSION['dispense_cart'][] = $item;
    }
    $_SESSION['message'] = "Medicine added to cart.";
    $_SESSION['message_type'] = "success";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_GET['remove'])) {
    array_splice($_SESSION['dispense_cart'], $_GET['remove'], 1);
    $_SESSION['message'] = "Item removed from cart.";
    $_SESSION['message_type'] = "info";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['finalize_dispense'])) {
    $pat_number = trim($_POST['pat_number'] ?? '');
    $pat_name = trim($_POST['pat_name'] ?? '');

  if (!empty($pat_number) && !preg_match('/^[\w\s\-\/]+$/', $pat_number)) {
      $_SESSION['message'] = "Invalid patient number format.";
      $_SESSION['message_type'] = "danger";
      header("Location: " . $_SERVER['PHP_SELF']);
      exit;
  }



    $success_count = 0;

    foreach ($_SESSION['dispense_cart'] as $item) {
        if (updateStockAndDispense($item, $pat_number, $pat_name, $pharmacist_id, $mysqli)) {
            $success_count++;
        }
    }

    if ($success_count > 0) {
        $_SESSION['dispense_cart'] = []; // clear cart
        $_SESSION['message'] = "Successfully dispensed {$success_count} medicine(s).";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "No medicines were dispensed. Check stock or try again.";
        $_SESSION['message_type'] = "warning";
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include('assets/inc/head.php'); ?>
<body>
<div id="wrapper">
  <?php include('assets/inc/nav.php'); ?>
  <?php include('assets/inc/sidebar.php'); ?>
  <div class="content-page">
    <div class="content">
      <div class="container-fluid">

                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <h4 class="page-title">Dispense Multiple Medicines </h4>
                            </div>
                        </div>
                    </div>
        <div class="card-box">

          <?php if (!empty($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type'] ?? 'info' ?>">
              <?= htmlspecialchars($_SESSION['message']) ?>
            </div>
            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
          <?php endif; ?>

          <form method="POST">
            <div class="form-row">
              <div class="col-sm-3">
                <label>Patient Name</label>
                <input type="text" name="pat_name" class="form-control" placeholder="John Doe">
              </div>
              <div class="col-sm-3">
                <label>Patient Number</label>
                <input type="text" name="pat_number" class="form-control" placeholder="123456">
              </div>
              <div class="col-sm-3">
                <label>Medicine</label>
                <select name="phar_bcode" id="phar_bcode" class="form-control" required onchange="fetchStockOptions()">
                  <option value="">-- Select --</option>
                  <?php foreach ($med_names as $bcode => $name): ?>
                    <option value="<?= htmlspecialchars($bcode) ?>"><?= htmlspecialchars($name) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-sm-3">
                <label>Unit</label>
                <select name="unit_of_measure" id="uom" class="form-control" required onchange="updatePrice()"></select>
              </div>
            </div>

            <div class="form-row mt-2">
              <div class="col-sm-2">
                <label>Dose/Time</label>
                <input type="number" name="dose_per_time" id="dose" class="form-control" min="1" value="1">
              </div>
              <div class="col-sm-2">
                <label>Times/Day</label>
                <input type="number" name="times_per_day" id="times" class="form-control" min="1" value="1">
              </div>
              <div class="col-sm-2">
                <label>Duration</label>
                <input type="number" name="med_duration" id="duration" class="form-control" min="1" value="1">
              </div>
              <div class="col-sm-2">
                <label>Quantity</label>
                <input type="number" id="quantity" name="quantity" class="form-control" readonly>
              </div>
              <div class="col-sm-2">
                <label>Unit Price</label>
                <input type="text" id="uprice" name="unit_price" class="form-control" readonly>
              </div>
              <div class="col-sm-2">
                <label>Total</label>
                <input type="text" id="tprice" name="total_price" class="form-control" readonly>
              </div>
            </div>

            <div class="form-row mt-2">
              <div class="col-sm-2 offset-sm-10">
                <label>&nbsp;</label>
                <button type="submit" name="add_to_cart" class="btn btn-primary btn-block">Add</button>
              </div>
            </div>
          </form>

          <hr>
          <h5>Medicine List</h5>
          <form method="POST" onsubmit="return confirmFinalize();">
            <table class="table table-bordered table-sm">
              <thead class="thead-light">
                <tr><th>Name</th><th>Unit</th><th>Qty</th><th>Unit Price</th><th>Total</th><th>Remove</th></tr>
              </thead>
              <tbody>
                <?php $total_all = 0; foreach ($_SESSION['dispense_cart'] as $idx => $item): ?>
                  <tr>
                    <td><?= $item['med_name'] ?></td>
                    <td><?= $item['unit_of_measure'] ?></td>
                    <td><?= $item['quantity_dispensed'] ?></td>
                    <td><?= number_format($item['price_per_unit'], 2) ?></td>
                    <td><?= number_format($item['total_price'], 2) ?></td>
                    <td><a href="?remove=<?= $idx ?>" class="btn btn-danger btn-sm">X</a></td>
                  </tr>
                  <?php $total_all += $item['total_price']; endforeach; ?>
                <tr><td colspan="4"><strong>Total</strong></td><td colspan="2"><strong><?= number_format($total_all, 2) ?></strong></td></tr>
              </tbody>
            </table>
            <button type="submit" name="finalize_dispense" class="btn btn-success">Finalize Dispense</button>
          </form>
        </div>
      </div>
    </div>
    <?php include('assets/inc/footer.php'); ?>
  </div>
</div>
  <script src="assets/js/vendor.min.js"></script>
  <script src="assets/js/app.min.js"></script>
  <script src="assets/js/jquery.min.js"></script>
  <script src="assets/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/app.js"></script>
        <!-- Vendor js -->
        <script src="assets/js/vendor.min.js"></script>

        <!-- Footable js -->
        <script src="assets/libs/footable/footable.all.min.js"></script>

        <!-- Init js -->
        <script src="assets/js/pages/foo-tables.init.js"></script>

        <!-- App js -->
        <script src="assets/js/app.min.js"></script>
        <script src="assets/js/vendor.min.js"></script>
        <script src="assets/js/app.min.js"></script>
<script>
let stockOptions = [];
function fetchStockOptions() {
  const bcode = document.getElementById('phar_bcode').value;
  if (!bcode) return;
  fetch('ajax_fetch_stock.php?bcode=' + bcode)
    .then(res => res.json())
    .then(data => {
      stockOptions = data;
      const uom = document.getElementById('uom');
      uom.innerHTML = '<option value="">-- Select --</option>';
      data.forEach(item => {
        const opt = document.createElement('option');
        opt.value = item.unit_of_measure;
        opt.textContent = item.unit_of_measure;
        uom.appendChild(opt);
      });
    });
}
function updatePrice() {
  const selectedUOM = document.getElementById('uom').value;
  const found = stockOptions.find(item => item.unit_of_measure === selectedUOM);
  if (found) {
    document.getElementById('uprice').value = found.selling_price_per_unit;
  } else {
    document.getElementById('uprice').value = '';
  }
  computeTotals();
}
function computeTotals() {
  const dose = parseInt(document.getElementById('dose').value) || 0;
  const times = parseInt(document.getElementById('times').value) || 0;
  const duration = parseInt(document.getElementById('duration').value) || 0;
  const qty = dose * times * duration;
  const uprice = parseFloat(document.getElementById('uprice').value) || 0;
  document.getElementById('quantity').value = qty;
  document.getElementById('tprice').value = (qty * uprice).toFixed(2);
}
['dose','times','duration'].forEach(id => document.getElementById(id).addEventListener('input', computeTotals));

function confirmFinalize() {
  return confirm("Are you sure you want to finalize and dispense the listed medicines?");
}
</script>
</body>
</html>
