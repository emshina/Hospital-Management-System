<?php 
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();
$pharmacist_id = $_SESSION['doc_id'];

// Dispense logic
if (isset($_POST['dispense'])) {
    $presc_id = $_POST['presc_id'];
    $pat_number = $_POST['pat_number'];
    $pat_name = $_POST['pat_name'];
    $phar_bcode = $_POST['phar_bcode'];
    $dose_per_time = (int)$_POST['dose_per_time'];
    $times_per_day = (int)$_POST['times_per_day'];
    $duration = (int)$_POST['med_duration'];
    $unit_of_measure = $_POST['unit_of_measure'];

    $pattern_str = "{$dose_per_time}*{$times_per_day}";
    $quantity_dispensed = $dose_per_time * $times_per_day * $duration;

    $stmt = $mysqli->prepare("SELECT selling_price_per_unit, stock_id, quantity FROM his_pharma_stock 
                              WHERE phar_bcode = ? AND unit_of_measure = ? AND quantity > 0 AND status = 'active'
                              ORDER BY expiry_date ASC LIMIT 1");
    $stmt->bind_param('ss', $phar_bcode, $unit_of_measure);
    $stmt->execute();
    $stmt->bind_result($unit_price, $stock_id, $available_qty);
    $stmt->fetch();
    $stmt->close();

    if (!isset($unit_price)) {
        echo "<script>alert('Medicine not found in active stock.');</script>";
    } elseif ($available_qty < $quantity_dispensed) {
        echo "<script>alert('Insufficient stock to dispense this quantity.');</script>";
    } else {
        $total_price = $unit_price * $quantity_dispensed;

        $stmt = $mysqli->prepare("SELECT phar_name FROM his_pharmaceuticals WHERE phar_bcode = ? LIMIT 1");
        $stmt->bind_param('s', $phar_bcode);
        $stmt->execute();
        $stmt->bind_result($med_name);
        $stmt->fetch();
        $stmt->close();
        if (!isset($med_name)) $med_name = "Unknown";

        $stmt = $mysqli->prepare("INSERT INTO his_pharma_dispense 
            (presc_id, pat_number, pat_name, med_name, med_pattern, med_duration, quantity_dispensed, price_per_unit, total_price, pharmacist_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('isssssiddi', $presc_id, $pat_number, $pat_name, $med_name, $pattern_str, $duration, $quantity_dispensed, $unit_price, $total_price, $pharmacist_id);
        $stmt->execute();
        $stmt->close();

        $remaining_qty = $available_qty - $quantity_dispensed;
        $stmt = $mysqli->prepare("UPDATE his_pharma_stock SET quantity = ? WHERE stock_id = ?");
        $stmt->bind_param('ii', $remaining_qty, $stock_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $mysqli->prepare("UPDATE his_pharmaceuticals SET phar_qty = phar_qty - ? WHERE phar_bcode = ?");
        $stmt->bind_param('is', $quantity_dispensed, $phar_bcode);
        $stmt->execute();
        $stmt->close();

        $stmt = $mysqli->prepare("UPDATE his_doc_prescriptions SET dispensed = 1 WHERE presc_id = ?");
        $stmt->bind_param('i', $presc_id);
        $stmt->execute();
        $stmt->close();

        echo "<script>window.location.href = '?dispensed={$presc_id}';</script>";
        exit();
    }
}

// Cancel logic
if (isset($_POST['cancel'])) {
    $presc_id = $_POST['presc_id'];
    $stmt = $mysqli->prepare("UPDATE his_doc_prescriptions SET dispensed = -1 WHERE presc_id = ?");
    $stmt->bind_param('i', $presc_id);
    $stmt->execute();
    $stmt->close();
    echo "<script>window.location.href = '?cancelled={$presc_id}';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include('assets/inc/head.php'); ?>
<body>
<div id="wrapper">
    <?php include('assets/inc/nav.php'); ?>
    <?php include("assets/inc/sidebar.php"); ?>
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <?php if (isset($_GET['dispensed'])): ?>
                    <div class="alert alert-success">Prescription ID <?= htmlspecialchars($_GET['dispensed']) ?> dispensed successfully.</div>
                <?php elseif (isset($_GET['cancelled'])): ?>
                    <div class="alert alert-warning">Prescription ID <?= htmlspecialchars($_GET['cancelled']) ?> was cancelled.</div>
                <?php endif; ?>
                <div class="row">
                    <div class="col-12">
                        <div class="card-box">
                            <h4 class="header-title">Dispense Medicine</h4>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered table-striped table-hover">
                                    <thead>
                                    <tr>
                                        <th>Patient Name</th>
                                        <th>Medicine</th>
                                        <th>Dose/Time</th>
                                        <th>Times/Day</th>
                                        <th>Duration</th>
                                        <th>Quantity</th>
                                        <th>Unit of Measure</th>
                                        <th>Unit Price</th>
                                        <th>Total Price</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $query = "SELECT p.pat_fname, p.pat_lname, p.pat_number, d.presc_id, d.pres_med_name, d.pres_med_pattern, d.pres_med_duration, d.phar_bcode
                                              FROM his_patients p
                                              JOIN his_doc_prescriptions d ON p.pat_number = d.pres_pat_number
                                              WHERE d.dispensed = 0
                                              GROUP BY d.presc_id";
                                    $result = $mysqli->query($query);
                                    while ($row = $result->fetch_assoc()) {
                                        $full_name = $row['pat_fname'] . ' ' . $row['pat_lname'];
                                        $pattern_parts = explode('*', $row['pres_med_pattern']);
                                        $dose_per_time = isset($pattern_parts[0]) ? (int)$pattern_parts[0] : 1;
                                        $times_per_day = isset($pattern_parts[1]) ? (int)$pattern_parts[1] : 1;
                                        $duration = (int)$row['pres_med_duration'];
                                        $quantity = $dose_per_time * $times_per_day * $duration;

                                        // Get med name always
                                        $med_stmt = $mysqli->prepare("SELECT phar_name FROM his_pharmaceuticals WHERE phar_bcode = ? LIMIT 1");
                                        $med_stmt->bind_param('s', $row['phar_bcode']);
                                        $med_stmt->execute();
                                        $med_stmt->bind_result($med_name);
                                        $med_stmt->fetch();
                                        $med_stmt->close();
                                        if (!isset($med_name)) $med_name = "Unknown";

                                        $unit_price = 0;
                                        $unit_of_measure = '';
                                        $available_qty = 0;
                                        $status_msg = "<span class='text-danger'>Out of Stock</span>";
                                        $can_dispense = false;

                                        $stock_stmt = $mysqli->prepare("SELECT selling_price_per_unit, unit_of_measure, quantity 
                                            FROM his_pharma_stock 
                                            WHERE phar_bcode = ? AND quantity > 0 AND status = 'active'
                                            ORDER BY expiry_date ASC LIMIT 1");
                                        $stock_stmt->bind_param('s', $row['phar_bcode']);
                                        $stock_stmt->execute();
                                        $stock_stmt->bind_result($unit_price_res, $unit_of_measure_res, $available_qty_res);
                                        if ($stock_stmt->fetch()) {
                                            $unit_price = $unit_price_res;
                                            $unit_of_measure = $unit_of_measure_res;
                                            $available_qty = $available_qty_res;
                                            if ($available_qty < $quantity) {
                                                $status_msg = "<span class='text-warning'>Only {$available_qty} left</span>";
                                            } else {
                                                $status_msg = "<span class='text-success'>In Stock ({$available_qty})</span>";
                                                $can_dispense = true;
                                            }
                                        }
                                        $stock_stmt->close(); // ✅ KEEP ONLY THIS ONE

                                        // Fallback if out of stock: fetch unit_of_measure only
                                        if (!$unit_of_measure) {
                                            $fallback_stmt = $mysqli->prepare("SELECT unit_of_measure FROM his_pharma_stock 
                                                WHERE phar_bcode = ? ORDER BY expiry_date ASC LIMIT 1");
                                            $fallback_stmt->bind_param('s', $row['phar_bcode']);
                                            $fallback_stmt->execute();
                                            $fallback_stmt->bind_result($unit_of_measure_fallback);
                                            if ($fallback_stmt->fetch()) {
                                                $unit_of_measure = $unit_of_measure_fallback;
                                            }
                                            $fallback_stmt->close();
                                        }


                                        $total = $quantity * $unit_price;

                                        echo "<tr data-presc-id='{$row['presc_id']}'>
                                            <form method='POST'>
                                                <td>{$full_name}<input type='hidden' name='pat_name' value='{$full_name}'></td>
                                                <td>{$med_name}</td>
                                                <td><input type='number' name='dose_per_time' value='{$dose_per_time}' min='1' class='form-control dose'></td>
                                                <td><input type='number' name='times_per_day' value='{$times_per_day}' min='1' class='form-control times'></td>
                                                <td><input type='number' name='med_duration' value='{$duration}' min='1' class='form-control duration'></td>
                                                <td><input type='number' name='quantity_dispensed' value='{$quantity}' class='form-control quantity' readonly></td>
                                                <td><input type='text' name='unit_of_measure' value='{$unit_of_measure}' class='form-control' readonly></td>
                                                <td><input type='text' name='unit_price' value='{$unit_price}' class='form-control unit_price' readonly></td>
                                                <td><input type='text' name='total_price' value='{$total}' class='form-control total' readonly></td>
                                                <td>{$status_msg}</td>
                                                <td>
                                                    <input type='hidden' name='presc_id' value='{$row['presc_id']}'>
                                                    <input type='hidden' name='pat_number' value='{$row['pat_number']}'>
                                                    <input type='hidden' name='phar_bcode' value='{$row['phar_bcode']}'>
                                                    ".($can_dispense ? "<button type='submit' name='dispense' class='btn btn-success btn-sm'>Dispense</button>" : "<button class='btn btn-secondary btn-sm' disabled>Unavailable</button>")."
                                                    <button type='submit' name='cancel' class='btn btn-danger btn-sm'>Cancel</button>
                                                </td>
                                            </form>
                                        </tr>";
                                    }
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include('assets/inc/footer.php'); ?>
    </div>
</div>

<!-- Scripts -->
<script src="assets/js/vendor.min.js"></script>
<script src="assets/js/app.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('table tbody tr').forEach(row => {
        const doseInput = row.querySelector('.dose');
        const timesInput = row.querySelector('.times');
        const durationInput = row.querySelector('.duration');
        const quantityInput = row.querySelector('.quantity');
        const unitPriceInput = row.querySelector('.unit_price');
        const totalInput = row.querySelector('.total');

        function updateValues() {
            const dose = parseFloat(doseInput.value) || 0;
            const times = parseFloat(timesInput.value) || 0;
            const duration = parseFloat(durationInput.value) || 0;
            const unitPrice = parseFloat(unitPriceInput.value) || 0;

            const quantity = dose * times * duration;
            const total = quantity * unitPrice;

            quantityInput.value = quantity;
            totalInput.value = total.toFixed(2);
        }

        doseInput.addEventListener('input', updateValues);
        timesInput.addEventListener('input', updateValues);
        durationInput.addEventListener('input', updateValues);
    });

    const params = new URLSearchParams(window.location.search);
    const dispensedId = params.get('dispensed');
    if (dispensedId) {
        const dispensedRow = document.querySelector(`tr[data-presc-id='${dispensedId}']`);
        if (dispensedRow) {
            dispensedRow.remove();
        }
    }
});
</script>
</body>
</html>
