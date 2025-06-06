<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();
$doc_id = $_SESSION['ad_id'];

if (isset($_GET['delete_pharm_name'])) {
    $id = intval($_GET['delete_pharm_name']);

    // Delete stock records first
    $stock_del = "DELETE FROM his_pharma_stock WHERE phar_id = ?";
    $stmt1 = $mysqli->prepare($stock_del);
    $stmt1->bind_param('i', $id);
    $stmt1->execute();
    $stmt1->close();

    // Delete from pharmaceuticals
    $pharm_del = "DELETE FROM his_pharmaceuticals WHERE phar_id = ?";
    $stmt2 = $mysqli->prepare($pharm_del);
    $stmt2->bind_param('i', $id);
    if ($stmt2->execute()) {
        $success = "Pharmaceutical and stock records deleted successfully.";
    } else {
        $err = "Error: Could not delete records. Try again.";
    }
    $stmt2->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('assets/inc/head.php'); ?>
    <link href="assets/libs/footable/footable.core.min.css" rel="stylesheet" type="text/css" />
</head>
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
                            <h4 class="page-title">Pharmaceuticals</h4>
                        </div>
                    </div>
                </div>     

                <div class="row">
                    <div class="col-12">
                        <div class="card-box">

                            <div class="mb-2">
                                <div class="form-group">
                                    <input id="demo-foo-search" type="text" placeholder="Search here..." class="form-control form-control-sm">
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table id="demo-foo-filtering" class="table table-bordered" data-filter="#demo-foo-search" data-page-size="7">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Barcode</th>
                                            <th>Vendor</th>
                                            <th>Category</th>
                                            <th>Qty</th>
                                            <th>Batch</th>
                                            <th>Expiry</th>
                                            <th>Purchase Price</th>
                                            <th>Selling Price</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $ret = "SELECT p.phar_id, p.phar_name, p.phar_bcode, p.phar_cat, 
                                                        s.batch_number, s.quantity, s.expiry_date, 
                                                        s.purchase_price, s.selling_price,
                                                        v.v_name AS vendor_name
                                                FROM his_pharma_stock s
                                                JOIN his_pharmaceuticals p ON s.phar_id = p.phar_id
                                                LEFT JOIN his_vendor v ON s.vendor_id = v.v_id
                                                ORDER BY p.phar_name ASC";
                                        $stmt = $mysqli->prepare($ret);
                                        $stmt->execute();
                                        $res = $stmt->get_result();
                                        $cnt = 1;
                                        while ($row = $res->fetch_object()) {
                                        ?>
                                            <tr>
                                                <td><?= $cnt++; ?></td>
                                                <td><?= $row->phar_name; ?></td>
                                                <td><?= $row->phar_bcode; ?></td>
                                                <td><?= $row->vendor_name; ?></td>
                                                <td><?= $row->phar_cat; ?></td>
                                                <td><?= $row->quantity; ?></td>
                                                <td><?= $row->batch_number ?: 'N/A'; ?></td>
                                                <td><?= $row->expiry_date ?: 'N/A'; ?></td>
                                                <td><?= $row->purchase_price ? 'Ksh ' . $row->purchase_price : 'N/A'; ?></td>
                                                <td><?= $row->selling_price ? 'Ksh ' . $row->selling_price : 'N/A'; ?></td>
                                                <td>
                                                    <a href="his_admin_view_single_pharm.php?phar_bcode=<?= $row->phar_bcode; ?>" class="badge badge-success">View</a>
                                                    <a href="his_admin_update_single_stock.php?phar_bcode=<?= $row->phar_bcode; ?>" class="badge badge-warning">Update</a>
                                                    <a href="?delete_pharm_name=<?= $row->phar_id; ?>" class="badge badge-danger" onclick="return confirm('Delete this item?');">Delete</a>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="11">
                                                <div class="text-right">
                                                    <ul class="pagination footable-pagination m-2 justify-content-end"></ul>
                                                </div>
                                            </td>
                                        </tr>
                                    </tfoot>
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

<script src="assets/js/vendor.min.js"></script>
<script src="assets/libs/footable/footable.all.min.js"></script>
<script>
    $(document).ready(function () {
        $('#demo-foo-filtering').footable();
    });
</script>
<script src="assets/js/app.min.js"></script>
</body>
</html>