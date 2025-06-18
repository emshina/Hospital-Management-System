<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="pharmacy_invoice_report.csv"');

$output = fopen('php://output', 'w');

// Column headers
fputcsv($output, [
    'Patient Number', 'Patient Name', 'Medicine', 'Pattern', 'Duration',
    'Unit Price (KES)', 'Quantity', 'Pharmacist ID', 'Dispense Date', 'Total Price (KES)'
]);

$where = "";
if ($_GET['period'] === 'monthly' && !empty($_GET['month'])) {
    $month = $_GET['month'];
    $where = "WHERE DATE_FORMAT(dispense_date, '%Y-%m') = '$month'";
} elseif ($_GET['period'] === 'annually' && !empty($_GET['month'])) {
    $year = substr($_GET['month'], 0, 4);
    $where = "WHERE YEAR(dispense_date) = '$year'";
}

$query = "
    SELECT 
        pat_number, pat_name, med_name, med_pattern, med_duration,
        price_per_unit, quantity_dispensed, pharmacist_id, dispense_date, total_price
    FROM his_pharma_dispense
    $where
    ORDER BY dispense_date DESC
";
$result = $mysqli->query($query);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['pat_number'],
        $row['pat_name'],
        $row['med_name'],
        $row['med_pattern'],
        $row['med_duration'],
        number_format($row['price_per_unit'], 2),
        $row['quantity_dispensed'],
        $row['pharmacist_id'],
        $row['dispense_date'],
        number_format($row['total_price'], 2),
    ]);
}

fclose($output);
exit;
