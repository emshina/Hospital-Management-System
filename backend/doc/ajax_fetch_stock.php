<?php
include('assets/inc/config.php');

header('Content-Type: application/json');

if (isset($_GET['bcode'])) {
    $bcode = $_GET['bcode'];
    $stmt = $mysqli->prepare("SELECT unit_of_measure, selling_price_per_unit FROM his_pharma_stock WHERE phar_bcode = ? AND quantity > 0 AND status = 'active'");
    $stmt->bind_param('s', $bcode);
    $stmt->execute();
    $res = $stmt->get_result();

    $output = [];
    while ($row = $res->fetch_assoc()) {
        $output[] = $row;
    }
    echo json_encode($output);
}
