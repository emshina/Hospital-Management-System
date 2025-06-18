<?php
	session_start();
	include('assets/inc/config.php');
	if(isset($_POST['add_pharmaceutical'])) {
		$phar_name = $_POST['phar_name'];
		$phar_desc = $_POST['phar_desc'];
        $phar_qty = $_POST['phar_qty'];
        $phar_cat = $_POST['phar_cat'];
        $phar_bcode = $_POST['phar_bcode'];
        $phar_vendor_name = $_POST['phar_vendor'];

        // Get vendor_id from name
        $vendor_query = "SELECT v_id FROM his_vendor WHERE v_name = ?";
        $stmt_vendor = $mysqli->prepare($vendor_query);
        $stmt_vendor->bind_param("s", $phar_vendor_name);
        $stmt_vendor->execute();
        $result = $stmt_vendor->get_result();
        $vendor = $result->fetch_object();
        $vendor_id = $vendor->v_id;

        // Insert into pharmaceuticals
        $query = "INSERT INTO his_pharmaceuticals (phar_name, phar_bcode, phar_desc, phar_qty, phar_cat, phar_vendor) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('ssssss', $phar_name, $phar_bcode, $phar_desc, $phar_qty, $phar_cat, $phar_vendor_name);
        $stmt->execute();

        $phar_id = $mysqli->insert_id;

        // Placeholder stock data
        $batch_number = substr(uniqid(), -8);
        $expiry_date = date('Y-m-d', strtotime('+2 years'));
        $purchase_price = floatval($_POST['purchase_price']);
        $profit_margin = floatval($_POST['profit_margin']);

        // Calculate selling price
        $selling_price = $purchase_price + ($purchase_price * ($profit_margin / 100));


        $stock_query = "INSERT INTO his_pharma_stock (phar_id, batch_number, quantity, expiry_date, purchase_price, selling_price, vendor_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_stock = $mysqli->prepare($stock_query);
        $stmt_stock->bind_param('isisssi', $phar_id, $batch_number, $phar_qty, $expiry_date, $purchase_price, $selling_price, $vendor_id);
        $stmt_stock->execute();

        if($stmt && $stmt_stock) {
            $success = "Pharmaceutical and Stock Added Successfully";
        } else {
            $err = "Error: Please Try Again Later";
        }
	}
?>

<!--End Server Side-->
<!--End Patient Registration-->
<!DOCTYPE html>
<html lang="en">
    
    <!--Head-->
    <?php include('assets/inc/head.php');?>
    <body>

        <!-- Begin page -->
        <div id="wrapper">

            <!-- Topbar Start -->
            <?php include("assets/inc/nav.php");?>
            <!-- end Topbar -->

            <!-- ========== Left Sidebar Start ========== -->
            <?php include("assets/inc/sidebar.php");?>
            <!-- Left Sidebar End -->

            <!-- ============================================================== -->
            <!-- Start Page Content here -->
            <!-- ============================================================== -->

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
                                            <li class="breadcrumb-item active">Add Pharmaceutical</li>
                                        </ol>
                                    </div>
                                    <h4 class="page-title">Create A Pharmaceutical</h4>
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
                                                    <label class="col-form-label">Pharmaceutical Name</label>
                                                    <input type="text" required name="phar_name" class="form-control">
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label class="col-form-label">Pharmaceutical Quantity (Cartons)</label>
                                                    <input required type="number" name="phar_qty" class="form-control">
                                                </div>
                                            </div>

                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label class="col-form-label">Pharmaceutical Category</label>
                                                    <select required name="phar_cat" class="form-control">
                                                        <?php
                                                        $ret = "SELECT * FROM his_pharmaceuticals_categories ORDER BY RAND()";
                                                        $stmt = $mysqli->prepare($ret);
                                                        $stmt->execute();
                                                        $res = $stmt->get_result();
                                                        while ($row = $res->fetch_object()) {
                                                            echo "<option>$row->pharm_cat_name</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label class="col-form-label">Pharmaceutical Vendor</label>
                                                    <select required name="phar_vendor" class="form-control">
                                                        <?php
                                                        $ret = "SELECT * FROM his_vendor ORDER BY RAND()";
                                                        $stmt = $mysqli->prepare($ret);
                                                        $stmt->execute();
                                                        $res = $stmt->get_result();
                                                        while ($row = $res->fetch_object()) {
                                                            echo "<option>$row->v_name</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label class="col-form-label">Pharmaceutical Barcode (EAN-8)</label>
                                                <?php $phar_bcode = substr(str_shuffle('0123456789'), 1, 10); ?>
                                                <input required type="text" value="<?php echo $phar_bcode; ?>" name="phar_bcode" class="form-control">
                                            </div>

                                            <div class="form-group">
                                                <label class="col-form-label">Pharmaceutical Description</label>
                                                <textarea required class="form-control" name="phar_desc" id="editor"></textarea>
                                            </div>

                                            <!-- 🆕 Stock Fields -->
                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label class="col-form-label">Batch Number</label>
                                                    <input required type="text" name="batch_number" class="form-control">
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label class="col-form-label">Expiry Date</label>
                                                    <input required type="date" name="expiry_date" class="form-control">
                                                </div>
                                            </div>

                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label class="col-form-label">Purchase Price</label>
                                                    <input required type="text" name="purchase_price" class="form-control">
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label class="col-form-label">Profit Margin (%)</label>
                                                    <input required type="number" name="profit_margin" class="form-control" placeholder="Enter percentage e.g. 25">
                                                </div>

                                            </div>

                                            <button type="submit" name="add_pharmaceutical" class="btn btn-success">Add Pharmaceutical</button>
                                        </form>

                                     
                                    </div> <!-- end card-body -->
                                </div> <!-- end card-->
                            </div> <!-- end col -->
                        </div>
                        <!-- end row -->

                    </div> <!-- container -->

                </div> <!-- content -->

                <!-- Footer Start -->
                <?php include('assets/inc/footer.php');?>
                <!-- end Footer -->

            </div>

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