<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();
$pharmacist_id = $_SESSION['doc_id'];

$med_names = [];
$med_query = $mysqli->query("SELECT phar_bcode, phar_name FROM his_pharmaceuticals ORDER BY phar_name ASC");
while ($row = $med_query->fetch_assoc()) {
    $med_names[$row['phar_bcode']] = $row['phar_name'];
}

if (isset($_POST['dispense'])) {
    $presc_id = !empty($_POST['presc_id']) ? intval($_POST['presc_id']) : null;
    $pat_number = $_POST['pat_number'] ?: null;
    $pat_name = $_POST['pat_name'] ?: null;
    $phar_bcode = $_POST['phar_bcode'];
    $dose_per_time = (int)$_POST['dose_per_time'];
    $times_per_day = (int)$_POST['times_per_day'];
    $duration = (int)$_POST['med_duration'];
    $unit_of_measure = $_POST['unit_of_measure'];

    $quantity_dispensed = $dose_per_time * $times_per_day * $duration;

    $stmt = $mysqli->prepare("SELECT selling_price_per_unit, stock_id, quantity 
                              FROM his_pharma_stock 
                              WHERE phar_bcode = ? AND unit_of_measure = ? AND status = 'active'
                              ORDER BY expiry_date ASC");
    $stmt->bind_param('ss', $phar_bcode, $unit_of_measure);
    $stmt->execute();
    $result = $stmt->get_result();

    $sufficient_stock_found = false;
    while ($row = $result->fetch_assoc()) {
        if ($row['quantity'] >= $quantity_dispensed) {
            $unit_price = $row['selling_price_per_unit'];
            $stock_id = $row['stock_id'];
            $available_qty = $row['quantity'];
            $sufficient_stock_found = true;
            break;
        }
    }
    $stmt->close();

    if (!$sufficient_stock_found) {
        $stmt = $mysqli->prepare("SELECT SUM(quantity) FROM his_pharma_stock WHERE phar_bcode = ? AND unit_of_measure = ? AND status = 'active'");
        $stmt->bind_param('ss', $phar_bcode, $unit_of_measure);
        $stmt->execute();
        $stmt->bind_result($remaining_qty);
        $stmt->fetch();
        $stmt->close();

        $remaining_qty = $remaining_qty ?: 0;
        echo "<script>alert('Insufficient stock. Only $remaining_qty unit(s) available. Please adjust the dose or choose an alternative.');history.back();</script>";
        exit;
    }

    $total_price = $unit_price * $quantity_dispensed;
    $med_name = $med_names[$phar_bcode] ?? "Unknown";
    $pattern = "{$dose_per_time}*{$times_per_day}";

    $stmt = $mysqli->prepare("INSERT INTO his_pharma_dispense 
        (presc_id, pat_number, pat_name, med_name, med_pattern, med_duration, quantity_dispensed, price_per_unit, total_price, pharmacist_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('isssssiddi', $presc_id, $pat_number, $pat_name, $med_name, $pattern, $duration, $quantity_dispensed, $unit_price, $total_price, $pharmacist_id);
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

    if ($presc_id) {
        $stmt = $mysqli->prepare("UPDATE his_doc_prescriptions SET dispensed = 1 WHERE presc_id = ?");
        $stmt->bind_param('i', $presc_id);
        $stmt->execute();
        $stmt->close();
    }

    echo "<script>alert('Medicine dispensed successfully.');window.location='his_doc_add_presc.php.php';</script>";
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

          <div class="card-box">
            <h4 class="header-title">Dispense Medicine (Including Walk-in Patients)</h4>
<form method="POST" class="mb-4">
  <div class="form-row">
    <div class="col">
      <label>Patient Name (optional)</label>
      <input type="text" name="pat_name" class="form-control" placeholder="John Doe">
    </div>
    <div class="col">
      <label>Patient Number (optional)</label>
      <input type="text" name="pat_number" class="form-control" placeholder="123456">
    </div>
  </div>

  <div class="form-row mt-2">
    <div class="col">
      <label>Medicine</label>
      <select name="phar_bcode" id="phar_bcode" class="form-control" required onchange="fetchStockOptions()">
        <option value="">-- Select Medicine --</option>
        <?php foreach ($med_names as $bcode => $name): ?>
          <option value="<?= htmlspecialchars($bcode) ?>"><?= htmlspecialchars($name) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col">
      <label>Unit of Measure</label>
      <select name="unit_of_measure" id="uom" class="form-control" required onchange="updatePrice()">
        <option value="">-- Select Unit --</option>
      </select>
    </div>
    <div class="col">
      <label>Dose/Time</label>
      <input type="number" name="dose_per_time" id="dose" min="1" value="1" class="form-control" required>
    </div>
    <div class="col">
      <label>Times/Day</label>
      <input type="number" name="times_per_day" id="times" min="1" value="1" class="form-control" required>
    </div>
    <div class="col">
      <label>Duration (Days)</label>
      <input type="number" name="med_duration" id="duration" min="1" value="1" class="form-control" required>
    </div>
  </div>

  <div class="form-row mt-2">
    <div class="col">
      <label>Quantity</label>
      <input type="number" id="quantity" class="form-control" readonly>
    </div>
    <div class="col">
      <label>Unit Price</label>
      <input type="text" name="unit_price" id="uprice" class="form-control" readonly>
    </div>
    <div class="col">
      <label>Total Price</label>
      <input type="text" name="total_price" id="tprice" class="form-control" readonly>
    </div>
  </div>

  <button type="submit" name="dispense" class="btn btn-success mt-3">Dispense</button>
</form>


            <!-- Existing doctor-prescribed pending list stays unchanged -->
            <!-- ... -->
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

<script>
  let stockOptions = [];

function fetchStockOptions() {
  const bcode = document.getElementById('phar_bcode').value;
  if (!bcode) return;

  fetch('ajax_fetch_stock.php?bcode=' + encodeURIComponent(bcode))
    .then(res => res.json())
    .then(data => {
      stockOptions = data;
      const uomSelect = document.getElementById('uom');
      uomSelect.innerHTML = '';

      if (data.length === 0) {
        alert('No active stock available for the selected medicine.');
        uomSelect.innerHTML = '<option value="">-- No units available --</option>';
        document.getElementById('uprice').value = '';
        document.getElementById('quantity').value = '';
        document.getElementById('tprice').value = '';
        return;
      }

      uomSelect.innerHTML = '<option value="">-- Select Unit --</option>';
      data.forEach(option => {
        const opt = document.createElement('option');
        opt.value = option.unit_of_measure;
        opt.textContent = option.unit_of_measure;
        uomSelect.appendChild(opt);
      });

      updatePrice(); // Auto-populate price and quantity fields
    })
    .catch(err => {
      alert('Failed to fetch stock data. Please try again.');
      console.error(err);
    });
}


  function updatePrice() {
    const selectedUOM = document.getElementById('uom').value;
    const match = stockOptions.find(item => item.unit_of_measure === selectedUOM);
    if (match) {
      document.getElementById('uprice').value = parseFloat(match.selling_price_per_unit).toFixed(2);
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
    const tprice = qty * uprice;

    document.getElementById('quantity').value = qty;
    document.getElementById('tprice').value = tprice.toFixed(2);
  }

  ['dose', 'times', 'duration'].forEach(id => {
    document.getElementById(id).addEventListener('input', computeTotals);
  });
</script>

</body>

</html>