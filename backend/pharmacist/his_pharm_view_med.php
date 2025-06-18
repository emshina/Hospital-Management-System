<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();
$doc_id = $_SESSION['doc_id'];
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
                                <h4 class="page-title">Quick Medicine Lookup</h4>
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
                                                <input id="demo-foo-search" type="text" placeholder="Search medicine..." class="form-control form-control-sm" autocomplete="on">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table id="demo-foo-filtering" class="table table-bordered toggle-circle mb-0" data-page-size="10">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Medicine Name</th>
                                                <th>Category</th>
                                                <th>Unit</th>
                                                <th>Unit Price</th>
                                                <th>Quantity</th>
                                                <th>Expiry Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $ret = "SELECT 
                                                        p.phar_name, 
                                                        p.phar_cat, 
                                                        s.unit_of_measure, 
                                                        s.selling_price_per_unit, 
                                                        s.quantity, 
                                                        s.expiry_date
                                                    FROM his_pharma_stock s
                                                    JOIN his_pharmaceuticals p ON s.phar_id = p.phar_id
                                                    ORDER BY p.phar_name ASC";

                                            $stmt = $mysqli->prepare($ret);
                                            $stmt->execute();
                                            $res = $stmt->get_result();
                                            $cnt = 1;
                                            while ($row = $res->fetch_object()) {
                                            ?>
                                                <tr>
                                                    <td><?php echo $cnt++; ?></td>
                                                    <td><?php echo htmlspecialchars($row->phar_name); ?></td>
                                                    <td><?php echo htmlspecialchars($row->phar_cat); ?></td>
                                                    <td><?php echo htmlspecialchars($row->unit_of_measure ?: 'N/A'); ?></td>
                                                    <td><?php echo $row->selling_price_per_unit ? 'Ksh ' . number_format($row->selling_price_per_unit, 2) : 'N/A'; ?></td>
                                                    <td><?php echo htmlspecialchars($row->quantity); ?></td>
                                                    <td><?php echo $row->expiry_date ?: 'N/A'; ?></td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="7">
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
