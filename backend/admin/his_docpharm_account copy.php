<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();

$pharmacist_id = $_SESSION['doc_id'];

// Build WHERE clause based on filter
$where = "";
$filterTitle = "All Records";
if (isset($_GET['period']) && $_GET['period'] !== 'all') {
    $month = $_GET['month'] ?? date('Y-m');
    if ($_GET['period'] === 'monthly') {
        $where = "WHERE DATE_FORMAT(dispense_date, '%Y-%m') = '$month'";
        $filterTitle = "Monthly Report for " . date('F Y', strtotime($month));
    } elseif ($_GET['period'] === 'annually') {
        $year = substr($month, 0, 4);
        $where = "WHERE YEAR(dispense_date) = '$year'";
        $filterTitle = "Annual Report for $year";
    }
}

// Fetch filtered data
$result = $mysqli->query("
    SELECT 
        pat_number,
        pat_name,
        med_name,
        med_pattern,
        med_duration,
        price_per_unit,
        quantity_dispensed,
        total_price,
        pharmacist_id,
        dispense_date
    FROM his_pharma_dispense
    $where
    ORDER BY dispense_date DESC
");

$totalResult = $mysqli->query("SELECT SUM(quantity_dispensed) AS total_dispensed FROM his_pharma_dispense $where");
$totalRow = $totalResult->fetch_assoc();
$totalDispensed = $totalRow['total_dispensed'] ?? 0;

$revenueResult = $mysqli->query("SELECT SUM(total_price) AS total_revenue FROM his_pharma_dispense $where");
$revenueRow = $revenueResult->fetch_assoc();
$totalRevenue = $revenueRow['total_revenue'] ?? 0;
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

                <div class="row mb-3">
                    <div class="col-12 text-center">
                        <h2>Emshina pk Hospital</h2>
                        <h4>Pharmacy Dispensing Report</h4>
                        <p><strong><?= $filterTitle ?></strong></p>
                    </div>
                </div>

                <form method="GET" class="form-inline mb-3">
                    <label class="mr-2">Filter By:</label>
                    <select name="period" class="form-control mr-2">
                        <option value="all" <?= ($_GET['period'] ?? '') == 'all' ? 'selected' : '' ?>>All</option>
                        <option value="monthly" <?= ($_GET['period'] ?? '') == 'monthly' ? 'selected' : '' ?>>Monthly</option>
                        <option value="annually" <?= ($_GET['period'] ?? '') == 'annually' ? 'selected' : '' ?>>Annually</option>
                    </select>
                    <input type="month" name="month" class="form-control mr-2" value="<?= $_GET['month'] ?? '' ?>">
                    <input type="submit" class="btn btn-primary" value="Apply">
                </form>

                <div class="mb-3">
                    <button onclick="window.print();" class="btn btn-secondary">Print</button>
                    <!-- <a href="export_invoice_pdf.php?period=<?= $_GET['period'] ?? 'all' ?>&month=<?= $_GET['month'] ?? '' ?>" class="btn btn-danger">Download PDF</a> -->
                    <a href="export_invoice_csv.php?period=<?= $_GET['period'] ?? 'all' ?>&month=<?= $_GET['month'] ?? '' ?>" class="btn btn-success">Download CSV</a>
                </div>

                <div class="card-box shadow rounded">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover table-sm">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Patient Number</th>
                                    <th>Patient Name</th>
                                    <th>Medicine</th>
                                    <th>Pattern</th>
                                    <th>Duration</th>
                                    <th class="text-right">Unit Price (KES)</th>
                                    <th class="text-right">Quantity</th>
                                    <th>Pharmacist ID</th>
                                    <th>Date</th>
                                    <th class="text-right">Total Price (KES)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['pat_number']) ?></td>
                                            <td><?= htmlspecialchars($row['pat_name']) ?></td>
                                            <td><?= htmlspecialchars($row['med_name']) ?></td>
                                            <td><?= htmlspecialchars($row['med_pattern']) ?></td>
                                            <td><?= htmlspecialchars($row['med_duration']) ?></td>
                                            <td class="text-right"><?= number_format($row['price_per_unit'], 2) ?></td>
                                            <td class="text-right"><?= $row['quantity_dispensed'] ?></td>
                                            <td><?= htmlspecialchars($row['pharmacist_id']) ?></td>
                                            <td><?= date('d M Y', strtotime($row['dispense_date'])) ?></td>
                                            <td class="text-right"><strong><?= number_format($row['total_price'], 2) ?></strong></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="10" class="text-center">No records found for the selected period.</td></tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="6" class="text-right">TOTALS:</th>
                                    <th class="text-right"><?= $totalDispensed ?></th>
                                    <th colspan="2"></th>
                                    <th class="text-right"><strong>KES <?= number_format($totalRevenue, 2) ?></strong></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

            </div> <!-- container -->
        </div> <!-- content -->
        <?php include("assets/inc/footer.php"); ?>
    </div>
</div>

<script src="assets/js/vendor.min.js"></script>
<script src="assets/js/app.min.js"></script>
</body>
</html>
