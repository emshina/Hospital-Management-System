<?php
session_start();
include('assets/inc/config.php');

include('assets/inc/checklogin.php');
check_login();
$aid=$_SESSION['ad_id'];
  


if (isset($_POST['add_pharmaceutical'])) {
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

    $batch_number = $_POST['batch_number'];
    $expiry_date = $_POST['expiry_date'];
    $manufacture_date = $_POST['manufacture_date'];
    $unit_value = $_POST['unit_value'];
    $unit_type = $_POST['unit_type'];
    $unit_of_measure = $unit_value . ' ' . $unit_type;

    $purchase_price = floatval($_POST['purchase_price']);
    $profit_margin = floatval($_POST['profit_margin']);
    $storage_location = $_POST['storage_location'];
    $reorder_level = intval($_POST['reorder_level']);
    $notes = $_POST['notes'];
    $received_by = $_SESSION['ad_id'];



    $selling_price = $purchase_price + ($purchase_price * ($profit_margin / 100));

    $selling_price_per_unit = 0;
    if ($phar_qty > 0) {
    $unit_price = $selling_price / $phar_qty;
    $selling_price_per_unit = round($unit_price * 10) / 10; // Round to nearest 0.10
    }

    $stock_query = "INSERT INTO his_pharma_stock (phar_id, phar_bcode, batch_number, quantity, unit_of_measure, expiry_date, manufacture_date, purchase_price, selling_price, selling_price_per_unit, vendor_id, storage_location, received_by, reorder_level, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_stock = $mysqli->prepare($stock_query);
    $stmt_stock->bind_param(
        'ississsdddiiiss',
        $phar_id, $phar_bcode, $batch_number, $phar_qty, $unit_of_measure,
        $expiry_date, $manufacture_date, $purchase_price, $selling_price,
        $selling_price_per_unit, $vendor_id, $storage_location, $received_by,
        $reorder_level, $notes
    );
    $stmt_stock->execute();

    if ($stmt && $stmt_stock) {
        $success = "Pharmaceutical and Stock Added Successfully";
    } else {
        $err = "Error: Please Try Again Later";
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
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="header-title">Fill all fields</h4>
                                <form method="post">
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Pharmaceutical Name</label>
                                            <input type="text" required name="phar_name" class="form-control">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Pharmaceutical Quantity (Units)</label>
                                            <input required type="number" name="phar_qty" class="form-control">
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Pharmaceutical Category</label>
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
                                            <label>Pharmaceutical Vendor</label>
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
                                        <label>Pharmaceutical Barcode</label>
                                        <?php $phar_bcode = substr(str_shuffle('0123456789'), 1, 10); ?>
                                        <input required type="text" value="<?php echo $phar_bcode; ?>" name="phar_bcode" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Pharmaceutical Description</label>
                                        <textarea required class="form-control" name="phar_desc" id="editor"></textarea>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Batch Number</label>
                                            <input required type="text" name="batch_number" class="form-control">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Expiry Date</label>
                                            <input required type="date" name="expiry_date" class="form-control">
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Manufacture Date</label>
                                            <input required type="date" name="manufacture_date" class="form-control">
                                        </div>
                                        <div class="form-group col-md-6">
                                         <label>Unit of Measure</label>
                                            <div class="input-group">
                                                <input required type="number" name="unit_value" class="form-control" placeholder="Enter value (e.g., 5)">
                                                <select name="unit_type" class="form-control" required>
                                                    <option value="">Select Unit</option>
                                                    <option value="tablet">Tablet</option>
                                                    <option value="capsule">Capsule</option>
                                                    <option value="ml">Milliliter (ml)</option>
                                                    <option value="g">Gram (g)</option>
                                                    <option value="bottle">Bottle</option>
                                                    <option value="vial">Vial</option>
                                                    <option value="ampoule">Ampoule</option>
                                                    <option value="sachet">Sachet</option>
                                                    <option value="patch">Patch</option>
                                                    <option value="syringe">Syringe</option>
                                                    <option value="drop">Drop</option>
                                                    <option value="tube">Tube</option>
                                                </select>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Purchase Price</label>
                                            <input required type="text" name="purchase_price" class="form-control">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Profit Margin (%)</label>
                                            <input required type="number" name="profit_margin" class="form-control">
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Storage Location</label>
                                            <input type="text" name="storage_location" class="form-control">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Reorder Level</label>
                                            <input type="number" name="reorder_level" class="form-control" value="0">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Notes</label>
                                        <textarea name="notes" class="form-control"></textarea>
                                    </div>
                                    <button type="submit" name="add_pharmaceutical" class="btn btn-success">Add Pharmaceutical</button>
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
<script src="//cdn.ckeditor.com/4.6.2/basic/ckeditor.js"></script>
<script>CKEDITOR.replace('editor');</script>
<script src="assets/js/vendor.min.js"></script>
<script src="assets/js/app.min.js"></script>
<script src="assets/libs/ladda/spin.js"></script>
<script src="assets/libs/ladda/ladda.js"></script>
<script src="assets/js/pages/loading-btn.init.js"></script>
</body>
</html>
