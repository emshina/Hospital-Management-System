<?php
session_start();
include('assets/inc/config.php');

if (isset($_POST['update_pharmaceutical'])) {
    $phar_name = $_POST['phar_name'];
    $phar_desc = $_POST['phar_desc'];
    $phar_qty = $_POST['phar_qty'];
    $phar_cat = $_POST['phar_cat'];
    $phar_bcode = $_GET['phar_bcode'];
    $phar_vendor = $_POST['phar_vendor'];
    $batch_number = $_POST['batch_number'];
    $expiry_date = $_POST['expiry_date'];
    $purchase_price = floatval($_POST['purchase_price']);
    $profit_margin = floatval($_POST['profit_margin']);

    $selling_price = $purchase_price + ($purchase_price * ($profit_margin / 100));

    // Get phar_id using phar_bcode
    $get_id_query = "SELECT phar_id FROM his_pharmaceuticals WHERE phar_bcode = ?";
    $stmt_id = $mysqli->prepare($get_id_query);
    $stmt_id->bind_param('s', $phar_bcode);
    $stmt_id->execute();
    $stmt_id->bind_result($phar_id);
    $stmt_id->fetch();
    $stmt_id->close();

    if ($phar_id) {
        // Update pharmaceuticals table
        $query = "UPDATE his_pharmaceuticals 
                  SET phar_name = ?, phar_desc = ?, phar_qty = ?, phar_cat = ?, phar_vendor = ? 
                  WHERE phar_bcode = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('ssssss', $phar_name, $phar_desc, $phar_qty, $phar_cat, $phar_vendor, $phar_bcode);

        $stmt->execute();

        // Update pharma_stock table
        $update_stock = "UPDATE his_pharma_stock 
                        SET batch_number = ?, quantity = ?, expiry_date = ?, 
                            purchase_price = ?, selling_price = ?
                        WHERE phar_id = ?";

        $stmt2 = $mysqli->prepare($update_stock);
        // $stmt2->bind_param('sissdii', $batch_number, $phar_qty, $expiry_date, $purchase_price, $selling_price, $phar_id);
        $stmt2->bind_param('sisddi', $batch_number, $phar_qty, $expiry_date, $purchase_price, $selling_price, $phar_id);

        $stmt2->execute();

        if ($stmt && $stmt2) {
            $success = "Pharmaceutical Updated";
        } else {
            $err = "Failed to update stock. Please try again.";
        }
    } else {
        $err = "Pharmaceutical ID not found.";
    }
}
?>

<!--End Server Side-->
<!--End Patient Registration-->
<!DOCTYPE html>
<html lang="en">

<!--Head-->
<?php include('assets/inc/head.php'); ?>

