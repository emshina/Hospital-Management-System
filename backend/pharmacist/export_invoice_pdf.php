<?php
require('fpdf/fpdf.php');
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();

$where = "";
$title = "All Dispensing Records";

if ($_GET['period'] === 'monthly' && !empty($_GET['month'])) {
    $month = $_GET['month'];
    $where = "WHERE DATE_FORMAT(dispense_date, '%Y-%m') = '$month'";
    $title = "Monthly Report - " . date('F Y', strtotime($month));
} elseif ($_GET['period'] === 'annually' && !empty($_GET['month'])) {
    $year = substr($_GET['month'], 0, 4);
    $where = "WHERE YEAR(dispense_date) = '$year'";
    $title = "Annual Report - $year";
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

$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'Velvet Roast Hospital - Pharmacy Dispensing Report', 0, 1, 'C');
$pdf->SetFont('Arial', 'I', 12);
$pdf->Cell(0, 10, $title, 0, 1, 'C');

$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 10);
$header = [
    'Patient No.', 'Patient Name', 'Medicine', 'Pattern', 'Duration',
    'Unit Price', 'Qty', 'Pharmacist ID', 'Date', 'Total Price'
];
$widths = [25, 35, 30, 20, 20, 25, 15, 30, 25, 25];
foreach ($header as $i => $col) {
    $pdf->Cell($widths[$i], 10, $col, 1);
}
$pdf->Ln();

$pdf->SetFont('Arial', '', 9);
$totalQty = 0;
$totalAmount = 0;

while ($row = $result->fetch_assoc()) {
    $pdf->Cell($widths[0], 8, $row['pat_number'], 1);
    $pdf->Cell($widths[1], 8, $row['pat_name'], 1);
    $pdf->Cell($widths[2], 8, $row['med_name'], 1);
    $pdf->Cell($widths[3], 8, $row['med_pattern'], 1);
    $pdf->Cell($widths[4], 8, $row['med_duration'], 1);
    $pdf->Cell($widths[5], 8, number_format($row['price_per_unit'], 2), 1);
    $pdf->Cell($widths[6], 8, $row['quantity_dispensed'], 1);
    $pdf->Cell($widths[7], 8, $row['pharmacist_id'], 1);
    $pdf->Cell($widths[8], 8, date('d-m-Y', strtotime($row['dispense_date'])), 1);
    $pdf->Cell($widths[9], 8, number_format($row['total_price'], 2), 1);
    $pdf->Ln();

    $totalQty += $row['quantity_dispensed'];
    $totalAmount += $row['total_price'];
}

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(array_sum(array_slice($widths, 0, 6)), 10, 'TOTAL', 1);
$pdf->Cell($widths[6], 10, $totalQty, 1);
$pdf->Cell($widths[7] + $widths[8], 10, '', 1);
$pdf->Cell($widths[9], 10, 'KES ' . number_format($totalAmount, 2), 1);

$pdf->Output('D', 'Pharmacy_Dispense_Report.pdf');
exit;
