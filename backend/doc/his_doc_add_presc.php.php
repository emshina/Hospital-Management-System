<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();
$pharmacist_id = $_SESSION['doc_id'];

if (isset($_POST['dispense'])) {
    $presc_id = $_POST['presc_id'];
    $pat_number = $_POST['pat_number'];
    $pat_name = $_POST['pat_name'];
    $med_name = $_POST['med_name'];
    $dose_per_time = (int)$_POST['dose_per_time'];
    $times_per_day = (int)$_POST['times_per_day'];
    $duration = (int)$_POST['med_duration'];
    $unit_of_measure = $_POST['unit_of_measure'];

    $pattern_str = "{$dose_per_time}*{$times_per_day}";
    $quantity_dispensed = $dose_per_time * $times_per_day * $duration;

    $stmt = $mysqli->prepare("SELECT selling_price_per_unit, stock_id, quantity FROM his_pharma_stock WHERE phar_bcode = ? AND unit_of_measure = ? AND quantity > 0 AND status = 'active' ORDER BY expiry_date ASC LIMIT 1");
    $stmt->bind_param('ss', $med_name, $unit_of_measure);
    $stmt->execute();
    $stmt->bind_result($unit_price, $stock_id, $available_qty);
    $stmt->fetch();
    $stmt->close();

    if ($available_qty < $quantity_dispensed) {
        echo "<script>alert('Insufficient stock to dispense this quantity.');</script>";
    } else {
        $total_price = $unit_price * $quantity_dispensed;

        $stmt = $mysqli->prepare("INSERT INTO his_pharma_dispense (presc_id, pat_number, pat_name, med_name, med_pattern, med_duration, quantity_dispensed, price_per_unit, total_price, pharmacist_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('isssssiddi', $presc_id, $pat_number, $pat_name, $med_name, $pattern_str, $duration, $quantity_dispensed, $unit_price, $total_price, $pharmacist_id);
        $stmt->execute();
        $stmt->close();

        $remaining_qty = $available_qty - $quantity_dispensed;
        $stmt = $mysqli->prepare("UPDATE his_pharma_stock SET quantity = ? WHERE stock_id = ?");
        $stmt->bind_param('ii', $remaining_qty, $stock_id);
        $stmt->execute();
        $stmt->close();

        echo "<script>alert('Medicine dispensed successfully.');</script>";
    }
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
                <div class="row">
                    <div class="col-12">
                        <div class="card-box">
                            <h4 class="header-title">Dispense Medicine</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                    <tr>
                                        <th>Patient Name</th>
                                        <th>Medicine</th>
                                        <th>Dose/Time</th>
                                        <th>Times/Day</th>
                                        <th>Duration</th>
                                        <th>Quantity</th>
                                        <th>Unit</th>
                                        <th>Unit Price</th>
                                        <th>Total Price</th>
                                        <th>Action</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $query = "SELECT p.pat_fname, p.pat_lname, p.pat_number, d.presc_id, d.pres_med_name, d.pres_med_pattern, d.pres_med_duration, s.unit_of_measure
                                              FROM his_patients p
                                              JOIN his_doc_prescriptions d ON p.pat_number = d.pres_pat_number
                                              LEFT JOIN his_pharma_stock s ON s.phar_bcode = d.pres_med_name AND s.status = 'active' AND s.quantity > 0
                                              GROUP BY d.presc_id";
                                    $result = $mysqli->query($query);
                                    while ($row = $result->fetch_assoc()) {
                                        $full_name = $row['pat_fname'] . ' ' . $row['pat_lname'];
                                        $pattern_parts = explode('*', $row['pres_med_pattern']);
                                        $dose_per_time = isset($pattern_parts[0]) ? (int)$pattern_parts[0] : 1;
                                        $times_per_day = isset($pattern_parts[1]) ? (int)$pattern_parts[1] : 1;
                                        $duration = (int)$row['pres_med_duration'];
                                        $quantity = $dose_per_time * $times_per_day * $duration;

                                        $unit_query = $mysqli->query("SELECT selling_price_per_unit FROM his_pharma_stock WHERE phar_bcode = '" . $row['pres_med_name'] . "' AND unit_of_measure = '" . $row['unit_of_measure'] . "' AND quantity > 0 AND status = 'active' ORDER BY expiry_date ASC LIMIT 1");
                                        $unit_price = 0;
                                        if ($unit_query->num_rows > 0) {
                                            $unit_price = $unit_query->fetch_assoc()['selling_price_per_unit'];
                                        }
                                        $total = $quantity * $unit_price;
                                        echo "<tr>
                                            <form method='POST'>
                                                <td>{$full_name}<input type='hidden' name='pat_name' value='{$full_name}'></td>
                                                <td>{$row['pres_med_name']}<input type='hidden' name='med_name' value='{$row['pres_med_name']}'></td>
                                                <td><input type='number' name='dose_per_time' value='{$dose_per_time}' min='1' class='form-control dose'></td>
                                                <td><input type='number' name='times_per_day' value='{$times_per_day}' min='1' class='form-control times'></td>
                                                <td><input type='number' name='med_duration' value='{$duration}' min='1' class='form-control duration'></td>
                                                <td><input type='number' name='quantity_dispensed' value='{$quantity}' class='form-control quantity' readonly></td>
                                                <td><input type='text' name='unit_of_measure' value='{$row['unit_of_measure']}' class='form-control' readonly></td>
                                                <td><input type='text' name='unit_price' value='{$unit_price}' class='form-control unit_price' readonly></td>
                                                <td><input type='text' name='total_price' value='{$total}' class='form-control total' readonly></td>
                                                <td>
                                                    <input type='hidden' name='presc_id' value='{$row['presc_id']}'>
                                                    <input type='hidden' name='pat_number' value='{$row['pat_number']}'>
                                                    <button type='submit' name='dispense' class='btn btn-success btn-sm'>Dispense</button>
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
    const rows = document.querySelectorAll('table tbody tr');

    rows.forEach(row => {
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
});
</script>
</body>
</html>
