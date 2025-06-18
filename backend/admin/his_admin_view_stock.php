<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();
$doc_id = $_SESSION['ad_id'];
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
                            <div class="page-title-box">
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Pharmaceuticals</a></li>
                                        <li class="breadcrumb-item active">View Pharmaceuticals</li>
                                    </ol>
                                </div>
                                <h4 class="page-title">Medicine</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card-box">
                                <div class="mb-2">
                                    <div class="row">
                                        <div class="col-12 text-sm-center form-inline">
                                            <div class="form-group">
                                                <input id="demo-foo-search" type="text" placeholder="Search by name, barcode..." class="form-control form-control-sm" autocomplete="on">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table id="demo-foo-filtering" class="table table-bordered toggle-circle mb-0" data-page-size="7">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Pharm Name</th>
                                                <th>Barcode</th>
                                                <th>Vendor</th>
                                                <th>Category</th>
                                                <th>Qty</th>
                                                <th>Unit Measure</th>
                                                <th>Unit Price</th>
                                                <th>Batch</th>
                                                <th>Expiry</th>
                                                <th>Purchase Price</th>
                                                <th>Total Selling Price</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $ret = "SELECT 
                                                        p.phar_name, 
                                                        p.phar_bcode, 
                                                        p.phar_cat, 
                                                        s.batch_number, 
                                                        s.quantity, 
                                                        s.unit_of_measure, 
                                                        s.selling_price_per_unit, 
                                                        s.expiry_date, 
                                                        s.purchase_price, 
                                                        s.selling_price, 
                                                        s.date_received, 
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
                                                    <td><?php echo $cnt++; ?></td>
                                                    <td><?php echo $row->phar_name; ?></td>
                                                    <td><?php echo $row->phar_bcode; ?></td>
                                                    <td><?php echo $row->vendor_name ?: 'N/A'; ?></td>
                                                    <td><?php echo $row->phar_cat; ?></td>
                                                    <td><?php echo $row->quantity; ?></td>
                                                    <td><?php echo $row->unit_of_measure ?: 'N/A'; ?></td>
                                                    <td><?php echo $row->selling_price_per_unit ? 'Ksh ' . number_format($row->selling_price_per_unit, 2) : 'N/A'; ?></td>
                                                    <td><?php echo $row->batch_number ?: 'N/A'; ?></td>
                                                    <td><?php echo $row->expiry_date ?: 'N/A'; ?></td>
                                                    <td><?php echo $row->purchase_price ? 'Ksh ' . number_format($row->purchase_price, 2) : 'N/A'; ?></td>
                                                    <td><?php echo $row->selling_price ? 'Ksh ' . number_format($row->selling_price, 2) : 'N/A'; ?></td>
                                                    <td>
                                                        <a href="his_admin_view_single_pharm.php?phar_bcode=<?php echo $row->phar_bcode; ?>" class="badge badge-success">
                                                            <i class="far fa-eye"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="13">
                                                    <div class="text-right">
                                                        <ul class="pagination pagination-rounded justify-content-end footable-pagination m-t-10 mb-0"></ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                            </div> <!-- end card-box -->
                        </div> <!-- end col -->
                    </div> <!-- end row -->

                </div> <!-- container -->
            </div> <!-- content -->

            <?php include('assets/inc/footer.php'); ?>
        </div> <!-- content-page -->
    </div> <!-- wrapper -->

    <div class="rightbar-overlay"></div>

    <!-- JS Scripts -->
    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/libs/footable/footable.all.min.js"></script>
    <script src="assets/js/pages/foo-tables.init.js"></script>
    <script src="assets/js/app.min.js"></script>

</body>
</html>
