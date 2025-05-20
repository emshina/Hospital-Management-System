<?php
session_start();
include('assets/inc/config.php');

if (isset($_GET['phar_bcode'])) {
    $phar_bcode = $_GET['phar_bcode'];
    // $phar_bcode = intval($_GET['$phar_bcode']);

    // Fetch pharmaceutical info

    


    
    $query_pharma = "SELECT * FROM his_pharmaceuticals WHERE phar_bcode = ?";
    $stmt_pharma = $mysqli->prepare($query_pharma);
    $stmt_pharma->bind_param('i', $phar_bcode);
    $stmt_pharma->execute();
    $res_pharma = $stmt_pharma->get_result();
    $pharma = $res_pharma->fetch_object();

    // Fetch stock info
    $query_stock = "SELECT s.*, v.v_name FROM his_pharma_stock s JOIN his_vendor v ON s.vendor_id = v.v_id WHERE phar_bcode = ?";
    $stmt_stock = $mysqli->prepare($query_stock);
    $stmt_stock->bind_param('i', $phar_bcode);
    $stmt_stock->execute();
    $res_stock = $stmt_stock->get_result();
    $stock = $res_stock->fetch_object();
}

if (isset($_POST['update_pharmaceutical'])) {
    $phar_name = $_POST['phar_name'];
    $phar_desc = $_POST['phar_desc'];
    $phar_qty = $_POST['phar_qty'];
    $phar_cat = $_POST['phar_cat'];
    $phar_bcode = $_POST['phar_bcode'];
    $phar_vendor_name = $_POST['phar_vendor'];

    $batch_number = $_POST['batch_number'];
    $expiry_date = $_POST['expiry_date'];
    $purchase_price = floatval($_POST['purchase_price']);
    $profit_margin = floatval($_POST['profit_margin']);
    $selling_price = $purchase_price + ($purchase_price * ($profit_margin / 100));

    // Get vendor_id
    $vendor_query = "SELECT v_id FROM his_vendor WHERE v_name = ?";
    $stmt_vendor = $mysqli->prepare($vendor_query);
    $stmt_vendor->bind_param("s", $phar_vendor_name);
    $stmt_vendor->execute();
    $result = $stmt_vendor->get_result();
    $vendor = $result->fetch_object();
    $vendor_id = $vendor->v_id;

    // Update his_pharmaceuticals
    $update_pharma = "UPDATE his_pharmaceuticals SET phar_name=?, phar_bcode=?, phar_desc=?, phar_qty=?, phar_cat=?, phar_vendor=? WHERE phar_id=?";
    $stmt1 = $mysqli->prepare($update_pharma);
    $stmt1->bind_param('ssssssi', $phar_name, $phar_bcode, $phar_desc, $phar_qty, $phar_cat, $phar_vendor_name, $phar_id);

    // Update his_pharma_stock
    $update_stock = "UPDATE his_pharma_stock SET batch_number=?, quantity=?, expiry_date=?, purchase_price=?, selling_price=?, vendor_id=? WHERE phar_id=?";
    $stmt2 = $mysqli->prepare($update_stock);
    $stmt2->bind_param('sisssii', $batch_number, $phar_qty, $expiry_date, $purchase_price, $selling_price, $vendor_id, $phar_id);

    if ($stmt1->execute() && $stmt2->execute()) {
        $success = "Pharmaceutical and stock updated successfully.";
    } else {
        $err = "Update failed. Please try again.";
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

    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box">
                            <h4 class="page-title">Update Pharmaceutical and Stock</h4>
                        </div>
                    </div>
                </div>

                <?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
                <?php if (isset($err)) echo "<div class='alert alert-danger'>$err</div>"; ?>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <form method="post">
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Pharmaceutical Name</label>
                                            <input type="text" name="phar_name" required class="form-control" value="<?= $pharma->phar_name ?>">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Quantity (Cartons)</label>
                                            <input type="number" name="phar_qty" required class="form-control" value="<?= $pharma->phar_qty ?>">
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Category</label>
                                            <select name="phar_cat" required class="form-control">
                                                <?php
                                                $ret = "SELECT * FROM his_pharmaceuticals_categories";
                                                $stmt = $mysqli->prepare($ret);
                                                $stmt->execute();
                                                $res = $stmt->get_result();
                                                while ($row = $res->fetch_object()) {
                                                    $selected = ($pharma->phar_cat == $row->pharm_cat_name) ? "selected" : "";
                                                    echo "<option $selected>$row->pharm_cat_name</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Vendor</label>
                                            <select name="phar_vendor" required class="form-control">
                                                <?php
                                                $ret = "SELECT * FROM his_vendor";
                                                $stmt = $mysqli->prepare($ret);
                                                $stmt->execute();
                                                $res = $stmt->get_result();
                                                while ($row = $res->fetch_object()) {
                                                    $selected = ($stock->v_name == $row->v_name) ? "selected" : "";
                                                    echo "<option $selected>$row->v_name</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>Barcode</label>
                                        <input type="text" name="phar_bcode" required class="form-control" value="<?= $pharma->phar_bcode ?>">
                                    </div>

                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea class="form-control" name="phar_desc" id="editor"><?= $pharma->phar_desc ?></textarea>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Batch Number</label>
                                            <input type="text" name="batch_number" required class="form-control" value="<?= $stock->batch_number ?>">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Expiry Date</label>
                                            <input type="date" name="expiry_date" required class="form-control" value="<?= $stock->expiry_date ?>">
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Purchase Price</label>
                                            <input type="text" name="purchase_price" required class="form-control" value="<?= $stock->purchase_price ?>">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Profit Margin (%)</label>
                                            <input type="number" name="profit_margin" required class="form-control" placeholder="Enter % e.g. 25">
                                        </div>
                                    </div>

                                    <button type="submit" name="update_pharmaceutical" class="btn btn-warning">Update</button>
                                </form>
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
<script src="assets/js/app.min.js"></script>
<script src="//cdn.ckeditor.com/4.6.2/basic/ckeditor.js"></script>
<script>
    CKEDITOR.replace('editor');
</script>
</body>
</html>



                                                    <div class="form-group col-md-6">
                                                        <label for="pharVendor" class="col-form-label">Pharmaceutical Vendor</label>
                                                        <input required="required" type="text" value="<?php echo $row->phar_vendor; ?>" name="phar_vendor" class="form-control" id="pharVendor">
                                                    </div> 