<body>

    <!-- Begin page -->
    <div id="wrapper">

        <!-- Topbar Start -->
        <?php include("assets/inc/nav.php"); ?>
        <!-- end Topbar -->

        <!-- ========== Left Sidebar Start ========== -->
        <?php include("assets/inc/sidebar.php"); ?>
        <!-- Left Sidebar End -->

        <!-- ============================================================== -->
        <!-- Start Page Content here -->
        <!-- ============================================================== -->
        <?php
        $phar_bcode = $_GET['phar_bcode'];
        $ret = "SELECT * FROM his_pharma_stock AS s JOIN his_pharmaceuticals AS p ON s.phar_id = p.phar_id WHERE p.phar_bcode = ?";


        $stmt = $mysqli->prepare($ret);
        $stmt->bind_param('s', $phar_bcode);
        $stmt->execute(); //ok
        $res = $stmt->get_result();
        //$cnt=1;
        while ($row = $res->fetch_object()) {
        ?>
            <div class="content-page">
                <div class="content">

                    <!-- Start Content-->
                    <div class="container-fluid">

                        <!-- start page title -->
                        <div class="row">
                            <div class="col-12">
                                <div class="page-title-box">
                                    <div class="page-title-right">
                                        <ol class="breadcrumb m-0">
                                            <li class="breadcrumb-item"><a href="his_doc_dashboard.php">Dashboard</a></li>
                                            <li class="breadcrumb-item"><a href="javascript: void(0);">Pharmaceuticals</a></li>
                                            <li class="breadcrumb-item active">Manage Pharmaceutical</li>
                                        </ol>
                                    </div>
                                    <h4 class="page-title">Update #<?php echo $row->phar_bcode; ?> - <?php echo $row->phar_name; ?></h4>
                                </div>
                            </div>
                        </div>
                        <!-- end page title -->
                        <!-- Form row -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="header-title">Fill all fields</h4>
                                        <!--Add Patient Form-->
                                        <form method="post">
                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label for="pharName" class="col-form-label">Pharmaceutical Name</label>
                                                    <input type="text" required="required" value="<?php echo $row->phar_name; ?>" name="phar_name" class="form-control" id="pharName">
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label for="pharQty" class="col-form-label">Pharmaceutical Quantity (Cartons)</label>
                                                    <input required="required" type="text" value="<?php echo $row->phar_qty; ?>" name="phar_qty" class="form-control" id="pharQty">
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label for="pharDesc" class="col-form-label">Pharmaceutical Description</label>
                                                <textarea required="required" class="form-control" name="phar_desc" id="editor"><?php echo $row->phar_desc; ?></textarea>
                                            </div>

                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label for="pharCat" class="col-form-label">Pharmaceutical Category</label>
                                                    <select id="pharCat" required="required" name="phar_cat" class="form-control">
                                                        <?php
                                                        $ret = "SELECT * FROM his_pharmaceuticals_categories ORDER BY RAND()";
                                                        $stmt = $mysqli->prepare($ret);
                                                        $stmt->execute();
                                                        $res = $stmt->get_result();
                                                        while ($cat = $res->fetch_object()) {
                                                            $selected = ($row->phar_cat == $cat->pharm_cat_name) ? "selected" : "";
                                                            echo "<option $selected>" . $cat->pharm_cat_name . "</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>


                                                <div class="form-group col-md-6">
                                                    <label for="batchNumber" class="col-form-label">Batch Number</label>
                                                    <input type="text" name="batch_number" required="required" class="form-control" id="batchNumber" value="<?php echo $row->batch_number; ?>">
                                                </div>

                                            </div>

                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label for="expiryDate" class="col-form-label">Expiry Date</label>
                                                    <input type="date" name="expiry_date" required="required" class="form-control" id="expiryDate" value="<?php echo $row->expiry_date; ?>">
                                                </div>

                                                <div class="form-group col-md-6">
                                                    <label for="purchasePrice" class="col-form-label">Purchase Price</label>
                                                    <input type="text" name="purchase_price" required="required" class="form-control" id="purchasePrice" value="<?php echo $row->purchase_price; ?>">
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label for="profitMargin" class="col-form-label">Profit Margin (%)</label>
                                                    <input type="number" name="profit_margin" required="required" class="form-control" id="profitMargin" value="<?php echo $row->profit_margin; ?>" placeholder="Enter % e.g. 25">
                                                </div>



                                                <div class="form-group col-md-6">
                                                    <label for="inputState" class="col-form-label">Pharmaceutical Vendor</label>
                                                    <select id="inputState" required="required" name="phar_vendor" class="form-control">
                                                        <?php

                                                        $ret = "SELECT * FROM  his_vendor ORDER BY RAND() ";
                                                        //sql code to get to ten docs  randomly
                                                        $stmt = $mysqli->prepare($ret);
                                                        $stmt->execute(); //ok
                                                        $res = $stmt->get_result();
                                                        $cnt = 1;
                                                        while ($row = $res->fetch_object()) {
                                                            //$mysqlDateTime = $row->s_pat_date;
                                                        ?>
                                                            <option><?php echo $row->v_name; ?></option>

                                                        <?php } ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <button type="submit" name="update_pharmaceutical" class="ladda-button btn btn-warning" data-style="expand-right">Update Pharmaceutical</button>
                                        </form>









                                    </div> <!-- end card-body -->
                                </div> <!-- end card-->
                            </div> <!-- end col -->
                        </div>
                        <!-- end row -->

                    </div> <!-- container -->

                </div> <!-- content -->

                <!-- Footer Start -->
                <?php include('assets/inc/footer.php'); ?>
                <!-- end Footer -->

            </div>
        <?php } ?>
        <!-- ============================================================== -->
        <!-- End Page content -->
        <!-- ============================================================== -->


    </div>
    <!-- END wrapper -->
    <!--Load CK EDITOR Javascript-->
    <script src="//cdn.ckeditor.com/4.6.2/basic/ckeditor.js"></script>
    <script type="text/javascript">
        CKEDITOR.replace('editor')
    </script>

    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>

    <!-- Vendor js -->
    <script src="assets/js/vendor.min.js"></script>

    <!-- App js-->
    <script src="assets/js/app.min.js"></script>

    <!-- Loading buttons js -->
    <script src="assets/libs/ladda/spin.js"></script>
    <script src="assets/libs/ladda/ladda.js"></script>

    <!-- Buttons init js-->
    <script src="assets/js/pages/loading-btn.init.js"></script>

</body>

</html>