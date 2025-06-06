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
    $unit_of_measure = $_POST['unit_of_measure'];
    $manufacture_date = $_POST['manufacture_date'];
    $storage_location = $_POST['storage_location'];
    $received_by = $_POST['received_by'];
    $reorder_level = $_POST['reorder_level'];
    $status = $_POST['status'];

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
        // Update his_pharmaceuticals
        $query = "UPDATE his_pharmaceuticals 
                  SET phar_name = ?, phar_desc = ?, phar_qty = ?, phar_cat = ?, phar_vendor = ? 
                  WHERE phar_bcode = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('ssssss', $phar_name, $phar_desc, $phar_qty, $phar_cat, $phar_vendor, $phar_bcode);
        $stmt->execute();

        // Update his_pharma_stock
        // $update_stock = "UPDATE his_pharma_stock 
        //                  SET batch_number = ?, quantity = ?, expiry_date = ?, 
        //                      purchase_price = ?, selling_price = ?, unit_of_measure = ?, 
        //                      manufacture_date = ?, storage_location = ?, received_by = ?, 
        //                      reorder_level = ?, status = ?, profit_margin = ?
        //                  WHERE phar_id = ?";
        // $stmt2 = $mysqli->prepare($update_stock);
        // $stmt2->bind_param(
        //     'sissssssssisi',
        //     $batch_number, $phar_qty, $expiry_date,
        //     $purchase_price, $selling_price, $unit_of_measure,
        //     $manufacture_date, $storage_location, $received_by,
        //     $reorder_level, $status, $profit_margin, $phar_id
        // );

        $update_stock = "UPDATE his_pharma_stock 
                        SET batch_number = ?, quantity = ?, expiry_date = ?, 
                            purchase_price = ?, selling_price = ?, selling_price_per_unit = ?, unit_of_measure = ?, 
                            manufacture_date = ?, storage_location = ?, received_by = ?, vendor_id =?
                            reorder_level = ?, status = ?, profit_margin = ?, notes =?
                        WHERE phar_id = ?";
        $stmt2 = $mysqli->prepare($update_stock);
        $stmt2->bind_param(
            'sissssssssisi',
            $batch_number, $phar_qty, $expiry_date,
            $purchase_price, $selling_price, $unit_of_measure,
            $manufacture_date, $storage_location, $received_by,
            $reorder_level, $status, $profit_margin, $phar_id
        );
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

<!DOCTYPE html>
<html lang="en">
<?php include('assets/inc/head.php'); ?>

<body>
    <div id="wrapper">
        <?php include("assets/inc/nav.php"); ?>
        <?php include("assets/inc/sidebar.php"); ?>

        <?php
        $phar_bcode = $_GET['phar_bcode'];
        $ret = "SELECT * FROM his_pharma_stock AS s 
                JOIN his_pharmaceuticals AS p ON s.phar_id = p.phar_id 
                WHERE p.phar_bcode = ?";
        $stmt = $mysqli->prepare($ret);
        $stmt->bind_param('s', $phar_bcode);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_object()) {
        ?>
            <div class="content-page">
                <div class="content">
                    <div class="container-fluid">
                        <div class="row"><div class="col-12">
                            <div class="page-title-box">
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="his_doc_dashboard.php">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="#">Pharmaceuticals</a></li>
                                        <li class="breadcrumb-item active">Manage Pharmaceutical</li>
                                    </ol>
                                </div>
                                <h4 class="page-title">Update #<?php echo $row->phar_bcode; ?> - <?php echo $row->phar_name; ?></h4>
                            </div>
                        </div></div>

                        <div class="row">
                            <div class="col-12">
                                <div class="card"><div class="card-body">
                                    <h4 class="header-title">Fill all fields</h4>
                                    <form method="post">
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Pharmaceutical Name</label>
                                                <input type="text" name="phar_name" value="<?php echo $row->phar_name; ?>" required class="form-control">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Quantity</label>
                                                <input type="number" name="phar_qty" value="<?php echo $row->quantity; ?>" required class="form-control">
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>Description</label>
                                            <textarea name="phar_desc" required class="form-control" id="editor"><?php echo $row->phar_desc; ?></textarea>
                                        </div>

                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Category</label>
                                                <select name="phar_cat" class="form-control" required>
                                                    <?php
                                                    $cat_stmt = $mysqli->prepare("SELECT pharm_cat_name FROM his_pharmaceuticals_categories");
                                                    $cat_stmt->execute();
                                                    $cat_res = $cat_stmt->get_result();
                                                    while ($cat = $cat_res->fetch_object()) {
                                                        $selected = ($row->phar_cat == $cat->pharm_cat_name) ? "selected" : "";
                                                        echo "<option $selected>{$cat->pharm_cat_name}</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Batch Number</label>
                                                <input type="text" name="batch_number" value="<?php echo $row->batch_number; ?>" required class="form-control">
                                            </div>
                                        </div>

                                        <div class="form-row">
                                            <div class="form-group col-md-4">
                                                <label>Expiry Date</label>
                                                <input type="date" name="expiry_date" value="<?php echo $row->expiry_date; ?>" required class="form-control">
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label>Manufacture Date</label>
                                                <input type="date" name="manufacture_date" value="<?php echo $row->manufacture_date; ?>" class="form-control">
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label>Unit of Measure</label>
                                                <input type="text" name="unit_of_measure" value="<?php echo $row->unit_of_measure; ?>" class="form-control">
                                            </div>
                                        </div>

                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Purchase Price</label>
                                                <input type="number" step="0.01" name="purchase_price" value="<?php echo $row->purchase_price; ?>" required class="form-control">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Profit Margin (%)</label>
                                                <input type="number" name="profit_margin" value="<?php echo $row->profit_margin; ?>" required class="form-control">
                                            </div>
                                        </div>

                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Storage Location</label>
                                                <input type="text" name="storage_location" value="<?php echo $row->storage_location; ?>" class="form-control">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Received By</label>
                                                <input type="text" name="received_by" value="<?php echo $row->received_by; ?>" class="form-control">
                                            </div>
                                        </div>

                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Reorder Level</label>
                                                <input type="number" name="reorder_level" value="<?php echo $row->reorder_level; ?>" class="form-control">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Status</label>
                                                <select name="status" class="form-control">
                                                    <option value="Active" <?php if ($row->status == 'Active') echo 'selected'; ?>>Active</option>
                                                    <option value="Inactive" <?php if ($row->status == 'Inactive') echo 'selected'; ?>>Inactive</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>Vendor</label>
                                            <select name="phar_vendor" class="form-control" required>
                                                <?php
                                                $vendor_stmt = $mysqli->prepare("SELECT v_name FROM his_vendor");
                                                $vendor_stmt->execute();
                                                $vendor_res = $vendor_stmt->get_result();
                                                while ($v = $vendor_res->fetch_object()) {
                                                    $selected = ($row->phar_vendor == $v->v_name) ? "selected" : "";
                                                    echo "<option $selected>{$v->v_name}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>

                                        <button type="submit" name="update_pharmaceutical" class="btn btn-warning">Update Pharmaceutical</button>
                                    </form>
                                </div></div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php include('assets/inc/footer.php'); ?>
            </div>
        <?php } ?>
    </div>

    <script src="//cdn.ckeditor.com/4.6.2/basic/ckeditor.js"></script>
    <script>CKEDITOR.replace('editor')</script>
    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/js/app.min.js"></script>
    <script src="assets/libs/ladda/spin.js"></script>
    <script src="assets/libs/ladda/ladda.js"></script>
    <script src="assets/js/pages/loading-btn.init.js"></script>
</body>
</html>
